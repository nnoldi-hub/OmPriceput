<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Manopera electrician', 'category' => 'electric', 'unit' => 'ora', 'cost_price' => 70, 'sale_price' => 110, 'description' => 'Instalatii, prize, iluminat, tablouri electrice'],
            ['name' => 'Manopera instalator sanitar', 'category' => 'sanitar', 'unit' => 'ora', 'cost_price' => 70, 'sale_price' => 110, 'description' => 'Tevi, robineti, WC, cazan, scurgeri'],
            ['name' => 'Manopera zugrav', 'category' => 'vopsire', 'unit' => 'ora', 'cost_price' => 55, 'sale_price' => 85, 'description' => 'Pregatire suprafete, vopsire, tencuieli'],
            ['name' => 'Manopera montaj mobilier', 'category' => 'montaj', 'unit' => 'ora', 'cost_price' => 55, 'sale_price' => 90, 'description' => 'Corpuri, rafturi, TV, jaluzele'],
            ['name' => 'Manopera intretinere generala', 'category' => 'general', 'unit' => 'ora', 'cost_price' => 50, 'sale_price' => 80, 'description' => 'Reparatii mici, demontare, montare, transport'],

            ['name' => 'Constatare la fata locului', 'category' => 'general', 'unit' => 'vizita', 'cost_price' => 0, 'sale_price' => 0, 'description' => 'Gratuita daca acceptati devizul rezultat'],
            ['name' => 'Deplasare / transport', 'category' => 'general', 'unit' => 'vizita', 'cost_price' => 20, 'sale_price' => 40, 'description' => 'Deplasarea mesterului la adresa indicata'],

            ['name' => 'Vopsire per metru patrat', 'category' => 'vopsire', 'unit' => 'm2', 'cost_price' => 22, 'sale_price' => 38, 'description' => 'Manopera vopsire, fara vopsea'],
            ['name' => 'Inlocuire bec sau priza', 'category' => 'electric', 'unit' => 'buc', 'cost_price' => 10, 'sale_price' => 25, 'description' => 'Demontare vechi, montare piesa noua'],
            ['name' => 'Deblocare usa / sertar', 'category' => 'general', 'unit' => 'vizita', 'cost_price' => 60, 'sale_price' => 120, 'description' => 'Interventie fara spargere, in functie de tipul blocarii'],
            ['name' => 'Reglaj usi si ferestre', 'category' => 'montaj', 'unit' => 'buc', 'cost_price' => 30, 'sale_price' => 60, 'description' => 'Ajustare balamale, buloane, nivelare'],
            ['name' => 'Sigilare cada', 'category' => 'sanitar', 'unit' => 'buc', 'cost_price' => 60, 'sale_price' => 110, 'description' => 'Curatare si sigilare silicon'],
        ];

        foreach ($items as $item) {
            Service::updateOrCreate(['name' => $item['name']], $item + ['is_active' => true]);
        }
    }
}