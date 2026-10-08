<script setup>
const logoPath = '/branding/logo-trim.webp';
</script>

<template>
    <img v-bind="$attrs" :src="logoPath" alt="Om Priceput" width="480" height="480" class="h-auto w-auto object-contain" />
</template>
