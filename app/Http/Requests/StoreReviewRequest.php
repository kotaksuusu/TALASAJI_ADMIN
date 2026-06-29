<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pelanggan';
    }

    public function rules(): array
    {
        return [
            'order_id' => 'nullable|exists:orders,id',
            'store_id' => 'required|exists:stores,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'photo' => 'nullable|string|max:500',
            'menu_id' => 'nullable|exists:menus,id',
            'recommend' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Pesanan wajib dipilih',
            'store_id.required' => 'Toko wajib dipilih',
            'rating.required' => 'Rating wajib diisi',
            'rating.min' => 'Rating minimal 1',
            'rating.max' => 'Rating maksimal 5',
        ];
    }
}
