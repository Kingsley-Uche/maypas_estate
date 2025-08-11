<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PropertySubType;

class PropertySubTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subTypes = [
            // ['conference room', 1],
            // ['desk', 1],
            // ['meeting room', 1],
            // ['private office', 1],
            // ['work station', 1],
            ['church', 2],
            ['event center', 2],
            ['factory', 2],
            ['Filling station', 2],
            ['hotel', 2],
            ['Office space', 2],
            ['school', 2],
            ['shop', 2],
            ['Show room', 2],
            ['warehouse', 2],
            ['Boys quarters', 3],
            ['Mini-flat', 3],
            ['penthouse', 3],
            ['Self contain', 3],
            ['Shared apartment', 3],
            ['Studio apartment', 3],
            ['Blocks of flats', 4],
            ['Detached bungalow', 4],
            ['Detached duplex', 4],
            ['massionette', 4],
            ['Semi-detached bungalow', 4],
            ['Semi-detached duplex', 4],
            ['Terraced Bungalow', 4],
            ['Terraced duplex', 4],
            ['Commercial land', 5],
            ['Industrial land', 5],
            ['Joint venture land', 5],
            ['mixed -use land', 5],
            ['Residential land', 5],
            ['Serviced residential land', 5],
        ];

        foreach ($subTypes as [$name, $typeId]) {
            PropertySubType::create([
                'property_sub_type' => $name,
                'property_type_id' => $typeId,
            ]);
        }
    }
}
