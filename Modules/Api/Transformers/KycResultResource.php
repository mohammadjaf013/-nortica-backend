<?php

namespace Modules\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Api\Models\KycDataModel;
use Modules\Api\Models\KycValidateModel;

class KycResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $name = "";
        $family = "";
        $kdata = KycDataModel::query()->where("kyc_id", $this->id)->orderByDesc("id")->first();
        if ($kdata) {
            $name = (isset($kdata->civil_data['firstName'])) ? $kdata->civil_data['firstName'] : "";
            $family = (isset($kdata->civil_data['lastName'])) ? $kdata->civil_data['lastName'] : "";
        }
        $result = [
            'id' => $this->code,
            'name' => $name,
            'family' => $family,
            'status' => $this->status,
            'photo' => ($this->photo != null) ? $this->imageUrl("photo") : null,
        ];

        $data = KycValidateModel::query()->where("kyc_id", $this->id)->first();

        if (!$data) {
            $data = new KycValidateModel();
        }


        $result['data'] = new KycValidateResource($data);

        return $result;
    }
}
