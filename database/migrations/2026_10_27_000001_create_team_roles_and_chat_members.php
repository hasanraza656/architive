<?php

use App\Support\SafeFk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Team users + dynamic roles, and group-chat membership. Purely additive: no existing column or row is changed.
     * Existing admins keep users.role = 'admin' (super admin, always everything); customers are untouched.
     * Every step checks what already exists, so the migration can safely be run again after an interrupted attempt.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('slug', 100)->unique();
                $table->string('description', 255)->nullable();
                $table->string('color', 9)->default('#FFD60A');
                $table->text('permissions')->nullable();          // JSON list of "module.action" keys
                $table->timestamps();
            });
        }

        $needsKey = ! Schema::hasColumn('users', 'role_id');
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('role')->index();     // only used when role = 'staff'
            }
            if (! Schema::hasColumn('users', 'invited_at')) {
                $table->dateTime('invited_at')->nullable()->after('last_seen_at');
            }
        });
        SafeFk::add('users', 'role_id', 'roles', 'set null');

        // who is in which order conversation besides the customer (team members added to the chat)
        if (! Schema::hasTable('order_chat_members')) {
            Schema::create('order_chat_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('added_by')->nullable()->index();
                $table->timestamps();
                $table->unique(['order_id', 'user_id']);
                SafeFk::inline($table, 'order_id', 'orders');
                SafeFk::inline($table, 'user_id', 'users');
                SafeFk::inline($table, 'added_by', 'users', 'set null');
            });
            SafeFk::add('order_chat_members', 'order_id', 'orders');
            SafeFk::add('order_chat_members', 'user_id', 'users');
            SafeFk::add('order_chat_members', 'added_by', 'users', 'set null');
        }

        // starter roles so the team page is useful on day one (they can be edited or deleted)
        if (DB::table('roles')->count() === 0) {
            foreach (config('permissions.presets', []) as $slug => $preset) {
                DB::table('roles')->insert([
                    'name' => $preset['name'], 'slug' => $slug, 'description' => $preset['description'], 'color' => $preset['color'],
                    'permissions' => json_encode($preset['permissions']), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_chat_members');
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign('users_role_id_foreign');
            } catch (\Throwable) {
                // the key may never have been added
            }
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_id', 'invited_at']);
        });
        Schema::dropIfExists('roles');
    }
};
