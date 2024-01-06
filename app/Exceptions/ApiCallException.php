<?php

namespace App\Exceptions;

use Exception;

class ApiCallException extends Exception
{
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }



    public function getJson()
    {
        return json_decode($this->getMessage(),true);
    }




}
