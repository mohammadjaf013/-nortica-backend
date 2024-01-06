<?php

namespace App\Libs\ApiCaller;


use BenSampo\Enum\Enum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiCenterPresent
{


    private $endPoint = "https://api.presentid.com";
    private $endPoint2 = "https://api.presentid.com/api";
    private $methods = [
        'REQUEST_FILE' => 'GetRequestFile',
        'REGISTER_USER_IMAGE' => 'registerUserImage'
    ];

    public function setEndpoint($endpoint)
    {
        $this->endPoint = $endpoint;
    }

    public function getEndpoint($endpoint)
    {
        return $this->endPoint;
    }


    public function getFiles($requestid, $fileType)
    {

        $params = [

            [
                'name' => 'requestId',
                'contents' => $requestid,
            ],



        ];


       $result =  $this->call(APIMETHODSUBMIT::POST, $this->methods['REQUEST_FILE'], $params);
       return $result;

    }
    public function registerUserImage($userid, $fileimage ,$hasFile=false)
    {
        $params = [
            [
                'name' => 'userID',
                'contents' => (string) $userid,
            ],
            [
                'name' => 'image',
                'filename' =>Str::uuid().".png",
                'type' => 'image/png',// $_FILES['video']['type'],
                'contents' => ($hasFile) ? file_get_contents($fileimage) : fopen('data://image/png;base64,'. $fileimage,'r'),
            ],

        ];
       $result =  $this->call("post", $this->methods['REGISTER_USER_IMAGE'], $params,[],2);
       return $result;

    }

    private function call($methodSubmit = "post", $method = "", $params, $headers = [],$endpoint=1)
    {


        $endpointn = ($endpoint == 1)? $this->endPoint : $this->endPoint2;

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $endpointn . "/" . $method,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post(
                $endpointn . "/" . $method,
                [
                    'multipart' => $params
                ]);
            $result = $resultBase->getBody()->getContents();
            return \GuzzleHttp\json_decode($result);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            report($e);
            return false;
        }


    }


}


