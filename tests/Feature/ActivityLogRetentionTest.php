<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ActivityLogRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_purge_removes_logs_older_than_configured_retention_period(): void
    {
        Carbon::setTestNow('2026-11-07 12:00:00');
        config(['activity_logs.retention_days' => 365]);

        $expiredLogId = DB::table('activity_logs')->insertGetId([
            'action' => 'POST members',
            'method' => 'POST',
            'url' => '/members',
            'created_at' => now()->subDays(366)->toDateTimeString(),
            'updated_at' => now()->subDays(366)->toDateTimeString(),
        ]);
        $boundaryLogId = DB::table('activity_logs')->insertGetId([
            'action' => 'POST members',
            'method' => 'POST',
            'url' => '/members',
            'created_at' => now()->subDays(365)->toDateTimeString(),
            'updated_at' => now()->subDays(365)->toDateTimeString(),
        ]);
        $recentLogId = DB::table('activity_logs')->insertGetId([
            'action' => 'POST members',
            'method' => 'POST',
            'url' => '/members',
            'created_at' => now()->subDays(30)->toDateTimeString(),
            'updated_at' => now()->subDays(30)->toDateTimeString(),
        ]);

        $this->artisan('activity-logs:purge')->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['id' => $expiredLogId]);
        $this->assertDatabaseHas('activity_logs', ['id' => $boundaryLogId]);
        $this->assertDatabaseHas('activity_logs', ['id' => $recentLogId]);

        Carbon::setTestNow();
    }

    public function test_purge_rejects_invalid_retention_period(): void
    {
        config(['activity_logs.retention_days' => 0]);

        $this->expectException(InvalidArgumentException::class);
        $this->artisan('activity-logs:purge');
    }
}
