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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable');

            // e.g., 'base_price', 'extra_bed_price'
            $table->string('price_type')->default('base_price');

            // 'syrian', 'expat', 'foreigner'
            $table->string('nationality_category');

            // 'SYP', 'USD', 'EUR'
            $table->string('currency');

            $table->decimal('amount', 15, 2);

            // Nullable for default non-seasonal prices
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
