<?php

namespace App\Libs\ApiCaller;

use App\Exceptions\ApiCallException;
use Illuminate\Http\Request;


class DivarApiCenter
{

    private $endPoint = "https://api.divar.ir/v1/open-platform";

    private $apiKey = "dp2xHMdWLD66uZu7gT5Xn-kS0Z1p9vl4TtngtEOLv61xxy7hd2vqqFgNPZcjvjcpP77O2KGaRhv9DJPb0Z-gtjUJK7r0K29Y-Az5bSKVYGobHnJGJ1yUiVepucoxWYux4j3gTGWWtivNDNr8Hmb0_AEUxPmEa8_oLsv5YFqsqJjeEwbNHVi0HZIKgFIJVArstOimPGhjRPo0e9hJtoWZMWD9cknH4irMakyFnpMxJDfgjKQLe0EvTHNCUYVRovCm";

    public function __construct()
    {

    }


    public function getToken(Request $request)
    {

        $params = [
            'code' => $request->get("code"),
            'client_id' => 'gata',
            'client_secret' => $this->apiKey,
            'grant_type' => 'authorization_code',
        ];

        if (env('APP_DEBUG')) {
            $headers = [
                'X-Debug-Token' => "JNbtiaMZ",
            ];
        }
        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.divar.ir/v1/open-platform/oauth/access_token',
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post('https://api.divar.ir/v1/open-platform/oauth/access_token',
                ['body' => json_encode($params)]
            );
            $result = json_decode($resultBase->getBody()->getContents());
            return $result;
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


    public function getPhone($token)
    {
        $params = [

        ];
        $headers = [];
        $headers['x-access-token'] = $token;
        $headers['x-api-key'] = $this->apiKey;
        if (env('APP_DEBUG')) {
            $headers['X-Debug-Token'] = "JNbtiaMZ";
        }
        $headers['Content-Type'] = "application/json; charset=utf-8";

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.divar.ir/v1/open-platform/users',
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post('https://api.divar.ir/v1/open-platform/users',
            );
            $result = json_decode($resultBase->getBody()->getContents());
            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
            return false;
        }

        $result = $this->callRaw("post", "users", $params, $headers);
        return $result;
    }


    public function uploadPhoto($file)
    {
        $headers = [];

        $headers['x-api-key'] = $this->apiKey;

        $headers['Content-Type'] = "image/jpeg";
        $headers['Content-Length'] = strlen($file);

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://divar.ir/v2/image-service/open-platform/image.jpg',
                    'headers' => $headers
                ]
            );
            $resultBase = $client->put('https://divar.ir/v2/image-service/open-platform/image.jpg',
                ['body'=>$file]
            );

            $result = json_decode($resultBase->getBody()->getContents());
            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
            return false;
        }

        $result = $this->callRaw("post", "users", $params, $headers);
        return $result;
    }
    public function createAddon($kyc,$token)
    {

        $headers = [];

        $headers['x-api-key'] = $this->apiKey;
        $headers['x-access-token'] = $token;

        $headers['Content-Type'] = "application/json";
        if (env('APP_DEBUG')) {
            $headers['X-Debug-Token'] = "JNbtiaMZ";
        }
        $data = [
            "widgets" => [
                "widget_list" => [
                    [
                        "widget_type" => "EVENT_ROW",
                        "data" => [
                            "@type" => "type.googleapis.com/widgets.EventRowData",
                            "title" => "هویت تأیید شده",
                            "subtitle" => "نام و تصویر و مشخصات این شخص تایید شده است",
                            "has_indicator" => true,
                            "label" => "قدرت گرفته از گاتا",
                            "has_divider" => true,
                            "image_url" => $kyc->divar_photo_id,
                            "padded" => true,
                            "hide_image" => false,
                            "counter" => 2,
                            "icon" => [
                                "icon_name" => "VERIFIED_GREEN",
                                "icon_color" => "SUCCESS_PRIMARY"
                            ]
                        ]
                    ],

                    [
                        "widget_type" => "WIDE_BUTTON_BAR",
                        "data" => [
                            "@type" => "type.googleapis.com/widgets.WideButtonBarWidgetData",
                            "style" => "SECONDARY",
                            "button" => [
                                "action" => [
                                    "type" => "OPEN_WEB_PAGE",
                                    "fallback_link" => "https://ekyc.msgata.com",
                                    "payload" => [
                                        "@type" => "type.googleapis.com/widgets.OpenWebPagePayload",
                                        "link" => "https://ekyc.msgata.com"
                                    ]
                                ],
                                "title" => "مشاهده صفحه تأیید هویت",
                            ]
                        ]
                    ]

                ]
            ],
            "link_in_spec" => "https://msgata.com",
            "notes" => "Created"
        ];

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.divar.ir/v1/open-platform/add-ons/post/'.$kyc->ref_id,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post('https://api.divar.ir/v1/open-platform/add-ons/post/'.$kyc->ref_id,[
                \GuzzleHttp\RequestOptions::JSON =>($data)
            ]);

            $result = json_decode($resultBase->getBody()->getContents());

            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
            return false;
        }

        $result = $this->callRaw("post", "users", $params, $headers);
        return $result;
    }
    public function deleteAddon($kyc,$token)
    {

        $headers = [];

        $headers['x-api-key'] = $this->apiKey;
        $headers['x-access-token'] = $token;

        $headers['Content-Type'] = "application/json";
        if (env('APP_DEBUG')) {
            $headers['X-Debug-Token'] = "JNbtiaMZ";
        }

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.divar.ir/v1/open-platform/add-ons/post/'.$kyc->ref_id,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->delete('https://api.divar.ir/v1/open-platform/add-ons/post/'.$kyc->ref_id);

            $result = json_decode($resultBase->getBody()->getContents());

            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
            return false;
        }

        $result = $this->callRaw("post", "users", $params, $headers);
        return $result;
    }
    public function listAddon($kyc,$token)
    {

        $headers = [];

        $headers['x-api-key'] = $this->apiKey;
        $headers['x-access-token'] = $token;

        $headers['Content-Type'] = "application/json";
        if (env('APP_DEBUG')) {
            $headers['X-Debug-Token'] = "JNbtiaMZ";
        }


        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.divar.ir/v1/open-platform/add-ons/post/'.$kyc->ref_id,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->get('https://api.divar.ir/v1/open-platform/add-ons/post/'.$kyc->ref_id);

            $result = json_decode($resultBase->getBody()->getContents());

            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
            return false;
        }

        $result = $this->callRaw("post", "users", $params, $headers);
        return $result;
    }
    public function getPost($ref)
    {
        $params = [

        ];
        $headers = [];

        $headers['x-api-key'] = $this->apiKey;

        $headers['Content-Type'] = "application/json; charset=utf-8";

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => 'https://api.divar.ir/v1/open-platform/finder/post/'.$ref,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->get('https://api.divar.ir/v1/open-platform/finder/post/'.$ref,
            );
            $result = json_decode($resultBase->getBody()->getContents());
            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
            return false;
        }

        $result = $this->callRaw("post", "users", $params, $headers);
        return $result;
    }


    private function call($methodSubmit = "post", $method = "", $params = [], $headers = [])
    {
        $headers['Content-Type'] = 'application/json-patch+json;  charset=utf-8';
        $headers['x-api-key'] = env("DIVAR_TOKEN");
        $headers['accept'] = 'application/json';

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $this->endPoint . "/" . $method,
                    'headers' => $headers
                ]
            );
            $resultBase = $client->post(
                $this->endPoint . "/" . $method,
                ['body' => json_encode($params)]
            );
            $result = $resultBase;
            dd($result);
            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            return false;
        }
    }

    private function callRaw($methodSubmit = "post", $method = "", $params, $headers = [])
    {
//        $token = $this->getToken();
//        $headers['Authorization'] = $this->token_type . " " . $this->access_token;
        $headers['Content-Type'] = 'application/json-patch+json';
        $headers['accept'] = 'application/json';

        try {
            $client = new \GuzzleHttp\Client(
                [
                    'base_uri' => $this->endPoint . "/" . $method,
                ]
            );
            $resultBase = $client->post(
                $this->endPoint . "/" . $method,
                [
                    \GuzzleHttp\RequestOptions::JSON => json_encode($params)
                ]);
            $result = $resultBase;

            dd($result);
            return $result;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            dd($e);
            report($e);
            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            report($e);

            throw  new ApiCallException($e->getResponse()->getBody());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            dd($e);
            report($e);
            return false;

        } catch (\Exceptionon $e) {
            dd($e);
            report($e);
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


