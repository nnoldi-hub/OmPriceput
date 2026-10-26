<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Lot 2: 5 articole pentru blog. Se ruleaza O SINGURA DATA pe server
 * (reruleaza suprascrie articolele daca le-ai editat intre timp).
 */
class BlogArticlesBatch2Seeder extends Seeder
{
    /** La cate zile apare fiecare articol (0 = toate deodata). */
    private const DRIP_DAYS = 0;

    public function run(): void
    {
        $columns = Schema::getColumnListing((new Post)->getTable());
        $authorId = User::query()->orderBy('id')->value('id');

        foreach ($this->articles() as $i => $a) {
            $attrs = [
                'title' => $a['title'],
                'excerpt' => $a['excerpt'],
                'body' => $this->render($a['blocks']),
                'cover_image_alt' => $a['alt'],
                'author_id' => $authorId,
                'meta_title' => $a['meta_title'],
                'meta_description' => $a['meta_description'],
                'focus_keyword' => $a['keyword'],
                'status' => 'published',
                'published_at' => $i === 0 ? now()->subMinute() : now()->addDays($i * self::DRIP_DAYS),
                'auto_internal_links' => true,
                'faq' => array_map(fn ($f) => ['question' => $f[0], 'answer' => $f[1]], $a['faq']),
            ];

            $post = Post::updateOrCreate(
                ['slug' => $a['slug']],
                array_intersect_key($attrs, array_flip($columns))
            );

            $this->attach($post, 'App\\Models\\Tag', 'tags', $a['tags']);
            $this->attach($post, 'App\\Models\\Category', 'categories', ['Servicii la domiciliu'], false);
        }

        $this->command->info('Articole procesate: '.count($this->articles()));
        $this->command->line('Adauga imaginile de coperta din admin (Blog -> editeaza articolul).');
    }

    private function attach(Post $post, string $class, string $relation, array $names, bool $create = true): void
    {
        $table = (new $class)->getTable();
        if (! class_exists($class) || ! Schema::hasTable($table)) {
            return;
        }

        $ids = [];
        foreach ($names as $name) {
            $slug = Str::slug($name);
            $found = $class::query()->where('slug', $slug)->first();

            if (! $found && $create) {
                $found = $class::create(['name' => $name, 'slug' => $slug]);
            }

            if ($found) {
                $ids[] = $found->getKey();
            }
        }

        try {
            $post->{$relation}()->syncWithoutDetaching($ids);
        } catch (\Throwable $e) {
            $this->command->warn("Nu s-a putut atasa {$relation}: ".$e->getMessage());
        }
    }

    private function render(array $blocks): string
    {
        $out = [];

        foreach ($blocks as $b) {
            $out[] = match ($b[0]) {
                'h2' => '<h2>'.e($b[1]).'</h2>',
                'p' => '<p>'.$this->inline($b[1]).'</p>',
                'ul' => '<ul>'.collect($b[1])->map(fn ($li) => '<li>'.$this->inline($li).'</li>')->implode('').'</ul>',
                'ol' => '<ol>'.collect($b[1])->map(fn ($li) => '<li>'.$this->inline($li).'</li>')->implode('').'</ol>',
                'cta' => '<p>'.$this->inline('Vrei ca lucrarea să fie făcută corect, fără bătăi de cap? Alege ziua și ora direct pe [ompriceput.ro/cerere-deviz](/cerere-deviz), iar noi venim seara sau în weekend, cu sculele și materialele potrivite.').'</p>',
                'related' => '<h2>Citește și</h2><ul>'
                    .collect($b[1])->map(fn ($title, $slug) => '<li>'.$this->inline("[{$title}](/blog/{$slug})").'</li>')->implode('')
                    .'</ul>',
                default => '',
            };
        }

        return implode("\n", $out);
    }

    /** Escapeaza HTML si transforma [text](url) in link (extern: nofollow, tab nou). */
    private function inline(string $text): string
    {
        return preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function ($m) {
            $external = str_starts_with($m[2], 'http');

            return '<a href="'.$m[2].'"'.($external ? ' target="_blank" rel="noopener nofollow"' : '').'>'.$m[1].'</a>';
        }, e($text));
    }

    private function articles(): array
    {
        return [
            // 1 ---------------------------------------------------------------
            [
                'slug' => 'montaj-parchet-laminat-cost-si-pasi',
                'title' => 'Montaj Parchet Laminat: Cost, Pași și Greșeli de Evitat',
                'excerpt' => 'Cum se montează corect parchetul laminat, ce pregătire cere podeaua, ce greșeli strică rezultatul și cât costă manopera.',
                'keyword' => 'montaj parchet laminat',
                'meta_title' => 'Montaj Parchet Laminat | Cost pe mp și Pași',
                'meta_description' => 'Montaj parchet laminat pas cu pas: pregătirea podelei, rost de dilatare, plinte și greșeli frecvente. Manoperă de la 25 lei/mp.',
                'alt' => 'Montaj parchet laminat într-o cameră, cu plăci așezate pe folie',
                'tags' => ['montaj parchet laminat', 'parchet', 'plintă parchet', 'amenajări'],
                'blocks' => [
                    ['p', 'Un montaj parchet laminat bine făcut schimbă o cameră într-o singură zi. Unul făcut în grabă se vede imediat: plăci care se desprind, podea care scârțâie, rosturi pe margini. Diferența ține aproape întotdeauna de pregătire, nu de plăcile alese.'],
                    ['h2', 'Podeaua de dedesubt decide rezultatul'],
                    ['p', 'Parchetul laminat se așază flotant, peste o suprafață care trebuie să fie curată, uscată și plană. Denivelările mari fac plăcile să joace și să se desprindă la îmbinări. Ca reper, abaterile de câțiva milimetri pe doi metri se corectează înainte de montaj, cu șapă de nivelare sau cu un substrat potrivit.'],
                    ['ul', [
                        'Șapa nouă trebuie să se usuce complet, ceea ce poate dura săptămâni.',
                        'Gresia veche rămâne bună ca bază, dacă nu are plăci desprinse și rosturile nu sunt prea adânci.',
                        'Praful și resturile de adeziv se îndepărtează, pentru că rămân vizibile sub folie.',
                    ]],
                    ['h2', 'Aclimatizarea plăcilor'],
                    ['p', 'Pachetele se lasă în camera de montaj între 24 și 48 de ore, în ambalaj, ca să se obișnuiască cu temperatura și umiditatea locuinței. Plăcile montate direct din depozit se pot dilata sau contracta după montaj și apar rosturi.'],
                    ['h2', 'Pașii unui montaj corect'],
                    ['ol', [
                        'Așezi folia anti-umezeală și, dacă plăcile nu au substrat integrat, și un substrat fonoizolant.',
                        'Începi dintr-un colț, cu plăcile orientate pe direcția luminii din cameră.',
                        'Lași un rost de dilatare de aproximativ 8–10 mm față de toți pereții, folosind distanțieri.',
                        'Îmbini plăcile pe lungime și pe lățime, decalând rândurile pentru stabilitate.',
                        'Tai plăcile pentru praguri, țevi și uși cu rost de dilatare și în jurul lor.',
                        'Montezi plintele pe perete, nu pe podea, ca parchetul să poată lucra liber.',
                    ]],
                    ['h2', 'Greșeli care strică un parchet nou'],
                    ['ul', [
                        'Lipsa rostului de dilatare, care duce la bombarea plăcilor vara.',
                        'Plinte fixate pe parchet, nu pe perete.',
                        'Montaj peste podea umedă sau denivelată.',
                        'Comandă prea mică: ia 5–10% în plus pentru tăieturi și 10–15% dacă plăcile se montează pe diagonală.',
                    ]],
                    ['h2', 'Plintă PVC sau MDF'],
                    ['p', 'Plinta PVC este ieftină, rezistentă la apă și se montează repede. Cea MDF arată mai bine, se vopsește sau vine în aceeași culoare cu parchetul, dar cere tăieturi la unghi mai atente, deci costă mai mult la montaj.'],
                    ['h2', 'Cât costă montajul'],
                    ['ul', [
                        'Parchet laminat cu folie: 25–35 lei pe mp, manoperă.',
                        'Plintă PVC: aproximativ 10 lei pe metru liniar.',
                        'Plintă MDF: aproximativ 18 lei pe metru liniar.',
                    ]],
                    ['p', 'Exemplu: pentru o cameră de 15 mp și 16 metri liniari de plintă PVC, manopera ajunge între 535 și 685 lei. Materialele nu sunt incluse, iar taxa de deplasare de 50 lei se scade din lucrare dacă ne încredințezi montajul. Prețurile sunt orientative și se confirmă în devizul scris.'],
                    ['cta'],
                    ['related', [
                        'preturi-mici-reparatii-in-casa' => 'Cât Costă Mici Reparații în Casă: Ghid de Prețuri 2026',
                        'montaj-rafturi-si-polite-ghid' => 'Montaj Rafturi și Polițe: Cum Le Prinzi Corect de Perete',
                    ]],
                ],
                'faq' => [
                    ['Cât durează montajul parchetului laminat într-o cameră?', 'O cameră de 15–20 mp se montează de obicei într-o zi, inclusiv plintele, dacă podeaua este deja pregătită. Nivelarea sau demontarea podelei vechi adaugă timp.'],
                    ['Se poate monta parchet laminat peste gresie?', 'Da, dacă gresia este bine fixată, plană și fără rosturi foarte adânci. Rosturile se umplu sau se acoperă cu substrat, ca să nu se vadă prin parchet.'],
                    ['Cât parchet trebuie să comand în plus?', 'Aproximativ 5–10% pentru tăieturi la montaj drept și 10–15% la montaj pe diagonală sau în camere cu multe colțuri.'],
                ],
            ],

            // 2 ---------------------------------------------------------------
            [
                'slug' => 'baterie-de-chiuveta-care-picura',
                'title' => 'Baterie de Chiuvetă care Picură: Repari sau Înlocuiești?',
                'excerpt' => 'De ce picură o baterie, când merită reparată, când se înlocuiește și cum se montează una nouă corect.',
                'keyword' => 'baterie de chiuvetă care picură',
                'meta_title' => 'Baterie de Chiuvetă care Picură | Reparație sau Schimb',
                'meta_description' => 'Baterie de chiuvetă care picură: cauze, când o repari și când o înlocuiești, pași de montaj și prețuri de manoperă (de la 80 lei).',
                'alt' => 'Montaj baterie nouă la o chiuvetă de bucătărie',
                'tags' => ['baterie chiuvetă', 'instalator', 'reparații sanitare', 'mici reparații'],
                'blocks' => [
                    ['p', 'O baterie de chiuvetă care picură pare o problemă mică, dar o picătură pe secundă înseamnă mii de litri irosiți într-un an, plus calcar, mucegai și, în timp, mobilier umflat sub chiuvetă. Iată de ce se întâmplă, cum decizi între reparație și înlocuire și cum se face lucrarea corect.'],
                    ['h2', 'De ce picură o baterie'],
                    ['ul', [
                        'Garnitura sau cartușul ceramic s-au uzat și nu mai etanșează.',
                        'Calcarul s-a depus pe piesele interioare și împiedică închiderea completă.',
                        'Racordul de dedesubt sau piulița nu este strânsă ori garnitura s-a întărit.',
                        'Corpul bateriei este crăpat sau corodat, mai ales la modelele ieftine și vechi.',
                    ]],
                    ['p', 'Merită să afli întâi de unde picură. Dacă apa iese din cioc, vina este în cartuș. Dacă se strânge apă în dulapul de sub chiuvetă, problema este la racorduri sau la corpul bateriei.'],
                    ['h2', 'Când repari și când înlocuiești'],
                    ['p', 'Un cartuș nou rezolvă de obicei o baterie în stare bună, dar nu are rost dacă piesa nu se mai găsește sau dacă bateria are deja zeci de reparații. Înlocuirea este alegerea mai bună când corpul este coroziv sau crăpat, când finisajul s-a deteriorat sau când bateria a fost reparată de mai multe ori în ultimii ani.'],
                    ['h2', 'Cum se montează o baterie nouă'],
                    ['ol', [
                        'Oprești apa la robinetul de trecere de sub chiuvetă (sau la coloană, dacă nu există robinete).',
                        'Deschizi bateria veche ca să scurgi apa rămasă în țevi.',
                        'Demontezi racordurile și bateria veche, cu o cheie potrivită pentru piulița de fixare.',
                        'Curăți suprafața chiuvetei și montezi bateria nouă cu garnitura ei.',
                        'Montezi racorduri flexibile noi, nu reutilizezi cele vechi.',
                        'Deschizi apa încet și verifici cu o hârtie uscată să nu existe scurgeri.',
                    ]],
                    ['h2', 'Greșeli care duc la scurgeri'],
                    ['ul', [
                        'Racorduri flexibile vechi puse pe o baterie nouă.',
                        'Strângere excesivă, care crapă garnitura sau piulița.',
                        'Lucru fără oprirea apei, cu risc de inundație.',
                        'Montaj fără verificare după 24 de ore, când apar picăturile lente.',
                    ]],
                    ['p', 'În blocuri, dacă apartamentul nu are robinet de trecere, apa se oprește de la coloană, iar uneori e nevoie de acordul administratorului. Un robinet de trecere pe fiecare consumator rezolvă problema pentru viitor.'],
                    ['h2', 'Cât costă'],
                    ['ul', [
                        'Montare baterie (chiuvetă, lavoar, cadă), cu racorduri flexibile noi: 80–120 lei.',
                        'Înlocuire robinet de trecere sau racord flexibil: 40–60 lei.',
                        'Desfundare simplă a sifonului: 80–130 lei.',
                    ]],
                    ['p', 'Prețurile sunt pentru manoperă. Bateria o poți cumpăra tu sau poate fi adusă de noi, caz în care ea apare separat în deviz. Taxa de deplasare, 50 lei, se scade din lucrare dacă ne încredințezi montajul.'],
                    ['cta'],
                    ['related', [
                        'mici-reparatii-la-domiciliu' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid și Cât Costă',
                        'silicon-la-cada-si-dus-refacere' => 'Silicon la Cadă și Duș: Când Îl Refaci și Cum Eviți Mucegaiul',
                    ]],
                ],
                'faq' => [
                    ['Merită să schimb doar cartușul bateriei?', 'Da, dacă bateria este în stare bună și cartușul se găsește ușor. Dacă bateria este veche, coroziv sau a mai fost reparată, înlocuirea ei este mai economică pe termen lung.'],
                    ['Cât durează înlocuirea unei baterii de chiuvetă?', 'În general între 30 și 60 de minute, dacă racordurile și robinetele de trecere sunt în stare bună.'],
                    ['Trebuie să schimb și racordurile flexibile?', 'Da. Racordurile vechi se întăresc și pot curge după montaj. Sunt ieftine, iar schimbarea lor odată cu bateria evită o a doua intervenție.'],
                ],
            ],

            // 3 ---------------------------------------------------------------
            [
                'slug' => 'silicon-la-cada-si-dus-refacere',
                'title' => 'Silicon la Cadă și Duș: Când Îl Refaci și Cum Eviți Mucegaiul',
                'excerpt' => 'Siliconul înnegrit sau crăpat lasă apa să treacă în spatele căzii. Cum se reface corect și cum previi mucegaiul.',
                'keyword' => 'silicon la cadă',
                'meta_title' => 'Silicon la Cadă și Duș | Refacere și Mucegai',
                'meta_description' => 'Silicon la cadă negru sau crăpat? Vezi când îl refaci, cum se aplică corect siliconul sanitar și cât costă manopera (30–45 lei/ml).',
                'alt' => 'Aplicarea siliconului sanitar la îmbinarea dintre cadă și faianță',
                'tags' => ['silicon cadă', 'silicon sanitar', 'mucegai baie', 'mici reparații'],
                'blocks' => [
                    ['p', 'Siliconul la cadă este unul dintre lucrurile pe care le amâni până când devine o problemă: negru la colțuri, crăpat, desprins de faianță. Nu e doar o chestiune de aspect. Un rost deteriorat lasă apa să treacă în spatele căzii, iar acolo apar mucegaiul și, în timp, umezeală la vecinii de jos.'],
                    ['h2', 'Când este momentul să refaci siliconul'],
                    ['ul', [
                        'Apar pete negre sau verzi care nu dispar la curățare.',
                        'Siliconul s-a crăpat, s-a desprins sau s-a decolorat.',
                        'Se strânge apă la marginea căzii sau a dușului.',
                        'Rostul miroase a umezeală, chiar dacă baia este aerisită.',
                    ]],
                    ['h2', 'De ce nu merge un strat peste cel vechi'],
                    ['p', 'Siliconul nou nu se prinde pe cel vechi. Dacă aplici peste el, noul rost se desprinde în câteva săptămâni și mucegaiul rămâne prins dedesubt. Îndepărtarea completă a vechiului strat este singura variantă care ține.'],
                    ['h2', 'Cum se reface corect'],
                    ['ol', [
                        'Tai și scoți siliconul vechi cu un cutter și un răzuitor, fără să zgârii cada sau faianța.',
                        'Cureți rostul și îl degresezi cu alcool sau cu un produs special pentru baie.',
                        'Dezinfectezi zona dacă există mucegai, apoi o lași să se usuce complet.',
                        'Lipești bandă de mascare pe ambele margini, pentru un rost drept.',
                        'Aplici siliconul sanitar cu fungicid, dintr-o singură trecere, fără pauze.',
                        'Netezești imediat, cu un deget umezit în apă cu puțin detergent, apoi scoți banda.',
                        'Lași siliconul să se întărească aproximativ 24 de ore înainte de folosirea căzii sau a dușului.',
                    ]],
                    ['p', 'Un truc folosit de profesioniști la căzi: o umpli parțial cu apă înainte de aplicare, ca acrilicul sau tabla să fie deja apăsat de greutate și rostul să nu se desprindă când folosești cada.'],
                    ['h2', 'Greșeli frecvente'],
                    ['ul', [
                        'Silicon universal în loc de silicon sanitar, care nu are protecție la mucegai.',
                        'Aplicare pe suprafață umedă sau murdară.',
                        'Rost prea gros sau prea subțire, care se crapă mai repede.',
                        'Folosirea dușului înainte ca siliconul să se întărească.',
                    ]],
                    ['h2', 'Cum previi mucegaiul'],
                    ['ul', [
                        'Aerisește baia după fiecare duș, cu fereastra deschisă sau cu ventilatorul pornit.',
                        'Șterge rostul și faianța din zona dușului, ca să nu stea apa.',
                        'Refă siliconul la primele semne, nu abia când s-a înnegrit.',
                    ]],
                    ['h2', 'Cât costă'],
                    ['p', 'Îndepărtarea și aplicarea unui silicon nou, cu curățarea și dezinfectarea rostului, costă între 30 și 45 lei pe metru liniar. O cadă obișnuită are 3–4 metri liniari de rost, deci manopera ajunge de regulă între 90 și 180 lei. Taxa de deplasare, 50 lei, se scade din lucrare dacă ne încredințezi montajul, iar cartușul de silicon este inclus în preț sau trecut separat în deviz.'],
                    ['cta'],
                    ['related', [
                        'baterie-de-chiuveta-care-picura' => 'Baterie de Chiuvetă care Picură: Repari sau Înlocuiești?',
                        'top-reparatii-amanate-in-locuinta' => 'Top Reparații Amânate în Locuință',
                    ]],
                ],
                'faq' => [
                    ['Cât durează până se întărește siliconul?', 'De obicei este uscat la suprafață în câteva ore, dar se întărește complet în aproximativ 24 de ore. Până atunci nu se folosește cada sau dușul.'],
                    ['Pot curăța mucegaiul fără să refac siliconul?', 'Dacă mucegaiul este doar la suprafață, o soluție de curățare poate ajuta temporar. Dacă a pătruns în silicon, singura soluție durabilă este îndepărtarea și refacerea lui.'],
                    ['Ce silicon aleg pentru baie?', 'Siliconul sanitar cu fungicid, rezistent la apă și la mucegai. Cel universal nu este făcut pentru zone permanent umede.'],
                ],
            ],

            // 4 ---------------------------------------------------------------
            [
                'slug' => 'reglaj-termopan-ferestre-care-trag',
                'title' => 'Reglaj Termopan: Ferestre care Trag sau se Închid Greu',
                'excerpt' => 'Multe ferestre termopan care trag nu trebuie schimbate, ci reglate. Cum recunoști problema și ce se poate repara.',
                'keyword' => 'reglaj termopan',
                'meta_title' => 'Reglaj Termopan | Ferestre care Trag sau Nu se Închid',
                'meta_description' => 'Reglaj termopan pentru ferestre care trag sau se închid greu: semne, ce se poate regla, când schimbi garniturile. Manoperă de la 50 lei.',
                'alt' => 'Reglarea feroneriei unei ferestre termopan cu o cheie imbus',
                'tags' => ['reglaj termopan', 'ferestre termopan', 'garnituri', 'mici reparații'],
                'blocks' => [
                    ['p', 'Ai simțit iarna curent pe lângă fereastră sau ai observat că geamul se închide tot mai greu? De multe ori nu e nevoie de o fereastră nouă, ci de un reglaj termopan. Feroneria se desface în timp, iar o intervenție de o oră poate readuce fereastra în stare bună.'],
                    ['h2', 'Semne că fereastra are nevoie de reglaj'],
                    ['ul', [
                        'Simți aer rece sau auzi vântul pe lângă ramă.',
                        'Clanța se învârte greu sau fereastra nu se închide complet.',
                        'Canatul atinge rama jos sau pe lateral.',
                        'Garnitura are urme de frecare doar într-un punct.',
                        'Zgomotul de afară se aude mai tare decât înainte.',
                    ]],
                    ['h2', 'Ce se poate regla'],
                    ['p', 'Majoritatea ferestrelor termopan au balamale care se reglează în mai multe direcții și un sistem de închidere cu piese care strâng canatul pe garnitură.'],
                    ['ul', [
                        'Înălțimea și poziția canatului față de ramă, din balamalele de jos și de sus (de obicei cu o cheie imbus, în general de 4 mm).',
                        'Apăsarea pe garnitură, din piesele de închidere de pe margine, care se pot roti pentru reglaj de iarnă sau de vară.',
                        'Poziția clapetei de aerisire, dacă fereastra o are.',
                    ]],
                    ['h2', 'Reglajul de iarnă și de vară'],
                    ['p', 'Iarna garnitura se strânge mai tare, ca fereastra să etanșeze mai bine. Vara se slăbește puțin, ca să nu forțezi feroneria și garnitura. Mulți fac reglajul o dată pe an, la schimbarea sezonului, și evită astfel uzura prematură.'],
                    ['h2', 'Garniturile și lubrifierea'],
                    ['p', 'Garniturile crăpate, întărite sau uscate lasă aerul să treacă, chiar dacă feroneria este reglată perfect. Se pot curăța și hrăni cu un produs pe bază de silicon, iar cele deteriorate se înlocuiesc. Piesele mobile ale feroneriei se ung o dată pe an cu ulei special, nu cu orice spray.'],
                    ['h2', 'Ce nu se rezolvă prin reglaj'],
                    ['ul', [
                        'Geam crăpat sau spart.',
                        'Ramă deformată, de exemplu din cauza unui canat prea greu sau a unui montaj greșit.',
                        'Condens între cele două geamuri, semn că etanșarea pachetului s-a pierdut.',
                    ]],
                    ['p', 'În aceste cazuri trebuie schimbat geamul sau fereastra, iar un meseriaș îți poate spune cinstit dacă merită reparația sau înlocuirea.'],
                    ['h2', 'Cât costă'],
                    ['p', 'Reglajul feroneriei la o fereastră sau ușă termopan costă între 50 și 80 lei, în funcție de numărul de canaturi și de starea garniturilor. Dacă ai mai multe ferestre sau alte mici lucrări, le rezolvăm într-o singură vizită. Taxa de deplasare, 50 lei, se scade din lucrare dacă ne încredințezi montajul.'],
                    ['cta'],
                    ['related', [
                        'mici-reparatii-la-domiciliu' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid și Cât Costă',
                        'handyman-la-domiciliu-ce-inseamna' => 'Handyman la Domiciliu: Ce Înseamnă și Când Merită Să Apelezi la Unul',
                    ]],
                ],
                'faq' => [
                    ['Cât durează reglajul unei ferestre termopan?', 'Aproximativ 20–30 de minute pe fereastră, dacă feroneria este în stare bună. Garniturile deteriorate sau piesele de înlocuit adaugă timp.'],
                    ['Cât de des trebuie reglat termopanul?', 'Cel puțin o dată pe an, la schimbarea sezonului. Dacă ferestrele sunt folosite des, merită o verificare și mai des.'],
                    ['Pot regla singur fereastra?', 'Reglajele simple se pot face cu o cheie imbus, dar un reglaj greșit poate deteriora feroneria. Dacă nu ești sigur, mai sigur este să ceri ajutorul unui specialist.'],
                ],
            ],

            // 5 ---------------------------------------------------------------
            [
                'slug' => 'preturi-mici-reparatii-in-casa',
                'title' => 'Cât Costă Mici Reparații în Casă: Ghid de Prețuri 2026',
                'excerpt' => 'Prețuri orientative pentru montaje și mici reparații în locuință: electrice, sanitare, montaj, parchet și lucrări generale.',
                'keyword' => 'mici reparații în casă',
                'meta_title' => 'Cât Costă Mici Reparații în Casă | Ghid Prețuri 2026',
                'meta_description' => 'Prețuri orientative pentru mici reparații în casă în 2026: electrice, sanitare, montaj, parchet. Ce influențează costul final.',
                'alt' => 'Listă de prețuri pentru mici reparații și montaje în locuință',
                'tags' => ['prețuri mici reparații', 'tarife handyman', 'cost montaj', 'deviz'],
                'blocks' => [
                    ['p', 'Înainte să chemi pe cineva, vrei să știi cât costă lucrarea. Pentru mici reparații în casă, prețurile diferă mult de la un meseriaș la altul, iar lipsa unei liste clare duce la negocieri și surprize. Iată un ghid cu tarifele noastre orientative pentru manoperă, plus ce influențează costul final.'],
                    ['h2', 'Lucrări electrice (pe instalația existentă)'],
                    ['ul', [
                        'Montare priză, întrerupător sau doză de aparat: 35–50 lei pe bucată.',
                        'Montare corp de iluminat standard (aplică, plafonieră): 60–90 lei.',
                        'Montare lustră complexă sau ventilator de tavan: 120–200 lei.',
                        'Montare bandă LED în profil de aluminiu: 30–45 lei pe metru liniar.',
                        'Diagnosticare defecțiune: 100–150 lei pe oră.',
                    ]],
                    ['h2', 'Lucrări sanitare mici'],
                    ['ul', [
                        'Montare baterie (chiuvetă, lavoar, cadă), cu racorduri noi: 80–120 lei.',
                        'Înlocuire robinet de trecere sau racord flexibil: 40–60 lei.',
                        'Desfundare simplă chiuvetă sau sifon: 80–130 lei.',
                        'Racordare mașină de spălat: 80–120 lei.',
                    ]],
                    ['h2', 'Montaj și mobilier'],
                    ['ul', [
                        'Suport TV pe perete: 80–130 lei.',
                        'Oglindă, tablou sau raft mic: 35–60 lei.',
                        'Galerie de perdele: 50–80 lei.',
                        'Corp suspendat de bucătărie sau baie: 60–90 lei.',
                        'Mobilier în pachete (comode, noptiere, paturi): 100–250 lei.',
                        'Parchet laminat cu folie: 25–35 lei pe mp.',
                    ]],
                    ['h2', 'Mici reparații generale'],
                    ['ul', [
                        'Schimbare butuc sau broască la ușă: 60–90 lei.',
                        'Reglaj feronerie termopan: 50–80 lei.',
                        'Silicon nou la cadă sau duș: 30–45 lei pe metru liniar.',
                        'Reparație gaură în rigips (petic și glet): 70–120 lei.',
                        'Înlocuire sau reglare balamale mobilă: 25–40 lei pe bucată.',
                        'Tarif orar pentru mai multe sarcini mici: 80–120 lei pe oră.',
                    ]],
                    ['h2', 'Ce influențează prețul final'],
                    ['ul', [
                        'Starea instalației sau a peretelui, care poate cere pași suplimentari.',
                        'Accesul la locul lucrării (etaj, spațiu îngust, mobilă de mutat).',
                        'Materialele: pot fi cumpărate de tine sau aduse de noi, caz în care apar separat în deviz.',
                        'Urgența și programul lucrării.',
                    ]],
                    ['h2', 'Cum plătești mai puțin'],
                    ['ul', [
                        'Strânge mai multe lucrări mici și rezolvă-le într-o singură vizită, cu o singură deplasare.',
                        'Trimite poze cu problema, ca să venim cu piesele potrivite din prima.',
                        'Cere deviz scris înainte de începerea lucrării.',
                    ]],
                    ['p', 'Taxa de deplasare este de 50 lei și se scade din lucrare dacă ne încredințezi montajul. Toate prețurile de mai sus sunt orientative și se confirmă în devizul scris, după ce vedem lucrarea. Pentru lucrări la instalația electrică fixă sau la tablou, verifică atestarea persoanei care le execută pe site-ul [ANRE](https://anre.ro). Drepturile tale ca și consumator sunt explicate pe site-ul [ANPC](https://anpc.ro).'],
                    ['cta'],
                    ['related', [
                        'cum-alegi-un-meserias-de-incredere' => 'Cum Alegi un Meseriaș de Încredere: 8 Verificări Înainte de Comandă',
                        'mici-reparatii-la-domiciliu' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid și Cât Costă',
                    ]],
                ],
                'faq' => [
                    ['Prețurile includ materialele?', 'Nu. Prețurile sunt pentru manoperă. Materialele (baterii, prize, parchet, silicon) le poți cumpăra tu sau le aducem noi, caz în care apar separat în deviz.'],
                    ['Se plătește deplasarea separat?', 'Taxa de deplasare este de 50 lei și se scade din lucrare dacă ne încredințezi montajul.'],
                    ['Cum aflu prețul exact pentru lucrarea mea?', 'Trimite o cerere de deviz cu poze și serviciile dorite. Prețul exact îl primești în devizul scris, după constatarea la fața locului.'],
                ],
            ],
        ];
    }
}
