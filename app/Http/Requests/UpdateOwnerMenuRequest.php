<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnerMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pemilik';
    }

    public function rules(): array
    {
        return [
            'owner_category_id' => 'sometimes|exists:owner_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'is_recommended' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
