<?php

use App\Support\SafeFk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To-do checklists inside tasks and projects (Notion style). One table for both: owner_type is 'task' or 'project'.
 * Additive: nothing existing is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checklist_items')) {
            return;
        }

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 10);                       // task | project
            $table->unsignedBigInteger('owner_id');
            $table->string('body', 500);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_done')->default(false);
            $table->unsignedBigInteger('done_by')->nullable()->index();
            $table->dateTime('done_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['owner_type', 'owner_id', 'position']);
            SafeFk::inline($table, 'done_by', 'users', 'set null');
            SafeFk::inline($table, 'created_by', 'users', 'set null');
        });
        SafeFk::add('checklist_items', 'done_by', 'users', 'set null');
        SafeFk::add('checklist_items', 'created_by', 'users', 'set null');
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
