<script setup>
const logoPath = '/branding/logo-header.png';
</script>

<template>
    <img v-bind="$attrs" :src="logoPath" alt="Om Priceput" class="h-auto w-auto object-contain" />
</template>
