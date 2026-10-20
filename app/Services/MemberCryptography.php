<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use JsonException;
use RuntimeException;

class MemberCryptography
{
    private const AAD = 'perpustakaan-anggota:v1';

    public function __construct(private readonly NodeCryptoBridge $bridge) {}

    /**
     * Encrypt member attributes using both the hybrid and baseline schemes.
     *
     * @throws JsonException
     */
    public function protect(array $member): array
    {
        $keyPair = $this->keyPair();

        return $this->bridge->call('protect', [
            'data' => $member,
            'public_key' => $keyPair['public_key'],
            'baseline_key' => $this->baselineKey(),
            'aad' => self::AAD,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function decryptHybrid(array $payload): array
    {
        $keyPair = $this->keyPair();

        return $this->bridge->call('decrypt-hybrid', [
            'payload' => $payload,
            'private_key' => $keyPair['private_key'],
            'aad' => self::AAD,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function decryptBaseline(array $payload): array
    {
        return $this->bridge->call('decrypt-baseline', [
            'payload' => $payload,
            'baseline_key' => $this->baselineKey(),
            'aad' => self::AAD,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function benchmark(array $data): array
    {
        $keyPair = $this->keyPair();

        return $this->bridge->call('benchmark', [
            'data' => $data,
            'public_key' => $keyPair['public_key'],
            'private_key' => $keyPair['private_key'],
            'baseline_key' => $this->baselineKey(),
            'aad' => self::AAD,
        ]);
    }

    public function tamperCiphertext(array $payload): array
    {
        $ciphertext = base64_decode($payload['ciphertext'] ?? '', true);

        if ($ciphertext === false || $ciphertext === '') {
            throw new RuntimeException('Ciphertext tidak valid sehingga tidak dapat diuji.');
        }

        $ciphertext[0] = chr(ord($ciphertext[0]) ^ 1);
        $payload['ciphertext'] = base64_encode($ciphertext);

        return $payload;
    }

    /**
     * @throws JsonException
     */
    private function keyPair(): array
    {
        $path = config('crypto.key_path');
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori penyimpanan kunci tidak dapat dibuat.');
        }

        $lock = fopen($path.'.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException('File lock kunci ML-KEM tidak dapat dibuka.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Penyimpanan kunci ML-KEM tidak dapat dikunci.');
            }

            if (is_file($path)) {
                $stored = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

                try {
                    $stored['private_key'] = Crypt::decryptString($stored['private_key']);
                } catch (DecryptException $exception) {
                    throw new RuntimeException(
                        'Private key ML-KEM tidak dapat dibuka. Pastikan APP_KEY tidak berubah.',
                        previous: $exception
                    );
                }

                return $stored;
            }

            $generated = $this->bridge->call('keygen');
            $stored = [
                'public_key' => $generated['public_key'],
                'private_key' => Crypt::encryptString($generated['private_key']),
            ];
            $written = file_put_contents($path, json_encode($stored, JSON_THROW_ON_ERROR), LOCK_EX);

            if ($written === false) {
                throw new RuntimeException('Private key ML-KEM tidak dapat disimpan.');
            }

            chmod($path, 0600);

            return [
                'public_key' => $stored['public_key'],
                'private_key' => $generated['private_key'],
            ];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function baselineKey(): string
    {
        $applicationKey = config('app.key');

        if (! is_string($applicationKey) || $applicationKey === '') {
            throw new RuntimeException('APP_KEY belum diatur; jalankan php artisan key:generate.');
        }

        $keyMaterial = hash('sha256', $applicationKey, true);

        return base64_encode(hash_hkdf(
            'sha256',
            $keyMaterial,
            32,
            'AES-256-GCM baseline v1',
            'library-member-encryption'
        ));
    }
}
