<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik', 'admin']);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,confirmed,preparing,ready,completed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan wajib diisi',
            'status.in' => 'Status pesanan tidak valid',
        ];
    }
}
