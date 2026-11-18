<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Support\Names\PersonName;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class PasskeyOptionsService
{
    private const CHALLENGE_TYPE_REGISTER = 'register';

    private const CHALLENGE_TYPE_AUTHENTICATE = 'authenticate';

    private const CHALLENGE_TTL_SECONDS = 300;

    /**
     * @return array<string,mixed>
     */
    public function createRegistrationOptions(User $user): array
    {
        $this->ensureSchemaReady();

        $challenge = $this->createChallenge((int) $user->id, self::CHALLENGE_TYPE_REGISTER);

        $existingCredentials = $this->activePasskeysQuery()
            ->where('user_id', (int) $user->id)
            ->pluck('credential_id')
            ->all();

        $displayName = PersonName::fromParts([
            $user->firstname,
            $user->surname,
            $user->lastname,
        ], (string) ($user->username ?? $user->email ?? ('user-'.$user->id)));

        $excludeCredentials = [];
        foreach ($existingCredentials as $credentialIdRaw) {
            if (! is_string($credentialIdRaw) || $credentialIdRaw === '') {
                continue;
            }

            $excludeCredentials[] = [
                'type' => 'public-key',
                'id' => self::base64UrlEncode($credentialIdRaw),
            ];
        }

        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => $this->rpName(),
            ],
            'user' => [
                'id' => self::base64UrlEncode((string) $user->id),
                'name' => (string) ($user->username ?: $user->email),
                'displayName' => $displayName,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
            'excludeCredentials' => $excludeCredentials,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function verifyRegistration(User $user, array $payload): void
    {
        $this->ensureSchemaReady();

        $credentialIdB64 = trim((string) ($payload['credential_id'] ?? ''));
        $clientDataB64 = trim((string) ($payload['client_data_json'] ?? ''));
        $attestationObjectB64 = trim((string) ($payload['attestation_object'] ?? ''));
        $deviceNameRaw = trim((string) ($payload['device_name'] ?? ''));

        if ($credentialIdB64 === '' || $clientDataB64 === '' || $attestationObjectB64 === '') {
            throw new RuntimeException('Incomplete passkey registration payload.');
        }

        $clientDataJson = self::base64UrlDecode($clientDataB64);
        $clientData = $this->validateClientData($clientDataJson, 'webauthn.create');
        $challenge = (string) ($clientData['challenge'] ?? '');

        if (! $this->consumeChallenge($challenge, self::CHALLENGE_TYPE_REGISTER, (int) $user->id)) {
            throw new RuntimeException('Passkey registration challenge is invalid or expired.');
        }

        $credentialId = self::base64UrlDecode($credentialIdB64);
        $attestationObject = self::base64UrlDecode($attestationObjectB64);
        $offset = 0;
        $attestation = self::cborDecode($attestationObject, $offset);
        if (! is_array($attestation) || ! isset($attestation['authData']) || ! is_string($attestation['authData'])) {
            throw new RuntimeException('Invalid attestation object.');
        }

        $authData = $attestation['authData'];
        $parsed = self::parseAuthData($authData, true);
        $expectedRpHash = hash('sha256', $this->rpId(), true);

        if (! hash_equals($expectedRpHash, $parsed['rpIdHash'])) {
            throw new RuntimeException('Passkey registration RP ID mismatch.');
        }
        if (($parsed['flags'] & 0x01) !== 0x01) {
            throw new RuntimeException('Passkey registration missing user presence flag.');
        }
        if (! hash_equals($parsed['credentialId'], $credentialId)) {
            throw new RuntimeException('Passkey credential ID mismatch.');
        }

        $publicKey = $parsed['credentialPublicKey'];
        $coseOffset = 0;
        $coseMap = self::cborDecode($publicKey, $coseOffset);
        $algorithm = is_array($coseMap) && isset($coseMap[3]) && is_int($coseMap[3]) ? (int) $coseMap[3] : null;

        $aaguid = self::formatAaguid($parsed['aaguid']);
        $deviceNameBase = $deviceNameRaw !== '' ? $deviceNameRaw : ('Passkey '.gmdate('Y-m-d'));
        $deviceName = function_exists('mb_substr')
            ? mb_substr($deviceNameBase, 0, 120)
            : substr($deviceNameBase, 0, 120);
        $transports = self::normalizeTransports($payload['transports'] ?? null);

        $insert = [
            'user_id' => (int) $user->id,
            'credential_id' => $credentialId,
            'public_key' => $publicKey,
            'sign_count' => $parsed['signCount'],
            'device_name' => $deviceName,
            'created_at' => now(),
        ];

        if (Schema::hasColumn('user_passkeys', 'algorithm')) {
            $insert['algorithm'] = $algorithm;
        }
        if (Schema::hasColumn('user_passkeys', 'transports')) {
            $insert['transports'] = $transports;
        }
        if (Schema::hasColumn('user_passkeys', 'aaguid')) {
            $insert['aaguid'] = $aaguid;
        }

        try {
            DB::table('user_passkeys')->insert($insert);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw new RuntimeException('This passkey is already registered.');
            }

            throw $exception;
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function createAuthenticationOptions(string $identifier): array
    {
        $this->ensureSchemaReady();

        $identifier = trim($identifier);
        if ($identifier === '') {
            throw new RuntimeException('Username is required for passkey login.');
        }

        $user = User::query()->where('username', $identifier)->first();
        if ($user === null) {
            $user = User::query()->where('email', $identifier)->first();
        }
        if ($user === null || ! $user->maySignIn()) {
            throw new RuntimeException('Invalid login request.');
        }

        $rows = $this->activePasskeysQuery()
            ->where('user_id', (int) $user->id)
            ->select(['credential_id', 'transports'])
            ->get();

        if ($rows->isEmpty()) {
            throw new RuntimeException('No passkeys are registered for this user.');
        }

        $allowCredentials = [];
        foreach ($rows as $row) {
            $credentialIdRaw = is_string($row->credential_id ?? null) ? $row->credential_id : '';
            if ($credentialIdRaw === '') {
                continue;
            }

            $allowCredentials[] = [
                'type' => 'public-key',
                'id' => self::base64UrlEncode($credentialIdRaw),
                'transports' => $this->transportList(is_string($row->transports ?? null) ? $row->transports : ''),
            ];
        }

        if ($allowCredentials === []) {
            throw new RuntimeException('No active passkey credentials are available.');
        }

        $challenge = $this->createChallenge((int) $user->id, self::CHALLENGE_TYPE_AUTHENTICATE);

        return [
            'challenge' => $challenge,
            'timeout' => 60000,
            'userVerification' => 'preferred',
            'allowCredentials' => $allowCredentials,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function verifyAuthentication(array $payload): User
    {
        $this->ensureSchemaReady();

        $credentialIdB64 = trim((string) ($payload['credential_id'] ?? ''));
        $clientDataB64 = trim((string) ($payload['client_data_json'] ?? ''));
        $authenticatorDataB64 = trim((string) ($payload['authenticator_data'] ?? ''));
        $signatureB64 = trim((string) ($payload['signature'] ?? ''));

        if ($credentialIdB64 === '' || $clientDataB64 === '' || $authenticatorDataB64 === '' || $signatureB64 === '') {
            throw new RuntimeException('Incomplete passkey login payload.');
        }

        $credentialId = self::base64UrlDecode($credentialIdB64);

        $passkey = $this->activePasskeysQuery()
            ->where('credential_id', $credentialId)
            ->select(['id', 'user_id', 'public_key', 'sign_count'])
            ->first();

        if ($passkey === null) {
            throw new RuntimeException('Unknown passkey.');
        }

        $userId = (int) ($passkey->user_id ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Passkey user is invalid.');
        }

        $clientDataJson = self::base64UrlDecode($clientDataB64);
        $clientData = $this->validateClientData($clientDataJson, 'webauthn.get');
        $challenge = (string) ($clientData['challenge'] ?? '');

        if (! $this->consumeChallenge($challenge, self::CHALLENGE_TYPE_AUTHENTICATE, $userId)) {
            throw new RuntimeException('Passkey login challenge is invalid or expired.');
        }

        $authenticatorData = self::base64UrlDecode($authenticatorDataB64);
        $signature = self::base64UrlDecode($signatureB64);
        $parsed = self::parseAuthData($authenticatorData, false);

        $expectedRpHash = hash('sha256', $this->rpId(), true);
        if (! hash_equals($expectedRpHash, $parsed['rpIdHash'])) {
            throw new RuntimeException('Passkey login RP ID mismatch.');
        }
        if (($parsed['flags'] & 0x01) !== 0x01) {
            throw new RuntimeException('Passkey login missing user presence flag.');
        }

        $clientDataHash = hash('sha256', $clientDataJson, true);
        $signedData = $authenticatorData.$clientDataHash;
        $publicKey = is_string($passkey->public_key ?? null) ? $passkey->public_key : '';

        if ($publicKey === '' || ! self::verifySignature($publicKey, $signedData, $signature)) {
            throw new RuntimeException('Passkey signature validation failed.');
        }

        $storedCounter = (int) ($passkey->sign_count ?? 0);
        $newCounter = (int) $parsed['signCount'];

        if ($newCounter > 0 && $storedCounter > 0 && $newCounter <= $storedCounter) {
            throw new RuntimeException('Passkey sign counter check failed.');
        }

        $update = [
            'last_used_at' => now(),
        ];

        if ($newCounter > $storedCounter) {
            $update['sign_count'] = $newCounter;
        }

        DB::table('user_passkeys')
            ->where('id', (int) $passkey->id)
            ->update($update);

        $user = User::query()->find($userId);
        if (! $user instanceof User || ! $user->maySignIn()) {
            throw new RuntimeException('Passkey user unavailable.');
        }

        return $user;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listPasskeys(User $user): array
    {
        $this->ensureSchemaReady();

        $rows = $this->activePasskeysQuery()
            ->where('user_id', (int) $user->id)
            ->select(['id', 'device_name', 'created_at', 'last_used_at'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) ($row->id ?? 0),
                'device_name' => (string) ($row->device_name ?? 'Passkey'),
                'created_at' => $this->formatDate($row->created_at ?? null),
                'last_used_at' => $this->formatDate($row->last_used_at ?? null),
            ];
        }

        return $result;
    }

    public function deletePasskey(User $user, int $passkeyId): bool
    {
        $this->ensureSchemaReady();

        if ($passkeyId <= 0) {
            return false;
        }

        $hasRevokedAt = Schema::hasColumn('user_passkeys', 'revoked_at');
        $hasDeletedAt = Schema::hasColumn('user_passkeys', 'deleted_at');

        $query = DB::table('user_passkeys')
            ->where('id', $passkeyId)
            ->where('user_id', (int) $user->id);

        if ($hasRevokedAt) {
            $query->whereNull('revoked_at');
        }
        if ($hasDeletedAt) {
            $query->whereNull('deleted_at');
        }

        if ($hasRevokedAt || $hasDeletedAt) {
            $update = [];
            if ($hasRevokedAt) {
                $update['revoked_at'] = now();
            }
            if ($hasDeletedAt) {
                $update['deleted_at'] = now();
            }

            $affected = $query->update($update);

            return $affected > 0;
        }

        $affected = $query->delete();

        return $affected > 0;
    }

    private function activePasskeysQuery(): Builder
    {
        $query = DB::table('user_passkeys');

        if (Schema::hasColumn('user_passkeys', 'revoked_at')) {
            $query->whereNull('revoked_at');
        }
        if (Schema::hasColumn('user_passkeys', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    private function ensureSchemaReady(): void
    {
        if (! Schema::hasTable('user_passkeys') || ! Schema::hasTable('webauthn_challenges')) {
            throw new RuntimeException('Passkey tables are not available.');
        }
    }

    private function createChallenge(int $userId, string $type): string
    {
        $challenge = self::base64UrlEncode(random_bytes(32));

        DB::table('webauthn_challenges')->insert([
            'user_id' => $userId,
            'challenge' => $challenge,
            'challenge_type' => $type,
            'expires_at' => now()->addSeconds(self::CHALLENGE_TTL_SECONDS),
            'created_at' => now(),
        ]);

        return $challenge;
    }

    private function consumeChallenge(string $challenge, string $type, ?int $userId): bool
    {
        if ($challenge === '') {
            return false;
        }

        $query = DB::table('webauthn_challenges')
            ->where('challenge', $challenge)
            ->where('challenge_type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now());

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $row = $query->orderByDesc('id')->first(['id']);
        if ($row === null) {
            return false;
        }

        $affected = DB::table('webauthn_challenges')
            ->where('id', (int) $row->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return $affected === 1;
    }

    private function rpName(): string
    {
        $configured = trim((string) config('webauthn.rp_name', ''));

        return $configured !== '' ? $configured : ((string) config('app.name', 'Ski CRM'));
    }

    private function rpId(): string
    {
        $configured = trim((string) config('webauthn.rp_id', ''));
        if ($configured !== '') {
            return strtolower($configured);
        }

        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appUrlHost) && $appUrlHost !== '') {
            return strtolower($appUrlHost);
        }

        return 'localhost';
    }

    /**
     * @return list<string>
     */
    private function getAllowedOrigins(): array
    {
        $configured = config('webauthn.origins', '');
        $values = is_array($configured) ? $configured : explode(',', trim((string) $configured));
        $origins = array_values(array_filter(array_map(
            static fn (mixed $value): string => rtrim(trim((string) $value), '/'),
            $values,
        )));

        return $origins === [] ? [rtrim((string) config('app.url'), '/')] : $origins;
    }

    /**
     * @return array<string,mixed>
     */
    private function validateClientData(string $clientDataJson, string $expectedType): array
    {
        $clientData = json_decode($clientDataJson, true);
        if (! is_array($clientData)) {
            throw new RuntimeException('Invalid clientDataJSON.');
        }

        $type = (string) ($clientData['type'] ?? '');
        $challenge = (string) ($clientData['challenge'] ?? '');
        $origin = rtrim((string) ($clientData['origin'] ?? ''), '/');
        $crossOrigin = (bool) ($clientData['crossOrigin'] ?? false);

        if ($type !== $expectedType) {
            throw new RuntimeException('Unexpected WebAuthn ceremony type.');
        }
        if ($challenge === '') {
            throw new RuntimeException('Missing WebAuthn challenge.');
        }
        if ($origin === '') {
            throw new RuntimeException('Missing WebAuthn origin.');
        }
        if ($crossOrigin) {
            throw new RuntimeException('Cross-origin WebAuthn is not allowed.');
        }

        if (! in_array($origin, $this->getAllowedOrigins(), true)) {
            throw new RuntimeException('WebAuthn origin is not allowed.');
        }

        return $clientData;
    }

    private static function normalizeTransports(mixed $raw): ?string
    {
        $allowed = ['usb', 'nfc', 'ble', 'hybrid', 'internal'];
        $items = [];

        if (is_array($raw)) {
            $items = array_map(static fn (mixed $item): string => (string) $item, $raw);
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed !== '') {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $items = array_map(static fn (mixed $item): string => (string) $item, $decoded);
                } else {
                    $items = preg_split('/\s*,\s*/', $trimmed) ?: [];
                }
            }
        }

        $normalized = [];
        foreach ($items as $item) {
            $item = strtolower(trim($item));
            if ($item !== '' && in_array($item, $allowed, true)) {
                $normalized[] = $item;
            }
        }

        $normalized = array_values(array_unique($normalized));

        return $normalized === [] ? null : implode(',', $normalized);
    }

    /**
     * @return list<string>
     */
    private function transportList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $raw)
        ), static fn (string $item): bool => $item !== ''));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $remainder = strlen($base64) % 4;
        if ($remainder > 0) {
            $base64 .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url payload.');
        }

        return $decoded;
    }

    private static function cborDecode(string $data, int &$offset): mixed
    {
        if (! isset($data[$offset])) {
            throw new RuntimeException('Unexpected CBOR end of data.');
        }

        $initial = ord($data[$offset++]);
        $majorType = $initial >> 5;
        $additionalInfo = $initial & 0x1F;

        if ($additionalInfo === 31) {
            throw new RuntimeException('Indefinite-length CBOR is not supported.');
        }

        $length = self::cborReadLength($data, $offset, $additionalInfo);

        return match ($majorType) {
            0 => $length,
            1 => -1 - $length,
            2 => self::cborReadBytes($data, $offset, $length),
            3 => self::cborReadBytes($data, $offset, $length),
            4 => self::cborReadArray($data, $offset, $length),
            5 => self::cborReadMap($data, $offset, $length),
            6 => self::cborDecode($data, $offset),
            default => self::cborReadSimple($data, $offset, $additionalInfo, $length),
        };
    }

    private static function cborReadLength(string $data, int &$offset, int $additionalInfo): int
    {
        if ($additionalInfo < 24) {
            return $additionalInfo;
        }

        $lengthBytes = match ($additionalInfo) {
            24 => 1,
            25 => 2,
            26 => 4,
            27 => 8,
            default => throw new RuntimeException('Unsupported CBOR additional info.'),
        };

        if (strlen($data) < $offset + $lengthBytes) {
            throw new RuntimeException('Invalid CBOR length encoding.');
        }

        $slice = substr($data, $offset, $lengthBytes);
        $offset += $lengthBytes;

        if ($lengthBytes === 1) {
            return ord($slice);
        }
        if ($lengthBytes === 2) {
            return (int) (unpack('nvalue', $slice)['value'] ?? 0);
        }
        if ($lengthBytes === 4) {
            return (int) (unpack('Nvalue', $slice)['value'] ?? 0);
        }

        return self::cborReadUint64($slice);
    }

    private static function cborReadUint64(string $bytes): int
    {
        $parts = unpack('Nhigh/Nlow', $bytes);
        $high = (int) ($parts['high'] ?? 0);
        $low = (int) ($parts['low'] ?? 0);

        if ($high > 0 && PHP_INT_SIZE < 8) {
            throw new RuntimeException('CBOR uint64 value is too large for this platform.');
        }

        return ($high << 32) | $low;
    }

    private static function cborReadBytes(string $data, int &$offset, int $length): string
    {
        if ($length < 0 || strlen($data) < $offset + $length) {
            throw new RuntimeException('Invalid CBOR bytes length.');
        }

        $value = substr($data, $offset, $length);
        $offset += $length;

        return $value;
    }

    /**
     * @return list<mixed>
     */
    private static function cborReadArray(string $data, int &$offset, int $length): array
    {
        $values = [];
        for ($i = 0; $i < $length; $i++) {
            $values[] = self::cborDecode($data, $offset);
        }

        return $values;
    }

    /**
     * @return array<mixed>
     */
    private static function cborReadMap(string $data, int &$offset, int $length): array
    {
        $values = [];

        for ($i = 0; $i < $length; $i++) {
            $key = self::cborDecode($data, $offset);
            $value = self::cborDecode($data, $offset);

            if (is_int($key) || is_string($key)) {
                $values[$key] = $value;
            } else {
                $values[(string) $i] = $value;
            }
        }

        return $values;
    }

    private static function cborReadSimple(string $data, int &$offset, int $additionalInfo, int $value): mixed
    {
        return match ($additionalInfo) {
            20 => false,
            21 => true,
            22 => null,
            23 => null,
            24 => $value,
            25 => self::decodeHalfFloat($value),
            26 => self::decodeFloat32($value),
            27 => (float) $value,
            default => $value,
        };
    }

    private static function decodeHalfFloat(int $value): float
    {
        $sign = ($value >> 15) & 0x1;
        $exp = ($value >> 10) & 0x1F;
        $frac = $value & 0x3FF;
        $fraction = (float) $frac / 1024.0;

        if ($exp === 0) {
            $result = $fraction * (2.0 ** -14.0);
        } elseif ($exp === 31) {
            $result = $frac === 0 ? INF : NAN;
        } else {
            $result = (1.0 + $fraction) * (2.0 ** ((float) $exp - 15.0));
        }

        return $sign === 1 ? -$result : $result;
    }

    private static function decodeFloat32(int $bits): float
    {
        return unpack('Gvalue', pack('N', $bits))['value'] ?? 0.0;
    }

    /**
     * @return array<string,mixed>
     */
    private static function parseAuthData(string $authData, bool $expectAttestedData): array
    {
        if (strlen($authData) < 37) {
            throw new RuntimeException('Authenticator data is too short.');
        }

        $result = [
            'rpIdHash' => substr($authData, 0, 32),
            'flags' => ord($authData[32]),
            'signCount' => (int) (unpack('Ncounter', substr($authData, 33, 4))['counter'] ?? 0),
        ];

        $offset = 37;
        $hasAttestedData = (($result['flags'] & 0x40) === 0x40);
        if ($expectAttestedData && ! $hasAttestedData) {
            throw new RuntimeException('Attested credential data is missing.');
        }

        if ($hasAttestedData) {
            if (strlen($authData) < $offset + 18) {
                throw new RuntimeException('Attested credential data is incomplete.');
            }

            $aaguid = substr($authData, $offset, 16);
            $offset += 16;

            $credentialLength = (int) (unpack('nlen', substr($authData, $offset, 2))['len'] ?? 0);
            $offset += 2;

            if (strlen($authData) < $offset + $credentialLength) {
                throw new RuntimeException('Credential ID is incomplete.');
            }

            $credentialId = substr($authData, $offset, $credentialLength);
            $offset += $credentialLength;

            $publicKeyOffset = $offset;
            self::cborDecode($authData, $offset);
            if ($offset <= $publicKeyOffset) {
                throw new RuntimeException('Credential public key is invalid.');
            }

            $credentialPublicKey = substr($authData, $publicKeyOffset, $offset - $publicKeyOffset);

            $result['aaguid'] = $aaguid;
            $result['credentialId'] = $credentialId;
            $result['credentialPublicKey'] = $credentialPublicKey;
        }

        return $result;
    }

    private static function formatAaguid(string $aaguid): string
    {
        if (strlen($aaguid) !== 16) {
            return '';
        }

        $hex = bin2hex($aaguid);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    private static function verifySignature(string $cosePublicKey, string $signedData, string $signature): bool
    {
        $publicKeyPem = self::coseToPem($cosePublicKey);
        $verify = openssl_verify($signedData, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);

        return $verify === 1;
    }

    private static function coseToPem(string $coseKey): string
    {
        $offset = 0;
        $decoded = self::cborDecode($coseKey, $offset);
        if (! is_array($decoded)) {
            throw new RuntimeException('COSE key is invalid.');
        }

        $kty = (int) ($decoded[1] ?? 0);

        if ($kty === 2) {
            $curve = (int) ($decoded[-1] ?? 0);
            $x = $decoded[-2] ?? null;
            $y = $decoded[-3] ?? null;

            if ($curve !== 1 || ! is_string($x) || ! is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new RuntimeException('Unsupported EC key parameters.');
            }

            $point = "\x04".$x.$y;
            $algorithmIdentifier = hex2bin('301306072A8648CE3D020106082A8648CE3D030107');
            if ($algorithmIdentifier === false) {
                throw new RuntimeException('Failed to build EC algorithm identifier.');
            }

            $spki = self::derSequence($algorithmIdentifier.self::derBitString($point));

            return self::derToPem($spki, 'PUBLIC KEY');
        }

        if ($kty === 3) {
            $modulus = $decoded[-1] ?? null;
            $exponent = $decoded[-2] ?? null;
            if (! is_string($modulus) || ! is_string($exponent)) {
                throw new RuntimeException('Unsupported RSA key parameters.');
            }

            $rsaPublicKey = self::derSequence(self::derInteger($modulus).self::derInteger($exponent));
            $algorithmIdentifier = hex2bin('300D06092A864886F70D0101010500');
            if ($algorithmIdentifier === false) {
                throw new RuntimeException('Failed to build RSA algorithm identifier.');
            }

            $spki = self::derSequence($algorithmIdentifier.self::derBitString($rsaPublicKey));

            return self::derToPem($spki, 'PUBLIC KEY');
        }

        throw new RuntimeException('Unsupported COSE key type.');
    }

    private static function derSequence(string $bytes): string
    {
        return "\x30".self::derLength(strlen($bytes)).$bytes;
    }

    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            /** @var int<0, 127> $shortLength */
            $shortLength = $length;

            return self::byte($shortLength);
        }

        $encoded = '';
        $value = $length;
        while ($value > 0) {
            /** @var int<0, 255> $chunk */
            $chunk = $value & 0xFF;

            $encoded = self::byte($chunk).$encoded;
            $value >>= 8;
        }

        /** @var int<128, 255> $prefix */
        $prefix = 0x80 | strlen($encoded);

        return self::byte($prefix).$encoded;
    }

    private static function derBitString(string $bytes): string
    {
        return "\x03".self::derLength(strlen($bytes) + 1)."\x00".$bytes;
    }

    private static function derToPem(string $der, string $label): string
    {
        return '-----BEGIN '.$label."-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            .'-----END '.$label."-----\n";
    }

    private static function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '') {
            $bytes = "\x00";
        }
        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".self::derLength(strlen($bytes)).$bytes;
    }

    /**
     * @param  int<0, 255>  $value
     */
    private static function byte(int $value): string
    {
        return chr($value);
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
