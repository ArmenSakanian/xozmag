<template>
  <section>
    <div id="photo" class="photo-gallery">
      <h2 class="page-title">Фотографии магазина</h2>
  
      <!-- Переключатели -->
      <div class="pg-switch">
        <button
          :class="{ active: mode === 'outside' }"
          @click="setMode('outside')"
        >
          Снаружи
        </button>
  
        <button
          :class="{ active: mode === 'product' }"
          @click="setMode('product')"
        >
          Товары
        </button>
      </div>
  
      <!-- ГАЛЕРЕЯ -->
      <swiper
        :modules="[Navigation]"
        navigation
        :slides-per-view="slidesPerView"
        :space-between="16"
        :loop="photos.length > slidesPerView"
        class="pg-swiper"
      >
        <swiper-slide v-for="(img, i) in photos" :key="i">
          <img :src="img" class="pg-img" />
        </swiper-slide>
      </swiper>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from "vue";

/* ВАЖНО — правильные импорты Swiper */
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation } from "swiper/modules";

/* Стили Swiper */
import "swiper/css";
import "swiper/css/navigation";

/* Фото */
const outsidePhotos = Array.from(
  { length: 4 },
  (_, i) =>
    new URL(`../assets/photo-shop/outside${i + 1}.webp`, import.meta.url).href
);

const productPhotos = Array.from(
  { length: 20 },
  (_, i) =>
    new URL(`../assets/photo-shop/product${i + 1}.webp`, import.meta.url).href
);

const mode = ref("outside");

const photos = computed(() =>
  mode.value === "outside" ? outsidePhotos : productPhotos
);

/* Адаптивное количество фото */
const slidesPerView = computed(() => {
  const w = window.innerWidth;
  if (w < 600) return 2; // мобильный → 2 фото
  if (w < 1024) return 3; // планшет → 3
  return 4; // ПК → 4
});

function setMode(val) {
  mode.value = val;
}
</script>

<style scoped>
/* Контейнер */
.photo-gallery {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}


/* Переключатели */
.pg-switch {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 22px;
}

.pg-switch button {
  padding: 10px 18px;
  border: none;
  background: var(--background-input);
  color: white;
  border-radius: 10px;
  cursor: pointer;
  font-size: 15px;
  transition: 0.2s;
}

.pg-switch button.active {
  background: var(--accent-color);
}

/* Swiper */
.pg-swiper {
  max-width: 900px;
  width: 100%;
  margin: 0 auto;
  position: relative;
}

/* Фото внутри слайдов */
.pg-img {
  width: 100%;
  height: 230px;
  object-fit: cover;
  border-radius: 12px;
}

@media (max-width: 1024px) {
  .pg-img {
    height: 200px;
  }
}

@media (max-width: 600px) {
  .pg-img {
    height: 200px;
  }
}

/* --- СТРЕЛКИ SWIPER --- */

/* Убираем дефолтный синий цвет */
:root {
  --swiper-navigation-color: white !important; /* Иконка — белая */
  --swiper-theme-color: white !important;
}

/* Фон стрелки */
.swiper-button-next,
.swiper-button-prev {
  background: var(--accent-color) !important; /* ФОН стрелок */
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Иконка стрелки (белая) */
.swiper-button-next:after,
.swiper-button-prev:after {
  font-size: 18px !important;
  color: white !important;
}

.swiper-button-disabled {
  opacity: 0.4 !important;
}
</style>
