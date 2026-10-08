<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        // [nume, categorie, unitate, pret min, pret max, durata (min), observatii, activ?]
        $rows = [
            // ---- ELECTRIC ----
            ['Montare priză / întrerupător / doză aparat', 'electric', 'buc', 35, 50, 20, 'Schimbare simplă pe instalație existentă'],
            ['Montare corp de iluminat standard (aplică, lustră)', 'electric', 'buc', 60, 90, 30, 'Include prinderea în tavan/perete și legăturile'],
            ['Montare lustră complexă / ventilator tavan', 'electric', 'buc', 120, 200, 90, 'Corpuri grele sau cu asamblare migăloasă'],
            ['Montare bandă LED (în profil de aluminiu)', 'electric', 'metru', 30, 45, 30, 'Fără prețul benzii și al transformatorului'],
            ['Înlocuire siguranță automată în tablou', 'electric', 'buc', 40, 60, 20, 'Per modul de siguranță'],
            ['Schimbat tablou electric complet (apartament)', 'electric', 'buc', 450, 700, 240, 'Demontare, montare și punere în funcțiune'],
            ['Diagnosticare defecțiune / scurtcircuit', 'electric', 'ora', 100, 150, 60, 'Se poate deduce dacă se face reparația pe loc'],
            ['Realizare circuit electric nou (aparent sau prin canal)', 'electric', 'metru', 25, 40, 60, 'Pentru consumatori mari (plită, AC)'],

            // ---- SANITAR ----
            ['Montare baterie (chiuvetă, lavoar, cadă)', 'sanitar', 'buc', 80, 120, 45, 'Include racordurile flexibile noi'],
            ['Desfundare simplă chiuvetă / sifon', 'sanitar', 'buc', 80, 130, 45, 'Intervenție mecanică / pompă'],
            ['Desfundare coloană WC / scurgere principală', 'sanitar', 'buc', 150, 250, 90, 'Intervenție cu șarpe electric sau presiune'],
            ['Montare vas WC + rezervor (clasic pe podea)', 'sanitar', 'buc', 150, 220, 120, 'Include fixarea în pardoseală și etanșarea'],
            ['Montare cadru WC suspendat (încastrat)', 'sanitar', 'buc', 250, 350, 240, 'Doar structura și racordurile, fără finisaj rigips'],
            ['Înlocuire chiuvetă / lavoar (doar obiectul)', 'sanitar', 'buc', 100, 150, 90, 'Fără bateria aferentă'],
            ['Înlocuire robinet de trecere / racord flexibil', 'sanitar', 'buc', 40, 60, 30, 'Necesită oprirea apei de la coloana generală'],
            ['Montare / înlocuire calorifer', 'sanitar', 'buc', 180, 250, 120, 'Include robineții de tur/retur și consolele'],
            ['Racordare mașină de spălat (rufe / vase)', 'sanitar', 'buc', 80, 120, 45, 'Pe poziție pregătită (apă + scurgere)'],

            // ---- ZUGRAV ----
            ['Zugrăvit lavabil alb (2 straturi)', 'vopsire', 'm2', 15, 20, 180, 'Suprafață perete/tavan fără reparații'],
            ['Zugrăvit lavabil colorat (2 straturi)', 'vopsire', 'm2', 18, 25, 180, 'Include uniformizarea culorii'],
            ['Aplicare amorsă perete / tavan', 'vopsire', 'm2', 4, 7, 60, 'Pregătirea obligatorie a suportului'],
            ['Gletuire perete (încărcare + finisare)', 'vopsire', 'm2', 15, 25, 180, 'Preț per strat aplicat'],
            ['Șlefuire și uniformizare glet', 'vopsire', 'm2', 5, 8, 120, 'Pregătire înainte de amorsă'],
            ['Reparații glafuri ferestre / uși (după montaj)', 'vopsire', 'metru', 50, 80, 120, 'Include colțare, glet și adus la nivel'],
            ['Vopsit calorifer fontă', 'vopsire', 'buc', 80, 80, 60, 'Curățare și vopsire'],
            ['Vopsit țevi rețea', 'vopsire', 'metru', 15, 15, 60, 'Curățare și vopsire'],

            // ---- MONTAJ ----
            ['Montare mobilier pachete (comode, noptiere, paturi)', 'montaj', 'buc', 100, 250, 120, 'Sau ~15% din valoarea mobilei'],
            ['Montare corp suspendat (bucătărie / baie)', 'montaj', 'buc', 60, 90, 45, 'Include suspendarea pe șină sau console'],
            ['Montare suport TV pe perete (beton/cărămidă)', 'montaj', 'buc', 80, 130, 60, 'Include calibrarea cu polobocul'],
            ['Montare galerie perdele / draperii', 'montaj', 'buc', 50, 80, 30, 'Preț per galerie (simplă sau dublă)'],
            ['Montare oglindă / tablou / raft mic', 'montaj', 'buc', 35, 60, 30, 'În funcție de greutate și tipul de perete'],
            ['Montare parchet laminat + folie', 'montaj', 'm2', 25, 35, 240, 'Suprafață curată și plană'],
            ['Montare plintă parchet PVC', 'montaj', 'metru', 10, 10, 60, null],
            ['Montare plintă parchet MDF', 'montaj', 'metru', 18, 18, 90, 'Necesită tăieri la unghi'],

            // ---- LUCRĂRI GENERALE ----
            ['Schimbare butuc (cilindru) / broască ușă', 'general', 'buc', 60, 90, 30, 'Schimb direct pe ușa existentă'],
            ['Reglaj feronerie fereastră / ușă termopan', 'general', 'buc', 50, 80, 30, 'Reglaj de iarnă/vară sau reinițializare cursă'],
            ['Înlocuire / reglare balamale mobilă', 'general', 'buc', 25, 40, 20, 'Preț per balama înlocuită'],
            ['Îndepărtare și aplicare silicon nou (cadă/duș)', 'general', 'metru', 30, 45, 45, 'Include curățarea și dezinfectarea'],
            ['Reparație gaură rigips (petic + glet finisaj)', 'general', 'buc', 70, 120, 90, 'Găuri accidentale sau după decupaje'],
            ['Montare plasă insecte (pe balamale)', 'general', 'buc', 40, 60, 30, 'Doar montajul pe ramă/fereastră'],
            ['Tarif orar handyman (mărunțișuri diverse)', 'general', 'ora', 80, 120, 60, 'Când sunt mai multe sarcini mici'],
            // taxa de deplasare nu apare ca optiune in formularul public
            ['Taxă deplasare standard (în oraș)', 'general', 'vizita', 50, 80, 30, 'Adesea gratuită peste pragul minim', false],
        ];

        $unitLabels = ['ora' => 'oră', 'buc' => 'buc.', 'm2' => 'mp', 'metru' => 'm.l.', 'vizita' => 'vizită'];

        foreach ($rows as $r) {
            [$name, $category, $unit, $min, $max, $minutes, $note] = $r;

            $range = $min === $max ? "{$min} lei" : "{$min}–{$max} lei";
            $description = "Preț orientativ: {$range} / {$unitLabels[$unit]}".($note ? ". {$note}" : '');

            Service::firstOrCreate(
                ['name' => $name, 'category' => $category],
                [
                    'unit' => $unit,
                    'cost_price' => 0,
                    'sale_price' => (int) round(($min + $max) / 2),
                    'duration_minutes' => $minutes,
                    'description' => $description,
                    'is_active' => $r[7] ?? true,
                ]
            );
        }
    }
}