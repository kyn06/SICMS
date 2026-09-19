<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use GeoIp2\Database\Reader;

class GeoLocation {
    public static function forIp(string $ip): string {
        $ip = trim($ip);
        if (in_array($ip, ['', '::1', '127.0.0.1'], true)) {
            return 'Localhost (this computer)';
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'Unknown location';
        }

        $databasePath = __DIR__ . '/../../GeoLite2-City.mmdb';
        if (!is_file($databasePath)) {
            return $ip;
        }

        try {
            $reader = new Reader($databasePath);
            $city = $reader->city($ip);
            $parts = array_filter([
                $city->city->name,
                $city->mostSpecificSubdivision->name,
                $city->country->name,
            ]);
            return implode(', ', array_unique($parts)) ?: $ip;
        } catch (Throwable $exception) {
            return $ip;
        }
    }
}
