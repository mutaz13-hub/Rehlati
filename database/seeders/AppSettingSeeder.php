<?php

namespace Database\Seeders;

use App\Services\AppSettingService;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        app(AppSettingService::class)->set('active_currency', 'USD');
    }
}
