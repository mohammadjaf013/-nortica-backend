<?php

namespace Modules\Api\Http\Controllers;

use App\Exceptions\ApiCallException;
use App\Libs\ApiCaller\ApiCenterPresent;
use App\Libs\ApiCaller\DivarApiCenter;
use App\Libs\ApiCaller\ItsazanApiCenter;
use App\Libs\Helper\FrontUrl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use Modules\Api\Http\Requests\DataSetRequest;
use Modules\Api\Models\DiscountModel;
use Modules\Api\Models\KycDataLogModel;
use Modules\Api\Models\KycDataModel;
use Modules\Api\Models\KycModel;
use Modules\Api\Models\KycValidateModel;
use Modules\Api\Models\LivenessLogModel;
use Modules\Api\Models\PaymentLogModel;
use Modules\Api\Models\UserTokenModel;
use Modules\Api\Transformers\KycDataResource;
use Modules\Api\Transformers\KycValidateResource;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use Illuminate\Support\Facades\Log;
use App\Libs\ApiCaller\SanbadApiCenter;

class KycController extends Controller
{
    private $price = "50000";

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function tobank(Request $request,$id)
    {
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $kyc = KycModel::query()->where("user_id", $user->id)
                ->where("ref_id",$id)
                ->first();

            if (!$kyc) {
                $kyc = new KycModel();
                $kyc->user_id = $user->id;
                $kyc->status = "start";
                $kyc->is_paid = 0;
            }

            $discountPrice= 0;

            if($request->has('key') && $request->has('code')){
                $discount = DiscountModel::query()->where("code",$request->post('code'))->where("use_key",$request->post('key') )->first();
                if($discount){
                    $discountPrice=  $discount->value;
                }
            }

            if ($kyc->is_paid == 1) {
                return RB::success([
                    'url' => FrontUrl::url("/kyc/data"),
                    'type' => 'redirect',
                ],
                    200);
            }
            $kyc->step = 0;
            $kyc->price = $this->price;
            $kyc->save();

            $log = new PaymentLogModel();
            $log->kyc_id = $kyc->id;
            $log->user_id = $user->id;
            $log->is_paid = 0;
            $log->price = $kyc->price;
            $log->save();


            $callback = url("/api/v1/banback?lid=" . $log->code);

            $invoice = (new Invoice)->amount($kyc->price);
            $pay = Payment::callbackUrl($callback)->purchase(
                $invoice,
                function ($driver, $transactionId) use ($log, $invoice) {
                    $log->invoice_data = serialize($invoice);
                    $log->reference = $transactionId;
                    $log->save();
                }
            )->pay();


            DB::commit();
            return RB::success([
                'url' => $pay->getAction(),
                'type' => 'redirect',
            ],
                200);
        } catch (\Exception $e) {

//            $log->error_data = serialize($e);
//            $log->save();
            DB::rollBack();
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);

        }
    }


    public function banback(Request $request)
    {

        $log = PaymentLogModel::query()->where("code", $request->get("lid"))->first();
        if (!$log) {
            return redirect(FrontUrl::url("/kyc/start?error=log_not_found"));
        }
        DB::beginTransaction();

        try {
            $receipt = Payment::amount($log->price)->transactionId($log->reference)->verify();


            $log->receipt_id = $receipt->getReferenceId();
            $log->result = serialize($receipt);
            $log->is_paid = 1;
            $log->paid_at = Carbon::now();
            $log->save();
            $kyc = KycModel::query()->where("id", $log->kyc_id)->first();
            if ($kyc) {
                $kyc->is_paid = 1;
                $kyc->paid_at = Carbon::now();
                $kyc->step = 1;
                $kyc->update();
            }
            DB::commit();
            return redirect(FrontUrl::url("/kyc/data"));

        } catch (InvalidPaymentException $exception) {
            /**
             * when payment is not verified, it will throw an exception.
             * We can catch the exception to handle invalid payments.
             * getMessage method, returns a suitable message that can be used in user interface.
             **/
            Log::error("Payment error " );
            report($exception);
            DB::rollBack();
            $log->error_data = serialize($exception);
            $log->error_msg = $exception->getMessage();
            $log->save();
            DB::commit();
            return redirect(FrontUrl::url("/kyc?ref=" . $log->id));

        }
    }



    public function result(Request $request , $id)
    {


        $user = auth()->user();
        $kyc = KycModel::query()->where("user_id", $user->id)
            ->where("ref_id",$id)
            ->first();
        if (!$kyc) {

            return \response()->json(['message' => 'پست پیدا نشد.'],422);
        }


        if ($kyc->status == "completed") {
            if (empty($kyc->addon_id)) {
                $divar = new DivarApiCenter();
                if (empty($kyc->divar_photo_id)) {
                    $photoResult = $divar->uploadPhoto(Storage::disk("public")->get($kyc->photo));
                    $photoId = $photoResult->image_name;
                    $kyc->divar_photo_id = $photoResult->image_name;
                    $kyc->save();
                }
                /*$token = UserTokenModel::query()->where("user_id", $user->id)->orderByDesc("id")->first();
                try {
                    $result = $divar->listAddon($kyc, $token->access_token);

                    if(count($result->addons)){
                        $result = $divar->deleteAddon($kyc, $token->access_token);
                    }
                } catch (\Exception $e) {
                }


                try {
                    $result = $divar->createAddon($kyc, $token->access_token);
                    if($result !== false){
                        $kyc->is_added =1;
                        $kyc->save();
                    }
                    if($result != null && isset($result->id)){
                        $kyc->addon_id = $result->id;
                        $kyc->save();
                    }
                } catch (\Exception $e) {
                }*/

            }
        }
        $out = new KycDataResource($kyc);
        return RB::success($out,
            200);
    }


    public function uploadPhoto(Request $request ,$id)
    {

        Log::error("START AT: " . time());
        $user = auth()->user();
        $kyc = KycModel::query()->where("user_id", $user->id)
            ->where("ref_id",$id)
            ->first();

        if (!$kyc) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }



        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $name = $kyc->id ."-".$file->hashName();
            $upload = Storage::put("user/temp/".$kyc->id , $file);
        }

        $kycData = KycDataModel::query()->where("kyc_id", $kyc->id)->first();

        if (!$kycData) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }

        $liveness = new LivenessLogModel();
        $liveness->kyc_id = $kyc->id;
        $liveness->user_id = $user->id;
        $liveness->uniqid = Str::uuid();
        Log::error("end upload: " . time());
        try {
            $endpoint = 'http://192.168.5.3:2700/faceverification';
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $endpoint,
                    'headers' => [
                        'site' => $request->header('site'),
                        'X-Coop' => 'gata',
                    ]
                ]
            );

            $photo  = json_decode($kycData->photo_data);

            $resultBase = $client->post(
                $endpoint,
                [
                    'multipart' => [
                        [
                            'name' => 'photo1',
                            'filename' => @$_FILES['photo']['name'],
                            'type' => 'application/octet-stream',// $_FILES['video']['type'],
                            'contents' => file_get_contents($_FILES['photo']['tmp_name']),
                        ],
                        [
                            'name' => 'photo2',
                            'filename' =>Str::uuid().".png",
                            'type' => 'image/png',// $_FILES['video']['type'],
                            'contents' => fopen('data://image/png;base64,'. $photo->data,'r'),
                        ],

                    ]
                ]);

            Log::error("End Engine : " . time());
            $headers = $resultBase->getHeaders();
            $data = $resultBase->getBody();

            $result = json_decode($data->getContents());

            $liveness->response_data = $resultBase->getBody();
            $liveness->response_header = json_encode($resultBase->getHeaders());
            $liveness->status = 1;



            /** check head position */
            $endpoint = 'http://192.168.5.3:8090/GetFacesRotation';
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $endpoint,
                ]
            );


            /*  $resultHead = $client->post(
                  $endpoint,
                  [
                      'multipart' => [
                          [
                              'name' => 'photo',
                              'filename' => @$_FILES['photo']['name'],
                              'type' => 'application/octet-stream',// $_FILES['video']['type'],
                              'contents' => file_get_contents($_FILES['photo']['tmp_name']),
                          ],

                      ]
                  ]);

              $data = $resultHead->getBody();

              $resultPost = json_decode($data->getContents());
              */


            /** end check head position */



            if ($result->data->resultIndex >1) {
                $liveness->error = 1;
            }
            $liveness->save();
            $liveness->refresh();
            $validateData = KycValidateModel::query()->where("kyc_id", $kyc->id)->first();
            Log::error("End resultIndex : " . time());
            Log::error("res" . $result->data->resultIndex );
            if ( $result->data->resultIndex == 1 ||  $result->data->resultIndex == 0) {

                $validateData->photo_match = 1;
                $validateData->update();
                $kyc->step = 4;
                $kyc->status = "completed";
                $kyc->update();
                $path = Storage::disk('public')->path($kyc->photo);


                //Log::error(json_encode($resultPost));
                /*if(isset($resultPost->data->angle) && $resultPost->hasError == false){


                    if ($resultPost->data->angle > 10 || $resultPost->data->angle < -10) {
                        $degree = - $resultPost->data->angle;//abs($resultPost->data[0]->headPose->roll );
                        $source = imagecreatefromjpeg(Storage::disk('public')->path($kyc->photo));
                        $rotate = imagerotate($source, $degree,imagecolorallocate($source, 255, 255, 255));
                        imagejpeg($rotate,Storage::disk('public')->path($kyc->photo));
                        imagedestroy($source);
                    }
                }*/
                Log::error("End result : " . time());
                return RB::success(['photo' => $kyc->imageUrl("photo")],
                    200);

            }
            $liveness->error = 1;
            $liveness->status = 1;
            $liveness->save();

            return RB::error(422, null, ['message' => 'تصویر بارگزاری شده متعلق به شما نمی باشد.'], 422);

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            report($e);
            $liveness->error = 1;
            $liveness->error_result = ['status' => 'ServerErrorResponseException', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return RB::error(422, null, ['message' => 'تصویر بارگزاری شده متعلق به شما نمی باشد.'], 422);

        }catch (\Exceptionon $e) {

            report($e);

            $liveness->error = 1;
            $liveness->error_result = ['status' => 'exception', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return RB::error(422, null, ['message' => 'تصویر بارگزاری شده متعلق به شما نمی باشد.'], 422);
        }



    }

    public function websdk(Request $request,$id)
    {

        $type = 'liveness';
        $user = auth()->user();
        $kyc = KycModel::query()->where("user_id", $user->id)
            ->where("ref_id",$id)
            ->first();

        if (!$kyc) {
            return RB::error(401, null, ['message' => "فایلی ارسال نشده است."], 401);
        }

//        $kycData= KycDataModel::query()->where("kyc_id",$kyc->id)->orderByDesc("id")->first();
//        $photo = json_decode($kycData->photo_data);

        $reference_id = $request->header('referId', '');

        if ($request->file('video')) {
            $imagePath = $request->file('video');
            $imageName = uniqid() . "--" . $kyc->id . "." . Str::lower($imagePath->getClientOriginalExtension());
            $path = $request->file('video')->storeAs('temp/video', $imageName, 'public');
        } else {
            return RB::error(401, null, ['message' => "فایلی ارسال نشده است."], 401);
        }

        $liveness = new LivenessLogModel();
        $liveness->kyc_id = $kyc->id;
        $liveness->user_id = $user->id;
        $liveness->uniqid = $kyc->photoId;
        try {
            $endpoint = 'http://192.168.5.3:2700/api/registerNewUser';
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $endpoint,
                    'headers' => [
                        'site' => $request->header('site'),
                        'X-Coop' => 'balvin',
                    ]
                ]
            );



            $resultBase = $client->post(
                $endpoint,
                [
                    'multipart' => [
                        [
                            'name' => 'data',
                            'contents' => $request->post('data'),
                        ],
                        [
                            'name' => 'video',
                            'filename' => @$_FILES['video']['name'],
                            'type' => 'application/octet-stream',// $_FILES['video']['type'],
                            'contents' => file_get_contents($_FILES['video']['tmp_name']),

                        ],

                    ]
                ]);
            $headers = $resultBase->getHeaders();
            $data = $resultBase->getBody();

            $liveness->response_data = $resultBase->getBody();
            $liveness->response_header = json_encode($resultBase->getHeaders());
            $liveness->status = 1;
            $liveness->statusMessage = (isset($headers['X-StatusMessage'])) ? $headers['X-StatusMessage'][0] : "";
            $liveness->statusCode = (isset($headers['X-StatusCode'])) ? $headers['X-StatusCode'][0] : "";
            $liveness->requestID = (isset($headers['X-RequestID'])) ? $headers['X-RequestID'][0] : "";//$liveness->uniqid;//
            if ($liveness->statusCode != 200 && $liveness->statusCode != 201) {
                $liveness->error = 1;
            }


            $liveness->save();
            $liveness->refresh();
            $result = json_decode($liveness->response_data);


            $validateData = KycValidateModel::query()->where("kyc_id", $kyc->id)->first();


            if($liveness->statusCode == 200 ){
                $kyc->is_verify=1;
            }
            if($liveness->statusCode == 201 ){
                $kyc->is_verify=2;
            }
            if ($liveness->statusCode == 200 || $liveness->statusCode == 201) {
                $validateData->liveness = 1;
                $validateData->update();
                $kyc->step = 3;
                $kyc->update();

            } else {
                $liveness->error = 1;
                $liveness->status = 1;

                $liveness->save();
            }
            //   return response()->json($result);
            return response()->json($result, $resultBase->getStatusCode())->withHeaders([
                'X-StatusCode' => ($liveness->statusCode ==200 || $liveness->statusCode == 201 ) ? 200 : $liveness->statusCode,
                'X-StatusMessage' => $liveness->statusMessage,
                'X-RequstID' => $liveness->requestID,
                'CF-Cache-Status' => 'DYNAMIC',
                'cf-request-id' => (isset($headers['cf-request-id'])) ? $headers['cf-request-id'][0] : "",
                'NEL' => (isset($headers['NEL'])) ? $headers['NEL'][0] : "",
                'CF-RAY' => (isset($headers['CF-RAY'])) ? $headers['CF-RAY'][0] : "",
                'alt-svc' => (isset($headers['alt-svc'])) ? $headers['alt-svc'][0] : "",
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $headers = $e->getResponse()->getHeaders();
            $error = \GuzzleHttp\Psr7\str($e->getResponse());
            $body = (string)$e->getResponse()->getBody(true);
            $liveness->error = 1;
            $liveness->status = 1;
            $liveness->error_result = ['status' => 'ClientException', 'data' => json_encode($e->getMessage())];
            $liveness->response_header = json_encode($e->getResponse()->getHeaders());
            $liveness->statusMessage = (isset($headers['X-StatusMessage'])) ? $headers['X-StatusMessage'][0] : "";
            $liveness->statusCode = (isset($headers['X-StatusCode'])) ? $headers['X-StatusCode'][0] : "";
            $liveness->requestID = (isset($headers['X-RequestID'])) ? $headers['X-RequestID'][0] : "";
            $liveness->save();
            $respp = $e->getResponse()->getBody(true);
            return response()->json(json_decode($respp), $e->getResponse()->getStatusCode())->withHeaders([
                'X-StatusCode' => ($liveness->statusCode ==200 || $liveness->statusCode == 201 ) ? 200 : $liveness->statusCode,
                'X-StatusMessage' => $liveness->statusMessage,
                'X-RequstID' => $liveness->requestID,
                'CF-Cache-Status' => 'DYNAMIC',
                'cf-request-id' => (isset($headers['cf-request-id'])) ? $headers['cf-request-id'][0] : "",
                'NEL' => (isset($headers['NEL'])) ? $headers['NEL'][0] : "",
                'CF-RAY' => (isset($headers['CF-RAY'])) ? $headers['CF-RAY'][0] : "",
                'alt-svc' => (isset($headers['alt-svc'])) ? $headers['alt-svc'][0] : "",
            ]);

            return response()->json(json_decode($body), $e->getResponse()->getStatusCode());//$e->getResponse()->getBody()

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            report($e);
            $liveness->error = 1;
            $liveness->error_result = ['status' => 'ServerErrorResponseException', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return response()->json($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            report($e);
            $liveness->error = 1;
            $liveness->error_result = ['status' => 'BadResponseException', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return response()->json($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());

        } catch (\Exceptionon $e) {
            report($e);

            $liveness->error = 1;
            $liveness->error_result = ['status' => 'exception', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return response()->json($e->getResponse()->getBody(), $e->getResponse()->getStatusCode());
        }

    }



    public function data(DataSetRequest $request , $id)
    {

        $user = auth()->user();
        $kyc = KycModel::query()
            ->where("ref_id",$id)
            ->where("user_id", $user->id)->first();

        if (!$kyc) {
            return \response(['message'=>'پستی یافت نشد'],404);
        }
        $kycData = KycDataModel::query()->where("kyc_id", $kyc->id)->first();
        if (!$kycData) {
            $kycData = new KycDataModel();
            $kycData->kyc_id = $kyc->id;
            $kycData->user_id = $user->id;
        }
        $kycLog = new KycDataLogModel();
        $kycLog->ssn = $request->post("ssn");
        $kycLog->birthday = $request->post("birthday");
        $kycLog->kyc_id = $kyc->id;
        $kycLog->save();

        $kycData->ssn = $request->post("ssn");
        $kycData->birthday = $request->post("birthday");
        $kycData->save();


        $validateData = KycValidateModel::query()->where("kyc_id", $kyc->id)->first();
        if (!$validateData) {
            $validateData = new KycValidateModel();
            $validateData->user_id = $user->id;
            $validateData->kyc_id = $kyc->id;
            $validateData->save();
        }
//        $apiCenter = new ItsazanApiCenter();
        $apiCenter = new SanbadApiCenter();

        /** SHAHKAR CHECK  */

        if ($validateData->ssn != 1) {
            try {
                $resultBase = $apiCenter->VerifyMobile($kycLog->ssn, $user->mobile, "LOG-" . $kycLog->id);

                $result = $resultBase->getBody()->getContents();
                $result = \GuzzleHttp\json_decode($result);

                $validateData->ssn = ($result->message->ismatched ==1) ? 1 : 0;
                $validateData->save();
                $kycData->ssn_data = json_encode($result);
                $kycData->save();
            } catch (\Exception $e) {

                $validateData->ssn = 0;
                $validateData->update();

                $error = json_decode($e->getMessage());
                Log::error("----------------------------------- MOBILE" );
                Log::error("KYC ID:" .$kyc->id);
                Log::error([$kycLog->ssn, $user->mobile]);
                Log::error($e->getMessage());
                Log::error("============" );
                if (isset($error->error) && isset($error->error->customMessage)) {
                    return \response(['message'=>'کد ملی شما با شماره موبایل ثبت شده مطابقت ندارد'],422);
                }
            }
        }


        /** SABTE AHVAL CHECK  */
        if ($validateData->civil_registry != 1) {
            try {
                $resultBase = $apiCenter->Identity($kycData->ssn, $kycData->birthday, "Log-" . $kycLog->id);
                $result = $resultBase->getBody()->getContents();

                $result = \GuzzleHttp\json_decode($result);


                $validateData->civil_registry = 1;
                $validateData->save();

                $kycData->civil_data = $result->message;
                $kycData->save();


                if(count($result->message->images) > 0){

                    $obj= new \stdClass();
                    $obj->data=$result->message->images[0]->image;
                    $obj->meta=null;
                    $kycData->photo_data = json_encode($obj);
                    $kycData->save();
                    $validateData->photo = 1;
                    $validateData->save();

                    $code = uniqid();
                    try {
                        $apiPresent = new ApiCenterPresent();
                        $registerPhoto = $apiPresent->registerUserImage($code, $result->data);
                        $kyc->photoId = $registerPhoto->statusMessage;
                        $kyc->save();
                    } catch (\Exception $e) {
                        report($e);
                    }

                }
                $user->first_name = $result->message->firstName;
                $user->last_name = $result->message->lastName;
                $user->update();
            } catch (ApiCallException $e) {
                $validateData->civil_registry = 0;
                $validateData->update();


                $error = json_decode($e->getMessage());

                Log::error("-----------------------------------CIVIL1" );
                Log::error("KYC ID:" .$kyc->id);
                Log::error([$kycData->ssn, $kycData->birthday]);
                Log::error($e->getMessage());
                Log::error("============" );

                return \response(['message'=>' کد ملی و تاریخ تولد شما مطابقت ندارد'],422);
            } catch (\Exception $e) {
                $validateData->civil_registry = 0;
                $validateData->update();
                $error = json_decode($e->getMessage());

                Log::error("-----------------------------------CIVIL" );
                Log::error("KYC ID:" .$kyc->id);
                Log::error([$kycData->ssn, $kycData->birthday]);
                Log::error($e->getMessage());
                Log::error("============" );

                return \response(['message'=>' خطا در برقراری ارتباط با ثبت احوال مجدداً سعی نمایید'],422);
            }

        }

        if ($validateData->civil_registry == 1 && $validateData->photo == 1 && $validateData->ssn == 1) {
            $kyc->step = 2;
            $kyc->update();
        }


        $out = new KycValidateResource($validateData);

        return \response([],200);

    }

    public function datacheck(Request $request)
    {


//        $divar = new DivarApiCenter();
//
//        $result = $divar->listAddon($kyc, $token->access_token);

//        $apiCenter = new SanbadApiCenter();
//        $resultBase = $apiCenter->VerifyMobile($request->post('ssn'), $request->post('mobile'), "LOG-" . time());
//
//        $result = $resultBase->getBody()->getContents();
//        $result = \GuzzleHttp\json_decode($result);
//
//        dd($result);

    }
    public function dataAdd(Request $request)
    {

        $user = auth()->user();
        $kyc = KycModel::query()
            ->where("id",$request->post("kyc_id"))->first();

        if (!$kyc) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }
        $kycData = KycDataModel::query()->where("kyc_id", $kyc->id)->first();
        if (!$kycData) {
            $kycData = new KycDataModel();
            $kycData->kyc_id = $kyc->id;
            $kycData->user_id = $user->id;
        }
        $kycLog = new KycDataLogModel();
        $kycLog->ssn = $request->post("ssn");
        $kycLog->birthday = $request->post("birthday");
        $kycLog->kyc_id = $kyc->id;
        $kycLog->save();

        $kycData->ssn = $request->post("ssn");
        $kycData->birthday = $request->post("birthday");
        $kycData->save();


//$apiCenter = new ItsazanApiCenter();
//$x = $apiCenter->civilRegistryInfo($kycData->ssn, $kycData->birthday, "Log-" . $kycLog->id);
//dd($x);
        $validateData = KycValidateModel::query()->where("kyc_id", $kyc->id)->first();
        if (!$validateData) {
            $validateData = new KycValidateModel();
            $validateData->user_id = $user->id;
            $validateData->kyc_id = $kyc->id;
            $validateData->save();
        }
//        $apiCenter = new ItsazanApiCenter();
        $apiCenter = new SanbadApiCenter();

        /** SHAHKAR CHECK  */

        if ($validateData->ssn != 1) {
            try {
                $resultBase = $apiCenter->VerifyMobile($kycLog->ssn, $user->mobile, "LOG-" . $kycLog->id);

                $result = $resultBase->getBody()->getContents();
                $result = \GuzzleHttp\json_decode($result);


                if ( $result== null  || !isset($result->message) || !isset( $result->message->ismatched) ||  $result->message->ismatched ==0){
                    $validateData->ssn = 0;
                    $validateData->update();
                    return RB::error(422, null, ['message' => 'کد ملی شما با شماره موبایل ثبت شده مطابقت ندارد.'], 422);
                }
                $validateData->ssn = ($result->message->ismatched ==1) ? 1 : 0;
                $validateData->save();
                $kycData->ssn_data = json_encode($result);
                $kycData->save();
            } catch (\Exception $e) {

                $validateData->ssn = 0;
                $validateData->update();

                $error = json_decode($e->getMessage());
                Log::error("----------------------------------- MOBILE" );
                Log::error("KYC ID:" .$kyc->id);
                Log::error([$kycLog->ssn, $user->mobile]);
                Log::error($e->getMessage());
                Log::error("============" );
                if (isset($error->error) && isset($error->error->customMessage)) {
                    return RB::error(422, null, ['message' => 'کد ملی شما با شماره موبایل ثبت شده مطابقت ندارد.'], 422);
                }
            }
        }


        /** SABTE AHVAL CHECK  */
        if ($validateData->civil_registry != 1) {
            try {


                $resultBase = $apiCenter->civilRegistryInfo($kycData->ssn, $kycData->birthday, "Log-" . $kycLog->id);
                $result = $resultBase->getBody()->getContents();

                $result = \GuzzleHttp\json_decode($result);


                $validateData->civil_registry = 1;
                $validateData->save();

                $kycData->civil_data = $result->message;
                $kycData->save();


                if(count($result->message->images) > 0){

                    $obj= new \stdClass();
                    $obj->data=$result->message->images[0]->image;
                    $obj->meta=null;
                    $kycData->photo_data = json_encode($obj);
                    $kycData->save();
                    $validateData->photo = 1;
                    $validateData->save();
                }
                $user->first_name = $result->message->firstName;
                $user->last_name = $result->message->lastName;
                $user->update();
            } catch (ApiCallException $e) {
                $validateData->civil_registry = 0;
                $validateData->update();


                $error = json_decode($e->getMessage());

                Log::error("-----------------------------------CIVIL1" );
                Log::error("KYC ID:" .$kyc->id);
                Log::error([$kycData->ssn, $kycData->birthday]);
                Log::error($e->getMessage());
                Log::error("============" );

                return RB::error(422, null, ['message' => 'اطلاعات کد ملی و تاریخ تولد شما مطابقت ندارد.'], 422);
            } catch (\Exception $e) {
                $validateData->civil_registry = 0;
                $validateData->update();
                $error = json_decode($e->getMessage());

                Log::error("-----------------------------------CIVIL" );
                Log::error("KYC ID:" .$kyc->id);
                Log::error([$kycData->ssn, $kycData->birthday]);
                Log::error($e->getMessage());
                Log::error("============" );

                return RB::error(422, null, ['message' => 'خطا در برقراری ارتباط با ثبت احوال مجدداً سعی نمایید.'], 422);
            }

        }



        if ($validateData->civil_registry == 1 && $validateData->photo == 1 && $validateData->ssn == 1) {
            $kyc->step = 2;
            $kyc->update();
        }


        $out = new KycValidateResource($validateData);

        return RB::success($out,
            200);
    }
    public function changePhoto(Request $request,$id)
    {

        Log::error("START AT: " . time());
        $user = auth()->user();
        $kyc = KycModel::query()->where("user_id", $user->id)
            ->where("ref_id",$id)
            ->first();

        if (!$kyc) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }

        $kycData = KycDataModel::query()->where("kyc_id", $kyc->id)->first();
        if (!$kycData) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }


        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $name = $kyc->id ;
            $upload = Storage::put("user/tempC/{$name}", $file);
            $fileN = $upload;

        }

        $liveness = new LivenessLogModel();
        $liveness->kyc_id = $kyc->id;
        $liveness->user_id = $user->id;
        $liveness->uniqid = Str::uuid();
        Log::error("end upload: " . time());
        try {
            $endpoint = 'http://192.168.5.3:2700/faceverification';
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $endpoint,
                    'headers' => [
                        'site' => $request->header('site'),
                        'X-Coop' => 'gata',
                    ]
                ]
            );

            $photo  = json_decode($kycData->photo_data);

            $resultBase = $client->post(
                $endpoint,
                [
                    'multipart' => [
                        [
                            'name' => 'photo1',
                            'filename' => @$_FILES['photo']['name'],
                            'type' => 'application/octet-stream',// $_FILES['video']['type'],
                            'contents' => file_get_contents($_FILES['photo']['tmp_name']),
                        ],
                        [
                            'name' => 'photo2',
                            'filename' =>Str::uuid().".png",
                            'type' => 'image/png',// $_FILES['video']['type'],
                            'contents' => fopen('data://image/png;base64,'. $photo->data,'r'),
                        ],

                    ]
                ]);

            Log::error("End Engine : " . time());
            $headers = $resultBase->getHeaders();
            $data = $resultBase->getBody();

            $result = json_decode($data->getContents());

            $liveness->response_data = $resultBase->getBody();
            $liveness->response_header = json_encode($resultBase->getHeaders());
            $liveness->status = 1;



            if ($result->data->resultIndex >1) {
                $liveness->error = 1;
            }
            $liveness->save();
            $liveness->refresh();
            $validateData = KycValidateModel::query()->where("kyc_id", $kyc->id)->first();
            Log::error("End resultIndex : " . time());
            Log::error("res" . $result->data->resultIndex );
            if (1==1) {

                $validateData->photo_match = 1;
                $validateData->update();

                $divar = new DivarApiCenter();
                $photoResult = $divar->uploadPhoto(Storage::get($fileN));

                $photoId = $photoResult->image_name;
                $kyc->divar_photo_id = $photoResult->image_name;
                $kyc->save();
                $token = UserTokenModel::query()->where("user_id", $user->id)->orderByDesc("id")->first();
                try {
                    $result = $divar->listAddon($kyc, $token->access_token);

                    if(count($result->addons)){
                        $result = $divar->deleteAddon($kyc, $token->access_token);
                    }

                } catch (\Exception $e) {
                }


                try {
                    $result = $divar->createAddon($kyc, $token->access_token);
                    if($result !== false){
                        $kyc->is_added =1;
                        $kyc->save();
                    }
                    if($result != null && isset($result->id)){
                        $kyc->addon_id = $result->id;
                        $kyc->save();
                    }
                } catch (\Exception $e) {
                }
                return RB::success(['photo' => $kyc->imageUrl("photo")],
                    200);
            }


            $liveness->error = 1;
            $liveness->status = 1;
            $liveness->save();

            return RB::error(422, null, ['message' => 'تصویر بارگزاری شده متعلق به شما نمی باشد.'], 422);

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            report($e);
            $liveness->error = 1;
            $liveness->error_result = ['status' => 'ServerErrorResponseException', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return RB::error(422, null, ['message' => 'تصویر بارگزاری شده متعلق به شما نمی باشد.'], 422);

        }catch (\Exceptionon $e) {

            report($e);

            $liveness->error = 1;
            $liveness->error_result = ['status' => 'exception', 'data' => serialize($e->getMessage())];
            $liveness->save();
            return RB::error(422, null, ['message' => 'تصویر بارگزاری شده متعلق به شما نمی باشد.'], 422);
        }



    }

    public function uploadPhotoSkip(Request $request,$id)
    {

        Log::error("START AT: " . time());
        $user = auth()->user();
        $kyc = KycModel::query()->where("user_id", $user->id)
            ->where("ref_id",$id)
            ->first();

        if (!$kyc) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }



        $kycData = KycDataModel::query()->where("kyc_id", $kyc->id)->first();

        if (!$kycData) {
            return RB::error(422, null, ['message' => 'UNKNOWN_ERROR'], 422);
        }
        $kyc->step = 4;
        $kyc->status = "completed";
        // $kyc->update();

        $fileN= Storage::disk('public')->get("avatar.jpg");
        $divar = new DivarApiCenter();
        $photoResult = $divar->uploadPhoto( $fileN);

        $photoId = $photoResult->image_name;
        $kyc->divar_photo_id = $photoResult->image_name;
        $kyc->save();
        $token = UserTokenModel::query()->where("user_id", $user->id)->orderByDesc("id")->first();
        try {
            $result = $divar->listAddon($kyc, $token->access_token);

            if(count($result->addons)){
                $result = $divar->deleteAddon($kyc, $token->access_token);
            }

        } catch (\Exception $e) {
        }


        try {
            $result = $divar->createAddon($kyc, $token->access_token);
            if($result !== false){
                $kyc->is_added =1;
                $kyc->save();
            }
            if($result != null && isset($result->id)){
                $kyc->addon_id = $result->id;
                $kyc->save();
            }
        } catch (\Exception $e) {
        }

        return RB::success([],200);



    }

}
