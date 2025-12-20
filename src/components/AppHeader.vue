<template>
  <header class="header">
    <div class="header-container">
      <!-- ЛОГО -->
      <div class="logo">
        <a href="/">
          <img src="@/assets/logo.webp" alt="Logo" />
          <h1>Все Для Дома</h1></a
        >
      </div>

      <!-- ДЕСКТОП МЕНЮ -->
      <nav class="nav">
        <a href="/product" class="nav-item">Каталог</a>
        <a class="nav-item" @click.prevent="scrollToSection('about')">О нас</a>
        <a class="nav-item" @click.prevent="scrollToSection('contact')"
          >Контакты</a
        >
                <a class="nav-item" @click.prevent="scrollToSection('StoreConditions')"
          >Удобства магазина</a
        >
        <a class="nav-item" @click.prevent="scrollToSection('photo')"
          >Фотографии</a
        >
      </nav>

      <!-- БУРГЕР -->
      <button class="burger" @click="mobileOpen = !mobileOpen">
        <span :class="{ open: mobileOpen }"></span>
        <span :class="{ open: mobileOpen }"></span>
        <span :class="{ open: mobileOpen }"></span>
      </button>
    </div>

    <!-- МОБИЛЬНОЕ МЕНЮ -->
    <div class="mobile-menu" :class="{ open: mobileOpen }">
      <a href="/product" class="mobile-item" @click="closeMenu">Каталог</a>
      <a class="mobile-item" @click.prevent="scrollToSection('about')">О нас</a>
      <a class="mobile-item" @click.prevent="scrollToSection('contact')"
        >Контакты</a
      >
                      <a class="mobile-item" @click.prevent="scrollToSection('StoreConditions')"
          >Удобства магазина</a
        >
      <a class="mobile-item" @click.prevent="scrollToSection('photo')"
        >Фотографии</a
      >
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";

const mobileOpen = ref(false);
const route = useRoute();

// ============================
//   ПЕРЕХОД И СКРОЛЛ
// ============================
function scrollToSection(id) {
  const currentPath = route.path;

  // если НЕ на главной → делаем ПОЛНОЕ обновление страницы
  if (currentPath !== "/") {
    mobileOpen.value = false; // закрыть бургер

    // передаём параметр scroll в URL
    window.location.href = `/?scroll=${id}`;
    return;
  }

  // если уже на главной — просто скроллим
  doScroll(id);
}

// ============================
//   ФУНКЦИЯ СКРОЛЛА
// ============================
function doScroll(id) {
  const el = document.getElementById(id);
  if (!el) return;

  const headerHeight = document.querySelector(".header").offsetHeight;

  window.scrollTo({
    top: el.offsetTop - headerHeight,
    behavior: "smooth",
  });

  mobileOpen.value = false; // закрыть бургер
}

// ============================
//   СКРОЛЛ ПОСЛЕ ПЕРЕХОДА
// ============================
onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const section = params.get("scroll");

  if (section) {
    setTimeout(() => doScroll(section), 400);
  }
});
</script>

<style scoped>
/* ===== HEADER (под твой :root) ===== */

.header{
  width: 100%;
  position: sticky;
  top: 0;
  z-index: 9999;

  height: var(--site-header-h);
  display: flex;
  align-items: center;

  background: rgba(255,255,255,0.85);
  border-bottom: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  backdrop-filter: blur(10px);
}

.header-container{
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 16px;

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

/* ===== ЛОГО ===== */
.logo a{
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: var(--text-main);
  white-space: nowrap;
}

.logo img{
  height: 42px;
  width: auto;
  display: block;
  border-radius: 10px;
  box-shadow: var(--shadow-sm);
}

.logo h1{
  margin: 0;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

/* ===== DESKTOP NAV ===== */
.nav{
  display: flex;
  align-items: center;
  gap: 18px;
}

.nav-item{
  position: relative;
  text-decoration: none;
  cursor: pointer;

  color: var(--text-main);
  font-size: 14.5px;
  font-weight: 800;
  padding: 10px 10px;
  border-radius: 12px;

  transition: background .18s ease, color .18s ease, transform .18s ease;
}

.nav-item:hover{
  background: var(--bg-soft);
  transform: translateY(-1px);
}

.nav-item:active{
  transform: translateY(0);
}

/* маленькая “точка-акцент” снизу */
.nav-item::after{
  content: "";
  position: absolute;
  left: 50%;
  bottom: 6px;
  width: 0;
  height: 2px;
  border-radius: 999px;
  background: var(--accent);
  transform: translateX(-50%);
  transition: width .2s ease;
  opacity: .9;
}

.nav-item:hover::after{
  width: 26px;
}

/* ===== BURGER ===== */
.burger{
  display: none;
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

.burger span{
  position: absolute;
  width: 22px;
  height: 2px;
  background: var(--text-main);
  border-radius: 3px;
  transition: transform .25s ease, opacity .2s ease;
}

.burger span:nth-child(1){ transform: translateY(-7px); }
.burger span:nth-child(2){ transform: translateY(0); }
.burger span:nth-child(3){ transform: translateY(7px); }

.burger span.open:nth-child(1){ transform: translateY(0) rotate(45deg); }
.burger span.open:nth-child(2){ opacity: 0; }
.burger span.open:nth-child(3){ transform: translateY(0) rotate(-45deg); }

/* ===== MOBILE MENU ===== */
.mobile-menu{
  position: fixed;
  top: var(--site-header-h);
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
  transition: opacity .2s ease, transform .2s ease;

  z-index: 9998;
}

.mobile-menu.open{
  transform: translateY(0);
  opacity: 1;
  pointer-events: auto;
}

.mobile-item{
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

  transition: transform .18s ease, filter .18s ease;
}

.mobile-item:hover{
  transform: translateY(-1px);
  filter: brightness(1.02);
}

.mobile-item:active{
  transform: translateY(0);
}

/* маленькая стрелка справа (без иконок) */
.mobile-item::after{
  content: "›";
  font-size: 18px;
  color: var(--text-muted);
}

/* ===== АДАПТАЦИЯ ===== */
@media (max-width: 900px){
  .logo h1{ font-size: 16px; }
  .nav{ gap: 10px; }
}

@media (max-width: 768px){
  .nav{ display: none; }
  .burger{ display: inline-flex; }
}

/* если хочешь “плотнее” на очень маленьких */
@media (max-width: 420px){
  .logo img{ height: 38px; border-radius: 9px; }
  .logo h1{ font-size: 15px; }
}
</style>

