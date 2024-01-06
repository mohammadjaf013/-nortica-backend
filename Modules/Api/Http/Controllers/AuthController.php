<?php

namespace Modules\Api\Http\Controllers;

use App\Libs\ApiCaller\DivarApiCenter;
use App\Libs\Helper\FrontUrl;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use Modules\Api\Models\KycModel;
use Modules\Api\Models\LoginStateModel;
use Modules\Api\Models\UserTempLoginModel;
use Modules\Api\Models\UserTokenModel;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function login(Request $request)
    {
        auth()->setDefaultDriver('api');

        $user = UserModel::query()->where('mobile', $request->post('mobile', null))->first();
        if (!$user || !(Hash::check($request->post('password'), $user->password))) {
            return RB::error(422, null, ['message' => __('api.login_incorrect')], 422);
        }
        $tokenResult = $user->createToken('user');

        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(54);
        $token->save();
        return RB::success([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
        ],
            200, ['api.login_success']);
    }


    public function authback(Request $request)
    {


        $modelState = LoginStateModel::query()->where("state", $request->get("state"))->first();
        if (!$modelState) {
            return redirect(FrontUrl::url("/?error=invalid_state"));
        }
        DB::beginTransaction();
        try {
            $divar = new DivarApiCenter();
            $dataToken = $divar->getToken($request);
            if ($dataToken === false) {
                return redirect(FrontUrl::url("/?error=invalid_error"));
            }
            $tokendata = new UserTokenModel();
            $tokendata->access_token = $dataToken->access_token;
            $tokendata->refresh_token = $dataToken->refresh_token;
            $tokendata->expired_at = $dataToken->expires;
            $phone = $divar->getPhone($tokendata->access_token);
            if (count($phone->phone_numbers) < 1) {
                return redirect(FrontUrl::url("/?error=invalid_number"));
            }
            $phoneNumber = $phone->phone_numbers[0];

            $user = UserModel::query()->where("mobile", $phoneNumber)->first();
            if (!$user) {
                $user = new UserModel();
                $user->mobile = $phoneNumber;
                $user->password = Str::random(50);
                $user->save();
            }

            $kyc = KycModel::query()->where("ref_id", $modelState->ref)->where("user_id", $user->id)->first();
            if (!$kyc) {
                $kyc = new KycModel();
                $kyc->user_id = $user->id;
                $kyc->status = "start";
                $kyc->is_paid = 0;
                $kyc->ref_id = $modelState->ref;
                $kyc->save();
            }

            UserTokenModel::query()->where("user_id", $user->id)->delete();
            $tokendata->user_id = $user->id;
            $tokendata->save();

            UserTempLoginModel::query()->where("user_id", $user->id)->delete();
            $temp = new UserTempLoginModel();
            $temp->user_id = $user->id;
            $temp->save();
            DB::commit();
            return redirect(FrontUrl::url("/auth/" . $temp->code."/".$kyc->ref_id));
        } catch (\Exception $e) {
            report($e);
            DB::rollBack();
            return redirect(FrontUrl::url("/?auth=invalid_failed"));

        }
    }

    public function authToken(Request $request, $id)
    {

        $temp = UserTempLoginModel::query()->where("code", $id)->first();
        if (!$temp) {
            return   \response()->json(['message' => 'کد شما صحیح نمیباشد.'],404);
        }


        $user = UserModel::query()->where("id", $temp->user_id)->first();
        if (!$user) {
            return     \response()->json(['message' => 'گاربر پیدا نشد'],404);
        }

        auth()->setDefaultDriver('api');
        $tokenResult = $user->createToken('user');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(54);
        $token->save();

        return \response()->json([
            'access_token' => $tokenResult->accessToken,
        ]);



    }


    public function auth(Request $request)
    {

        $redirect = url('/api/fromdivar');
        $state2 = uniqid();


        $scopes = ['USER_PHONE'];
        if ($request->has("ref") && !empty($request->post("ref"))) {
            $scopes[] = "ADDON_USER_APPROVED__" . $request->post("ref");
        }
        $url = 'https://open-platform-redirect.divar.ir/oauth?response_type=code&client_id=gata&redirect_uri=' . $redirect . '&scope=' . implode("+", $scopes) . '&state=' . $state2;//ADDON_USER_APPROVED__AZTH74V2
        $modelState = new LoginStateModel();
        $modelState->state = $state2;
        $modelState->status = "active";
        $modelState->ref = $request->post("ref");
        $modelState->save();
//        $data = Socialite::driver('divar')->stateless()->getAuthUrl("A");
        return \response()->json(['url' => $url]);
    }
}
