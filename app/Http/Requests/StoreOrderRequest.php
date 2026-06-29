<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pelanggan';
    }

    public function rules(): array
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'table_id' => 'nullable|exists:tables,id',
            'service_type' => 'required|in:dine_in,take_away',
            'table_number' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:2000',
            'location_validated' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Toko wajib dipilih',
            'service_type.required' => 'Tipe layanan wajib dipilih',
            'items.required' => 'Pesanan minimal 1 item',
            'items.min' => 'Pesanan minimal 1 item',
            'items.*.menu_id.required' => 'Menu wajib dipilih',
            'items.*.quantity.required' => 'Jumlah wajib diisi',
            'items.*.quantity.min' => 'Jumlah minimal 1',
        ];
    }
}
