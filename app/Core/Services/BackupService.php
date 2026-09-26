<?php

namespace App\Core\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BackupService
{
    public function run(string $type = 'full'): Backup
    {
        $disk = (string) setting('storage.backup_disk', config('lindu.backup.disk', 'local'));
        $b = Backup::create([
            'type' => $type,
            'status' => 'running',
            'disk' => $disk,
            'started_at' => now(),
        ]);

        try {
            $manifest = [
                'type' => $type,
                'created_at' => now()->toIso8601String(),
                'lindu_version' => config('lindu.version'),
                'php' => PHP_VERSION,
                'database' => config('database.default'),
                'contains' => [],
            ];

            $entries = [];

            if (in_array($type, ['full', 'database'], true)) {
                $sql = $this->dumpDatabase();
                $entries['database.sql'] = $sql;
                $manifest['contains'][] = 'database.sql';
                $manifest['rows'] = $this->countRows();
            }

            if (in_array($type, ['full', 'files'], true)) {
                foreach ($this->storagePayload() as $name => $contents) {
                    $entries[$name] = $contents;
                    $manifest['contains'][] = $name;
                }
            }

            $entries['manifest.json'] = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $entries['.env.example'] = $this->envExample();

            $name = 'backup-'.now()->format('Ymd-His').'-'.$type.'.zip';
            $path = 'backups/'.$name;

            if (! class_exists(ZipArchive::class)) {
                // No zip extension: fall back to a single concatenated file
                // so the backup is still restorable.
                $flat = "-- Lindu CMS backup (no zip extension available)\n";
                foreach ($entries as $n => $c) {
                    $flat .= "\n-- ===== {$n} =====\n{$c}\n";
                }
                $path = 'backups/backup-'.now()->format('Ymd-His').'-'.$type.'.sql';
                Storage::disk($disk)->put($path, $flat);
                $size = strlen($flat);
            } else {
                $tmp = tempnam(sys_get_temp_dir(), 'lindu-backup-');
                $zip = new ZipArchive;
                $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                foreach ($entries as $n => $c) {
                    $zip->addFromString($n, $c);
                }
                $zip->close();
                $size = filesize($tmp);
                $stream = fopen($tmp, 'rb');
                Storage::disk($disk)->put($path, $stream);
                fclose($stream);
                @unlink($tmp);
            }

            $b->update([
                'status' => 'completed',
                'path' => $path,
                'size' => $size,
                'finished_at' => now(),
                'log' => 'Backed up: '.implode(', ', $manifest['contains']),
            ]);

            $this->prune($disk);
        } catch (\Throwable $e) {
            $b->update(['status' => 'failed', 'log' => $e->getMessage(), 'finished_at' => now()]);
        }

        return $b->fresh();
    }

    public function delete(Backup $backup): void
    {
        if ($backup->path) {
            try {
                Storage::disk($backup->disk)->delete($backup->path);
            } catch (\Throwable $e) {
                // Disk may already be gone; deleting the row is still correct.
            }
        }
        $backup->delete();
    }

    /**
     * Restore a backup. Refuses to run without an explicit confirmation flag
     * so it can never be triggered by a stray GET or CSRF-less form post.
     */
    public function restore(Backup $backup, bool $confirmed = false): array
    {
        if (! $confirmed) {
            return ['ok' => false, 'message' => 'Restore requires explicit confirmation.'];
        }
        if ($backup->status !== 'completed' || ! $backup->path) {
            return ['ok' => false, 'message' => 'This backup has no usable archive.'];
        }
        if (! Storage::disk($backup->disk)->exists($backup->path)) {
            return ['ok' => false, 'message' => 'Backup file is missing from the storage disk.'];
        }

        $payload = $this->readArchive($backup);
        if (! isset($payload['manifest.json'])) {
            return ['ok' => false, 'message' => 'Archive is not a Lindu CMS backup (no manifest).'];
        }

        $restored = [];

        if (isset($payload['database.sql'])) {
            $ok = $this->loadSql($payload['database.sql']);
            $restored[] = $ok ? 'database' : 'database (see log)';
        }

        foreach ($payload as $name => $contents) {
            if (str_starts_with($name, 'storage/') || str_starts_with($name, 'public/')) {
                $target = base_path($name);
                if (! is_dir(dirname($target))) {
                    @mkdir(dirname($target), 0775, true);
                }
                @file_put_contents($target, $contents);
                $restored[] = $name;
            }
        }

        return [
            'ok' => true,
            'message' => 'Restored: '.(count($restored) ? implode(', ', $restored) : 'nothing to restore'),
        ];
    }

    /** @return array<string,string> */
    protected function readArchive(Backup $backup): array
    {
        $disk = Storage::disk($backup->disk);
        $contents = $disk->get($backup->path);

        if (! str_ends_with($backup->path, '.zip')) {
            return $this->parseFlatDump($contents);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'lindu-restore-');
        file_put_contents($tmp, $contents);
        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);

            return [];
        }
        $out = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $out[$name] = (string) $zip->getFromIndex($i);
        }
        $zip->close();
        @unlink($tmp);

        return $out;
    }

    protected function parseFlatDump(string $contents): array
    {
        $out = [];
        $current = 'raw.sql';
        $buf = [];
        foreach (explode("\n", $contents) as $line) {
            if (preg_match('/^-- ===== (.+) =====$/', $line, $m)) {
                if ($buf) {
                    $out[$current] = implode("\n", $buf);
                }
                $current = trim($m[1]);
                $buf = [];

                continue;
            }
            $buf[] = $line;
        }
        if ($buf) {
            $out[$current] = implode("\n", $buf);
        }

        return $out;
    }

    /** Produce a portable SQL dump for MySQL/MariaDB or SQLite. */
    public function dumpDatabase(): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite' ? $this->dumpSqlite() : $this->dumpMysql();
    }

    protected function dumpSqlite(): string
    {
        $path = DB::connection()->getDatabaseName();
        $out = "-- Lindu CMS SQLite backup\n-- generated ".now()->toIso8601String()."\nPRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n";

        try {
            $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        } catch (\Throwable $e) {
            return $out."-- unable to read sqlite_master: ".$e->getMessage()."\nCOMMIT;\n";
        }

        foreach ($tables as $t) {
            $table = $t->name;
            $out .= "\n-- structure: {$table}\n";
            if ($t->sql) {
                $out .= $t->sql.";\n";
            }

            try {
                $rows = DB::select('SELECT * FROM "'.str_replace('"', '""', $table).'"');
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($rows as $row) {
                $assoc = (array) $row;
                $cols = array_keys($assoc);
                $vals = array_map(
                    fn ($v) => $v === null ? 'NULL' : "'".str_replace("'", "''", (string) $v)."'",
                    array_values($assoc)
                );
                $out .= 'INSERT INTO "'.$table.'" ("'.implode('", "', $cols).'") VALUES ('.implode(', ', $vals).");\n";
            }
        }

        return $out."COMMIT;\n";
    }

    protected function dumpMysql(): string
    {
        $out = "-- Lindu CMS MySQL backup\n-- generated ".now()->toIso8601String()."\nSET FOREIGN_KEY_CHECKS=0;\n";

        try {
            $tables = DB::select('SHOW TABLES');
        } catch (\Throwable $e) {
            return $out."-- unable to list tables: ".$e->getMessage()."\nSET FOREIGN_KEY_CHECKS=1;\n";
        }

        foreach ($tables as $row) {
            $table = (string) array_values((array) $row)[0];
            $out .= "\n-- structure: {$table}\n";
            $create = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $out .= (string) array_values((array) $create)[1].";\n";

            try {
                $data = DB::select("SELECT * FROM `{$table}`");
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($data as $d) {
                $cols = array_keys((array) $d);
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : DB::getPdo()->quote((string) $v), array_values((array) $d));
                $out .= 'INSERT INTO `'.$table.'` (`'.implode('`, `', $cols).'`) VALUES ('.implode(', ', $vals).");\n";
            }
        }

        return $out."SET FOREIGN_KEY_CHECKS=1;\n";
    }

    protected function loadSql(string $sql): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $path = DB::connection()->getDatabaseName();
            $in = tempnam(sys_get_temp_dir(), 'lindu-sql-');
            file_put_contents($in, $sql);
            $ok = false;
            $output = [];
            $code = 0;
            exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$p=new PDO("sqlite:'.addslashes($path).'");$p->exec(file_get_contents($argv[1]));').' '.escapeshellarg($in).' 2>&1', $output, $code);
            $ok = $code === 0;
            @unlink($in);
            if ($ok) {
                Artisan::call('cache:clear');
            }

            return $ok;
        }

        $ok = true;
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            if (str_starts_with($statement, '--')) {
                continue;
            }
            try {
                DB::unprepared($statement);
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        return $ok;
    }

    protected function countRows(): int
    {
        try {
            $counts = DB::select(
                DB::connection()->getDriverName() === 'sqlite'
                    ? "SELECT (SELECT COUNT(*) FROM users) AS u, (SELECT COUNT(*) FROM settings) AS s"
                    : 'SELECT (SELECT COUNT(*) FROM users) AS u, (SELECT COUNT(*) FROM settings) AS s'
            );

            $row = (array) $counts[0];

            return (int) (($row['u'] ?? 0) + ($row['s'] ?? 0));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Application files worth keeping: uploads and the env template. */
    protected function storagePayload(): array
    {
        $out = [];

        foreach (['storage/app/public', 'public/storage/media', 'storage/app/careers'] as $rel) {
            $path = base_path($rel);
            if (! is_dir($path)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $f) {
                if (! $f->isFile() || $f->getSize() > 25 * 1024 * 1024) {
                    continue;
                }
                $key = str_replace('\\', '/', Str::after($f->getPathname(), base_path().DIRECTORY_SEPARATOR));
                $out[$key] = (string) file_get_contents($f->getPathname());
                if (count($out) > 3000) {
                    return $out;
                }
            }
        }

        return $out;
    }

    protected function envExample(): string
    {
        $path = base_path('.env.example');
        if (! is_file($path)) {
            return '';
        }
        // Strip secrets: a backup must never leak a live .env.
        $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents($path));

        return implode("\n", array_map(function ($line) {
            if (preg_match('/^\s*(APP_KEY|DB_PASSWORD|.*_SECRET|.*_KEY|.*_TOKEN)\s*=/i', $line)) {
                return preg_replace('/=.*/', '=', $line);
            }

            return $line;
        }, $lines));
    }

    protected function prune(string $disk): void
    {
        $keep = max(1, (int) setting('storage.keep_backups', config('lindu.backup.keep', 7)));
        Backup::orderByDesc('id')->skip($keep)->take(200)->get()->each(function ($old) use ($disk) {
            $this->delete($old);
        });
    }
}
