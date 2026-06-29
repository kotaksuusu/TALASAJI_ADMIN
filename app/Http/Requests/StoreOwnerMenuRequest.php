<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pemilik';
    }

    public function rules(): array
    {
        return [
            'owner_category_id' => 'required|exists:owner_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'is_recommended' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_category_id.required' => 'Kategori wajib dipilih',
            'name.required' => 'Nama menu wajib diisi',
            'price.required' => 'Harga wajib diisi',
        ];
    }
}
