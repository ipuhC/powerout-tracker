<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'between:0.5,25'],
            'duration_type' => ['required', 'string', 'in:fixed,until_closed'],
            'duration_hours' => ['required_if:duration_type,fixed', 'nullable', 'integer', 'min:1', 'max:72'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
