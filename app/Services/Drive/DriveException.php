<?php

namespace App\Services\Drive;

use RuntimeException;

/** Something went wrong talking to Google Drive. The message is safe to show to an admin. */
class DriveException extends RuntimeException
{
}
