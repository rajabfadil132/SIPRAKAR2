<?php

namespace App\Http\Requests\Auth;

use App\Rules\TurnstileCaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        if (config('services.turnstile.enabled')) {
            $rules['turnstile_token'] = ['required', 'string', new TurnstileCaptcha];
        }

        return $rules;
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = $this->user()?->loadMissing(['role', 'roleCategory']);
        $blockedMessage = match (true) {
            ! $user => trans('auth.failed'),
            $user->status !== 'active' => 'Akun Anda sedang nonaktif atau ditangguhkan. Hubungi admin untuk aktivasi akun.',
            ! $user->role || ! $user->role->is_active => 'Role akun Anda sedang nonaktif. Hubungi superadmin untuk pemeriksaan hak akses.',
            $user->roleCategory && ! $user->roleCategory->is_active => 'Subkategori role akun Anda sedang nonaktif. Hubungi superadmin untuk pemeriksaan akun.',
            default => null,
        };

        if ($blockedMessage) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $blockedMessage,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
