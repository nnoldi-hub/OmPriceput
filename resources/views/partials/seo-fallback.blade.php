{{--
    Conținut de rezervă afișat în #app până când Vue preia randarea.
    Oferă H1, descriere, conținut relevant și linkuri interne crawlerr-elor
    care nu execută JavaScript (și vizitatorilor fără JavaScript).
    Este înlocuit automat de aplicație la montare (mount), fără să rămână în DOM.
--}}
<div class="seo-fallback">
    <h1>{{ $seo->fullTitle() }}</h1>
    <p>{{ $seo->description }}</p>

    <h2>Ce putem rezolva pentru dumneavoastră</h2>
    <p>
        Om Priceput execută lucrări de electrician, instalator, zugrăveli și montaj pentru casă, apartament și spații de birouri:
        reparații ale scurgerilor și robinetelor, înlocuirea tablourilor și a prizelor, montarea corpurilor de iluminat, a mobilierului,
        oglinzilor și plăcilor, precum și zugrăveală cu materiale lavabile, cu mobilierul protejat și curățenia făcută la finalul lucrării.
        Intervențiile mici se rezolvă de regulă în aceeași zi, iar lucrările mai ample se programează în intervalul care vă convine.
    </p>
    <nav aria-label="Servicii">
        <a href="{{ route('public.services') }}">Electrician</a>
        <a href="{{ route('public.services') }}">Instalator</a>
        <a href="{{ route('public.services') }}">Zugrăveli</a>
        <a href="{{ route('public.services') }}">Montaj mobilier și corpuri de iluminat</a>
    </nav>

    <h2>Cum decurge o lucrare</h2>
    <p>
        Trimiteți o cerere prin formularul de deviz sau telefonic, atașând câteva fotografii ale problemei. Venim la constatare pentru a
        identifica defecțiunea și materialele necesare, apoi primiți un deviz scris, cu manopera și materialele prezentate separat.
        După acceptarea devizului programăm intervenția, executăm lucrarea și verificăm împreună rezultatul înainte de finalizare.
        Prețul afișat în deviz este prețul final al lucrării.
    </p>

    <h2>De ce ne aleg clienții</h2>
    <p>
        Răspundem la cereri în maximum 24 de ore și programăm constatarea în următoarea zi lucrătoare. Lucrăm cu materiale, consumabile
        și scule proprii, facturăm atât persoane fizice, cât și firme și păstrăm istoricul intervențiilor, astfel încât să ne puteți
        contacta oricând pentru o reparație ulterioară sau pentru întreținere periodică. Pentru locuințele care au nevoie de verificări
        regulate, oferim pachete de întreținere cu un număr fix de vizite pe an.
    </p>

    <h2>Întrebări frecvente</h2>
    <p>
        Da, primiți devizul în scris înainte de a începe orice lucrare, iar manopera și materialele sunt trecute separat în document.
        Serviciile sunt facturate pentru persoane fizice și juridice, iar garanția manoperei este menționată în devizul pe care îl
        aprobați. Pentru detalii despre prețuri, termene și disponibilitate, consultați paginile de servicii, scrieți-ne pe pagina de
        contact sau cereți un deviz online, iar noi vă răspundem cu primele detalii în aceeași zi.
    </p>

    <h2>Navigare</h2>
    <nav aria-label="Navigare principală">
        <a href="{{ route('public.home') }}">Acasă</a>
        <a href="{{ route('public.about') }}">Despre noi</a>
        <a href="{{ route('public.quote') }}">Cere deviz</a>
        <a href="{{ route('public.blog.index') }}">Blog</a>
        <a href="{{ route('public.contact') }}">Contact</a>
        <a href="{{ route('public.terms') }}">Termeni și condiții</a>
        <a href="{{ route('public.privacy') }}">Politica de confidențialitate</a>
    </nav>
</div>
