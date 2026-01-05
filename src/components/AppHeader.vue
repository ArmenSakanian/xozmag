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
          <RouterLink to="/" class="logo-link" @click="closeMenu">
            <img src="@/assets/logo.webp" alt="Логотип" />
            <h1>Все Для Дома</h1>
          </RouterLink>
        </div>

        <!-- DESKTOP NAV -->
        <nav class="nav" aria-label="Навигация по сайту">
          <RouterLink class="nav-item" to="/catalog">Каталог</RouterLink>
          <RouterLink class="nav-item" to="/aboutus">О нас</RouterLink>
          <RouterLink class="nav-item" to="/contact">Контакты</RouterLink>
        </nav>

        <!-- BURGER (mobile) -->
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

    <!-- ===== MOBILE MENU ===== -->
    <div class="mobile-menu" :class="{ open: mobileOpen }">
      <RouterLink to="/catalog" class="mobile-item" @click="closeMenu">Каталог</RouterLink>
      <RouterLink to="/aboutus" class="mobile-item" @click="closeMenu">О нас</RouterLink>
      <RouterLink to="/contact" class="mobile-item" @click="closeMenu">Контакты</RouterLink>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from "vue";
import { useRoute, RouterLink } from "vue-router";

const route = useRoute();
const mobileOpen = ref(false);

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

watch(mobileOpen, () => updateHeaderH());

/* =========================
   MOUNT
========================= */
onMounted(() => {
  updateHeaderH();
  window.addEventListener("resize", handleResize, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
});
</script>

<style scoped>
/* ===== HEADER SHELL ===== */
.header {
  width: 100%;
  position: sticky;
  top: 0;
  z-index: 9999;
  box-shadow: var(--shadow-sm);
  isolation: isolate;
}

.header::before {
  content: "";
  position: absolute;
  inset: 0;

  /* Чуть спокойнее и “дороже”, чем чисто черный */
  background: rgba(17, 24, 39, 0.92);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: -1;
}

/* ===== TOP ROW ===== */
.header-top {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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

/* LOGO */
.logo {
  margin-right: auto;
}

.logo-link{
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

/* ===== DESKTOP NAV ===== */
.nav {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 18px;
}

/* Ссылки “профессионально”: без кнопок, с тонким underline */
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

/* Активная как в футере: выделение + underline */
.nav-item.router-link-active {
  color: var(--secondary-accent);
  border-bottom-color: rgba(252, 200, 34, 0.85);
  transform: none;
}

/* ===== BURGER ===== */
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

/* ===== MOBILE MENU ===== */
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

  padding: 12px 12px;
  border-radius: 14px;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);

  color: var(--text-main);
  font-size: 15.5px;
  font-weight: 1000;

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

/* Активный пункт в мобиле */
.mobile-item.router-link-active{
  border-color: rgba(252, 200, 34, 0.45);
  background: rgba(252, 200, 34, 0.10);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .logo h1 { font-size: 16px; }
  .nav { gap: 12px; }
}

@media (max-width: 768px) {
  .nav { display: none; }
  .burger { display: inline-flex; }
}

@media (max-width: 420px) {
  .logo img { height: 38px; border-radius: 9px; }
  .logo h1 { font-size: 15px; }
  .header-top-container { padding: 9px 12px; }
}
</style>