<?php

namespace App\Console\Commands;

use App\Services\Drive\DriveSync;
use Illuminate\Console\Command;

/** Background job for the Google Drive storage: copy waiting files to Drive, and (with --prune) remove old local copies. */
class DriveSyncCommand extends Command
{
    protected $signature = 'drive:sync {--prune : Also remove local copies that have been safe in Drive for a while}';

    protected $description = 'Copy order files to the connected Google Drive';

    public function handle(DriveSync $sync): int
    {
        $copied = $sync->syncPending();
        $this->info("Copied to Drive: {$copied}");
        if ($this->option('prune')) {
            $this->info('Local copies removed: ' . $sync->pruneLocal());
        }

        return self::SUCCESS;
    }
}
