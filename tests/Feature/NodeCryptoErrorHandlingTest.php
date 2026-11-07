<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NodeCryptoErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private string $keyDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyDirectory = storage_path('framework/testing/node-error-'.bin2hex(random_bytes(8)));
        config([
            'app.debug' => true,
            'crypto.key_path' => $this->keyDirectory.'/private.json',
            'crypto.node_binary' => base_path('missing-node-binary'),
        ]);
    }

    protected function tearDown(): void
    {
        $lockPath = $this->keyDirectory.'/private.json.lock';
        $fakeNodePath = $this->keyDirectory.'/fake-node';

        if (is_file($lockPath)) {
            unlink($lockPath);
        }

        if (is_file($fakeNodePath)) {
            unlink($fakeNodePath);
        }

        if (is_dir($this->keyDirectory)) {
            rmdir($this->keyDirectory);
        }

        parent::tearDown();
    }

    public function test_node_process_failure_does_not_expose_internal_details_in_html(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/experiments')
            ->assertInternalServerError()
            ->assertSeeText('Permintaan tidak dapat diproses saat ini.')
            ->assertDontSee('missing-node-binary')
            ->assertDontSee('RuntimeException')
            ->assertDontSee('NodeCryptoBridge');
    }

    public function test_node_stderr_is_not_exposed_in_the_http_response(): void
    {
        $fakeNodePath = $this->keyDirectory.'/fake-node';
        mkdir($this->keyDirectory, 0700, true);
        file_put_contents($fakeNodePath, "#!/bin/sh\nprintf '%s\\n' 'internal-node-failure-secret' >&2\nexit 1\n");
        chmod($fakeNodePath, 0700);

        config(['crypto.node_binary' => $fakeNodePath]);

        $this->actingAs(User::factory()->create())
            ->post('/experiments')
            ->assertInternalServerError()
            ->assertSeeText('Permintaan tidak dapat diproses saat ini.')
            ->assertDontSee('internal-node-failure-secret')
            ->assertDontSee($fakeNodePath);
    }

    public function test_node_process_failure_does_not_expose_internal_details_in_json(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/experiments')
            ->assertInternalServerError()
            ->assertExactJson([
                'message' => 'Terjadi kesalahan internal. Silakan coba kembali atau hubungi administrator.',
            ]);
    }
}
