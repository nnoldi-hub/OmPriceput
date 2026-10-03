<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pachete de intretinere - Omul Potrivit
    |--------------------------------------------------------------------------
    |
    | Pachete afisate pe site-ul public. Sunt abonamente de intretinere cu
    | pret fix (fara TVA aplicat separat), pentru clientii care nu vor sa
    | cheme un meșter la fiecare problema.
    |
    */

    'tiers' => [
        [
            'key' => 'basic',
            'name' => 'Basic',
            'price_from' => 1190,
            'vizite_an' => 2,
            'timp_raspuns' => '72 ore',
            'prioritate' => 'Standard',
            'features' => [
                '2 vizite de intretinere pe an',
                'Verificare instalatii electrice si sanitare',
                'Inlocuirea consumabilelor uzuale',
                'Discount 5% la piesele de schimb',
                'Raport scris dupa fiecare vizita',
            ],
        ],
        [
            'key' => 'plus',
            'name' => 'Plus',
            'price_from' => 2490,
            'vizite_an' => 4,
            'timp_raspuns' => '24 ore',
            'prioritate' => 'Prioritar',
            'features' => [
                '4 vizite pe an, programare la ora exacta',
                'Verificare completa: electric, sanit, scari, calorifer',
                'Reparatii mici incluse, manopera',
                'Discount 10% la piese si consumabile',
                'Interventii de urgenta cu pret preferential',
            ],
            'highlight' => true,
        ],
        [
            'key' => 'premium',
            'name' => 'Premium',
            'price_from' => 4490,
            'vizite_an' => 8,
            'timp_raspuns' => '12 ore',
            'prioritate' => 'Maxim',
            'features' => [
                '8 vizite pe an, inclusiv seara',
                'Mentenanta completa pentru casa sau apartamentul',
                'Reparatii mici fara cost de manopera',
                'Discount 15% la toate produsele din magazin',
                'Un singur meșter de contact pentru toate cererile',
            ],
        ],
    ],

];