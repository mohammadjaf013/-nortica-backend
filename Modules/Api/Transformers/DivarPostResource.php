<?php

namespace Modules\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DivarPostResource extends JsonResource
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
            'title'=>(string)$this->resource->data->title,
            'token'=>(string)$this->resource->token,
            'new_price'=>(int) $this->resource->data->new_price,
            'images'=>  $this->resource->data->images,
            'description'=>(string)  $this->resource->data->description,
            'expire_days'=>(int) $this->resource->data->expire_days,
        ];
    }
}
