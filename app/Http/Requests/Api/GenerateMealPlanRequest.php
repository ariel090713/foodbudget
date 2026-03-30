<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'totalBudget' => ['required', 'numeric', 'gt:0'],
            'currencyCode' => ['required', 'string', 'size:3'],
            'numberOfDays' => ['required', 'integer', 'between:1,30'],
            'numberOfPersons' => ['required', 'integer', 'min:1'],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'countryCode' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'preferredTier' => ['nullable', 'string', 'in:extremePoverty,poor,middleClass,rich'],
            'skippedMealTypes' => ['nullable', 'array'],
            'skippedMealTypes.*' => ['string', 'in:breakfast,lunch,dinner,meryenda'],
        ];
    }
}
