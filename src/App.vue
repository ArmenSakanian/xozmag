<template>
  <div id="app">
    <AppHeader />

    <!-- ВАШЕ ОСНОВНОЕ СОДЕРЖИМОЕ -->
    <router-view />
  </div>
</template>

<script setup>
import AppHeader from "@/components/AppHeader.vue";
</script>

