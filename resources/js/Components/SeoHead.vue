<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

// Titlul vine din partea de server (App\Support\Seo), ca sa fie identic
// in HTML-ul livrat crawlerelor si in timpul navigarii Inertia.
const title = computed(() => page.props.seo?.title || document.title);
</script>

<template>
    <Head :title="title" />
</template>
