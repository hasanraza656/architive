<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Additive: the campaign (utm tags, ad id, click references) that brought a website request. Nullable, nothing else changes. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'attribution')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('attribution')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'attribution')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('attribution');
            });
        }
    }
};
