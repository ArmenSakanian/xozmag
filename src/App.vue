<template>
  <div id="app">
    <AppHeader />

    <main class="page-content">
      <router-view />
    </main>

    <AppFooter />
  </div>
</template>

<script setup>
import AppHeader from "@/components/AppHeader.vue";
import AppFooter from "@/components/AppFooter.vue";
</script>

<style>
/* ВАЖНО — основной контейнер */
#app {
  min-height: 100vh;       /* растягиваем на высоту экрана */
  display: flex;
  flex-direction: column;  /* элементы идут сверху вниз */
}

/* Контент растягивается, чтобы футер ушёл вниз */
.page-content {
  flex: 1;
}

/* Твои прежние стили — остаются */
#app section {
  margin-top: 30px;
}

#app section > div {
  padding: 0 10px;
}
</style>
