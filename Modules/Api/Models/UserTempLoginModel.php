<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserTempLoginModel extends Model
{

    protected $table = "user_temp_login";

    protected static function boot()
    {
        parent::boot();

        $creationCallback = function ($model) {
            $model->code = Str::random(50);
//            $model->code = Str::uuid()->toString();
        };

        static::creating($creationCallback);
    }

    public const UPDATED_AT = null;


}
