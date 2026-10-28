<?php

use App\Support\SafeFk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Drive as long-term storage for order files. Recent files stay on the server; a background job copies them to Drive
 * and later removes only the local copy. Additive: existing files keep working exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('drive_connections')) {
        Schema::create('drive_connections', function (Blueprint $table) {
            $table->id();
            $table->string('account_email', 190);
            $table->string('account_name', 190)->nullable();
            $table->text('refresh_token');                           // encrypted by the model
            $table->string('root_folder_id', 120)->nullable();       // the "Architive" folder the portal created
            $table->text('folder_map')->nullable();                  // json: order id => Drive folder id
            $table->boolean('is_active')->default(false);            // new files go to the active connection
            $table->string('status', 20)->default('connected');      // connected | needs_reconnect
            $table->boolean('moving_files')->default(false);         // "move my files to the active account" is running
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('quota_used')->nullable();
            $table->unsignedBigInteger('quota_total')->nullable();
            $table->dateTime('quota_checked_at')->nullable();
            $table->unsignedBigInteger('connected_by')->nullable()->index();
            $table->timestamps();
            $table->unique('account_email');
            SafeFk::inline($table, 'connected_by', 'users', 'set null');
        });
        SafeFk::add('drive_connections', 'connected_by', 'users', 'set null');
        }

        if (! Schema::hasColumn('order_files', 'drive_connection_id')) {
        Schema::table('order_files', function (Blueprint $table) {
            $table->unsignedBigInteger('drive_connection_id')->nullable()->after('annotated_of');
            $table->string('drive_file_id', 120)->nullable()->after('drive_connection_id');
            $table->dateTime('drive_synced_at')->nullable()->after('drive_file_id');
            $table->unsignedTinyInteger('drive_attempts')->default(0)->after('drive_synced_at');
            $table->dateTime('drive_next_attempt_at')->nullable()->after('drive_attempts');
            $table->text('drive_error')->nullable()->after('drive_next_attempt_at');
            $table->dateTime('local_pruned_at')->nullable()->after('drive_error');     // the local copy was removed (Drive has it)
            $table->dateTime('last_opened_at')->nullable()->after('local_pruned_at');
            $table->index(['drive_synced_at', 'drive_connection_id'], 'order_files_drive_idx');
        });
        }

        if (! Schema::hasTable('drive_deletions')) {
        Schema::create('drive_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drive_connection_id');
            $table->string('drive_file_id', 120);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_deletions');
        Schema::table('order_files', function (Blueprint $table) {
            $table->dropIndex('order_files_drive_idx');
            $table->dropColumn(['drive_connection_id', 'drive_file_id', 'drive_synced_at', 'drive_attempts', 'drive_next_attempt_at', 'drive_error', 'local_pruned_at', 'last_opened_at']);
        });
        Schema::dropIfExists('drive_connections');
    }
};
