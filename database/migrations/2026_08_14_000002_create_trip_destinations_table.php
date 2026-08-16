<?php

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
        Schema::create('trip_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_city_id')->constrained('trip_cities')->cascadeOnDelete();
            $table->morphs('destinable');
            $table->unsignedInteger('order')->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();

            $table->unique(['trip_city_id', 'destinable_type', 'destinable_id'], 'trip_destinations_city_destinable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_destinations');
    }
};
