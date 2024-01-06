<?php

namespace App\Libs\ApiCaller;


use App\Exceptions\ApiCallException;

class ItsazanApiCenter
{

    public $name = 'itsaaz';
    private $endPointAuth = "https://gateway.itsaaz.ir/sts/connect/token";

    private $endPointAi = "https://gateway.itsaaz.ir/hub/api/v1";
    public $access_token;
    public $token_type;

    public $clientId = '638105070999744339';
    public $clientSecret = '5d85665a-f262-4e41-b3e9-98b29c0c73d2';
    private $methods = [
        'VerifyMobile' => 'Shahkar/MixVerifyMobile',
        'CivilRegistryInfo' => 'IdentityDataTypeA',
        'ShebaInquiry' => 'FinancialServices/ShebaInquiry',
//        'ShebaInquiry' => 'FaraboomFinancialServices/ShebaInquiry',
        'personPhoto' => 'IdentityPhoto',
        'PostCodeByGeoLocation' => 'FaraboomFinancialServices/GetPostCodeByGeographyLocation',
        'AddressByPostcode' => 'FinancialServices/GetAddressByPostcode',
//        'AddressByPostcode' => 'FaraboomFinancialServices/GetAddressByPostcode',
    ];


    public function __construct()
    {
        if (cache()->has("token_type")) {
            $this->token_type = cache()->get("token_type");
            $this->access_token = cache()->get("access_token");
        } else {
            $this->getToken();
        }
    }

    public function setEndpoint($endpoint)
    {
        $this->endPoint = $endpoint;
    }

    public function getEndpointAuth($endpoint)
    {
        return $this->endPointAuth;
    }

    public function getEndpointAi($endpoint)
    {
        return $this->endPointAi;
    }


    public function test()
    {

    }


    /**
     * @param $nationalCode
     * @param $cardSerial
     * @return false|\Psr\Http\Message\ResponseInterface
     * @throws ApiCallException
     */
    public function personPhoto($nationalCode, $birthday)
    {
        $params = [
            'nationalCode' => $nationalCode,
            'birthDate' => $birthday,
        ];
        $result = $this->callRaw("post", $this->methods['personPhoto'], $params);
        return $result;
    }


    /**
     * @param $postcode
     * @param $platform
     * @param $userAgent
     * @return false|\Psr\Http\Message\ResponseInterface
     * @throws ApiCallException
     */
    public function addressByPostcode($postcode, $platform, $userAgent)
    {
        $params = [
            'postcode' => $postcode,
            'platform' => $platform,
            'userAgent' => $userAgent,
        ];


        $result = $this->callRaw("post", $this->methods['AddressByPostcode'], $params);
        return $result;
    }

    /**
     * @param $postcode
     * @param $platform
     * @param $userAgent
     * @return false|\Psr\Http\Message\ResponseInterface
     * @throws ApiCallException
     */
    public function postCodeByGeoLocation($postcode, $platform, $userAgent)
    {
        $params = [
            'postcode' => $postcode,
            'platform' => $platform,
            'userAgent' => $userAgent,
        ];


        $result = $this->callRaw("post", $this->methods['PostCodeByGeoLocation'], $params);
        return $result;
    }


    /**
     * @param $iban
     * @param $platform
     * @param $userAgent
     * @return false|\Psr\Http\Message\ResponseInterface
     * @throws ApiCallException
     */
    public function shebaInquiry($iban, $platform, $userAgent)
    {
        $params = [
            'iban' => $iban,
            'platform' => $platform,
            'userAgent' => $userAgent,
        ];
        $result = $this->callRaw("post", $this->methods['ShebaInquiry'], $params);


        return $result;
    }


    /**
     * @param $nationalCode national code 10 number
     * @param $birthday birthday Y/m/d
     * @return false|\Psr\Http\Message\ResponseInterface
     * @throws ApiCallException
     */
    public function civilRegistryInfo($nationalCode, $birthday, $orderId)
    {
        $params = [
            'NationalCode' => $nationalCode,
            'BirthDate' => $birthday,
            'orderId' => $orderId,
        ];

        $result = $this->call("post", $this->methods['CivilRegistryInfo'], $params);
        return $result;
    }

    /**
     * @param $nationalCode  national code 10 number
     * @param $mobile mobile number
     * @return false|\Psr\Http\Message\ResponseInterface
     * @throws ApiCallException
     */
    public function VerifyMobile($nationalCode, $mobile, $orderid)
    {
        $params = [
            'nationalCode' => $nationalCode,
            'mobile' => $mobile,
            'orderId' => $orderid,
        ];
        $result = $this->callRaw("post", $this->methods['VerifyMobile'], $params);
        return $result;
    }


    private function call($methodSubmit = "post", $method = "", $params, $headers = [])
    {

        $headers['Authorization'] = $this->token_type . " " . $this->access_token;
        $headers['Content-Type'] = 'application/json-patch+json';
        $headers['accept'] = 'application/json';
        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $this->endPointAi . "/" . $method,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post(
                $this->endPointAi . "/" . $method,
                ['body' => json_encode($params)]
            );
            $result = $resultBase;
            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            report($e);
            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {

            report($e);
            $datajson = json_decode($e->getResponse()->getBody()->getContents());

            if(isset($datajson->error) && isset($datajson->error->customMessage)){

            }
            throw  new ApiCallException($datajson->error->customMessage);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            report($e);
            return false;
        }
    }


    private function callRaw($methodSubmit = "post", $method = "", $params, $headers = [])
    {
        $token = $this->getToken();
        $headers['Authorization'] = $this->token_type . " " . $this->access_token;
        $headers['Content-Type'] = 'application/json-patch+json';
        $headers['accept'] = 'application/json';

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $this->endPointAi . "/" . $method,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post(
                $this->endPointAi . "/" . $method,
                [
                    'headers' => $headers,
                    'body' => json_encode($params)
                ]);
            $result = $resultBase;


            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            report($e);
            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            report($e);

            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            report($e);
            return false;
        }
    }


    public function GetPersonPhoto($nationalcode, $cardSerial)
    {

        $params = [
            [
                'name' => 'requestId',
                'contents' => $requestid,
            ],

            [
                'name' => 'fileType',
                'contents' => $fileType,
            ],

        ];
        $result = $this->call("post", $this->methods['REQUEST_FILE'], $params);
        return $result;


    }


    public function getToken()
    {


        $params = [
            'grant_type' => 'password',
            'username' => '87545554621',
            'password' => '315PEz#ww',
            'client_id' => $this->clientId,
            'Client_secret' => $this->clientSecret,
        ];


        try {
            $client = new \GuzzleHttp\Client(
                ['base_uri' => $this->endPointAuth,]
            );

            $resultBase = $client->post(
                $this->endPointAuth,
                [
                    'form_params' => $params
                ]);
            $result = $resultBase->getBody()->getContents();
            $resultObj = \GuzzleHttp\json_decode($result);



             cache()->put("token_type",$resultObj->token_type,3600);
            cache()->put("access_token",$resultObj->access_token,3600);
            cache()->put("scope",$resultObj->scope,3600);
            cache()->put("expire",date('Y-m-d H:i:s', strtotime('+ ' . $resultObj->expires_in . ' seconds', time())),3600);

            $this->access_token = $resultObj->access_token;
            $this->token_type = $resultObj->token_type;
            return true;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e->getMessage());
            return false;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e->getMessage());
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e->getMessage());
            return false;
        } catch (\Exceptionon $e) {
            dd($e->getMessage());
            return false;
        }


    }

    public function CallMultipart($methodSubmit, $method = "", $params = [], $headers = [])
    {


        $result = new \stdClass();
        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $this->endPoint . "/" . $method,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->{$methodSubmit}(
                $this->endPoint . "/" . $method,
                [
                    'multipart' => $params
                ]);


            $result->status = true;
            $result->request = $resultBase;
            $result->result = $resultBase->getBody()->getContents();
            return $result;

        } catch (\GuzzleHttp\Exception\ServerException $e) {

            $result->status = true;
            $result->request = $e;

            $result->error = true;
            $result->result = $e->getMessage();
            return $result;


        } catch (\GuzzleHttp\Exception\BadResponseException $e) {

            $result->status = false;
            $result->error = true;
            $result->result = $e->getMessage();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $result->status = false;
            $result->error = true;
            $result->result = $e->getMessage();

        } catch (\Exceptionon $e) {
            $result->status = false;
            $result->error = true;
            $result->result = $e->getMessage();
        }


        return $result;

    }


}


