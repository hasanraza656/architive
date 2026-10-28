<?php

use App\Support\SafeFk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Access keys that let an admin connect an AI assistant (MCP) to the portal. Only a hash of the key is stored. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mcp_tokens')) {
            return;
        }
        Schema::create('mcp_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 80);
            $table->string('token_hash', 64)->unique();
            $table->string('hint', 12);                       // last characters, so the person can tell keys apart
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            SafeFk::inline($table, 'user_id', 'users');
        });
        SafeFk::add('mcp_tokens', 'user_id', 'users');
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_tokens');
    }
};
