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
        Schema::create('vote_totals', function (Blueprint $table) {
            $table->id();
            $table->morphs('voteable');
            $table->string('vote_type');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['voteable_type', 'voteable_id', 'vote_type'], 'voteable_vote_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vote_totals');
    }
};
