<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PropertyPurpose;

class PropertyPurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = ['rent', 'sale', 'short-let'];

        foreach ($purposes as $purpose) {
            PropertyPurpose::create(['purpose' => $purpose]);
        }
    }
}
