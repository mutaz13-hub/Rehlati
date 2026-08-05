<?php

use App\Enums\RoomClass;
use App\Enums\RoomLayout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_class')->default(RoomClass::STANDARD->value)->after('name_ar');
            $table->string('room_layout')->default(RoomLayout::DOUBLE->value)->after('room_class');
            $table->unsignedInteger('max_adults')->default(2)->after('room_layout');
            $table->unsignedInteger('max_children')->default(0)->after('max_adults');
            $table->unsignedInteger('max_guests')->default(2)->after('max_children');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['room_class', 'room_layout', 'max_adults', 'max_children', 'max_guests']);
        });
    }
};
