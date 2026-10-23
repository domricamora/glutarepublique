<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Encrypted database backup (contract Annex A.9). Runs nightly from the
 * scheduler; a copy is only useful with the APP_KEY of the same install, so
 * keep that key somewhere other than the server.
 *
 * ponytail: the whole dump is encrypted in memory, fine for a clinic-sized
 * database; stream it through openssl if it grows past a few hundred MB.
 */
class BackupDatabase extends Command
{
    protected $signature = 'clinic:backup
        {--keep=14 : How many backups to keep}
        {--decrypt= : Decrypt this backup file to a .sql.gz next to it instead of taking one}';

    protected $description = 'Take an encrypted, compressed backup of the database';

    public function handle(): int
    {
        if ($file = $this->option('decrypt')) {
            $target = preg_replace('/\.enc$/', '', $file);
            File::put($target, Crypt::decryptString(File::get($file)));
            $this->info("Decrypted to $target");

            return self::SUCCESS;
        }

        $db = config('database.connections.'.config('database.default'));
        $dump = new Process(
            [config('clinic.mysqldump'), '--single-transaction', '--quick', '--no-tablespaces',
                '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'], $db['database']],
            env: ['MYSQL_PWD' => (string) $db['password']], // kept out of the process list
            timeout: 600,
        );
        $dump->run();

        if (! $dump->isSuccessful()) {
            $this->error('mysqldump failed: '.trim($dump->getErrorOutput()));

            return self::FAILURE;
        }

        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir);
        $path = $dir.'/'.config('clinic.organization', 'clinic').'-'.now()->format('Ymd-His').'.sql.gz.enc';
        $compressed = gzencode($dump->getOutput(), 9);
        if ($compressed === false) {
            $this->error('Could not compress the dump.');

            return self::FAILURE;
        }
        File::put($path, Crypt::encryptString($compressed));

        $old = collect(File::glob($dir.'/*.sql.gz.enc'))->sort()->reverse()->slice((int) $this->option('keep'));
        $old->each(fn (string $f) => File::delete($f));

        $this->info(sprintf('Backup written: %s (%s KB), %d old backup(s) removed.', basename($path), number_format(filesize($path) / 1024), $old->count()));

        return self::SUCCESS;
    }
}
