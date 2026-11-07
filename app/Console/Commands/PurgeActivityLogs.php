<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class PurgeActivityLogs extends Command
{
    protected $signature = 'activity-logs:purge';

    protected $description = 'Hapus log aktivitas yang melewati masa retensi.';

    public function handle(): int
    {
        $retentionDays = filter_var(
            config('activity_logs.retention_days'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($retentionDays === false) {
            throw new InvalidArgumentException('ACTIVITY_LOG_RETENTION_DAYS harus berupa bilangan bulat minimal 1.');
        }

        $cutoff = Carbon::now()->subDays($retentionDays);
        $deleted = ActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("{$deleted} log aktivitas lebih lama dari {$retentionDays} hari dihapus.");

        return self::SUCCESS;
    }
}
