<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['penjual', 'pemilik']);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meter' => 'nullable|integer|min:10|max:1000',
            'operational_status' => 'nullable|in:buka,tutup',
            'logo' => 'nullable|string|max:500',
            'payment_qr' => 'nullable|string|max:500',
            'branch_email' => 'nullable|string|email|max:255|unique:users,email',
            'branch_password' => 'nullable|string|min:6|required_with:branch_email',
            'branch_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama toko wajib diisi',
            'radius_meter.min' => 'Radius minimal 10 meter',
            'radius_meter.max' => 'Radius maksimal 1000 meter',
            'branch_password.required_with' => 'Password cabang wajib diisi jika email cabang diisi',
        ];
    }
}
