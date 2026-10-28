<?php

use App\Support\SafeFk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat upgrades: threads, reactions, @/# tags and richer attachments (voice, video, pdf, thumbnails).
     * Additive only: every existing message and file keeps working exactly as before. Safe to run again.
     */
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('order_messages', 'thread_root_id')) {
                $table->unsignedBigInteger('thread_root_id')->nullable()->after('user_id')->index();   // set on replies inside a thread
            }
            if (! Schema::hasColumn('order_messages', 'edited_at')) {
                $table->dateTime('edited_at')->nullable()->after('body');
            }
        });
        SafeFk::add('order_messages', 'thread_root_id', 'order_messages');

        Schema::table('order_files', function (Blueprint $table) {
            $add = fn (string $col) => ! Schema::hasColumn('order_files', $col);
            if ($add('kind')) {
                $table->string('kind', 12)->default('file')->after('mime');          // file | image | voice | video | pdf
            }
            if ($add('duration')) {
                $table->unsignedInteger('duration')->nullable()->after('kind');      // seconds, voice and video
            }
            if ($add('width')) {
                $table->unsignedInteger('width')->nullable()->after('duration');
            }
            if ($add('height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
            if ($add('thumb_path')) {
                $table->string('thumb_path')->nullable()->after('path');             // small preview image (pictures, PDF first page, video poster)
            }
            if ($add('meta')) {
                $table->text('meta')->nullable()->after('size');                     // JSON: voice waveform peaks, pdf page count...
            }
            if ($add('annotated_of')) {
                $table->unsignedBigInteger('annotated_of')->nullable()->after('meta');   // this is an annotated copy of that file
            }
        });

        if (! Schema::hasTable('order_message_reactions')) {
            Schema::create('order_message_reactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('emoji', 16);
                $table->timestamp('created_at')->nullable();
                $table->unique(['message_id', 'user_id', 'emoji']);
                SafeFk::inline($table, 'message_id', 'order_messages');
                SafeFk::inline($table, 'user_id', 'users');
            });
            SafeFk::add('order_message_reactions', 'message_id', 'order_messages');
            SafeFk::add('order_message_reactions', 'user_id', 'users');
        }

        if (! Schema::hasTable('order_message_mentions')) {
            Schema::create('order_message_mentions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->string('type', 8);                                           // user | task | project
                $table->unsignedBigInteger('target_id');
                $table->index(['type', 'target_id']);
                $table->index('message_id');
                SafeFk::inline($table, 'message_id', 'order_messages');
            });
            SafeFk::add('order_message_mentions', 'message_id', 'order_messages');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_message_mentions');
        Schema::dropIfExists('order_message_reactions');
        Schema::table('order_files', fn (Blueprint $t) => $t->dropColumn(['kind', 'duration', 'width', 'height', 'thumb_path', 'meta', 'annotated_of']));
        Schema::table('order_messages', function (Blueprint $t) {
            try {
                $t->dropForeign('order_messages_thread_root_id_foreign');
            } catch (\Throwable) {
                // the key may never have been added
            }
        });
        Schema::table('order_messages', fn (Blueprint $t) => $t->dropColumn(['thread_root_id', 'edited_at']));
    }
};
