<?php

final class PhoneNumber {
    public const ERROR_MESSAGE = 'Enter a valid Philippine mobile number (e.g., 0917 123 4567).';

    /**
     * Normalize a Philippine mobile number to 09XXXXXXXXX.
     * Returns an empty string for an empty optional value and null when invalid.
     */
    public static function normalize($value): ?string {
        $value = trim((string) $value);
        if ($value === '') return '';

        if (!preg_match('/^(?:\+63|0)[0-9 -]+$/', $value)) return null;

        $compact = str_replace([' ', '-'], '', $value);
        if (str_starts_with($compact, '+63')) {
            $compact = '0' . substr($compact, 3);
        }

        return preg_match('/^09\d{9}$/', $compact) ? $compact : null;
    }

    public static function isValid($value, bool $required = false): bool {
        $normalized = self::normalize($value);
        return $normalized !== null && (!$required || $normalized !== '');
    }
}
