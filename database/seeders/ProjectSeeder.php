<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Proiecte de pornire pentru agenda, ca utilizatorul sa nu porneasca de la zero.
     */
    public function run(): void
    {
        $starter = [
            [
                'name' => 'Lansare anunturi',
                'description' => 'Anunturi pe platforme (OLX, Facebook Marketplace, Google Business) si reinoirea lor periodica.',
            ],
            [
                'name' => 'Catalog materiale',
                'description' => 'Poze, preturi si specificatii pentru materialele si accesoriile folosite la lucrari.',
            ],
            [
                'name' => 'Blog si SEO',
                'description' => 'Articole si optimizare pentru intrebarile pe care le au clientii.',
            ],
            [
                'name' => 'Cutii de scule',
                'description' => 'Inventar, verificare si reaprovizionare periodica a echipamentului din masina.',
            ],
        ];

        foreach ($starter as $row) {
            Project::firstOrCreate(
                ['name' => $row['name']],
                ['type' => 'propriu', 'status' => 'activ', 'description' => $row['description']],
            );
        }
    }
}
