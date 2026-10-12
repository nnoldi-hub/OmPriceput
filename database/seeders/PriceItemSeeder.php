<?php

namespace Database\Seeders;

use App\Models\PriceItem;
use Illuminate\Database\Seeder;

class PriceItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['camera', 'Manopera montaj pe suprafata', 'mp', 25],
            ['priza', 'Priza (montaj + legare)', 'buc', 25],
            ['intrerupator', 'Intrerupator (montaj + legare)', 'buc', 22],
            ['doza', 'Doza (montaj)', 'buc', 15],
            ['traseu', 'Traseu cablu', 'ml', 10],
            ['tablou', 'Tablou electric (montaj + conexiuni)', 'buc', 250],
            ['suport', 'Suport / prindere in perete', 'buc', 30],
        ];

        foreach ($items as $position => [$type, $name, $unit, $price]) {
            PriceItem::updateOrCreate(
                ['object_type' => $type],
                ['name' => $name, 'unit' => $unit, 'price' => $price, 'is_active' => true, 'position' => $position],
            );
        }
    }
}
