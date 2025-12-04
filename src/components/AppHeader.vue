<template>
  <header class="header">
    <div class="header-container">

      <!-- ЛОГО -->
      <div class="logo">
        <a href="/">
        <img src="@/assets/logo.png" alt="Logo" />
        <h1>Все Для Дома</h1></a>
      </div>

      <!-- ДЕСКТОП МЕНЮ -->
      <nav class="nav">
        <a href="/product" class="nav-item">Каталог</a>
<a class="nav-item" @click.prevent="scrollToSection('about')">О нас</a>
<a class="nav-item" @click.prevent="scrollToSection('contact')">Контакты</a>

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
<a class="mobile-item" @click.prevent="scrollToSection('contact')">Контакты</a>

    </div>

  </header>
</template>

<script setup>
import { ref } from "vue";

const mobileOpen = ref(false);

function scrollToSection(id) {
  const el = document.getElementById(id);
  if (!el) {
    console.log("Нет элемента:", id);
    return;
  }

  const headerHeight = document.querySelector(".header").offsetHeight;

  const offset = el.getBoundingClientRect().top + window.pageYOffset - headerHeight;

  window.scrollTo({
    top: offset,
    behavior: "smooth"
  });

  mobileOpen.value = false;
}

</script>

<style scoped>

/* =============================== */
/*        CSS-ПЕРЕМЕННЫЕ           */
/* =============================== */
:root {
  --background-container: #1c1e22;
  --accent-color: #e53935; /* красная линия */
}

/* =============================== */
/*         HEADER DESKTOP          */
/* =============================== */
.header {
  width: 100%;
  background: var(--background-container);
  padding: 12px 0;
  box-shadow: 0 2px 8px rgb(0 0 0 / 0.2);
  position: sticky;
  top: 0;
  z-index: 2000;
}

.header-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 20px;

  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* ЛОГО */

.logo a {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: white;
}


.logo img {
  height: 45px;
}

/* МЕНЮ */
.nav {
  display: flex;
  gap: 28px;
}

.nav-item {
  color: var(--accent-color);
  font-size: 17px;
  position: relative;
  text-decoration: none;
  padding-bottom: 4px;
  cursor: pointer;
}

/* Линия снизу — по умолчанию скрыта */
.nav-item::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -2px;
  height: 2px;
  width: 0%;
  background: var(--accent-color);
  transition: width 0.25s ease-in-out;
}

/* При наведении растёт слева направо */
.nav-item:hover::after {
  width: 100%;
}

/* =============================== */
/*           БУРГЕР ИКОНКА         */
/* =============================== */

.burger {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px;
}

.burger span {
  display: block;
  width: 28px;
  height: 3px;
  background: #fff;
  margin: 6px 0;
  border-radius: 3px;
  transition: 0.35s;
}

/* Анимация превращения в крест */
.burger span.open:nth-child(1) {
  transform: translateY(9px) rotate(45deg);
}

.burger span.open:nth-child(2) {
  opacity: 0;
}

.burger span.open:nth-child(3) {
  transform: translateY(-9px) rotate(-45deg);
}

/* =============================== */
/*           МОБИЛЬНОЕ МЕНЮ        */
/* =============================== */

.mobile-menu {
  position: fixed;
  top: 0;
  right: -100%;
  width: 70%;
  height: 100vh;
  background: var(--background-container);
  padding: 20px;
  padding-top: 80px;

  display: flex;
  flex-direction: column;
  gap: 25px;

  transition: right 0.35s ease-in-out;
}

.mobile-menu.open {
  right: 0;
}

.mobile-item {
  color: #fff;
  font-size: 20px;
  text-decoration: none;
  position: relative;
  padding-bottom: 4px;
  cursor: pointer;
}

.mobile-item::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -2px;
  height: 2px;
  width: 0%;
  background: var(--accent-color);
  transition: width 0.25s;
}

.mobile-item:hover::after {
  width: 100%;
}

/* =============================== */
/*        АДАПТАЦИЯ < 768px        */
/* =============================== */
@media (max-width: 768px) {

  .nav {
    display: none; /* прячем меню */
  }

  .burger {
    display: block;
  }
}
</style>
