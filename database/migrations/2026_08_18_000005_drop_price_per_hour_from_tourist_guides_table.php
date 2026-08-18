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
        Schema::table('tourist_guides', function (Blueprint $table) {
            $table->dropColumn('price_per_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tourist_guides', function (Blueprint $table) {
            $table->decimal('price_per_hour', 15, 2)->nullable();
        });
    }
};
