<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_booking_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('product_id')->nullable(); // null = manual charge
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('description', 255);
            $table->enum('charge_type', ['food', 'beverage', 'laundry', 'room_service', 'other'])->default('food');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('amount', 10, 2)->default(0); // unit_price * quantity
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('room_bookings')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_booking_charges');
    }
};
