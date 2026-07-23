<?php

namespace App\Services\Registration;

class EmployerRegistrationEmailService
{
    /**
     * Consumer email domains blocked when corporate email is required.
     */
    public static function freeEmailDomains(): array
    {
        return [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
            'icloud.com', 'aol.com', 'protonmail.com', 'proton.me', 'yandex.com',
            'yandex.ru', 'mail.com', 'gmx.com', 'gmx.net', 'yahoo.co.uk',
            'yahoo.in', 'yahoo.co.in', 'rediffmail.com', 'msn.com', 'me.com',
            'mac.com', 'googlemail.com', 'fastmail.com', 'tutanota.com',
        ];
    }

    public static function corporateEmailRequired(): bool
    {
        $value = setting('employer_corporate_email_required');

        return $value === null ? true : (bool) $value;
    }

    public static function isFreeEmailDomain(string $email): bool
    {
        $domain = strtolower(ltrim(strrchr($email, '@') ?: '', '@'));

        return $domain !== '' && in_array($domain, self::freeEmailDomains(), true);
    }

    public static function validationMessage(string $email): ?string
    {
        if (! self::corporateEmailRequired()) {
            return null;
        }

        if (self::isFreeEmailDomain($email)) {
            return __('employer_corporate_email_required_message');
        }

        return null;
    }
}
