<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'store_id' => 'sometimes|exists:stores,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ];
    }
}
