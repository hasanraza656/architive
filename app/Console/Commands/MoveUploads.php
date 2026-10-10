<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * One-off helper: older versions kept order files in storage/app/orders. Uploads now live in public/uploads
 * (no storage:link needed), so this copies the old files across. Safe to run more than once.
 */
class MoveUploads extends Command
{
    protected $signature = 'uploads:move-from-storage';

    protected $description = 'Move order files from storage/app/orders to public/uploads/orders';

    public function handle(): int
    {
        $from = storage_path('app/orders');
        $to = public_path('uploads/orders');

        if (! is_dir($from)) {
            $this->info('Nothing to move: storage/app/orders does not exist.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists($to);
        $moved = 0;
        foreach (File::allFiles($from) as $file) {
            $target = $to . DIRECTORY_SEPARATOR . $file->getRelativePathname();
            File::ensureDirectoryExists(dirname($target));
            if (! is_file($target)) {
                File::copy($file->getPathname(), $target);
                $moved++;
            }
        }
        File::deleteDirectory($from);

        $this->info("Moved $moved file(s) to public/uploads/orders.");

        return self::SUCCESS;
    }
}
