<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-slate-900">Termeni si conditii</h1>
            <p class="mt-4 text-sm text-slate-500">Ultima actualizare: 10 septembrie 2026</p>
            <div class="prose prose-slate mt-8 max-w-none">
                <h2>1. Informatii generale</h2>
                <p>Platforma Om Priceput prezinta servicii de reparatii, montaje si intretinere pentru locuinte, apartamente si spatii comerciale.</p>
                <h2>2. Cereri de deviz si comenzi</h2>
                <p>Informatiile transmise prin site reprezinta o solicitare de deviz si nu o comanda ferma. Devizul se intocmeste dupa constatarea la locul lucrarii si devine ferma doar dupa acceptarea sa de catre client.</p>
                <h2>3. Preturi si termene</h2>
                <p>Preturile comunicate sunt estimative pana la efectuarea constatarii. Orice modificare de pret, termen sau configuratie va fi comunicata in timp util, inainte de executarea lucrarilor.</p>
                <h2>4. Responsabilitati</h2>
                <p>Clientul furnizeaza informatii corecte si asigura accesul la locul lucrarii. Om Priceput executa lucrarile asumate cu atentie, conform materialelor si manoperei din devizul acceptat.</p>
                <h2>5. Date personale</h2>
                <p>Prelucrarea datelor personale se realizeaza conform <a :href="route('public.privacy')">Politicii de confidentialitate</a>.</p>
                <h2>6. Contact</h2>
                <p>Pentru clarificari, folositi pagina <a :href="route('public.contact')">Contact</a>.</p>
            </div>
        </section>
    </PublicLayout>
</template>
