<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WebAuthn
{
    public static function challenge(): string
    {
        return self::base64UrlEncode(random_bytes(32));
    }

    /**
     * @return array<string, mixed>
     */
    public static function registrationOptions(Request $request, string $challenge, int $userId, string $username, string $displayName): array
    {
        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => 'Teras Rasa Cafe POS',
                'id' => $request->getHost(),
            ],
            'user' => [
                'id' => self::base64UrlEncode((string) $userId),
                'name' => $username,
                'displayName' => $displayName,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'residentKey' => 'preferred',
                'userVerification' => 'required',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function authenticationOptions(Request $request, string $challenge, string $credentialId): array
    {
        return [
            'challenge' => $challenge,
            'rpId' => $request->getHost(),
            'allowCredentials' => [[
                'type' => 'public-key',
                'id' => $credentialId,
            ]],
            'userVerification' => 'required',
            'timeout' => 60000,
        ];
    }

    public static function validateClientData(string $clientDataJson, string $type, string $challenge, Request $request): void
    {
        $clientData = json_decode(self::base64UrlDecode($clientDataJson), true);

        if (! is_array($clientData)) {
            throw new InvalidArgumentException('Data biometric tidak valid.');
        }

        if (($clientData['type'] ?? null) !== $type) {
            throw new InvalidArgumentException('Tipe biometric tidak valid.');
        }

        if (($clientData['challenge'] ?? null) !== $challenge) {
            throw new InvalidArgumentException('Challenge biometric sudah tidak cocok.');
        }

        $origin = (string) ($clientData['origin'] ?? '');
        $host = parse_url($origin, PHP_URL_HOST);

        if ($host && ! hash_equals($request->getHost(), $host)) {
            throw new InvalidArgumentException('Origin biometric tidak valid.');
        }
    }

    public static function normalizeCredentialId(string $credentialId): string
    {
        $credentialId = trim($credentialId);

        if ($credentialId === '' || strlen($credentialId) > 512 || ! Str::of($credentialId)->isMatch('/^[A-Za-z0-9_-]+$/')) {
            throw new InvalidArgumentException('Credential biometric tidak valid.');
        }

        return $credentialId;
    }

    public static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Base64 biometric tidak valid.');
        }

        return $decoded;
    }
}
