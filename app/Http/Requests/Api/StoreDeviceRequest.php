<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'port' => (int) $this->input('port', 4370),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => [
                'required',
                'ip',
                Rule::unique('devices')->where(function ($query) {
                    return $query->where('ip_address', $this->input('ip_address'))
                        ->where('port', $this->input('port'));
                }),
            ],
            'port' => ['integer', 'min:1', 'max:65535'],
        ];
    }
}
