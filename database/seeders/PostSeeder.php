<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Cat costa un mester si de ce conteaza atat de mult constatarea',
                'excerpt' => 'Pretul orar difera intre orase si meserii, iar estimarea online fara vizita la locatie poate fi cu 30-50% sub realitate.',
                'body' => "Un mester electrician lucreaza in medie cu 100-140 lei pe ora in marile orase, iar un instalator sanitar se apropie de aceleasi valori. In orase mai mici preturile sunt cu 20-30% mai mici.\n\nProblema nu este tariful, ci estimarea. O lucrare presupune adesea si demontare, si materiale care nu se vad pana cand se deschide zidul sau se da jos robinetul. De aceea devizul se intocmeste dupa constatare, la locul lucrarii: asa stim exact ce materiale intra si cat timp se consuma.\n\nLa Om Priceput constatarea este gratuita daca acceptati devizul rezultat.",
                'meta_description' => 'Aflati cat costa un mester pe ora si de ce devizul se face dupa constatarea la fata locului.',
            ],
            [
                'title' => 'Cum verifici ca ai angajat un mester bun, in 5 intrebari',
                'excerpt' => 'Cele cinci intrebari pe care ar trebui sa le puneti oricui vine sa repare ceva acasa.',
                'body' => "Un mester profesionist va poate spune, fara sa caute preturi, cam ce materiale are nevoie si cat dureaza lucrarea. Daca evita intrebarile sau promite ca stie pretul final doar din telefon, este un semn ca nu a vazut problema.\n\nMai trebuie sa va spuna cine plateste in caz de daune, daca lucreaza cu piese proprii si cum este preluata garantia. La Om Priceput se lucreaza doar cu consumabile si scule proprii, iar devizul semnat de dumneavoastra este documentul de acord pentru lucrare.",
                'meta_description' => 'Cum verifici un mester bun: 5 intrebari despre pret, termen, garantie si materiale.',
            ],
            [
                'title' => 'Deviz gratuit sau estimare online? Ce inseamna concret',
                'excerpt' => "Diferenta dintre o estimare facuta pe calculator si un deviz intocmit dupa ce mesterul a vazut problema.",
                'body' => "Estimarea online este utila pentru a va face o idee despre ordinul de marime, dar nu poate include surprizele care apar in teren: tevi vechi care trebuie inlocuite, cablu deteriorat in perete, structura de tavan pe care nu se poate ancora nimic.\n\nDevizul este exact opusul estimarii: este scris dupa ce problema a fost vazuta. De aceea la Om Priceput nu va dam un pret din birou. Vedem lucrarea, va spunem ce credem ca aveti nevoie, si va livram devizul scris pe loc. Daca nu este rentabil, nu aveti nicio obligatie.",
                'meta_description' => 'Diferenta dintre estimarea online si devizul intocmit dupa constatarea la fata locului.',
            ],
        ];

        foreach ($posts as $index => $post) {
            Post::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                [
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'meta_title' => $post['title'],
                    'meta_description' => $post['meta_description'],
                    'published_at' => now()->subDays(10 - $index * 3),
                ]
            );
        }
    }
}
