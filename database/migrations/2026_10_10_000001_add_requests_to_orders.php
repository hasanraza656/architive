<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order requests (leads). A request is an order in the "request" stage: the customer's brief + a chat.
     * When the admin sends a custom offer, the same row becomes a payable order, so chat/files/history are never lost.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('admin')->after('status');          // admin | website | portal
            $table->string('service', 30)->nullable()->after('title');
            $table->string('audience', 30)->nullable()->after('service');
            $table->string('company', 160)->nullable()->after('audience');
            $table->text('brief')->nullable()->after('company');                      // what the customer asked for
            $table->date('requested_deadline')->nullable()->after('brief');
        });

        Schema::table('order_messages', function (Blueprint $table) {
            $table->string('kind', 20)->default('text')->after('user_id');            // text | offer
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('profile_completed_at')->nullable()->after('email_verified_at');
        });

        DB::table('users')->where('role', 'customer')->update(['profile_completed_at' => now()]);   // existing customers are already complete
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('profile_completed_at'));
        Schema::table('order_messages', fn (Blueprint $t) => $t->dropColumn('kind'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['source', 'service', 'audience', 'company', 'brief', 'requested_deadline']));
    }
};
