<?php

use App\Support\SafeFk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Basic project management: projects, members, tasks, assignees and task comments. New tables only; safe to run again. */
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->text('description')->nullable();
                $table->string('status', 20)->default('active')->index();       // active | on_hold | completed | archived
                $table->string('color', 9)->default('#FFD60A');
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->boolean('visible_to_customer')->default(false);          // the customer may open tagged tasks/projects read-only
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                SafeFk::inline($table, 'customer_id', 'users', 'set null');
                SafeFk::inline($table, 'order_id', 'orders', 'set null');
                SafeFk::inline($table, 'created_by', 'users', 'set null');
            });
            SafeFk::add('projects', 'customer_id', 'users', 'set null');
            SafeFk::add('projects', 'order_id', 'orders', 'set null');
            SafeFk::add('projects', 'created_by', 'users', 'set null');
        }

        if (! Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('role', 10)->default('member');                   // lead | member
                $table->timestamps();
                $table->primary(['project_id', 'user_id']);
                SafeFk::inline($table, 'project_id', 'projects');
                SafeFk::inline($table, 'user_id', 'users');
            });
            SafeFk::add('project_members', 'project_id', 'projects');
            SafeFk::add('project_members', 'user_id', 'users');
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->string('status', 20)->default('todo')->index();          // backlog | todo | in_progress | review | done
                $table->string('priority', 10)->default('normal');               // low | normal | high | urgent
                $table->date('due_date')->nullable()->index();
                $table->unsignedInteger('position')->default(0);                 // order inside a board column
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();
                SafeFk::inline($table, 'project_id', 'projects');
                SafeFk::inline($table, 'created_by', 'users', 'set null');
            });
            SafeFk::add('tasks', 'project_id', 'projects');
            SafeFk::add('tasks', 'created_by', 'users', 'set null');
        }

        if (! Schema::hasTable('task_assignees')) {
            Schema::create('task_assignees', function (Blueprint $table) {
                $table->unsignedBigInteger('task_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->primary(['task_id', 'user_id']);
                SafeFk::inline($table, 'task_id', 'tasks');
                SafeFk::inline($table, 'user_id', 'users');
            });
            SafeFk::add('task_assignees', 'task_id', 'tasks');
            SafeFk::add('task_assignees', 'user_id', 'users');
        }

        if (! Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('task_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('kind', 10)->default('comment');                  // comment | event (system line such as "moved to Done")
                $table->text('body');
                $table->timestamps();
                SafeFk::inline($table, 'task_id', 'tasks');
                SafeFk::inline($table, 'user_id', 'users', 'set null');
            });
            SafeFk::add('task_comments', 'task_id', 'tasks');
            SafeFk::add('task_comments', 'user_id', 'users', 'set null');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};
