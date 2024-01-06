<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Sagalbot\Encryptable\Encryptable;

class KycDataModel extends Model
{
    use Encryptable;
    protected $table = "kyc_data";

    protected $encryptable = [ 'ssn' ];//, 'civil_data','photo_data'

    protected $casts=[
        "civil_data"=>'array'
    ];


}
