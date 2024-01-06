<?php

namespace Modules\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Api\Models\KycDataModel;
use Modules\Api\Models\KycValidateModel;

class KycDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {


        $result = [
            'id' => $this->code,
            'is_paid' => $this->is_paid,
            'status' => $this->status,
            'level' => (int) $this->step,
            'photoId' => $this->photoId,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'photo' => ($this->photo != null ) ?  $this->imageUrl("photo") : null,
        ];

        $data = KycValidateModel::query()->where("kyc_id", $this->id)->first();

        if (!$data) {
            $data = new KycValidateModel();
        }


        $result['data'] = new KycValidateResource($data);

        return $result;
    }
}
