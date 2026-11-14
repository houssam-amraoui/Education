<?php

namespace Database\Seeders;

use Database\Seeders\Demo\CatalogueSeeder;
use Database\Seeders\Demo\LibrarySeeder;
use Database\Seeders\Demo\OperationsSeeder;
use Illuminate\Database\Seeder;

class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->call([CatalogueSeeder::class, LibrarySeeder::class, OperationsSeeder::class]);
        }
    }
}
