<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('room_number', 20);
            $table->string('name', 100)->nullable();
            $table->enum('type', ['ac', 'non_ac'])->default('non_ac');
            $table->enum('category', ['single', 'double', 'triple', 'suite', 'dormitory'])->default('single');
            $table->string('floor', 20)->nullable();
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->decimal('rate_per_night', 10, 2)->default(0);
            $table->enum('status', ['available', 'occupied', 'cleaning', 'maintenance'])->default('available');
            $table->text('amenities')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['branch_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
