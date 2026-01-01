<template>
  <section id="photo" class="full-slider">
    <Swiper
      class="full-swiper"
      :modules="[Navigation, Pagination, Autoplay]"
      :slides-per-view="1"
      :loop="slides.length > 1"
      :speed="650"
      navigation
      :pagination="{ clickable: true }"
      :autoplay="{
        delay: 5000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true
      }"
    >
      <SwiperSlide v-for="(src, i) in slides" :key="i">
        <img
          class="slide-img"
          :src="src"
          :alt="`Slide ${i + 1}`"
          loading="lazy"
          decoding="async"
        />
      </SwiperSlide>
    </Swiper>
  </section>
</template>

<script setup>
// Swiper
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, Autoplay } from "swiper/modules";

// ✅ ВРУЧНУЮ укажи пути к фоткам из /public
// Пример: public/img/photo-shop/Slide1.webp
const slides = [
  "/img/photo-shop/Slide1.webp",
  "/img/photo-shop/Slide1.webp",
    "/img/photo-shop/Slide1.webp",
];
</script>

<style scoped>
/* На всю ширину экрана даже если компонент внутри контейнера */
.full-slider{
  width: 100vw;
  margin-left: calc(50% - 50vw);
}

/* Высота слайдера */
.full-swiper{
  height: 80vh;
  min-height: 320px;
  max-height: 820px;

  background: var(--bg-panel);
  border-top: 1px solid var(--border-soft);
  border-bottom: 1px solid var(--border-soft);
}

/* Чтобы Swiper занял всю высоту */
:global(.full-swiper .swiper),
:global(.full-swiper .swiper-wrapper),
:global(.full-swiper .swiper-slide){
  height: 100%;
}

/* Картинка — на весь экран, аккуратно */
.slide-img{
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  object-position: center;
}

/* Стрелки — минимализм, без неона */
:global(.full-swiper .swiper-button-next),
:global(.full-swiper .swiper-button-prev){
  width: 46px;
  height: 46px;
  border-radius: var(--radius-lg);

  background: rgba(255,255,255,0.85);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-md);

  transition: transform .18s ease, filter .18s ease, opacity .18s ease;
}

:global(.full-swiper .swiper-button-next:hover),
:global(.full-swiper .swiper-button-prev:hover){
  transform: translateY(-1px);
  filter: brightness(1.03);
}

:global(.full-swiper .swiper-button-next:active),
:global(.full-swiper .swiper-button-prev:active){
  transform: translateY(0);
  filter: brightness(0.98);
}

:global(.full-swiper .swiper-button-next::after),
:global(.full-swiper .swiper-button-prev::after){
  font-size: 16px;
  font-weight: 900;
  color: var(--text-main);
}

/* Буллеты (круглые точки) — тоже минимально */
:global(.full-swiper .swiper-pagination-bullet){
  width: 8px;
  height: 8px;
  opacity: .35;
  background: var(--text-main);
}

:global(.full-swiper .swiper-pagination-bullet-active){
  opacity: 1;
  background: var(--accent);
}

/* Мобилка */
@media (max-width: 600px){
  .full-swiper{
    height: 72vh;
    min-height: 280px;
  }

  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev){
    width: 40px;
    height: 40px;
  }
}
</style>
