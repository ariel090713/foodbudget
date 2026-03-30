<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->firebase_uid,
            'email' => $this->email,
            'displayName' => $this->display_name,
            'photoUrl' => null,
            'isAnonymous' => empty($this->email),
        ];
    }
}
