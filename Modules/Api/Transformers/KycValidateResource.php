<?php

namespace Modules\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class KycValidateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'phone'=>(int)$this->ssn,
            'identify'=>(int) $this->civil_registry,
            'photo'=>(int)  $this->photo,
            'liveness'=>(int)  $this->liveness,
            'photo_match'=>(int) $this->photo_match,

        ];
    }
}
