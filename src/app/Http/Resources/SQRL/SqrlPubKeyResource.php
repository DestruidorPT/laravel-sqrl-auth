<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Resources\SQRL;

use Illuminate\Http\Resources\Json\JsonResource;

class SqrlPubKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
