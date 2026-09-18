<?php

namespace App\Services\WhatsApp;

class PhoneNumber
{
    /**
     * Normalise a stored phone number to the digits-only E.164 form the
     * Cloud API expects (no '+', no separators). Returns null when the value
     * cannot be resolved into a plausible number.
     */
    public static function normalize(?string $phone, ?string $countryCode = null): ?string
    {
        if ($phone === null) {
            return null;
        }

        $countryCode = preg_replace('/\D/', '', (string) ($countryCode ?? config('services.whatsapp.default_country_code')));
        $phone = trim($phone);
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return null;
        }

        // A leading '+' or '00' means the number already carries its own country
        // code, so the local default must not be prepended.
        $isInternational = str_starts_with($phone, '+') || str_starts_with($digits, '00');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($isInternational) {
            return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
        }

        if ($countryCode !== '' && str_starts_with($digits, $countryCode)) {
            $national = substr($digits, strlen($countryCode));
        } elseif (str_starts_with($digits, '0')) {
            $national = ltrim($digits, '0');
        } else {
            $national = $digits;
        }

        if (strlen($national) < 6) {
            return null;
        }

        $normalized = $countryCode.$national;

        return strlen($normalized) >= 8 && strlen($normalized) <= 15 ? $normalized : null;
    }

    /**
     * Mask a number for logging so recipient identities stay out of the log file.
     */
    public static function mask(string $phone): string
    {
        return strlen($phone) <= 4
            ? str_repeat('*', strlen($phone))
            : str_repeat('*', strlen($phone) - 4).substr($phone, -4);
    }
}
