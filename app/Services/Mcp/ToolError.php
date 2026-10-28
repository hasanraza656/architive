<?php

namespace App\Services\Mcp;

use RuntimeException;

/** A problem the assistant can understand and fix (wrong id, not allowed, bad value). Shown to it as the tool result. */
class ToolError extends RuntimeException
{
}
