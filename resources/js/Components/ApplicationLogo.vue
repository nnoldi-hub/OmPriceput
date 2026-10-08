<script setup>
const logoPath = '/branding/logo-header.png';
</script>

<template>
    <img v-bind="$attrs" :src="logoPath" alt="Om Priceput" width="512" height="512" class="h-auto w-auto object-contain" />
</template>
