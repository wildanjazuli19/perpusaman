<?php

namespace Tests\Feature;

use App\Models\ExperimentRun;
use App\Models\Member;
use App\Models\User;
use App\Services\MemberCryptography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class MemberCryptographyTest extends TestCase
{
    use RefreshDatabase;

    private string $keyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyPath = storage_path('framework/testing/mlkem-'.bin2hex(random_bytes(8)).'/private.json');
        config(['crypto.key_path' => $this->keyPath]);
    }

    protected function tearDown(): void
    {
        foreach ([$this->keyPath, $this->keyPath.'.lock'] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir(dirname($this->keyPath))) {
            rmdir(dirname($this->keyPath));
        }

        parent::tearDown();
    }

    public function test_private_key_and_storage_directory_permissions_are_enforced(): void
    {
        $cryptography = app(MemberCryptography::class);
        $data = ['nama' => 'Anggota Uji'];

        $cryptography->protect($data);

        $this->assertSame(0700, fileperms(dirname($this->keyPath)) & 0777);
        $this->assertSame(0600, fileperms($this->keyPath) & 0777);
        $this->assertSame(0600, fileperms($this->keyPath.'.lock') & 0777);

        $this->assertTrue(chmod(dirname($this->keyPath), 0755));
        $this->assertTrue(chmod($this->keyPath, 0644));

        $cryptography->protect($data);

        $this->assertSame(0700, fileperms(dirname($this->keyPath)) & 0777);
        $this->assertSame(0600, fileperms($this->keyPath) & 0777);
    }

    public function test_private_key_storage_inside_public_directory_is_rejected(): void
    {
        config(['crypto.key_path' => public_path('mlkem-'.bin2hex(random_bytes(8)).'/private.json')]);

        try {
            app(MemberCryptography::class)->protect(['nama' => 'Anggota Uji']);
            $this->fail('Lokasi key di direktori publik seharusnya ditolak.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Private key ML-KEM harus disimpan di luar direktori publik.', $exception->getMessage());
        }
    }

    public function test_member_data_is_encrypted_before_storage_and_can_be_decrypted(): void
    {
        $user = User::factory()->create();
        $memberData = [
            'no_anggota' => 'TEST-001',
            'nama' => 'Anggota Uji',
            'kampus' => 'Meruya',
            'tanggal_berlaku' => '2027-12-31',
            'email' => 'uji@example.test',
            'telepon' => '08123456789',
        ];

        $response = $this->actingAs($user)->post(route('members.store'), $memberData);

        $member = Member::query()->sole();
        $response->assertRedirect(route('members.show', $member));
        $this->get(route('members.index'))
            ->assertOk()
            ->assertSee('Rekaman anggota')
            ->assertSee('member-table-wrap')
            ->assertSee('Identitas disimpan terenkripsi')
            ->assertSee('Lihat detail');

        $this->get(route('members.show', $member))
            ->assertOk()
            ->assertSee('Pengukuran saat enkripsi')
            ->assertSee('member-metrics-grid')
            ->assertSee('Lihat rincian waktu tiap tahap');

        $storedRecord = json_encode(DB::table('members')->where('id', $member->id)->first(), JSON_THROW_ON_ERROR);

        foreach ($memberData as $value) {
            $this->assertStringNotContainsString($value, $storedRecord);
        }

        $this->actingAs($user)
            ->post(route('members.decrypt', $member))
            ->assertOk()
            ->assertSee('Data hasil dekripsi')
            ->assertSee('Anggota Uji')
            ->assertSee('TEST-001')
            ->assertSee('Meruya')
            ->assertSee('2027-12-31')
            ->assertSee('uji@example.test')
            ->assertSee('08123456789')
            ->assertSee('Autentikasi valid');
    }

    public function test_modified_ciphertext_fails_authentication(): void
    {
        $user = User::factory()->create();
        $member = Member::create(app(MemberCryptography::class)->protect([
            'no_anggota' => 'TEST-002',
            'nama' => 'Anggota Integritas',
            'kampus' => 'Meruya',
            'tanggal_berlaku' => '2027-12-31',
            'email' => 'integritas@example.test',
            'telepon' => '08120000000',
        ]));

        $this->actingAs($user)
            ->post(route('members.tamper-test', $member))
            ->assertOk()
            ->assertSee('Tampering terdeteksi')
            ->assertDontSee('Anggota Integritas');
    }

    public function test_member_routes_require_authentication(): void
    {
        $this->get(route('members.index'))->assertRedirect('/login');
        $this->get(route('experiments.index'))->assertRedirect('/login');
    }

    public function test_authenticated_dashboard_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Perpustakaan')
            ->assertSee('Keamanan & eksperimen', false)
            ->assertSee('ML-KEM-768');
    }

    public function test_benchmark_records_real_hybrid_and_baseline_results(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('experiments.store'));
        $experiment = ExperimentRun::query()->sole();

        $response->assertRedirect(route('experiments.show', $experiment));
        $this->assertTrue($experiment->metrics['hybrid_roundtrip_matches']);
        $this->assertTrue($experiment->metrics['baseline_roundtrip_matches']);
        $this->assertTrue($experiment->metrics['tampering_rejected']);

        $this->actingAs($user)
            ->get(route('experiments.show', $experiment))
            ->assertOk()
            ->assertSee('Metrik kinerja dan penyimpanan');
    }
}
