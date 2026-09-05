<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('branches', 'show_kot')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('show_kot')->default(true)->after('shop_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'show_kot')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('show_kot');
            });
        }
    }
};