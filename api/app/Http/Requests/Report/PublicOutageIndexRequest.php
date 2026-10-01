<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class PublicOutageIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_lat' => ['required', 'numeric', 'between:-90,90'],
            'min_lng' => ['required', 'numeric', 'between:-180,180'],
            'max_lat' => ['required', 'numeric', 'between:-90,90'],
            'max_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
