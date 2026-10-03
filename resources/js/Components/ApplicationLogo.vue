<script setup>
const logoPath = '/branding/op-logo.png';
</script>

<template>
    <img v-bind="$attrs" :src="logoPath" alt="Omul Potrivit" class="h-auto w-auto object-contain" />
</template>
