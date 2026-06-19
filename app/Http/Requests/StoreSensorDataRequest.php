<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSensorDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by the ValidateDeviceToken middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'water_level' => ['required', 'numeric', 'min:0', 'max:50'],
            'ph_value' => ['required', 'numeric', 'min:0', 'max:14'],
            'flow_rate' => ['required', 'numeric', 'min:0'],
            'distance_cm' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'water_level.max' => 'Water level cannot exceed 50 cm.',
            'ph_value.max' => 'pH value must be between 0 and 14.',
        ];
    }
}
