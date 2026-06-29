<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'store_id' => 'sometimes|exists:stores,id',
            'number' => 'sometimes|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:tersedia,terisi,dipesan',
            'qr_code_content' => 'nullable|string',
        ];
    }
}
