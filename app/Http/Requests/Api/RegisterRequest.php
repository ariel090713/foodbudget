<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firebase_uid' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
            'is_anonymous' => ['nullable', 'boolean'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ];
    }
}
