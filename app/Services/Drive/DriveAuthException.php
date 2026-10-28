<?php

namespace App\Services\Drive;

/** Google no longer accepts the saved connection: the account has to be connected again. */
class DriveAuthException extends DriveException
{
}
