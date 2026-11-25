<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rute Ziggy
    |--------------------------------------------------------------------------
    |
    | Toate rutele sunt trimise către client la prima încărcare (@routes în
    | resources/views/app.blade.php), inclusiv pentru vizitatorii neautentificați.
    | Filtrarea după un grup «public» părea o optimizare, dar loginul este o
    | visită Inertia (XHR): după autentificare JS-ul nu primește un HTML nou,
    | deci rutele lipsă (profile.edit, sales.*, admin.* etc.) făceau ca
    | layoutul să arunce eroare și să apară pagina albă până la un refresh.
    |
    | Clasa route() nu se mai injectează inline (dist/route.umd.js, ~21 KB):
    | funcția se importă în resources/js/app.js și se expune pe globalThis.
    |
    */

    'skip-route-function' => true,

];
