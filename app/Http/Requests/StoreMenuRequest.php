<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'stock_status' => 'nullable|in:tersedia,habis',
            'is_recommended' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih',
            'name.required' => 'Nama menu wajib diisi',
            'price.required' => 'Harga wajib diisi',
            'price.numeric' => 'Harga harus angka',
            'price.min' => 'Harga tidak boleh negatif',
        ];
    }
}
