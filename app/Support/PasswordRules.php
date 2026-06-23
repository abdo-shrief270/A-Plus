<?php

namespace App\Support;

/**
 * Canonical strong-password policy shared across all v2 auth requests.
 * Keep this regex in sync with the web (app/schemas/auth.ts) and Flutter
 * (AppValidator.passwordValidator) — same definition of "symbol" = any
 * non-alphanumeric character — so a password accepted on one client is not
 * rejected by the backend.
 */
class PasswordRules
{
    /** 8+ chars with at least one lowercase, uppercase, digit, and symbol. */
    public const REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

    public const MESSAGE = 'كلمة المرور يجب أن تتكون من 8 أحرف على الأقل وتحتوي على حرف كبير وحرف صغير ورقم ورمز';

    /**
     * @return array<int,string>
     */
    public static function rules(bool $required = true, bool $confirmed = true): array
    {
        $rules = [$required ? 'required' : 'sometimes', 'string', 'min:8'];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        $rules[] = 'regex:'.self::REGEX;

        return $rules;
    }

    /**
     * @return array<string,string>
     */
    public static function messages(string $field = 'password'): array
    {
        return [
            "{$field}.regex" => self::MESSAGE,
            "{$field}.min" => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            "{$field}.confirmed" => 'تأكيد كلمة المرور غير متطابق',
        ];
    }
}
