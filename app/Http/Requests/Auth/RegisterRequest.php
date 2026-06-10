<?php

namespace App\Http\Requests\Auth;

use App\Models\Cabang;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $cabangIds = Cabang::where('status', 'active')->pluck('id')->toArray();

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cabang_id' => ['nullable', Rule::in($cabangIds)],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Alamat email ini sudah terdaftar. Gunakan email lain atau hubungi admin.',
            'password.confirmed' => 'Konfirmasi password tidak cocok dengan password yang dimasukkan.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim($this->email ?? '')),
            'name' => trim($this->name ?? ''),
        ]);
    }
}