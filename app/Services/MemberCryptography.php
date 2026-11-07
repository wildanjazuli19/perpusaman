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

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Lokasi penyimpanan kunci ML-KEM tidak valid.');
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        $directory = dirname($path);

        $this->assertKeyStorageLocation($directory);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori penyimpanan kunci tidak dapat dibuat.');
        }

        $this->assertKeyStorageLocation($directory);
        $this->setPermissions($directory, 0700, 'Direktori penyimpanan kunci');

        if (is_link($path)) {
            throw new RuntimeException('File private key ML-KEM tidak boleh berupa symbolic link.');
        }

        $lockPath = $path.'.lock';

        if (is_link($lockPath)) {
            throw new RuntimeException('File lock kunci ML-KEM tidak boleh berupa symbolic link.');
        }

        $lock = fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException('File lock kunci ML-KEM tidak dapat dibuka.');
        }

        $locked = false;

        try {
            $this->setPermissions($lockPath, 0600, 'File lock kunci ML-KEM');

            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Penyimpanan kunci ML-KEM tidak dapat dikunci.');
            }

            $locked = true;

            if (is_file($path)) {
                $this->setPermissions($path, 0600, 'File private key ML-KEM');
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
            $keyFile = fopen($path, 'x');

            if ($keyFile === false) {
                throw new RuntimeException('Private key ML-KEM tidak dapat disimpan.');
            }

            try {
                $this->setPermissions($path, 0600, 'File private key ML-KEM');
                $contents = json_encode($stored, JSON_THROW_ON_ERROR);
                $written = fwrite($keyFile, $contents);

                if ($written !== strlen($contents) || ! fflush($keyFile)) {
                    throw new RuntimeException('Private key ML-KEM tidak dapat disimpan secara lengkap.');
                }
            } finally {
                fclose($keyFile);
            }

            return [
                'public_key' => $stored['public_key'],
                'private_key' => $generated['private_key'],
            ];
        } finally {
            if ($locked) {
                flock($lock, LOCK_UN);
            }

            fclose($lock);
        }
    }

    private function assertKeyStorageLocation(string $directory): void
    {
        $publicDirectory = realpath(public_path());

        if ($publicDirectory === false) {
            throw new RuntimeException('Direktori publik aplikasi tidak dapat ditentukan.');
        }

        $existingDirectory = $directory;
        $missingDirectories = [];

        while (! file_exists($existingDirectory)) {
            $parentDirectory = dirname($existingDirectory);

            if ($parentDirectory === $existingDirectory) {
                throw new RuntimeException('Lokasi penyimpanan kunci ML-KEM tidak valid.');
            }

            array_unshift($missingDirectories, basename($existingDirectory));
            $existingDirectory = $parentDirectory;
        }

        $resolvedDirectory = realpath($existingDirectory);

        if ($resolvedDirectory === false) {
            throw new RuntimeException('Lokasi penyimpanan kunci ML-KEM tidak dapat ditentukan.');
        }

        foreach ($missingDirectories as $missingDirectory) {
            $resolvedDirectory .= DIRECTORY_SEPARATOR.$missingDirectory;
        }

        if ($this->pathIsWithin($resolvedDirectory, $publicDirectory)) {
            throw new RuntimeException('Private key ML-KEM harus disimpan di luar direktori publik.');
        }
    }

    private function pathIsWithin(string $path, string $directory): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $directory = strtolower($directory);
        }

        return $path === $directory || str_starts_with($path, $directory.DIRECTORY_SEPARATOR);
    }

    private function setPermissions(string $path, int $permissions, string $description): void
    {
        if (! chmod($path, $permissions)) {
            throw new RuntimeException($description.' tidak dapat diamankan.');
        }

        clearstatcache(true, $path);
        $actualPermissions = fileperms($path);

        if ($actualPermissions === false || ($actualPermissions & 0777) !== $permissions) {
            throw new RuntimeException('Permission '.$description.' tidak aman.');
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
