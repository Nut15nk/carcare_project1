<?php
// libs/JWT.php
class JWT {
    public static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode($payload, $secret, $alg = 'HS256') {
        $header = ['alg' => $alg, 'typ' => 'JWT'];
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $sig = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($sig);
        return implode('.', $segments);
    }

    public static function decode($token, $secret) {
        $parts = explode('.', $token);
        if (count($parts) != 3) return null;
        list($h64, $p64, $s64) = $parts;
        $signingInput = $h64 . '.' . $p64;
        $sig = self::base64UrlDecode($s64);
        $expected = hash_hmac('sha256', $signingInput, $secret, true);
        if (!hash_equals($expected, $sig)) return null;
        $payload = json_decode(self::base64UrlDecode($p64), true);
        return $payload;
    }
}
