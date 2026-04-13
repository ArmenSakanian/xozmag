<template>
  <header
    ref="headerRef"
    class="header"
    :class="{ compact: isCompact, 'menu-open': mobileOpen }"
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
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import HomeSearch from "@/components/HomeSearch.vue";

const route = useRoute();
const mobileOpen = ref(false);
const isCompact = ref(false);

const headerRef = ref(null);
const headerH = ref(0);

let scrollRaf = 0;
let prevScrollY = 0;
let ignoreScrollUntil = 0;

const COMPACT_ENTER_SCROLL = 72;
const COMPACT_EXIT_SCROLL = 2;
const COMPACT_IGNORE_MS = 280;

async function updateHeaderH() {
  await nextTick();
  headerH.value = headerRef.value?.offsetHeight || 0;
}

function setCompact(nextValue, options = {}) {
  const { lock = true } = options;
  if (isCompact.value === nextValue) return;

  isCompact.value = nextValue;
  if (nextValue) mobileOpen.value = false;
  if (lock) ignoreScrollUntil = performance.now() + COMPACT_IGNORE_MS;
  updateHeaderH();
}

function syncCompactState(force = false) {
  const y = Math.max(window.scrollY || 0, 0);
  const delta = y - prevScrollY;
  const scrollingDown = delta > 1;
  const scrollingUp = delta < -1;

  if (force) {
    prevScrollY = y;
    setCompact(y >= COMPACT_ENTER_SCROLL, { lock: false });
    return;
  }

  if (performance.now() < ignoreScrollUntil) {
    prevScrollY = y;
    return;
  }

  if (!isCompact.value) {
    if (y >= COMPACT_ENTER_SCROLL && scrollingDown) {
      setCompact(true);
    }
    prevScrollY = y;
    return;
  }

  if (y <= COMPACT_EXIT_SCROLL && scrollingUp) {
    setCompact(false);
  }

  prevScrollY = y;
}

function handleScroll() {
  if (scrollRaf) return;
  scrollRaf = window.requestAnimationFrame(() => {
    scrollRaf = 0;
    syncCompactState();
  });
}

function handleResize() {
  prevScrollY = Math.max(window.scrollY || 0, 0);
  updateHeaderH();
  syncCompactState(true);
}

function closeMenu() {
  mobileOpen.value = false;
}

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
    prevScrollY = Math.max(window.scrollY || 0, 0);
    updateHeaderH();
    syncCompactState(true);
  }
);

watch(mobileOpen, () => updateHeaderH());

onMounted(() => {
  prevScrollY = Math.max(window.scrollY || 0, 0);
  syncCompactState(true);
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
  isolation: isolate;
  overflow-anchor: none;
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.22s ease;
}

.header::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: -1;
  background: rgba(17, 24, 39, 0.92);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
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
  display: grid;
  grid-template-rows: 1fr;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
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
  background: rgba(255, 255, 255, 0.1);
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
  width: 44px;
  height: 44px;
  padding: 0;
  align-items: center;
  justify-content: center;
  position: relative;
  cursor: pointer;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
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
  background: rgba(255, 255, 255, 0.92);
  border-radius: 3px;
  transition: transform 0.25s ease, opacity 0.2s ease;
}

.burger span:nth-child(1) {
  transform: translateY(-7px);
}

.burger span:nth-child(2) {
  transform: translateY(0);
}

.burger span:nth-child(3) {
  transform: translateY(7px);
}

.burger span.open:nth-child(1) {
  transform: translateY(0) rotate(45deg);
}

.burger span.open:nth-child(2) {
  opacity: 0;
}

.burger span.open:nth-child(3) {
  transform: translateY(0) rotate(-45deg);
}

.mobile-menu {
  position: fixed;
  top: var(--hdr-h);
  left: 0;
  right: 0;
  padding: 14px;
  display: grid;
  gap: 10px;
  background: var(--bg-panel);
  box-shadow: var(--shadow-lg);
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
  padding: 14px;
  text-decoration: none;
  font-weight: 800;
  color: var(--text-main);
  background: rgba(17, 24, 39, 0.04);
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 14px;
}

.mobile-item.router-link-active {
  border-color: rgba(252, 200, 34, 0.32);
  background: rgba(252, 200, 34, 0.1);
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

  .header-search:deep(.search-wrap),
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
    min-height: 50px;
    border-radius: 16px;
  }

  .header.compact .header-search:deep(.search-box) {
    min-height: 48px;
  }
}
</style>
