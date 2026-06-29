<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'number' => 'required|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:tersedia,terisi,dipesan',
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Toko wajib dipilih',
            'number.required' => 'Nomor meja wajib diisi',
            'number.integer' => 'Nomor meja harus angka',
            'number.min' => 'Nomor meja minimal 1',
        ];
    }
}
