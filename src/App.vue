<template>
  <AppHeader />

  <main class="page-content">
    <router-view />
  </main>

  <AppFooter />
</template>

<script setup>
import AppHeader from "@/components/AppHeader.vue";
import AppFooter from "@/components/AppFooter.vue";
</script>

<style>
/* ВАЖНО — основной контейнер */
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Контент растягивается, чтобы футер ушёл вниз */
.page-content {
  flex: 1;
}

/* Твои прежние стили — остаются */
#app section {
  margin-bottom: 30px;
}

/* Заголовок страницы */
.page-title {
  text-align: center;
  color: var(--accent-color);
  font-size: 38px;
  font-weight: 700;
  letter-spacing: 0.5px;
  margin-bottom: 40px;
  text-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
}

@media (max-width: 600px) {
  .page-title {
    font-size: 30px;
  }
}
</style>
