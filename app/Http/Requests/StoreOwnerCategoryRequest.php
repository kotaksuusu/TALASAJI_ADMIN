<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pemilik';
    }

    public function rules(): array
    {
        return [
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
            'name.required' => 'Nama kategori wajib diisi',
        ];
    }
}
