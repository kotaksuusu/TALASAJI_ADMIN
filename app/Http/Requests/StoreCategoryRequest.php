<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Toko wajib dipilih',
            'store_id.exists' => 'Toko tidak ditemukan',
            'name.required' => 'Nama kategori wajib diisi',
        ];
    }
}
