<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Foreign keys for the newer migrations. Hosting databases are sometimes set up slightly differently from a fresh Laravel
 * install (older table engine, column types), and MySQL then refuses a foreign key with "errno 150". Because MySQL cannot undo
 * half-finished table changes, one refused key must not stop the whole update: the column and its index are always created,
 * the key is added afterwards and, if the server refuses it, only a warning is logged (the app removes related rows itself).
 */
final class SafeFk
{
    private static function sqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    /** Inside Schema::create. Only SQLite (the tests) takes the key right here; MySQL gets it afterwards through add(). */
    public static function inline(Blueprint $table, string $column, string $ref, string $onDelete = 'cascade'): void
    {
        if (self::sqlite()) {
            $table->foreign($column)->references('id')->on($ref)->onDelete($onDelete);
        }
    }

    public static function add(string $table, string $column, string $ref, string $onDelete = 'cascade'): void
    {
        if (self::sqlite()) {
            return;
        }
        try {
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column, "{$table}_{$column}_foreign")->references('id')->on($ref)->onDelete($onDelete));
        } catch (\Throwable $e) {
            Log::warning("Foreign key {$table}.{$column} -> {$ref} was not added: " . $e->getMessage());
        }
    }
}
