<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateCampaignRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:191',
            'description' => 'required|string',
            'target_amount' => 'required|numeric|min:100000|max:1000000000',
            'deadline' => 'required|date|after:today',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'title.required' => 'Judul campaign wajib diisi.',
            'title.max' => 'Judul campaign maksimal 191 karakter.',
            'description.required' => 'Deskripsi campaign wajib diisi.',
            'target_amount.required' => 'Target dana wajib diisi.',
            'target_amount.numeric' => 'Target dana harus berupa angka.',
            'target_amount.min' => 'Target dana minimal Rp 100.000.',
            'target_amount.max' => 'Target dana maksimal Rp 1.000.000.000.',
            'deadline.required' => 'Batas waktu wajib diisi.',
            'deadline.date' => 'Format batas waktu tidak valid.',
            'deadline.after' => 'Batas waktu harus setelah hari ini.',
            'cover_image.image' => 'File harus berupa gambar.',
            'cover_image.mimes' => 'Format gambar harus jpeg, png, atau jpg.',
            'cover_image.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
