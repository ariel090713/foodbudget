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
        ];
    }
}
