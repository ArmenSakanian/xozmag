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

import { watch } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();

const V = "1"; // меняй на "2" если iPhone упёрся в кэш

function setAppleTitle(title) {
  const meta = document.querySelector('meta[name="apple-mobile-web-app-title"]');
  if (meta) meta.setAttribute("content", title);
}

function applyA2HSAssets() {
  const p = window.location.pathname || "/";

  // ✅ админ ТОЛЬКО если строго /admin или /admin/...
  const isAdmin = p === "/admin" || p.startsWith("/admin/");

  const manifest = document.getElementById("app-manifest");
  const appleIcon = document.getElementById("app-apple-icon");
  const theme = document.getElementById("app-theme-color");

  if (manifest) manifest.setAttribute("href", (isAdmin ? "/admin.webmanifest" : "/site.webmanifest") + "?v=" + V);
  if (appleIcon) appleIcon.setAttribute("href", (isAdmin ? "/icons/admin-apple-180.png" : "/icons/app-apple-180.png") + "?v=" + V);
  if (theme) theme.setAttribute("content", isAdmin ? "#111827" : "#0ea5e9");

  setAppleTitle(isAdmin ? "Все Для дома — Админ" : "Все Для дома");
}

// реагируем на смену роутов
watch(
  () => route.fullPath,
  () => applyA2HSAssets(),
  { immediate: true }
);
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
.page-title  {
  font-size: 30px;
}

 }

</style>
