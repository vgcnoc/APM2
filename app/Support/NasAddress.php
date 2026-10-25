<?php

namespace App\Support;

final class NasAddress
{
    /** One router is one RADIUS client; never accept a catch-all network. */
    public static function ip(?string $value): ?string
    {
        $value = trim((string) $value);
        if (str_contains($value, '/')) {
            [$address, $prefix] = explode('/', $value, 2);
            if ($prefix !== (str_contains($address, ':') ? '128' : '32')) return null;
            $value = $address;
        }
        if (!filter_var($value, FILTER_VALIDATE_IP)) return null;
        $value = inet_ntop(inet_pton($value));
        return in_array($value, ['0.0.0.0', '::'], true) ? null : $value;
    }
}
