<?php

use App\Enums\RoomClass;
use App\Enums\RoomLayout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('room_class')->default(RoomClass::STANDARD->value);
            $table->string('room_layout')->default(RoomLayout::DOUBLE->value);
            $table->unsignedInteger('max_adults')->default(2);
            $table->unsignedInteger('max_children')->default(0);
            $table->unsignedInteger('max_guests')->default(2);
            $table->unsignedInteger('total_rooms');
            $table->unsignedInteger('available_rooms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
