<?php

namespace App\Libs\Helper;

class FrontUrl
{


    public static function url($url=null){
        if($url == null){
            return 'http://localhost:3000/';
        }
        return 'http://localhost:3000/'.$url;
    }
}
