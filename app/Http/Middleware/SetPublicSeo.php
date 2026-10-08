<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicSeo
{
    /**
     * Titlul si descrierea paginilor publice. Rutele care nu apar aici raman noindex.
     *
     * @var array<string, array{title: string, description: string, indexed?: bool}>
     */
    private const PAGES = [
        'public.home' => [
            'title' => 'Reparații, montaje și întreținere',
            'description' => 'Om Priceput: lucrări de electrician, instalator, zugrăveli și montaj pentru casă și apartament. Trimiteți cererea și primiți devizul scris după constatarea gratuită.',
        ],
        'public.about' => [
            'title' => 'Despre noi',
            'description' => 'Om Priceput: lucrări de reparații, montaje și întreținere pentru casă și apartament. Deviz scris înainte de începerea lucrării.',
        ],
        'public.services' => [
            'title' => 'Servicii - electrician, instalator, zugrăveli, montaj',
            'description' => 'Lucrări de electrician, instalator, zugrăveli și montaj pentru casă și apartament. Constatare gratuită și deviz scris înainte de începerea lucrării.',
        ],
        'public.quote' => [
            'title' => 'Cere deviz',
            'description' => 'Trimiteți cererea de deviz: alegeți lucrările, ora și atașați câteva fotografii. Primiți devizul scris după constatarea la fața locului.',
        ],
        'public.contact' => [
            'title' => 'Contact',
            'description' => 'Contactați Om Priceput pentru reparații, montaje și întreținere. Răspundem în cel mai scurt timp.',
        ],
        'public.blog.index' => [
            'title' => 'Blog',
            'description' => 'Articole și ghiduri despre reparații, instalații electrice și sanitare, zugrăveli și întreținerea locuinței.',
        ],
        'public.terms' => [
            'title' => 'Termeni și condiții',
            'description' => 'Termenii și condițiile de utilizare a site-ului și a serviciilor Om Priceput.',
        ],
        'public.privacy' => [
            'title' => 'Politica de confidențialitate',
            'description' => 'Cum colectăm, folosim și protejăm datele personale pe site-ul Om Priceput.',
        ],
        'public.shop.index' => [
            'title' => 'Magazin online',
            'description' => 'Materiale, consumabile, scule și piese de schimb, disponibile pentru comandă online.',
        ],
        'public.shop.cart' => [
            'title' => 'Coșul de cumpărături',
            'description' => 'Finalizați comanda din magazinul Om Priceput.',
            'indexed' => false,
        ],
        'public.shop.confirmation' => [
            'title' => 'Comandă înregistrată',
            'description' => 'Confirmarea comenzii plasate în magazinul online Om Priceput.',
            'indexed' => false,
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $page = self::PAGES[$request->route()?->getName() ?? ''] ?? null;

        if ($page !== null) {
            $seo = app(Seo::class);
            $seo->title($page['title'])->description($page['description']);

            if ($page['indexed'] ?? true) {
                $seo->allowIndex();
            } else {
                $seo->noindex();
            }
        }

        return $next($request);
    }
}
