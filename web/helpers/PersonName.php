<?php

final class PersonName {
    /**
     * Clean whitespace and title-case only names whose letters are all lowercase.
     * Existing mixed/intentional capitalization is preserved exactly.
     */
    public static function normalize($value): string {
        $name = preg_replace('/\s+/u', ' ', trim((string) $value));
        if ($name === '' || $name === null) return '';

        $letters = preg_replace('/[^\p{L}]/u', '', $name);
        if ($letters === '' || $letters === null || $letters !== self::lower($letters)) {
            return $name;
        }

        return (string) preg_replace_callback(
            '/(^|[\s\-\'’])(\p{L})/u',
            fn(array $match): string => $match[1] . self::upper($match[2]),
            $name
        );
    }

    private static function lower(string $value): string {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private static function upper(string $value): string {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
