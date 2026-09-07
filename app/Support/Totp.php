<?php

namespace App\Support;

/**
 * RFC 6238 TOTP (SHA-1, 6 digits, 30s step) — the algorithm every
 * authenticator app speaks. Implemented locally so admin 2FA has no external
 * dependency. Secrets are base32 (RFC 4648), no padding.
 */
class Totp
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const STEP_SECONDS = 30;

    private const CODE_LENGTH = 6;

    /** Accept codes from the previous/current/next step window. */
    private const DRIFT_WINDOW = 1;

    public static function generateSecret(int $length = 32): string
    {
        $alphabet = self::BASE32_ALPHABET;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * otpauth:// URI for authenticator apps. Rendered as plain text (manual
     * entry) until a QR-code dependency is approved.
     */
    public static function uri(string $secret, string $accountName, string $issuer = 'Broca'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($accountName),
            $secret,
            rawurlencode($issuer),
        );
    }

    /**
     * The code that is valid right now (also used by tests).
     */
    public static function currentCode(string $secret): string
    {
        return self::codeAt($secret, self::timeStep());
    }

    /**
     * Verify and report WHICH time-step matched, so callers can persist it
     * and reject replays of an already-consumed step (TOTP replay guard).
     * Returns the matched step (current ± window) or null when no code in
     * the window matches.
     */
    public static function verifyStep(string $secret, string $code, int $window = self::DRIFT_WINDOW): ?int
    {
        $secret = strtoupper(trim($secret));
        $code = preg_replace('/\D/', '', $code) ?? '';

        if ($secret === '' || strlen($code) !== self::CODE_LENGTH) {
            return null;
        }

        $step = self::timeStep();

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $step + $i), $code)) {
                return $step + $i;
            }
        }

        return null;
    }

    public static function verify(string $secret, string $code, int $window = self::DRIFT_WINDOW): bool
    {
        return self::verifyStep($secret, $code, $window) !== null;
    }

    private static function timeStep(): int
    {
        return (int) floor(time() / self::STEP_SECONDS);
    }

    private static function codeAt(string $secret, int $counter): string
    {
        $binarySecret = self::base32Decode($secret);

        if ($binarySecret === '') {
            return '';
        }

        $hash = hash_hmac('sha1', pack('J', $counter), $binarySecret, true);

        // RFC 4226 dynamic truncation.
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::CODE_LENGTH)), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $base32): string
    {
        $base32 = preg_replace('/[^A-Z2-7]/i', '', strtoupper($base32)) ?? '';

        $bits = '';
        foreach (str_split($base32) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr((int) bindec($byte));
            }
        }

        return $binary;
    }
}
