<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanSyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Checked via middleware/controller later
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scans' => ['present', 'array'],
            'scans.*.code' => ['required', 'string', 'max:255'],
            'scans.*.timestamp' => ['nullable', 'string', 'max:255'],
            'scans.*.id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
