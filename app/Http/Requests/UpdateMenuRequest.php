<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'stock_status' => 'nullable|in:tersedia,habis',
            'is_recommended' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
