<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.enabled')) {
            return;
        }

        if (! config('services.turnstile.secret_key')) {
            $fail('Konfigurasi Turnstile belum lengkap. Secret key belum diisi di file .env.');
            return;
        }

        if (! is_string($value) || trim($value) === '') {
            $fail('Verifikasi keamanan wajib diselesaikan.');
            return;
        }

        $response = Http::asForm()
            ->timeout(8)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

        if (! $response->successful() || ! $response->json('success')) {
            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }
}
