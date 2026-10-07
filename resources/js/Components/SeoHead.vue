<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});

const fullTitle = `${props.title} - Om Priceput`;
</script>

<template>
    <Head :title="fullTitle">
        <meta v-if="description" name="description" :content="description" />
        <meta property="og:title" :content="fullTitle" />
        <meta v-if="description" property="og:description" :content="description" />
        <meta property="og:type" content="website" />
    </Head>
</template>
