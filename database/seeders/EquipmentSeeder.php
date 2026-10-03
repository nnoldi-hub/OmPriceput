<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Consumabile
            ['name' => 'Vopsea lavabila alba 15L', 'category' => 'consumabil', 'sku' => 'VOP-ALBA-15', 'unit' => 'buc', 'unit_price' => 189, 'cost_price' => 128, 'stock_quantity' => 24, 'minimum_stock' => 8],
            ['name' => 'Vopsea colorata 10L', 'category' => 'consumabil', 'sku' => 'VOP-COLOR-10', 'unit' => 'buc', 'unit_price' => 245, 'cost_price' => 168, 'stock_quantity' => 12, 'minimum_stock' => 5],
            ['name' => 'Folie de izolatie electrica', 'category' => 'consumabil', 'sku' => 'CON-IZOL', 'unit' => 'set', 'unit_price' => 18, 'cost_price' => 9, 'stock_quantity' => 60, 'minimum_stock' => 20],
            ['name' => 'Tub PVC pentru canalizare 32mm', 'category' => 'consumabil', 'sku' => 'TUB-PVC-32', 'unit' => 'metru', 'unit_price' => 7.5, 'cost_price' => 4, 'stock_quantity' => 400, 'minimum_stock' => 100],
            ['name' => 'Fitinguri PVC mixte', 'category' => 'consumabil', 'sku' => 'FIT-PVC-MIX', 'unit' => 'set', 'unit_price' => 32, 'cost_price' => 16, 'stock_quantity' => 45, 'minimum_stock' => 15],
            ['name' => 'Banda izolatoare', 'category' => 'consumabil', 'sku' => 'CON-BANDA', 'unit' => 'buc', 'unit_price' => 4, 'cost_price' => 1.8, 'stock_quantity' => 200, 'minimum_stock' => 50],
            ['name' => 'Silicon etans sanit 300ml', 'category' => 'consumabil', 'sku' => 'CON-SILICON', 'unit' => 'buc', 'unit_price' => 22, 'cost_price' => 11, 'stock_quantity' => 80, 'minimum_stock' => 25],
            ['name' => 'Gips de nivelare', 'category' => 'consumabil', 'sku' => 'CON-GIPS', 'unit' => 'kg', 'unit_price' => 14, 'cost_price' => 7, 'stock_quantity' => 150, 'minimum_stock' => 40],

            // Scule
            ['name' => 'Set surubelnite profesional', 'category' => 'scula', 'sku' => 'SCL-SURUB-SET', 'unit' => 'set', 'unit_price' => 289, 'cost_price' => 190, 'stock_quantity' => 6, 'minimum_stock' => 3],
            ['name' => 'Scara telescopica 3.2m', 'category' => 'scula', 'sku' => 'SCL-SCARA-32', 'unit' => 'buc', 'unit_price' => 420, 'cost_price' => 285, 'stock_quantity' => 4, 'minimum_stock' => 2],
            ['name' => 'Sclipire profesionala', 'category' => 'scula', 'sku' => 'SCL-SCLIP', 'unit' => 'buc', 'unit_price' => 45, 'cost_price' => 24, 'stock_quantity' => 25, 'minimum_stock' => 8],
            ['name' => 'Bormasina cu percutie', 'category' => 'scula', 'sku' => 'SCL-BORMA', 'unit' => 'buc', 'unit_price' => 540, 'cost_price' => 380, 'stock_quantity' => 3, 'minimum_stock' => 1],
            ['name' => 'Masina de taiat rigla', 'category' => 'scula', 'sku' => 'SCL-TIETOR', 'unit' => 'buc', 'unit_price' => 380, 'cost_price' => 250, 'stock_quantity' => 2, 'minimum_stock' => 1],

            // Piese de schimb
            ['name' => 'Robinet dublu closet', 'category' => 'piesa', 'sku' => 'PIE-ROBINET-D', 'unit' => 'buc', 'unit_price' => 96, 'cost_price' => 58, 'stock_quantity' => 18, 'minimum_stock' => 6],
            ['name' => 'Sifon chiuveta cromat', 'category' => 'piesa', 'sku' => 'PIE-SIFON', 'unit' => 'buc', 'unit_price' => 78, 'cost_price' => 44, 'stock_quantity' => 14, 'minimum_stock' => 5],
            ['name' => 'Supraportal WC', 'category' => 'piesa', 'sku' => 'PIE-PORTAL', 'unit' => 'buc', 'unit_price' => 64, 'cost_price' => 36, 'stock_quantity' => 16, 'minimum_stock' => 6],
            ['name' => 'Balama usi interioara', 'category' => 'piesa', 'sku' => 'PIE-BALAMA', 'unit' => 'buc', 'unit_price' => 38, 'cost_price' => 19, 'stock_quantity' => 30, 'minimum_stock' => 10],
            ['name' => 'Intrerupator dublu', 'category' => 'piesa', 'sku' => 'PIE-INTRERUP', 'unit' => 'buc', 'unit_price' => 27, 'cost_price' => 13, 'stock_quantity' => 70, 'minimum_stock' => 20],
            ['name' => 'Priza dubla cu imp vizibil', 'category' => 'piesa', 'sku' => 'PIE-PRIZA-D', 'unit' => 'buc', 'unit_price' => 21, 'cost_price' => 9.5, 'stock_quantity' => 90, 'minimum_stock' => 30],
            ['name' => 'Bec LED E27 12W', 'category' => 'piesa', 'sku' => 'PIE-LED-E27', 'unit' => 'buc', 'unit_price' => 15, 'cost_price' => 6, 'stock_quantity' => 120, 'minimum_stock' => 40],

            // Instalatii si aparate
            ['name' => 'Cazan termosferic 24L', 'category' => 'instalatii', 'sku' => 'INS-CAZAN-24', 'unit' => 'buc', 'unit_price' => 1850, 'cost_price' => 1380, 'stock_quantity' => 5, 'minimum_stock' => 2],
            ['name' => 'Chiuveta bucatarie inox', 'category' => 'instalatii', 'sku' => 'INS-CHIUV-50', 'unit' => 'buc', 'unit_price' => 460, 'cost_price' => 320, 'stock_quantity' => 8, 'minimum_stock' => 3],
            ['name' => 'Vas WC cu capac', 'category' => 'instalatii', 'sku' => 'INS-WC-SET', 'unit' => 'buc', 'unit_price' => 720, 'cost_price' => 520, 'stock_quantity' => 6, 'minimum_stock' => 3],
            ['name' => 'Usa interiora cu toaca', 'category' => 'instalatii', 'sku' => 'INS-USA-90', 'unit' => 'buc', 'unit_price' => 620, 'cost_price' => 430, 'stock_quantity' => 10, 'minimum_stock' => 4],
        ];

        foreach ($items as $item) {
            Equipment::updateOrCreate(['sku' => $item['sku']], $item + [
                'is_active' => true,
                'markup_percent' => 0,
            ]);
        }
    }
}