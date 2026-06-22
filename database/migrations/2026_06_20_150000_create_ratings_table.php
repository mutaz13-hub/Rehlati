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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('rateable');
            $table->unsignedTinyInteger('rate'); // 1-5 stars
            $table->text('body')->nullable();
            $table->string('type')->default('text'); // text, audio
            $table->unsignedInteger('up_votes')->default(0);
            $table->unsignedInteger('down_votes')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'rateable_type', 'rateable_id'], 'user_rateable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
