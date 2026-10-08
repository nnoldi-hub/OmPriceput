{{--
    Conținut de rezervă afișat în #app până când Vue preia randarea.
    Oferă H1, descriere și linkuri interne crawlerr-elor care nu execută JavaScript.
    Este înlocuit automat de aplicație la montare (mount), fără să rămână în DOM.
--}}
<div class="seo-fallback">
    <h1>{{ $seo->fullTitle() }}</h1>
    <p>{{ $seo->description }}</p>

    <h2>Servicii</h2>
    <nav aria-label="Servicii">
        <a href="{{ route('public.services') }}">Electrician</a>
        <a href="{{ route('public.services') }}">Instalator</a>
        <a href="{{ route('public.services') }}">Zugrăveli</a>
        <a href="{{ route('public.services') }}">Montaj mobilier și corpuri de iluminat</a>
    </nav>

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
