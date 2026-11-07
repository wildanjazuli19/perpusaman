<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class NodeCryptoBridge
{
    /**
     * @throws JsonException
     */
    public function call(string $operation, array $input = []): array
    {
        $configuredBinary = config('crypto.node_binary', 'node');

        if (! is_string($configuredBinary) || $configuredBinary === '') {
            Log::error('Node.js executable configuration is invalid.', [
                'operation' => $operation,
            ]);

            throw new RuntimeException('Operasi kriptografi gagal. Silakan coba kembali atau hubungi administrator.');
        }

        $containsPath = str_contains($configuredBinary, '/') || str_contains($configuredBinary, '\\');
        $binary = $containsPath
            ? (is_file($configuredBinary) && is_executable($configuredBinary) ? $configuredBinary : null)
            : (new ExecutableFinder)->find($configuredBinary);

        if ($binary === null) {
            Log::error('Configured Node.js executable was not found or is not executable.', [
                'operation' => $operation,
                'configured_binary' => $configuredBinary,
            ]);

            throw new RuntimeException('Operasi kriptografi gagal. Silakan coba kembali atau hubungi administrator.');
        }

        $process = new Process([
            $binary,
            base_path('scripts/mlkem.cjs'),
            $operation,
        ], base_path(), [
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\Windows',
            'WINDIR' => getenv('WINDIR') ?: 'C:\Windows',
        ]);
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (Throwable $exception) {
            Log::error('Node.js cryptography process could not be started or completed.', [
                'operation' => $operation,
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Operasi kriptografi gagal. Silakan coba kembali atau hubungi administrator.'
            );
        }

        if (! $process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());
            Log::error('Node.js cryptography process returned a failure.', [
                'operation' => $operation,
                'exit_code' => $process->getExitCode(),
                'exit_status' => $process->getExitCodeText(),
                'stderr' => $errorOutput === '' ? null : mb_substr($errorOutput, 0, 4000),
                'stdout_present' => trim($process->getOutput()) !== '',
            ]);

            throw new RuntimeException(
                'Operasi kriptografi gagal. Silakan coba kembali atau hubungi administrator.'
            );
        }

        try {
            $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::error('Node.js cryptography process returned invalid JSON.', [
                'operation' => $operation,
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Operasi kriptografi gagal. Silakan coba kembali atau hubungi administrator.'
            );
        }

        if (! is_array($result)) {
            Log::error('Node.js cryptography process returned an unexpected response type.', [
                'operation' => $operation,
            ]);

            throw new RuntimeException(
                'Operasi kriptografi gagal. Silakan coba kembali atau hubungi administrator.'
            );
        }

        return $result;
    }
}
