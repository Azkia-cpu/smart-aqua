<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PumpControlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'pond_code' => ['required', 'string', 'exists:ponds,code'],
            'action' => ['required', Rule::in(['on', 'off', 'toggle_manual_on', 'toggle_manual_off'])],
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
            'pond_code.exists' => 'The specified pond does not exist.',
            'action.in' => 'Invalid pump action. Allowed actions: on, off, toggle_manual_on, toggle_manual_off.',
        ];
    }
}
