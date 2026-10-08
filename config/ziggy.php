<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grupe de rute Ziggy
    |--------------------------------------------------------------------------
    |
    | Vizitatorii neautentificați primesc doar rutele necesare paginilor
    | publice (și autentificării), nu întreaga hartă a aplicației. Astfel
    | HTML-ul pornit rămâne mai mic, iar scriptul inline Ziggy scade
    | de la ~42 KB la câțiva KB pentru prima încărcare.
    |
    | Clasa route() nu mai este injectată inline (dist/route.umd.js, ~21 KB):
    | funcția este importată în resources/js/app.js și expusă pe globalThis.
    |
    */

    'skip-route-function' => true,

    'groups' => [
        'public' => [
            'public.*',
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
            'dashboard',
            'client.dashboard',
        ],
    ],

];
