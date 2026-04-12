<template>
  <header
    class="header"
    :class="{ compact: isCompact, 'menu-open': mobileOpen }"
    ref="headerRef"
    :style="{ '--hdr-h': headerH + 'px' }"
  >
    <div class="header-top">
      <div class="header-top-curtain">
        <div class="header-top-container">
        <div class="logo">
          <RouterLink to="/" class="logo-link" @click="closeMenu">
            <img src="@/assets/logo.webp" alt="Логотип" />
            <h1>Все Для Дома</h1>
          </RouterLink>
        </div>

        <nav class="nav" aria-label="Навигация по сайту">
          <RouterLink class="nav-item" to="/catalog">Каталог</RouterLink>
          <RouterLink class="nav-item" to="/aboutus">О нас</RouterLink>
          <RouterLink class="nav-item" to="/contact">Контакты</RouterLink>
        </nav>

        <button
          class="burger"
          type="button"
          @click="mobileOpen = !mobileOpen"
          :aria-label="mobileOpen ? 'Закрыть меню' : 'Открыть меню'"
          :aria-expanded="mobileOpen"
        >
          <span :class="{ open: mobileOpen }"></span>
          <span :class="{ open: mobileOpen }"></span>
          <span :class="{ open: mobileOpen }"></span>
        </button>
        </div>
      </div>
    </div>

    <div class="header-search-row">
      <div class="header-search-container">
        <HomeSearch
          class="header-search"
          :show-category="true"
          :sync-route="true"
          catalog-path="/catalog"
        />
      </div>
    </div>

    <div class="mobile-menu" :class="{ open: mobileOpen && !isCompact }">
      <RouterLink to="/catalog" class="mobile-item" @click="closeMenu">Каталог</RouterLink>
      <RouterLink to="/aboutus" class="mobile-item" @click="closeMenu">О нас</RouterLink>
      <RouterLink to="/contact" class="mobile-item" @click="closeMenu">Контакты</RouterLink>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from "vue";
import { useRoute, RouterLink } from "vue-router";
import HomeSearch from "@/components/HomeSearch.vue";

const route = useRoute();
const mobileOpen = ref(false);
const isCompact = ref(false);

const headerRef = ref(null);
const headerH = ref(0);
let scrollRaf = 0;
const COMPACT_ENTER_SCROLL = 20;
const COMPACT_RESET_SCROLL = 2;

async function updateHeaderH() {
  await nextTick();
  headerH.value = headerRef.value?.offsetHeight || 0;
}

function syncCompactState() {
  const y = window.scrollY || 0;

  if (!isCompact.value) {
    if (y <= COMPACT_ENTER_SCROLL) return;
    isCompact.value = true;
    mobileOpen.value = false;
    updateHeaderH();
    return;
  }

  if (y > COMPACT_RESET_SCROLL) return;
  isCompact.value = false;
  updateHeaderH();
}

function handleScroll() {
  if (scrollRaf) return;
  scrollRaf = window.requestAnimationFrame(() => {
    scrollRaf = 0;
    syncCompactState();
  });
}

function handleResize() {
  updateHeaderH();
  syncCompactState();
}

function closeMenu() {
  mobileOpen.value = false;
}

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
    updateHeaderH();
    syncCompactState();
  }
);

watch(mobileOpen, () => updateHeaderH());

onMounted(() => {
  syncCompactState();
  updateHeaderH();
  window.addEventListener("resize", handleResize, { passive: true });
  window.addEventListener("scroll", handleScroll, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
  window.removeEventListener("scroll", handleScroll);
  if (scrollRaf) {
    window.cancelAnimationFrame(scrollRaf);
    scrollRaf = 0;
  }
});
</script>

<style scoped>
.header {
  width: 100%;
  position: sticky;
  top: 0;
  z-index: 9999;
  box-shadow: var(--shadow-sm);
  isolation: isolate;
  transition: box-shadow 0.22s ease;
}

.header::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(17, 24, 39, 0.92);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: -1;
  transition: background 0.22s ease, backdrop-filter 0.22s ease;
}

.header.compact {
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
}

.header.compact::before {
  background: rgba(17, 24, 39, 0.96);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

.header-top {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: grid;
  grid-template-rows: 1fr;
  transition:
    grid-template-rows 0.34s cubic-bezier(0.2, 0.8, 0.2, 1),
    border-color 0.22s ease;
  will-change: grid-template-rows;
}

.header-top-curtain {
  min-height: 0;
  overflow: hidden;
}

.header.compact .header-top {
  grid-template-rows: 0fr;
  border-bottom-color: transparent;
  pointer-events: none;
}

.header-top-container {
  transform: translateY(0);
  opacity: 1;
  transition:
    transform 0.34s cubic-bezier(0.2, 0.8, 0.2, 1),
    opacity 0.18s ease;
  will-change: transform, opacity;
}

.header.compact .header-top-container {
  transform: translateY(-14px);
  opacity: 0;
}

.header-top-container {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.logo {
  margin-right: auto;
}

.logo-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
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
  font-weight: 1000;
  letter-spacing: -0.02em;
  color: var(--secondary-accent);
}

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
  color: rgba(255, 255, 255, 0.78);
  font-size: 14.5px;
  font-weight: 850;
  padding: 10px 6px;
  border-bottom: 2px solid transparent;
  transition: color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
}

.nav-item:hover {
  color: rgba(255, 255, 255, 0.95);
  border-bottom-color: rgba(252, 200, 34, 0.55);
  transform: translateY(-1px);
}

.nav-item.router-link-active {
  color: var(--secondary-accent);
  border-bottom-color: rgba(252, 200, 34, 0.85);
  transform: none;
}

.header-search-row {
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  transition: border-color 0.18s ease;
}

.header.compact .header-search-row {
  border-bottom-color: transparent;
}

.header-search-container {
  width: min(1280px, 100%);
  margin: 0 auto;
  padding: 12px 16px 14px;
  transition: padding 0.22s ease;
}

.header.compact .header-search-container {
  padding: 10px 16px;
}

.header-search {
  display: block;
}

.header-search:deep(.search-wrap) {
  width: min(780px, 100%);
  transition: width 0.22s ease;
}

.header.compact .header-search:deep(.search-wrap) {
  width: min(920px, 100%);
}

.header-search:deep(.search-box) {
  min-height: 54px;
  padding: 8px 10px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.14);
  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.16),
    0 1px 0 rgba(255, 255, 255, 0.06) inset;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition:
    min-height 0.22s ease,
    border-color 0.22s ease,
    background 0.22s ease,
    box-shadow 0.22s ease,
    transform 0.22s ease;
}

.header.compact .header-search:deep(.search-box) {
  min-height: 50px;
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.18);
  box-shadow:
    0 14px 32px rgba(0, 0, 0, 0.16),
    0 1px 0 rgba(255, 255, 255, 0.08) inset;
}

.header-search:deep(.search-icon) {
  color: rgba(255, 255, 255, 0.72);
}

.header-search:deep(.search-input) {
  color: rgba(255, 255, 255, 0.96);
  font-size: 15px;
}

.header-search:deep(.search-input::placeholder) {
  color: rgba(255, 255, 255, 0.38);
}

.header-search:deep(.search-clear),
.header-search:deep(.search-scan),
.header-search:deep(.catpick-btn) {
  background: rgba(255, 255, 255, 0.10);
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: rgba(255, 255, 255, 0.94);
}

.header-search:deep(.catpick-btn) {
  min-height: 38px;
  border-radius: 12px;
}

.header-search:deep(.dd) {
  z-index: 10020;
  border-radius: 18px;
  box-shadow: 0 30px 70px rgba(0, 0, 0, 0.28);
}

.burger {
  display: none;
  margin-left: auto;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.10);
  border-radius: 12px;
  cursor: pointer;
  width: 44px;
  height: 44px;
  padding: 0;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.header.compact .burger {
  opacity: 0;
  transform: translateY(-6px);
  pointer-events: none;
}

.burger span {
  position: absolute;
  width: 22px;
  height: 2px;
  background: rgba(255,255,255,0.92);
  border-radius: 3px;
  transition: transform 0.25s ease, opacity 0.2s ease;
}

.burger span:nth-child(1) { transform: translateY(-7px); }
.burger span:nth-child(2) { transform: translateY(0); }
.burger span:nth-child(3) { transform: translateY(7px); }

.burger span.open:nth-child(1) { transform: translateY(0) rotate(45deg); }
.burger span.open:nth-child(2) { opacity: 0; }
.burger span.open:nth-child(3) { transform: translateY(0) rotate(-45deg); }

.mobile-menu {
  position: fixed;
  top: var(--hdr-h);
  left: 0;
  right: 0;
  background: var(--bg-panel);
  box-shadow: var(--shadow-lg);
  padding: 14px;
  display: grid;
  gap: 10px;
  transform: translateY(-10px);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.mobile-menu.open {
  transform: translateY(0);
  opacity: 1;
  pointer-events: auto;
}

.mobile-item {
  display: block;
  text-decoration: none;
  color: var(--text-main);
  background: rgba(17, 24, 39, 0.04);
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 14px;
  padding: 14px 14px;
  font-weight: 800;
}

.mobile-item.router-link-active {
  border-color: rgba(252, 200, 34, 0.32);
  background: rgba(252, 200, 34, 0.10);
  color: #111827;
}

@media (max-width: 900px) {
  .nav {
    display: none;
  }

  .burger {
    display: inline-flex;
  }

  .header-top-container {
    padding: 10px 12px;
  }

  .header-search-container {
    padding: 10px 12px 12px;
  }

  .header.compact .header-search-container {
    padding: 10px 12px;
  }

  .header-search:deep(.search-wrap) {
    width: 100%;
  }

  .header.compact .header-search:deep(.search-wrap) {
    width: 100%;
  }
}

@media (max-width: 640px) {
  .logo img {
    height: 36px;
  }

  .logo h1 {
    font-size: 16px;
  }

  .header-search:deep(.search-box) {
    min-height: 48px;
    border-radius: 16px;
    padding: 6px 8px;
  }

  .header.compact .header-search:deep(.search-box) {
    min-height: 46px;
  }

  .header-search:deep(.search-input) {
    font-size: 14px;
  }

  .header-search:deep(.search-clear),
  .header-search:deep(.search-scan) {
    width: 34px;
    height: 34px;
    border-radius: 12px;
  }

  .header-search:deep(.catpick-btn) {
    min-height: 34px;
    padding: 0 10px;
  }
}
</style>
