<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Toolbox;
use Illuminate\Database\Seeder;

class ToolboxSeeder extends Seeder
{
    public function run(): void
    {
        $boxes = [
            'master' => ['Master', true, 0, "Ruletă 5 m, poloboc mic, creion tâmplărie\nDibluri și șuruburi (6 și 8 mm), bandă izolatoare\nSilicon universal + pistol\nCiocan mediu, cutter, lanternă frontală\nMănuși, șervețele umede industriale"],
            'electric' => ['Electric', false, 1, "Șurubelnițe izolate 1000V (drept, PH, PZ)\nClește patent, cu vârf, tăietor\nClește automat de dezizolat\nMultimetru / creion de tensiune\nPresă ferule\nConectori Wago 2/3/5 pini, reglete, coliere\nBenzi izolatoare colorate"],
            'montaj' => ['Montaj', false, 2, "Mașină de înșurubat cu impact\nȘurubelniță electrică mică articulată\nSet biți (PH, PZ, imbus, Torx) + prelungitor magnetic\nChei imbus, 2 menghine rapide\nCiocan de cauciuc, magneți / tăviță magnetică"],
            'sanitar' => ['Sanitar', false, 3, "Clește mops de calitate, cheie franceză\nCheie specială pentru baterii\nFoarfece țevi PPR/PEX / cutter cupru\nȘarpe manual 3-5 m, perie de sârmă\nBandă teflon, șnur etanșare (Loctite 55)\nGarnituri 1/2, 3/4, 3/8\nRacorduri flexibile de rezervă"],
            'zugraveli' => ['Zugrăveli', false, 4, "Șpacluri inox 5/10/25 cm, gletieră\nTrafalet mic + pensulă colțuri\nGrip de șlefuit, șmirghel 120/180/240\nRăzuitor\nBandă de mascare, folie de protecție\nGlet de reparații rapide"],
        ];

        $model = [];
        foreach ($boxes as $slug => [$name, $always, $order, $contents]) {
            $model[$slug] = Toolbox::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'always_carry' => $always, 'sort_order' => $order, 'contents' => $contents]
            );
        }

        $byCategory = [
            'electric' => ['electric'],
            'sanitar' => ['sanitar'],
            'vopsire' => ['zugraveli'],
            'montaj' => ['montaj'],
            'general' => [],
        ];

        // excepții pe nume (fragment => cutii)
        $byName = [
            'corp de iluminat' => ['montaj'],
            'lustră' => ['montaj'],
            'bandă LED' => ['montaj'],
            'silicon' => ['zugraveli', 'sanitar'],
            'rigips' => ['zugraveli'],
            'balamale' => ['montaj'],
            'handyman' => ['montaj'],
            'plasă insecte' => ['montaj'],
        ];

        foreach (Service::all() as $service) {
            $slugs = $byCategory[$service->category] ?? [];

            foreach ($byName as $fragment => $extra) {
                if (str_contains(mb_strtolower($service->name), mb_strtolower($fragment))) {
                    $slugs = array_merge($slugs, $extra);
                }
            }

            $ids = collect(array_unique($slugs))->map(fn ($s) => $model[$s]->id)->all();
            $service->toolboxes()->syncWithoutDetaching($ids);
        }
    }
}