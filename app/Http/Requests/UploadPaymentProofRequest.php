<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pelanggan';
    }

    public function rules(): array
    {
        return [
            'payment_proof' => 'required|file|image|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.required' => 'Bukti pembayaran wajib diupload',
            'payment_proof.image' => 'File harus berupa gambar',
            'payment_proof.max' => 'Ukuran gambar maksimal 5MB',
        ];
    }
}
