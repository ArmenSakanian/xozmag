<template>
  <header
    class="header"
    ref="headerRef"
    :style="{ '--hdr-h': headerH + 'px' }"
  >
    <!-- ===== TOP ROW ===== -->
    <div class="header-top">
      <div class="header-top-container">
        <!-- LOGO -->
        <div class="logo">
          <a href="/">
            <img src="@/assets/logo.webp" alt="Logo" />
            <h1>Все Для Дома</h1>
          </a>
        </div>

        <!-- DESKTOP NAV -->
        <nav class="nav">
                  <router-link class="btn primary" to="catalog">Каталог</router-link>

          <a href="/catalog" class="nav-item">Каталог</a>
          <a class="nav-item" @click.prevent="scrollToSection('about')">О нас</a>
          <a class="nav-item" @click.prevent="scrollToSection('contact')">Контакты</a>
          <a class="nav-item" @click.prevent="scrollToSection('photo')">Фотографии</a>
        </nav>

        <!-- BURGER (mobile) -->
        <button
          class="burger"
          type="button"
          @click="mobileOpen = !mobileOpen"
          aria-label="Открыть меню"
        >
          <span :class="{ open: mobileOpen }"></span>
          <span :class="{ open: mobileOpen }"></span>
          <span :class="{ open: mobileOpen }"></span>
        </button>
      </div>
    </div>

    <!-- ===== BOTTOM ROW (CENTER SEARCH) ===== -->
    <!-- ❗️На Catalog не показываем -->
    <div v-if="showHeaderSearch" class="header-bottom">
      <div class="header-bottom-container">
        <div class="header-search">
<HomeSearch
  :show-category="false"
  :sync-route="false"
  catalog-path="/catalog"
/>

        </div>
      </div>
    </div>

    <!-- ===== MOBILE MENU ===== -->
    <div class="mobile-menu" :class="{ open: mobileOpen }">
      <a href="/catalog" class="mobile-item" @click="closeMenu">Каталог</a>
      <a class="mobile-item" @click.prevent="scrollToSection('about')">О нас</a>
      <a class="mobile-item" @click.prevent="scrollToSection('contact')">Контакты</a>
      <a class="mobile-item" @click.prevent="scrollToSection('photo')">Фотографии</a>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from "vue";
import { useRoute } from "vue-router";
import HomeSearch from "@/components/HomeSearch.vue";

const route = useRoute();
const mobileOpen = ref(false);

/* =========================
   HIDE SEARCH ON Catalog
========================= */
const showHeaderSearch = computed(() => {
  // прячем на /catalog и на вложенных типа /catalog/...
  return !(route.path === "/catalog" || route.path.startsWith("/catalog/"));
});

/* =========================
   CATEGORIES (from HomeSearch)
========================= */
const categories = ref([]);
function onCategoriesLoaded(arr) {
  categories.value = Array.isArray(arr) ? arr : [];
}

/* =========================
   HEADER HEIGHT (for mobile-menu top)
========================= */
const headerRef = ref(null);
const headerH = ref(0);

async function updateHeaderH() {
  await nextTick();
  headerH.value = headerRef.value?.offsetHeight || 0;
}

function handleResize() {
  updateHeaderH();
}

/* =========================
   SCROLL NAVIGATION (your logic)
========================= */
function scrollToSection(id) {
  const currentPath = route.path;

  if (currentPath !== "/") {
    mobileOpen.value = false;
    window.location.href = `/?scroll=${id}`;
    return;
  }
  doScroll(id);
}

function doScroll(id) {
  const el = document.getElementById(id);
  if (!el) return;

  const hh = headerRef.value?.offsetHeight || 0;

  window.scrollTo({
    top: el.offsetTop - hh,
    behavior: "smooth",
  });

  mobileOpen.value = false;
}

function closeMenu() {
  mobileOpen.value = false;
}

/* =========================
   WATCHERS
========================= */
watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
    updateHeaderH();
  }
);

watch(showHeaderSearch, () => updateHeaderH());
watch(mobileOpen, () => updateHeaderH());

/* =========================
   MOUNT
========================= */
onMounted(() => {
  updateHeaderH();
  window.addEventListener("resize", handleResize, { passive: true });

  const params = new URLSearchParams(window.location.search);
  const section = params.get("scroll");
  if (section) setTimeout(() => doScroll(section), 400);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
});
</script>

<style scoped>
/* ===== HEADER SHELL ===== */
/* ❗️ВАЖНО: backdrop-filter НЕ на header, иначе fixed внутри (категории) ломаются */
.header {
  width: 100%;
  position: sticky;
  top: 0;
  z-index: 9999;
  box-shadow: var(--shadow-sm);

  /* чтобы ::before с z-index:-1 не улетал за пределы */
  isolation: isolate;
}

/* фон + blur теперь на псевдоэлементе */
.header::before {
  content: "";
  position: absolute;
  inset: 0;
  background: #1e1e1e;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: -1;
}

/* ===== TOP ROW ===== */
.header-top {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.header-top-container {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 10px 16px;

  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

/* LOGO */
.logo {
  margin-right: auto;
}
.logo a {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: var(--text-main);
  white-space: nowrap;
}

.logo img {
  height: 42px;
  width: auto;
  display: block;
  border-radius: 10px;
  box-shadow: var(--shadow-sm);
}

.logo h1 {
  margin: 0;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--secondary-accent);}

/* DESKTOP NAV */
.nav {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 18px;
}

.nav-item {
  position: relative;
  text-decoration: none;
  cursor: pointer;

  color: var(--secondary-accent);
  font-size: 14.5px;
  font-weight: 800;
  padding: 10px 10px;
  border-radius: 12px;

  transition: background 0.18s ease, transform 0.18s ease;
}

.nav-item:hover {
  background: var(--bg-soft);
  transform: translateY(-1px);
}

.nav-item::after {
  content: "";
  position: absolute;
  left: 50%;
  bottom: 6px;
  width: 0;
  height: 2px;
  border-radius: var(--radius-lg);
  background: var(--accent);
  transform: translateX(-50%);
  transition: width 0.2s ease;
  opacity: 0.9;
}
.nav-item:hover::after {
  width: 26px;
}

/* BURGER */
.burger {
  display: none;
  margin-left: auto;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  cursor: pointer;

  width: 44px;
  height: 44px;
  padding: 0;

  align-items: center;
  justify-content: center;
  position: relative;
}

.burger span {
  position: absolute;
  width: 22px;
  height: 2px;
  background: var(--text-main);
  border-radius: 3px;
  transition: transform 0.25s ease, opacity 0.2s ease;
}

.burger span:nth-child(1) { transform: translateY(-7px); }
.burger span:nth-child(2) { transform: translateY(0); }
.burger span:nth-child(3) { transform: translateY(7px); }

.burger span.open:nth-child(1) { transform: translateY(0) rotate(45deg); }
.burger span.open:nth-child(2) { opacity: 0; }
.burger span.open:nth-child(3) { transform: translateY(0) rotate(-45deg); }

/* ===== BOTTOM ROW (CENTER SEARCH) ===== */
.header-bottom-container {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 10px 16px 12px;

  display: flex;
  justify-content: center;
}

.header-search {
  width: min(760px, 100%);
}

/* ===== MOBILE MENU ===== */
.mobile-menu {
  position: fixed;
  top: var(--hdr-h);
  right: 12px;
  left: 12px;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);

  padding: 14px;
  display: grid;
  gap: 10px;

  transform: translateY(-10px);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease, transform 0.2s ease;

  z-index: 9998;
}

.mobile-menu.open {
  transform: translateY(0);
  opacity: 1;
  pointer-events: auto;
}

.mobile-item {
  display: flex;
  align-items: center;
  justify-content: space-between;

  text-decoration: none;
  cursor: pointer;

  padding: 12px 12px;
  border-radius: 14px;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);

  color: var(--text-main);
  font-size: 15.5px;
  font-weight: 900;

  transition: transform 0.18s ease, filter 0.18s ease;
}

.mobile-item:hover {
  transform: translateY(-1px);
  filter: brightness(1.02);
}
.mobile-item::after {
  content: "›";
  font-size: 18px;
  color: var(--text-muted);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .logo h1 { font-size: 16px; }
  .nav { gap: 10px; }
}

@media (max-width: 768px) {
  .nav { display: none; }
  .burger { display: inline-flex; }
}

@media (max-width: 420px) {
  .logo img { height: 38px; border-radius: 9px; }
  .logo h1 { font-size: 15px; }
  .header-top-container { padding: 9px 12px; }
  .header-bottom-container { padding: 9px 12px 11px; }
}
</style>
