<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pelanggan';
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,qris,transfer',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah pembayaran wajib diisi',
            'amount.min' => 'Jumlah pembayaran tidak boleh negatif',
            'payment_method.required' => 'Metode pembayaran wajib dipilih',
        ];
    }
}
