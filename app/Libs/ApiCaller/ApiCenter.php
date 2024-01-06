<?php

namespace App\Libs\ApiCaller;


use BenSampo\Enum\Enum;

class ApiCenter
{


    private $endPoint = "https://api.hifacy.com";
    private $methods = [
        'FACE_VERIFY' => 'FaceVerification'
    ];

    public function setEndpoint($endpoint)
    {
        $this->endPoint = $endpoint;
    }

    public function getEndpoint($endpoint)
    {
        return $this->endPoint;
    }


    public function FaceVerification($file1, $file2)
    {


        $params = [

            [
                'name' => 'photo1',
                'filename' => $file1->getClientOriginalName(),
                'type' => 'application/octet-stream',
//                'type' => $file1->getMimeType(),
                'contents' => file_get_contents($file1->getRealPath()),
            ],

            [
                'name' => 'photo2',
                'filename' => $file2->getClientOriginalName(),
                'type' => 'application/octet-stream',
//                'type' => $file2->getMimeType(),
                'contents' => file_get_contents($file2->getRealPath()),
            ],

        ];

        $result = $this->CallMultipart(APIMETHODSUBMIT::POST, $this->methods['FACE_VERIFY'], $params);
        return $result;

    }

    public function Call($methodSubmit = "post", $method = "", $params, $headers = [])
    {

        $client = new \GuzzleHttp\Client(
            [
                'base_uri' => $this->endPoint . "/" . $method,
                'headers' => $headers
            ]
        );


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
            $resultBase = $client->{APIMETHODSUBMIT::getKey($methodSubmit)}(
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


final class APIMETHODSUBMIT extends Enum
{
    const POST = 'post';
    const GET = 'get';
}
