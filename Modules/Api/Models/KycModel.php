<?php

namespace Modules\Api\Models;

use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use QCod\ImageUp\HasImageUploads;

class KycModel extends Model
{
    use  Notifiable, HasImageUploads;

    protected $table = "kyc";

    protected static $imageFields = [
        'photo' => [
            'path' => 'user/photo',
            'placeholder' => null,
            'rules' => 'image|max:2000',
            'file_input' => 'photo',
            'auto_upload' => true,

        ],

    ];

    protected static function boot()
    {
        parent::boot();

        $creationCallback = function ($model) {
            $model->code = Str::uuid()->toString();
        };

        static::creating($creationCallback);
    }


    public function user()
    {
        return $this->hasOne(UserModel::class, "id", "user_id");
    }


}
