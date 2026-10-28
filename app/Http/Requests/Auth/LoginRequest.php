<?php

namespace App\Http\Requests\Auth;

use App\Support\UniversityAccount;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (UniversityAccount::emailOnlyLogin()) {
            return [
                'email' => ['required', 'string', 'email', 'max:255'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // โหมดทดสอบ: เข้าด้วยอีเมลมหาวิทยาลัยอย่างเดียว
        if (UniversityAccount::emailOnlyLogin()) {
            $email = UniversityAccount::normalize($this->input('email'));

            if (! UniversityAccount::canSignIn($email)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'ใช้ได้เฉพาะอีเมลมหาวิทยาลัย เช่น 6612345678-st@'.UniversityAccount::domain()
                        .' หรือ ชื่อ.นามสกุล@'.UniversityAccount::domain(),
                ]);
            }

            $user = UniversityAccount::findOrCreate($email);

            if ((int) $user->status !== 1) {
                throw ValidationException::withMessages([
                    'email' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อสำนักวิทยบริการฯ',
                ]);
            }

            Auth::login($user, $this->boolean('remember'));
            RateLimiter::clear($this->throttleKey());

            return;
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            ]);
        }

        // บัญชีที่ถูกระงับ (status = 0) เข้าสู่ระบบไม่ได้
        if ((int) Auth::user()->status !== 1) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อสำนักวิทยบริการฯ',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
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

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
