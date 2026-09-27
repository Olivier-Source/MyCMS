<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Starter content of the site. Administrator accounts are created only
     * with "php artisan mycms:admin" (or by the installer).
     */
    public function run(): void
    {
        $this->call(StarterContentSeeder::class);
    }
}
