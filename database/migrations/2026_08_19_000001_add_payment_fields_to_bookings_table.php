<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('currency');
            $table->string('payment_intent_id')->nullable()->unique()->after('payment_method');
            $table->string('payment_status')->nullable()->default('pending')->after('payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_intent_id', 'payment_status']);
        });
    }
};
