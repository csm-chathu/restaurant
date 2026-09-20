<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('booking_number', 30)->unique();

            // Guest details (in case no customer record)
            $table->string('guest_name', 150);
            $table->string('guest_phone', 30)->nullable();
            $table->string('guest_nic', 50)->nullable();
            $table->unsignedTinyInteger('guest_count')->default(1);

            // Stay
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedSmallInteger('nights')->default(1);
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();

            // Billing
            $table->decimal('rate_per_night', 10, 2);
            $table->decimal('room_total', 10, 2)->default(0);
            $table->decimal('charges_total', 10, 2)->default(0); // food + extras
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'other'])->default('cash');
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');

            $table->enum('status', ['reserved', 'checked_in', 'checked_out', 'cancelled'])->default('reserved');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
