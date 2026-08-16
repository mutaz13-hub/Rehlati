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
        $driver = Schema::getConnection()->getDriverName();

        Schema::create('trip_notes', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();

            if (in_array($driver, ['mysql', 'pgsql'], true)) {
                $table->geometry('coordinates', subtype: 'point', srid: 4326)->notNull();
                $table->spatialIndex('coordinates');
            } else {
                // Fallback for SQLite (tests): plain decimal coordinates.
                $table->decimal('latitude', 10, 8)->notNull();
                $table->decimal('longitude', 11, 8)->notNull();
            }

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_notes');
    }
};
