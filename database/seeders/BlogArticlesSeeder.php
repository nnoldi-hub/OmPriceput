<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * 8 articole de blog pentru Om Priceput.
 * Rulabil de mai multe ori: actualizeaza articolele dupa slug, nu le dubleaza.
 */
class BlogArticlesSeeder extends Seeder
{
    /** 'html' sau 'markdown': ajusteaza dupa cum arata `body` in articolele tale existente. */
    private const BODY_FORMAT = 'html';

    /** Cheile pentru intrebari frecvente (verifica in articolele existente, daca ai FAQ). */
    private const FAQ_QUESTION_KEY = 'question';
    private const FAQ_ANSWER_KEY = 'answer';

    private const WITH_FAQ = true;

    public function run(): void
    {
        $columns = Schema::getColumnListing((new Post)->getTable());
        $authorId = User::query()->orderBy('id')->value('id');
        $articles = $this->articles();
        $total = count($articles);

        foreach ($articles as $i => $a) {
            $faq = array_map(fn ($f) => [
                self::FAQ_QUESTION_KEY => $f[0],
                self::FAQ_ANSWER_KEY => $f[1],
            ], $a['faq']);

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
                'published_at' => now()->subDays($total - $i),
                'auto_internal_links' => true,
            ];

            if (self::WITH_FAQ) {
                $attrs['faq'] = $faq;
            }

            $attrs = array_intersect_key($attrs, array_flip($columns));

            $post = Post::updateOrCreate(['slug' => $a['slug']], $attrs);
            $this->attachTags($post, $a['tags']);
            $this->attachCategories($post, in_array($a['slug'], [
                'montaj-suport-tv-pe-perete-ghid-complet',
                'montaj-rafturi-si-polite-ghid',
            ], true) ? ['Montaj mobilă', 'Servicii la domiciliu'] : ['Servicii la domiciliu']);
        }

        $this->command->info("Articole procesate: {$total}. Format body: ".self::BODY_FORMAT);
        $this->command->line('Imaginile de copertă se adaugă din admin (Blog → editează articolul).');
    }

    private function attachCategories(Post $post, array $names): void
    {
        $class = 'App\\Models\\Category';
        if (! class_exists($class) || ! Schema::hasTable('categories')) {
            return;
        }

        $cols = Schema::getColumnListing('categories');
        $ids = [];

        foreach ($names as $name) {
            // folosim doar categoriile existente (cauta dupa nume sau slug), nu le inventam
            $q = $class::query();
            $q->where(function ($w) use ($name, $cols) {
                if (in_array('name', $cols, true)) {
                    $w->orWhere('name', $name);
                }
                if (in_array('slug', $cols, true)) {
                    $w->orWhere('slug', Str::slug($name));
                }
            });
            if ($found = $q->first()) {
                $ids[] = $found->getKey();
            }
        }

        try {
            $post->categories()->syncWithoutDetaching($ids);
        } catch (\Throwable $e) {
            $this->command->warn('Categoriile nu au putut fi atașate: '.$e->getMessage());
        }
    }

    private function attachTags(Post $post, array $tags): void
    {
        $tagClass = 'App\\Models\\Tag';
        if (! class_exists($tagClass) || ! Schema::hasTable('tags')) {
            return;
        }

        $cols = Schema::getColumnListing('tags');
        $ids = [];

        foreach ($tags as $name) {
            $attrs = array_intersect_key(['name' => $name, 'slug' => Str::slug($name)], array_flip($cols));
            $lookup = in_array('slug', $cols, true) ? ['slug' => Str::slug($name)] : ['name' => $name];
            $ids[] = $tagClass::firstOrCreate($lookup, $attrs)->getKey();
        }

        try {
            $post->tags()->syncWithoutDetaching($ids);
        } catch (\Throwable $e) {
            $this->command->warn('Etichetele nu au putut fi atașate: '.$e->getMessage());
        }
    }

    /** Blocuri: ['p', text] ['h2', text] ['ul', [..]] ['cta'] ['related', [slug => titlu]] */
    private function render(array $blocks): string
    {
        $html = self::BODY_FORMAT === 'html';
        $out = [];

        foreach ($blocks as $b) {
            switch ($b[0]) {
                case 'h2':
                    $out[] = $html ? '<h2>'.e($b[1]).'</h2>' : '## '.$b[1];
                    break;
                case 'p':
                    $out[] = $html ? '<p>'.$this->inline($b[1]).'</p>' : $b[1];
                    break;
                case 'ul':
                    $out[] = $html
                        ? '<ul>'.collect($b[1])->map(fn ($li) => '<li>'.$this->inline($li).'</li>')->implode('').'</ul>'
                        : collect($b[1])->map(fn ($li) => '- '.$li)->implode("\n");
                    break;
                case 'cta':
                    $text = 'Vrei să rezolvi lucrarea fără bătăi de cap? Alege ziua și ora direct pe [ompriceput.ro/cerere-deviz](/cerere-deviz) și vii la noi cu o cerere gata completată. Venim seara sau în weekend.';
                    $out[] = $html ? '<p>'.$this->inline($text).'</p>' : $text;
                    break;
                case 'related':
                    $links = collect($b[1])->map(fn ($title, $slug) => "[{$title}](/blog/{$slug})");
                    $out[] = $html
                        ? '<h2>Citește și</h2><ul>'.$links->map(fn ($l) => '<li>'.$this->inline($l).'</li>')->implode('').'</ul>'
                        : "## Citește și\n".$links->map(fn ($l) => '- '.$l)->implode("\n");
                    break;
            }
        }

        return implode($html ? "\n" : "\n\n", $out);
    }

    /** Escapeaza HTML si transforma [text](url) in link. */
    private function inline(string $text): string
    {
        $text = e($text);

        return preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);
    }

    private function articles(): array
    {
        return [
            // 1 -----------------------------------------------------------------
            [
                'slug' => 'montaj-suport-tv-pe-perete-ghid-complet',
                'title' => 'Montaj Suport TV pe Perete: Ghid Complet pentru o Fixare Sigură',
                'excerpt' => 'Cum alegi suportul, ce perete rezistă și cum se montează corect un televizor, ca să stea sigur ani la rând.',
                'keyword' => 'montaj suport tv',
                'meta_title' => 'Montaj Suport TV pe Perete | Ghid și Preț',
                'meta_description' => 'Ghid de montaj suport TV pe perete: tipuri de suport, ce dibluri alegi, greșeli de evitat și cât costește manopera (de la 80 lei).',
                'alt' => 'Meseriaș care montează un suport TV pe perete într-un living',
                'tags' => ['montaj suport tv', 'montaj tv perete', 'handyman', 'montaj'],
                'blocks' => [
                    ['p', 'Un televizor pe perete eliberează spațiu și arată mai bine decât unul pe comodă. Dar un TV de câteva zeci de kilograme prins prost poate cădea, iar reparația costă mult mai mult decât montajul corect. Iată ce trebuie să știi înainte de a începe.'],
                    ['h2', 'Ce tip de suport alegi'],
                    ['ul', [
                        'Suport fix: ecranul stă lipit de perete, este cel mai stabil și mai ieftin.',
                        'Suport înclinabil: permite reglarea unghiului, util dacă televizorul e montat mai sus.',
                        'Suport mobil cu brațe: ecranul se rotește și se trage spre tine, bun pentru colțuri sau camere mari.',
                    ]],
                    ['p', 'Verifică întotdeauna două lucruri: greutatea maximă admisă de suport și standardul VESA al televizorului (distanța dintre găurile din spate). Dacă nu se potrivesc, suportul nu se prinde sau nu rezistă.'],
                    ['h2', 'Din ce este făcut peretele'],
                    ['p', 'Aici se fac cele mai multe greșeli. Tipul de perete decide ce fixare folosești:'],
                    ['ul', [
                        'Beton sau cărămidă plină: dibluri și șuruburi de dimensiune potrivită, fixare foarte sigură.',
                        'Cărămidă cu goluri sau BCA: dibluri speciale, pentru că materialul se sfărâmă ușor.',
                        'Rigips: nu se prinde direct. Se folosește fixare în profilele metalice sau în plăci de lemn ascunse în perete, sau ancore speciale, doar pentru televizoare ușoare.',
                    ]],
                    ['h2', 'Cum se montează corect'],
                    ['ul', [
                        'Alegi înălțimea: centrul ecranului la nivelul ochilor când stai așezat, de obicei 100–120 cm de la podea în living.',
                        'Marchezi găurile cu poloboc (nivelă), ca suportul să iasă drept.',
                        'Găurești cu burghiul potrivit și pui dibluri adecvate peretelui.',
                        'Fixezi placa de perete, apoi atașezi brațele de televizor.',
                        'Treci cablurile înainte de a agăța ecranul și verifici că se agață complet, cu zăvorul închis.',
                    ]],
                    ['h2', 'Greșeli frecvente'],
                    ['ul', [
                        'Dibluri alese după ochi, nu după tipul de perete.',
                        'Montaj direct în rigips, fără fixare în structură.',
                        'Suport prea slab pentru greutatea televizorului.',
                        'Ecran montat prea sus, care obosește gâtul la vizionare.',
                    ]],
                    ['h2', 'Cât costește montajul'],
                    ['p', 'Manopera pentru un suport TV pe perete (beton sau cărămidă) este în jur de 80–130 lei, iar lucrarea durează în medie o oră. Suportul nu este inclus. La aceasta se adaugă taxa de deplasare de 50 lei, care se scade din lucrare dacă ne încredințezi montajul.'],
                    ['cta'],
                    ['related', [
                        'montaj-mobila-la-domiciliu-rapid-corect-si-fara-batai-de-cap' => 'Montaj Mobilă la Domiciliu: Rapid, Corect și Fără Bătăi de Cap',
                        'montaj-rafturi-si-polite-ghid' => 'Montaj Rafturi și Polițe: Cum Le Prinzi Corect de Perete',
                    ]],
                ],
                'faq' => [
                    ['Se poate monta un televizor pe rigips?', 'Da, dar nu direct în placă. Se fixează în profilele metalice sau în plăci de lemn din spatele rigipsului. Fără structură, doar televizoarele foarte ușoare se pot monta cu ancore speciale.'],
                    ['Cât durează montajul unui suport TV?', 'De obicei în jur de o oră, inclusiv marcarea, găurirea, fixarea și verificarea. Poate dura mai mult dacă peretele este dificil sau cablurile trebuie ascunse.'],
                ],
            ],

            // 2 -----------------------------------------------------------------
            [
                'slug' => 'montaj-lustre-corpuri-iluminat',
                'title' => 'Montaj Lustre și Corpuri de Iluminat: Ce Trebuie să Știi Înainte să Începi',
                'excerpt' => 'Cum se montează în siguranță o lustră sau o plafonieră, ce lucrări poți face singur și când ai nevoie de ajutor.',
                'keyword' => 'montaj lustre',
                'meta_title' => 'Montaj Lustre și Corpuri de Iluminat | Preț',
                'meta_description' => 'Montaj lustre, plafoniere și aplice: pași, siguranță, greșeli frecvente și prețuri de manoperă (de la 60 lei pentru un corp standard).',
                'alt' => 'Montaj lustră pe tavan într-un living',
                'tags' => ['montaj lustre', 'corpuri de iluminat', 'electric', 'handyman'],
                'blocks' => [
                    ['p', 'Schimbarea unei lustre sau a unei plafoniere pare simplă, dar implică lucru cu curentul electric și, în cazul corpurilor grele, o fixare care trebuie să reziste în timp. Iată cum se face corect.'],
                    ['h2', 'Siguranța înainte de orice'],
                    ['ul', [
                        'Oprește siguranța circuitului respectiv din tablou, nu doar întrerupătorul din perete.',
                        'Verifică lipsa tensiunii cu un creion de tensiune sau un multimetru.',
                        'Folosește o scară stabilă și lucrează cu cineva care să țină corpul greu în timp ce îl conectezi.',
                    ]],
                    ['h2', 'Tipuri de corpuri și dificultatea montajului'],
                    ['ul', [
                        'Aplice și plafoniere standard: se prind în câteva șuruburi pe suportul din tavan sau perete.',
                        'Lustre mari, cu multe elemente: necesită asamblare și un cârlig de tavan dimensionat pentru greutate.',
                        'Ventilatoare de tavan: cele mai pretențioase, pentru că vibrează și au nevoie de o fixare foarte solidă.',
                    ]],
                    ['h2', 'Pașii unui montaj corect'],
                    ['ul', [
                        'Demontezi corpul vechi și etichetezi firele dacă nu sunt evidente.',
                        'Verifici cârligul sau suportul din tavan: trebuie să reziste la greutatea noului corp.',
                        'Conectezi firele cu conectori rapizi (faza, nulul și, unde există, conductorul de protecție).',
                        'Fixezi corpul, montezi becurile și redai tensiunea.',
                        'Testezi funcționarea și verifici că nu se încălzește anormal.',
                    ]],
                    ['h2', 'Când nu este recomandat să lucrezi singur'],
                    ['p', 'Dacă instalația are fire deteriorate, dacă nu ai conductor de protecție, dacă siguranțele sar sau dacă trebuie modificat circuitul, lasă lucrarea unui electrician autorizat. Lucrările la tabloul electric și la instalația fixă se fac doar de persoane atestate. La OmPriceput montăm corpuri de iluminat pe instalația existentă.'],
                    ['h2', 'Cât costește montajul'],
                    ['ul', [
                        'Corp de iluminat standard (aplică, plafonieră, lustră simplă): 60–90 lei.',
                        'Lustră complexă sau ventilator de tavan: 120–200 lei.',
                        'Taxa de deplasare, 50 lei, se scade din lucrare dacă ne încredințezi montajul.',
                    ]],
                    ['cta'],
                    ['related', [
                        'schimbare-prize-si-intrerupatoare' => 'Schimbare Prize și Întrerupătoare: Ce Poți Face și Când Chemi un Specialist',
                        'handyman-la-domiciliu-ce-inseamna' => 'Handyman la Domiciliu: Ce Înseamnă și Când Merită',
                    ]],
                ],
                'faq' => [
                    ['Pot monta singur o plafonieră?', 'Da, dacă instalația este în stare bună și oprești siguranța circuitului. Pentru corpuri grele sau dacă ai dubii despre firele din tavan, este mai sigur să ceri ajutor.'],
                    ['Cât durează montajul unei lustre?', 'Un corp simplu se montează în 20–30 de minute. O lustră complexă sau un ventilator de tavan poate dura 1–2 ore.'],
                ],
            ],

            // 3 -----------------------------------------------------------------
            [
                'slug' => 'schimbare-prize-si-intrerupatoare',
                'title' => 'Schimbare Prize și Întrerupătoare: Ce Poți Face și Când Chemi un Specialist',
                'excerpt' => 'Ghid pentru înlocuirea prizelor și a întrerupătoarelor: cum se face în siguranță, ce semne arată o problemă și cât costă.',
                'keyword' => 'schimbare prize',
                'meta_title' => 'Schimbare Prize și Întrerupătoare | Preț și Ghid',
                'meta_description' => 'Cum se schimbă o priză sau un întrerupător, când este nevoie de specialist și cât costă: manoperă de la 35 lei, programare online seara sau în weekend.',
                'alt' => 'Înlocuire priză electrică într-un apartament',
                'tags' => ['schimbare prize', 'întrerupătoare', 'electric', 'reparații casă'],
                'blocks' => [
                    ['p', 'O priză care se încălzește, un întrerupător care scârțâie sau aparatele care nu mai pornesc sunt semne că aparatajul trebuie schimbat. Este una dintre cele mai frecvente lucrări dintr-un apartament și, făcută corect, durează puțin.'],
                    ['h2', 'Semne că trebuie înlocuite'],
                    ['ul', [
                        'Priză care se încălzește sau are urme de ardere.',
                        'Ștecherul stă slab sau iese singur.',
                        'Întrerupătorul face scântei, zgomot sau nu mai ține poziția.',
                        'Apar mirosuri de plastic încins în zona aparatului.',
                    ]],
                    ['p', 'Dacă observi urme de ardere sau miros, nu mai folosi priza și oprește siguranța circuitului până la verificare.'],
                    ['h2', 'Cum se schimbă în siguranță'],
                    ['ul', [
                        'Oprești siguranța circuitului din tablou și verifici lipsa tensiunii.',
                        'Scoți rama și aparatul, fără să tragi de fire.',
                        'Notezi sau fotografiezi conexiunile, apoi eliberezi firele.',
                        'Conectezi noul aparat respectând aceleași poziții și strângi bornele ferm.',
                        'Montezi aparatul în doză, pui rama și testezi.',
                    ]],
                    ['h2', 'Greșeli care costă'],
                    ['ul', [
                        'Lucrul fără a opri siguranța.',
                        'Borne strânse slab, care duc la încălzire.',
                        'Fire dezizolate prea mult sau prea puțin.',
                        'Aparataj ieftin pentru consumatori puternici (cuptor, boiler).',
                    ]],
                    ['h2', 'Când chemi un specialist'],
                    ['p', 'Dacă doza este deteriorată, firele sunt din aluminiu sau fragile, siguranțele sar des sau trebuie adăugat un circuit nou, problema nu mai este de aparataj. Astfel de lucrări se fac de electricieni autorizați.'],
                    ['h2', 'Cât costește'],
                    ['p', 'Montajul sau înlocuirea unei prize, a unui întrerupător sau a unei doze de aparat costă între 35 și 50 lei pe bucată, pe instalația existentă. Taxa de deplasare, 50 lei, se scade din lucrare. Dacă ai mai multe prize de schimbat, le rezolvăm într-o singură vizită.'],
                    ['cta'],
                    ['related', [
                        'montaj-lustre-corpuri-iluminat' => 'Montaj Lustre și Corpuri de Iluminat: Ce Trebuie să Știi Înainte să Începi',
                        'top-reparatii-amanate-in-locuinta' => 'Top Reparații Amânate în Locuință',
                    ]],
                ],
                'faq' => [
                    ['Cât costă schimbarea unei prize?', 'Între 35 și 50 lei pe bucată, pe instalația existentă, plus taxa de deplasare care se scade din lucrare.'],
                    ['Pot schimba singur o priză?', 'Poți, dacă oprești siguranța și verifici lipsa tensiunii. Dacă vezi urme de ardere, fire deteriorate sau siguranțe care sar, cheamă un electrician autorizat.'],
                ],
            ],

            // 4 -----------------------------------------------------------------
            [
                'slug' => 'montaj-rafturi-si-polite-ghid',
                'title' => 'Montaj Rafturi și Polițe: Cum Le Prinzi Corect de Perete',
                'excerpt' => 'Cum alegi diblurile potrivite și cum fixezi rafturi, polițe, oglinzi și tablouri ca să reziste fără să se desprindă.',
                'keyword' => 'montaj rafturi',
                'meta_title' => 'Montaj Rafturi și Polițe pe Perete | Ghid și Preț',
                'meta_description' => 'Ghid de montaj rafturi și polițe: tipuri de perete, dibluri potrivite, greșeli frecvente și manoperă de la 35 lei. Programare online seara sau în weekend.',
                'alt' => 'Raft montat pe perete cu nivelă',
                'tags' => ['montaj rafturi', 'montaj polițe', 'montaj oglindă', 'handyman'],
                'blocks' => [
                    ['p', 'Un raft prins prost cade, strică peretele și, mai rău, poate răni pe cineva. Diferența dintre un raft care ține zeci de ani și unul care cedează în luni este aproape întotdeauna tipul de diblu ales pentru perete.'],
                    ['h2', 'Mai întâi, ce perete ai'],
                    ['ul', [
                        'Beton și cărămidă plină: dibluri de plastic sau metalice, rezistență mare.',
                        'Cărămidă cu goluri sau BCA: dibluri speciale pentru goluri sau chimice.',
                        'Rigips: dibluri de tip fluture sau metalice pentru rigips, iar pentru greutăți mari, fixare în structură.',
                    ]],
                    ['h2', 'Cum montezi corect un raft'],
                    ['ul', [
                        'Alegi poziția și verifici cu detector sau cu ciocanul unde se află țevi și cabluri.',
                        'Marchezi cu poloboc, ca raftul să fie drept.',
                        'Găurești cu burghiul potrivit tipului de perete.',
                        'Pui diblurile, fixezi consolele și așezi raftul.',
                        'Testezi cu greutate înainte de a pune obiectele de valoare.',
                    ]],
                    ['h2', 'Greșeli frecvente'],
                    ['ul', [
                        'Dibluri prea mici pentru greutatea raftului.',
                        'Găurire fără a verifica traseul cablurilor și al țevilor.',
                        'Raft strâmb, pentru că nu s-a folosit nivela.',
                        'Rafturi grele pe rigips, fără fixare în structură.',
                    ]],
                    ['h2', 'Oglinzi, tablouri și galerii de perdele'],
                    ['p', 'Aceleași reguli se aplică și acestora: greutatea decide fixarea. O oglindă mare sau o galerie dublă are nevoie de mai multe puncte de prindere, nu de un singur cui.'],
                    ['h2', 'Cât costă'],
                    ['ul', [
                        'Oglindă, tablou sau raft mic: 35–60 lei.',
                        'Galerie de perdele (simplă sau dublă): 50–80 lei.',
                        'Corp suspendat de bucătărie sau baie: 60–90 lei.',
                    ]],
                    ['p', 'Taxa de deplasare, 50 lei, se scade din lucrare. Dacă ai mai multe de montat, le facem într-o singură vizită.'],
                    ['cta'],
                    ['related', [
                        'montaj-suport-tv-pe-perete-ghid-complet' => 'Montaj Suport TV pe Perete: Ghid Complet pentru o Fixare Sigură',
                        'mici-reparatii-la-domiciliu' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid',
                    ]],
                ],
                'faq' => [
                    ['Cât de mult poate ține un raft pe rigips?', 'Depinde de dibluri și de grosimea plăcii. Pentru greutăți mari trebuie fixat în structura de sub rigips, nu doar în placă.'],
                    ['Cât costă montajul unui raft?', 'Între 35 și 60 lei pentru un raft mic, o oglindă sau un tablou, plus taxa de deplasare care se scade din lucrare.'],
                ],
            ],

            // 5 -----------------------------------------------------------------
            [
                'slug' => 'mici-reparatii-la-domiciliu',
                'title' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid și Cât Costă',
                'excerpt' => 'Lista lucrărilor mici care se rezolvă într-o singură vizită: yală, termopan, silicon, rigips, balamale.',
                'keyword' => 'mici reparații la domiciliu',
                'meta_title' => 'Mici Reparații la Domiciliu | Servicii și Prețuri',
                'meta_description' => 'Mici reparații în casă: schimbat yală, reglaj termopan, silicon, găuri în rigips, balamale. Prețuri clare și programare online.',
                'alt' => 'Reglaj balamale la un corp de mobilier',
                'tags' => ['mici reparații', 'reparații domiciliu', 'handyman', 'reparații casă'],
                'blocks' => [
                    ['p', 'Mărunțișurile din casă se adună: o ușă care nu se mai închide, un termopan care trage, silicon înnegrit în baie. Fiecare durează puțin, dar cere scule și experiență. Iată ce se rezolvă de obicei într-o singură vizită.'],
                    ['h2', 'Cele mai cerute mici reparații'],
                    ['ul', [
                        'Schimbare butuc sau broască la ușă: 60–90 lei.',
                        'Reglaj feronerie la ferestre și uși din termopan: 50–80 lei.',
                        'Înlocuire sau reglare balamale la mobilier: 25–40 lei pe balama.',
                        'Îndepărtare și refacere silicon la cadă sau duș: 30–45 lei pe metru liniar.',
                        'Reparație gaură în rigips (petic și glet): 70–120 lei.',
                        'Montaj plasă de insecte: 40–60 lei.',
                    ]],
                    ['h2', 'Reglajul termopanului: un serviciu subestimat'],
                    ['p', 'Multe ferestre care „trag" nu au nevoie de înlocuire, ci de reglaj. O feronerie reglată corect oprește curenții de aer, face geamul să se închidă ușor și prelungește viața ferestrei. Se face de obicei la schimbarea sezonului.'],
                    ['h2', 'Siliconul din baie'],
                    ['p', 'Siliconul vechi, negru sau crăpat, lasă apa să treacă în spatele căzii sau al faianței și duce la mucegai. Refacerea lui înseamnă curățare, dezinfectare și aplicare de silicon sanitar nou, nu doar un strat peste cel vechi.'],
                    ['h2', 'Cum economisești'],
                    ['ul', [
                        'Strânge mai multe lucrări mici și rezolvă-le în aceeași vizită.',
                        'Pregătește o listă înainte de programare.',
                        'Trimite poze cu problema, ca să venim cu piesele potrivite.',
                    ]],
                    ['p', 'Când sunt multe sarcini mici, se poate aplica și tariful orar de 80–120 lei. Taxa de deplasare, 50 lei, se scade din lucrare.'],
                    ['cta'],
                    ['related', [
                        'handyman-la-domiciliu-ce-inseamna' => 'Handyman la Domiciliu: Ce Înseamnă și Când Merită',
                        'top-reparatii-amanate-in-locuinta' => 'Top Reparații Amânate în Locuință',
                    ]],
                ],
                'faq' => [
                    ['Pot rezolva mai multe mici reparații într-o singură vizită?', 'Da. Este chiar varianta cea mai avantajoasă: o singură deplasare, iar lucrările se fac una după alta.'],
                    ['Cât costă schimbarea unei yale?', 'Între 60 și 90 lei manoperă, pe ușa existentă. Butucul nu este inclus, dar îl putem aduce.'],
                ],
            ],

            // 6 -----------------------------------------------------------------
            [
                'slug' => 'handyman-la-domiciliu-ce-inseamna',
                'title' => 'Handyman la Domiciliu: Ce Înseamnă și Când Merită Să Apelezi la Unul',
                'excerpt' => 'Ce face un handyman, ce lucrări acoperă și cum te poate scuti de mai multe vizite ale unor meseriași diferiți.',
                'keyword' => 'handyman la domiciliu',
                'meta_title' => 'Handyman la Domiciliu | Ce Face și Cât Costă',
                'meta_description' => 'Ce este un handyman și când merită: montaj, mici reparații, electrice și sanitare simple. Tarif orar 80–120 lei, programare online.',
                'alt' => 'Handyman cu trusă de scule în fața unei uși',
                'tags' => ['handyman', 'handyman la domiciliu', 'meseriaș', 'servicii casă'],
                'blocks' => [
                    ['p', 'Un handyman este omul care rezolvă lucrările mici și medii din casă, fără să chemi câte un meseriaș pentru fiecare. Nu înlocuiește un constructor sau un instalator autorizat pentru lucrări mari, dar acoperă tot ce se află între ele.'],
                    ['h2', 'Ce face un handyman'],
                    ['ul', [
                        'Montaj de mobilă, rafturi, oglinzi, suporturi TV și galerii de perdele.',
                        'Mici reparații: yale, balamale, termopan, silicon, găuri în rigips.',
                        'Aparataj electric pe instalația existentă: prize, întrerupătoare, corpuri de iluminat.',
                        'Mici lucrări sanitare: baterii, racorduri, desfundări simple.',
                        'Zugrăveli și retușuri de mici dimensiuni.',
                    ]],
                    ['h2', 'Când merită'],
                    ['p', 'Un handyman este varianta bună când ai o listă de lucruri mici amânate, când vrei o singură vizită în loc de patru sau când nu știi ce fel de meseriaș ți-ar trebui. Plătești manopera pe lucrare sau pe oră, fără a negocia cu fiecare specialist.'],
                    ['h2', 'Ce nu face un handyman'],
                    ['ul', [
                        'Lucrări la tabloul electric, branșamente sau refacerea instalației: acestea cer electrician autorizat.',
                        'Instalații de gaze: doar firme autorizate.',
                        'Lucrări de structură, demolări sau modificări majore.',
                    ]],
                    ['h2', 'Cum se calculează prețul'],
                    ['p', 'Pentru lucrări clare se stabilește un preț pe lucrare (de exemplu, un suport TV, 80–130 lei). Pentru mai multe sarcini mici se aplică un tarif orar de 80–120 lei. Taxa de deplasare, 50 lei, se scade din lucrare dacă ne încredințezi munca.'],
                    ['cta'],
                    ['related', [
                        'cum-alegi-un-meserias-de-incredere' => 'Cum Alegi un Meseriaș de Încredere',
                        'mici-reparatii-la-domiciliu' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid și Cât Costă',
                    ]],
                ],
                'faq' => [
                    ['Care este diferența dintre un handyman și un meseriaș?', 'Un meseriaș este specializat pe o meserie (electrician, instalator). Handyman-ul acoperă mai multe lucrări mici și medii, ideal pentru mărunțișurile din casă.'],
                    ['Cât costă un handyman pe oră?', 'Tariful orar este de 80–120 lei, aplicat când sunt multe sarcini mici. Pentru lucrări clare, prețul se stabilește pe lucrare.'],
                ],
            ],

            // 7 -----------------------------------------------------------------
            [
                'slug' => 'cum-alegi-un-meserias-de-incredere',
                'title' => 'Cum Alegi un Meseriaș de Încredere: 8 Verificări Înainte de Comandă',
                'excerpt' => 'Ce să ceri, ce să verifici și ce semnale de alarmă să recunoști înainte să dai o lucrare unui meseriaș.',
                'keyword' => 'cum alegi un meseriaș',
                'meta_title' => 'Cum Alegi un Meseriaș de Încredere | 8 Verificări',
                'meta_description' => 'Cum alegi un meseriaș bun: preț scris, recenzii, poze cu lucrări, garanție și semnale de alarmă. 8 verificări înainte de comandă.',
                'alt' => 'Client și meseriaș discutând devizul lucrării',
                'tags' => ['cum alegi un meseriaș', 'meseriaș de încredere', 'deviz', 'sfaturi'],
                'blocks' => [
                    ['p', 'Un meseriaș bun îți economisește bani și nervi. Unul slab îți aduce lucrări refăcute și costuri neprevăzute. Iată ce verifici înainte să dai comanda.'],
                    ['h2', '8 verificări înainte de comandă'],
                    ['ul', [
                        'Cere un deviz scris, cu manoperă și materiale separate, înainte de începerea lucrării.',
                        'Verifică recenziile reale (Google, recomandări) și cere poze cu lucrări similare.',
                        'Întreabă clar ce este inclus: materiale, transport, curățenie, deplasare.',
                        'Verifică dacă lucrările cerute necesită autorizare (instalații electrice și de gaze).',
                        'Întreabă despre garanția lucrării și cum se rezolvă o eventuală problemă ulterioară.',
                        'Stabilește data de început și durata estimată.',
                        'Nu plăti integral în avans, ci eșalonat sau la finalizare.',
                        'Observă cum comunică: răspunde la timp, explică, nu promite imposibilul.',
                    ]],
                    ['h2', 'Semnale de alarmă'],
                    ['ul', [
                        'Preț „la ochi" fără să vadă lucrarea sau fotografiile.',
                        'Refuză devizul scris.',
                        'Cere avans mare, în numerar, fără nicio dovadă.',
                        'Ține neapărat să înceapă imediat, fără să înțeleagă problema.',
                    ]],
                    ['h2', 'De ce contează devizul scris'],
                    ['p', 'Devizul este singura garanție că știi ce plătești. Scrie ce se face, ce materiale intră, cât costă manopera și ce se întâmplă dacă apar lucrări în plus. La OmPriceput se întocmește după constatarea la fața locului, iar taxa de deplasare se scade din deviz dacă ne încredințezi lucrarea.'],
                    ['cta'],
                    ['related', [
                        'handyman-la-domiciliu-ce-inseamna' => 'Handyman la Domiciliu: Ce Înseamnă și Când Merită',
                        'top-reparatii-amanate-in-locuinta' => 'Top Reparații Amânate în Locuință',
                    ]],
                ],
                'faq' => [
                    ['Trebuie să cer deviz scris pentru o lucrare mică?', 'Este recomandat. Chiar și pentru lucrări mici, un mesaj scris cu prețul și ce este inclus evită neînțelegerile.'],
                    ['Cât avans este normal?', 'Pentru lucrări mici, de obicei plata se face la final. Pentru lucrări mari, avansul se limitează la materiale și se eșalonează restul.'],
                ],
            ],

            // 8 -----------------------------------------------------------------
            [
                'slug' => 'top-reparatii-amanate-in-locuinta',
                'title' => 'Top Reparații Amânate în Locuință: 8 Lucruri Pe Care Nu Mai Merită Să Le Lași',
                'excerpt' => 'Mărunțișurile amânate se transformă în probleme scumpe. Iată lista celor 8 reparații pe care merită să le rezolvi acum.',
                'keyword' => 'reparații amânate casă',
                'meta_title' => 'Top Reparații Amânate în Casă | 8 Lucruri de Rezolvat',
                'meta_description' => 'Cele 8 reparații mici amânate în locuință care se transformă în probleme mari: robinet, silicon, termopan, prize și altele.',
                'alt' => 'Listă de reparații mici de făcut în casă',
                'tags' => ['reparații amânate', 'mici reparații', 'întreținere casă', 'handyman'],
                'blocks' => [
                    ['p', 'Robinetul care picură, siliconul înnegrit, priza care se încălzește: le vezi zilnic și zici „mă ocup luna viitoare". Problema este că mărunțișurile amânate costă din ce în ce mai mult. Iată lista pe care ar merita s-o rezolvi.'],
                    ['h2', 'Cele 8 reparații pe care nu mai merită să le amâni'],
                    ['ul', [
                        'Robinet sau baterie care picură: apa irosită se vede în factură și poate deteriora mobilierul.',
                        'Silicon deteriorat la cadă sau duș: lasă apa să pătrundă și duce la mucegai.',
                        'Priză sau întrerupător care se încălzește sau face scântei: risc de incendiu.',
                        'Termopan care trage sau se închide greu: pierderi de căldură și uzură.',
                        'Rezervor WC care curge continuu: consum mare de apă.',
                        'Balamale slăbite la mobilier: ușile cad și se deteriorează.',
                        'Găuri în rigips sau tencuială crăpată: se extind în timp.',
                        'Corpuri de iluminat defecte sau rămase neinstalate: lumină proastă și improvizații periculoase.',
                    ]],
                    ['h2', 'De ce costă mai mult dacă aștepți'],
                    ['p', 'Un racord care picură ajunge să strice sub-chiuveta, iar o baterie veche poate umezi pereții. Un strat de silicon refăcut la timp costă puțin, iar mucegaiul instalat cere tratament și zugrăvire. Cu cât aștepți, cu atât reparația se complică.'],
                    ['h2', 'Cum le rezolvi dintr-o singură mișcare'],
                    ['p', 'Fă o listă cu toate lucrările mici, adaugă poze și rezolvă-le într-o singură vizită. Plătești o singură deplasare (50 lei, care se scade din lucrare), iar manopera este clară: de exemplu, silicon 30–45 lei pe metru, reglaj termopan 50–80 lei, priză 35–50 lei.'],
                    ['cta'],
                    ['related', [
                        'mici-reparatii-la-domiciliu' => 'Mici Reparații la Domiciliu: Ce Poți Rezolva Rapid și Cât Costă',
                        'schimbare-prize-si-intrerupatoare' => 'Schimbare Prize și Întrerupătoare: Ce Poți Face și Când Chemi un Specialist',
                    ]],
                ],
                'faq' => [
                    ['Care reparație este cea mai urgentă?', 'Orice problemă de siguranță electrică (priză care se încălzește, miros de ars) și orice scurgere de apă. Restul pot aștepta puțin, dar nu luni de zile.'],
                    ['Pot rezolva toate lucrările într-o singură zi?', 'De obicei da, dacă sunt lucrări mici. Trimite lista și pozele, iar estimăm durata înainte de vizită.'],
                ],
            ],
        ];
    }
}