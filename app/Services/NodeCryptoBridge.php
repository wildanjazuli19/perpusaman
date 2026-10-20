<?php

namespace App\Services;

use JsonException;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class NodeCryptoBridge
{
    /**
     * @throws JsonException
     */
    public function call(string $operation, array $input = []): array
    {
        $configuredBinary = config('crypto.node_binary', 'node');
        $containsPath = str_contains($configuredBinary, '/') || str_contains($configuredBinary, '\\');
        $binary = $containsPath
            ? (is_file($configuredBinary) && is_executable($configuredBinary) ? $configuredBinary : null)
            : (new ExecutableFinder)->find($configuredBinary);

        if ($binary === null) {
            throw new RuntimeException(
                "Node.js tidak ditemukan ({$configuredBinary}). Atur NODE_BINARY ke path executable Node.js."
            );
        }

        $process = new Process([
            $binary,
            base_path('scripts/mlkem.cjs'),
            $operation,
        ], base_path());
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            $output = trim($process->getErrorOutput() ?: $process->getOutput());
            $exitStatus = $process->getExitCodeText() ?? 'proses dihentikan oleh sinyal';
            $details = $output !== '' ? $output : 'Node.js tidak menghasilkan pesan error.';

            throw new RuntimeException(
                "Operasi kriptografi gagal ({$exitStatus}): {$details}"
            );
        }

        try {
            $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Node.js mengembalikan respons JSON yang tidak valid.',
                previous: $exception
            );
        }

        if (! is_array($result)) {
            throw new RuntimeException('Respons operasi kriptografi tidak valid.');
        }

        return $result;
    }
}
