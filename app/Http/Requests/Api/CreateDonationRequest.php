<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => 'required|exists:campaigns,id',
            'amount' => 'required|numeric|min:10000',
            'donor_name' => 'nullable|string|max:255',
            'is_anonymous' => 'nullable|boolean',
            'message' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages for validator.
     */
    public function messages(): array
    {
        return [
            'campaign_id.required' => 'ID kampanye harus diisi.',
            'campaign_id.exists' => 'Kampanye tidak ditemukan.',
            'amount.required' => 'Jumlah donasi harus diisi.',
            'amount.numeric' => 'Jumlah donasi harus berupa angka.',
            'amount.min' => 'Jumlah donasi minimal Rp 10.000.',
            'donor_name.max' => 'Nama donatur maksimal 255 karakter.',
            'message.max' => 'Pesan maksimal 500 karakter.',
        ];
    }
}
