<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_bookings', function (Blueprint $table) {
            $table->decimal('service_charge', 10, 2)->default(0)->after('charges_total');
            $table->unsignedTinyInteger('service_charge_pct')->default(10)->after('service_charge');
        });
    }

    public function down(): void
    {
        Schema::table('room_bookings', function (Blueprint $table) {
            $table->dropColumn(['service_charge', 'service_charge_pct']);
        });
    }
};
