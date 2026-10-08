<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Catalog de baza de materiale (consumabile, piese, aparate).
 * Preturile sunt de ACHIZITIE, orientative (lei, fara TVA): verifica-le.
 * Pretul de vanzare = achizitie + adaos (MARKUP). Rulabil de mai multe ori:
 * nu dubleaza si nu suprascrie articolele existente.
 */
class MaterialsSeeder extends Seeder
{
    private const MARKUP = 0.30;

    public function run(): void
    {
        $columns = Schema::getColumnListing((new Equipment)->getTable());
        $pick = fn (array $cands) => collect($cands)->first(fn ($c) => in_array($c, $columns, true));

        $col = [
            'name' => $pick(['name']),
            'sku' => $pick(['sku']),
            'category' => $pick(['category']),
            'unit' => $pick(['unit']),
            'cost' => $pick(['cost_price', 'purchase_price']),
            'sale' => $pick(['unit_price', 'sale_price', 'price']),
            'markup' => $pick(['markup_percent']),
            'stock' => $pick(['stock_quantity', 'stock', 'quantity']),
            'min' => $pick(['minimum_stock', 'min_stock']),
            'active' => $pick(['is_active', 'active']),
        ];

        $categories = defined(Equipment::class.'::CATEGORIES') ? Equipment::CATEGORIES : [];
        $resolveCategory = function (string $keyword, string $fallback) use ($categories) {
            foreach ($categories as $key => $label) {
                $hay = Str::lower(Str::ascii($key.' '.$label));
                if (str_contains($hay, $keyword)) {
                    return $key;
                }
            }

            return $fallback;
        };

        $cat = [
            'consumabile' => $resolveCategory('consum', 'consumabil'),
            'piese' => $resolveCategory('piese', 'piesa'),
            'instalatii' => $resolveCategory('instal', 'instalatii'),
        ];

        // [nume, categorie, unitate, achizitie, stoc urmarit (min) sau 0]
        $items = [
            // ---- CONSUMABILE ----
            ['Conector rapid Wago 2 pini', 'consumabile', 'buc', 1.2, 50],
            ['Conector rapid Wago 3 pini', 'consumabile', 'buc', 1.5, 50],
            ['Conector rapid Wago 5 pini', 'consumabile', 'buc', 2.2, 30],
            ['Coliere plastic 200 mm (set 100 buc)', 'consumabile', 'set', 8, 2],
            ['Coliere plastic 300 mm (set 100 buc)', 'consumabile', 'set', 14, 2],
            ['Bandă izolatoare neagră', 'consumabile', 'buc', 3, 5],
            ['Bandă izolatoare albastră', 'consumabile', 'buc', 3, 3],
            ['Bandă izolatoare roșie', 'consumabile', 'buc', 3, 3],
            ['Bandă izolatoare galben-verde', 'consumabile', 'buc', 3, 3],
            ['Clemă șir (reglete) 12 poli', 'consumabile', 'buc', 3, 0],
            ['Dibluri 6 mm (set 100 buc)', 'consumabile', 'set', 10, 3],
            ['Dibluri 8 mm (set 100 buc)', 'consumabile', 'set', 14, 3],
            ['Dibluri rigips tip fluture (set 50 buc)', 'consumabile', 'set', 18, 0],
            ['Șuruburi 3,5x35 (set 200 buc)', 'consumabile', 'set', 14, 2],
            ['Șuruburi 4x50 (set 100 buc)', 'consumabile', 'set', 14, 2],
            ['Șuruburi 5x60 (set 100 buc)', 'consumabile', 'set', 18, 2],
            ['Bandă teflon', 'consumabile', 'buc', 2, 20],
            ['Șnur etanșare Loctite 55 (160 m)', 'consumabile', 'buc', 35, 2],
            ['Silicon universal alb', 'consumabile', 'buc', 12, 4],
            ['Silicon sanitar alb', 'consumabile', 'buc', 18, 4],
            ['Silicon transparent', 'consumabile', 'buc', 14, 2],
            ['Spumă poliuretanică', 'consumabile', 'buc', 18, 0],
            ['Șmirghel foaie granulație 120', 'consumabile', 'buc', 1.5, 0],
            ['Șmirghel foaie granulație 180', 'consumabile', 'buc', 1.5, 0],
            ['Șmirghel foaie granulație 240', 'consumabile', 'buc', 1.5, 0],
            ['Bandă de mascare', 'consumabile', 'buc', 4, 0],
            ['Folie de protecție 4x5 m', 'consumabile', 'buc', 8, 0],
            ['Glet de reparații rapid 1 kg', 'consumabile', 'buc', 12, 0],
            ['Glet gata preparat 5 kg', 'consumabile', 'buc', 25, 0],
            ['Amorsă 2,5 L', 'consumabile', 'buc', 22, 0],
            ['Vopsea lavabilă albă 2,5 L', 'consumabile', 'buc', 45, 0],
            ['Colțar aluminiu perforat 2,5 m', 'consumabile', 'buc', 5, 0],
            ['Plasă rigips autoadezivă', 'consumabile', 'buc', 8, 0],
            ['Cablu electric MYYM 3x1,5', 'consumabile', 'metru', 4.5, 0],
            ['Cablu electric MYYM 3x2,5', 'consumabile', 'metru', 6.5, 0],
            ['Tub copex 20 mm', 'consumabile', 'metru', 1.2, 0],
            ['Lubrifiant spray multifuncțional', 'consumabile', 'buc', 18, 0],

            // ---- PIESE DE SCHIMB ----
            ['Racord flexibil 1/2" 30 cm', 'piese', 'buc', 6, 6],
            ['Racord flexibil 1/2" 50 cm', 'piese', 'buc', 7, 6],
            ['Racord flexibil 1/2" 60 cm', 'piese', 'buc', 8, 4],
            ['Racord flexibil 1/2" 80 cm', 'piese', 'buc', 10, 2],
            ['Racord flexibil 3/8" 50 cm', 'piese', 'buc', 7, 4],
            ['Set garnituri cauciuc 1/2" și 3/4"', 'piese', 'set', 8, 3],
            ['Set garnituri cauciuc 3/8"', 'piese', 'set', 6, 2],
            ['Robinet de trecere colț 1/2"', 'piese', 'buc', 14, 4],
            ['Sifon chiuvetă simplu', 'piese', 'buc', 18, 0],
            ['Sifon chiuvetă dublu', 'piese', 'buc', 28, 0],
            ['Ventil scurgere chiuvetă', 'piese', 'buc', 12, 0],
            ['Baterie chiuvetă simplă', 'piese', 'buc', 90, 0],
            ['Baterie lavoar', 'piese', 'buc', 80, 0],
            ['Baterie cadă / duș', 'piese', 'buc', 120, 0],
            ['Furtun duș cu para', 'piese', 'buc', 30, 0],
            ['Plutitor rezervor WC', 'piese', 'buc', 35, 0],
            ['Mecanism complet rezervor WC', 'piese', 'buc', 60, 0],
            ['Priză simplă încastrată', 'piese', 'buc', 12, 6],
            ['Priză dublă încastrată', 'piese', 'buc', 22, 4],
            ['Întrerupător simplu', 'piese', 'buc', 12, 6],
            ['Întrerupător dublu', 'piese', 'buc', 18, 4],
            ['Comutator cap scară', 'piese', 'buc', 16, 0],
            ['Doză de aparat PVC', 'piese', 'buc', 2, 10],
            ['Siguranță automată 10 A', 'piese', 'buc', 20, 0],
            ['Siguranță automată 16 A', 'piese', 'buc', 20, 2],
            ['Siguranță automată 25 A', 'piese', 'buc', 25, 0],
            ['Diferențial 25 A / 30 mA', 'piese', 'buc', 80, 0],
            ['Dulie E27', 'piese', 'buc', 5, 0],
            ['Bec LED E27 10 W', 'piese', 'buc', 12, 4],
            ['Bandă LED 5 m', 'piese', 'buc', 60, 0],
            ['Transformator LED 12 V 60 W', 'piese', 'buc', 40, 0],
            ['Profil aluminiu pentru LED 2 m', 'piese', 'buc', 25, 0],
            ['Butuc (cilindru) ușă 70 mm', 'piese', 'buc', 50, 0],
            ['Balama mobilă cu amortizor', 'piese', 'buc', 8, 10],
            ['Plasă insecte fereastră (pe balamale)', 'piese', 'buc', 70, 0],

            // ---- INSTALATII SI APARATE ----
            ['Plafonieră LED 24 W', 'instalatii', 'buc', 80, 0],
            ['Aplică perete', 'instalatii', 'buc', 70, 0],
            ['Lustră simplă', 'instalatii', 'buc', 120, 0],
            ['Ventilator de tavan', 'instalatii', 'buc', 250, 0],
            ['Suport TV fix', 'instalatii', 'buc', 55, 0],
            ['Suport TV mobil (brațe)', 'instalatii', 'buc', 120, 0],
            ['Galerie perdele simplă', 'instalatii', 'buc', 60, 0],
            ['Raft mic de perete', 'instalatii', 'buc', 30, 0],
            ['Console calorifer (set)', 'instalatii', 'set', 25, 0],
            ['Set robineți calorifer tur-retur', 'instalatii', 'set', 70, 0],
        ];

        $created = 0;
        $n = 0;

        foreach ($items as [$name, $group, $unit, $cost, $min]) {
            $n++;
            $attrs = [];

            if ($col['category']) {
                $attrs[$col['category']] = $cat[$group];
            }
            if ($col['unit']) {
                $attrs[$col['unit']] = $unit;
            }
            if ($col['cost']) {
                $attrs[$col['cost']] = $cost;
            }
            if ($col['sale']) {
                $attrs[$col['sale']] = round($cost * (1 + self::MARKUP), 2);
            }
            if ($col['markup']) {
                $attrs[$col['markup']] = self::MARKUP * 100;
            }
            if ($col['stock']) {
                $attrs[$col['stock']] = 0;
            }
            if ($col['min']) {
                $attrs[$col['min']] = $min;
            }
            if ($col['active']) {
                $attrs[$col['active']] = true;
            }
            if ($col['sku']) {
                $attrs[$col['sku']] = 'OP-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            }

            $row = Equipment::firstOrCreate([$col['name'] => $name], $attrs);
            $created += $row->wasRecentlyCreated ? 1 : 0;
        }

        $this->command->info("Articole noi: {$created} din ".count($items));
        $this->command->line('Coloane folosite: '.collect($col)->filter()->map(fn ($v, $k) => "{$k}={$v}")->implode(', '));
        $this->command->line('Categorii: '.collect($cat)->map(fn ($v, $k) => "{$k}={$v}")->implode(', '));
    }
}
