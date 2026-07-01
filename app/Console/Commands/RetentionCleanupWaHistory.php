<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RetentionCleanupWaHistory extends Command
{
    protected $signature = 'wa:retention-cleanup
        {--purge : Hapus data setelah backup berhasil}
        {--json : Output JSON}
        {--base-path= : Override folder backup relatif terhadap storage/app}';

    protected $description = 'Backup and optionally purge old WA history based on retention policy';

    private array $policies = [
        'wa_logs' => 90,
        'wa_inbox' => 365,
        'wa_schedules' => 180,
        'approval_requests' => 365,
    ];

    public function handle(): int
    {
        $startedAt = CarbonImmutable::instance(now());
        $backupRoot = trim((string) ($this->option('base-path') ?: 'backups/wa-retention/' . $startedAt->format('Ymd-His')), '/');
        $purge = (bool) $this->option('purge');
        $summary = [
            'ok' => true,
            'purge' => $purge,
            'started_at' => $startedAt->toIso8601String(),
            'backup_root' => storage_path('app/' . $backupRoot),
            'tables' => [],
        ];

        foreach ($this->policies as $table => $days) {
            if (!Schema::hasTable($table)) {
                $summary['tables'][$table] = [
                    'ok' => false,
                    'reason' => 'table_not_found',
                ];
                continue;
            }

            $timestampColumn = $this->timestampColumn($table);
            $cutoff = CarbonImmutable::instance(now())->subDays($days)->startOfDay();
            $query = DB::table($table)->where($timestampColumn, '<', $cutoff);
            $count = (clone $query)->count();
            $backupFile = $backupRoot . '/' . $table . '.jsonl';

            if ($count > 0) {
                $this->writeJsonlBackup($table, $query->orderBy('id'), $backupFile);
            } else {
                Storage::put($backupFile, '');
            }

            $deleted = 0;
            if ($purge && $count > 0) {
                $deleted = DB::table($table)
                    ->where($timestampColumn, '<', $cutoff)
                    ->delete();
            }

            $summary['tables'][$table] = [
                'retention_days' => $days,
                'timestamp_column' => $timestampColumn,
                'cutoff' => $cutoff->toDateTimeString(),
                'backup_file' => storage_path('app/' . $backupFile),
                'candidate_rows' => $count,
                'deleted_rows' => $deleted,
            ];
        }

        Storage::put($backupRoot . '/manifest.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($purge) {
            $this->cleanupOldBackups();
            $this->cleanupOldRuntimeFiles();
        }

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('Backup root: ' . $summary['backup_root']);
            foreach ($summary['tables'] as $table => $data) {
                $this->line($table . ': candidates=' . ($data['candidate_rows'] ?? 0) . ', deleted=' . ($data['deleted_rows'] ?? 0));
            }
        }

        return self::SUCCESS;
    }

    private function timestampColumn(string $table): string
    {
        if ($table === 'approval_requests') {
            return 'created_at';
        }

        return Schema::hasColumn($table, 'created_at') ? 'created_at' : 'id';
    }

    private function writeJsonlBackup(string $table, $query, string $backupFile): void
    {
        $absolutePath = storage_path('app/' . $backupFile);
        File::ensureDirectoryExists(dirname($absolutePath));

        $handle = fopen($absolutePath, 'wb');
        foreach ($query->cursor() as $row) {
            fwrite($handle, json_encode((array) $row, JSON_UNESCAPED_SLASHES) . PHP_EOL);
        }
        fclose($handle);
    }

    private function cleanupOldBackups(): void
    {
        $root = storage_path('app/backups/wa-retention');
        $keepDays = (int) env('WA_RETENTION_BACKUP_KEEP_DAYS', 30);

        if (!File::isDirectory($root)) {
            return;
        }

        foreach (File::directories($root) as $dir) {
            if (File::lastModified($dir) < now()->subDays($keepDays)->getTimestamp()) {
                File::deleteDirectory($dir);
            }
        }
    }

    private function cleanupOldRuntimeFiles(): void
    {
        $paths = [
            storage_path('logs'),
            storage_path('framework/sessions'),
        ];

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            foreach (File::files($path) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }

                if ($file->getMTime() < now()->subDays(14)->getTimestamp()) {
                    File::delete($file->getPathname());
                }
            }
        }
    }
}
