<?php

namespace App\Http\Requests\Api;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'ip_address' => ['sometimes', 'required', 'ip'],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Device $device */
            $device = $this->route('device');
            $ip = $this->input('ip_address', $device->ip_address);
            $port = (int) $this->input('port', $device->port);

            $collision = Device::query()
                ->where('ip_address', $ip)
                ->where('port', $port)
                ->whereKeyNot($device->id)
                ->exists();

            if ($collision) {
                $validator->errors()->add(
                    'ip_address',
                    'The combination of IP address and port is already in use.'
                );
            }
        });
    }
}
