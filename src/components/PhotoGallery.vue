<template>
  <section id="photo" class="full-slider" aria-label="Фото галерея">
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
        <div class="slide">
          <!-- ФОН (размытый) -->
          <div class="bg" :style="{ backgroundImage: `url(${src})` }" aria-hidden="true"></div>

          <!-- ОСНОВНОЕ ФОТО (целиком) -->
          <img
            class="slide-img"
            :src="src"
            :alt="`Slide ${i + 1}`"
            loading="lazy"
            decoding="async"
          />

          <!-- лёгкий градиент для читаемости элементов -->
          <div class="shade" aria-hidden="true"></div>
        </div>
      </SwiperSlide>
    </Swiper>
  </section>
</template>

<script setup>
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, Autoplay } from "swiper/modules";
import { onMounted, onBeforeUnmount } from "vue";

// Убедись, что глобально подключены стили swiper:
// import "swiper/css";
// import "swiper/css/navigation";
// import "swiper/css/pagination";

const slides = [
  "/img/photo-shop/Slide1.png",
  "/img/photo-shop/Slide2.png",
  "/img/photo-shop/Slide3.png",
  "/img/photo-shop/Slide4.png",
  "/img/photo-shop/Slide5.png",
  "/img/photo-shop/Slide6.png",
];

const setHeaderVar = () => {
  const header = document.querySelector("header");
  const h = header ? header.offsetHeight : 0;
  document.documentElement.style.setProperty("--header-h", `${h}px`);
};

onMounted(() => {
  setHeaderVar();
  window.addEventListener("resize", setHeaderVar);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", setHeaderVar);
});
</script>

<style scoped>
.full-slider{
  width: 100vw;
  margin-left: calc(50% - 50vw);
  overflow: hidden;
}

.full-swiper{
  height: calc(100dvh - var(--header-h, 0px));
  min-height: 320px;
  background: #0f1115;
  border-bottom: 1px solid rgba(255,255,255,0.10);
}

:global(.full-swiper .swiper),
:global(.full-swiper .swiper-wrapper),
:global(.full-swiper .swiper-slide){
  height: 100%;
}

.slide{
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

/* Размытый фон из той же картинки */
.bg{
  position: absolute;
  inset: -20px; /* чтобы края при blur не обрезались */
  background-size: cover;
  background-position: center;
  filter: blur(18px);
  transform: scale(1.08);
  opacity: 0.55;
}

/* Фото целиком (важно!) */
.slide-img{
  position: relative;
  z-index: 2;

  width: 100%;
  height: 100%;
  display: block;

  object-fit: contain;       /* <-- вот это делает “всё видно” */
  object-position: center;
}

/* Лёгкий “дорогой” градиент */
.shade{
  position: absolute;
  inset: 0;
  z-index: 3;
  pointer-events: none;
  background: linear-gradient(
    to bottom,
    rgba(0,0,0,0.15) 0%,
    rgba(0,0,0,0) 35%,
    rgba(0,0,0,0.35) 100%
  );
}

/* Стрелки */
:global(.full-swiper .swiper-button-next),
:global(.full-swiper .swiper-button-prev){
  width: 46px;
  height: 46px;
  border-radius: 14px;

  background: rgba(255,255,255,0.88);
  border: 1px solid rgba(0,0,0,0.10);
  box-shadow: 0 8px 24px rgba(0,0,0,0.18);

  transition: transform .18s ease, filter .18s ease, opacity .18s ease;
}

:global(.full-swiper .swiper-button-next:hover),
:global(.full-swiper .swiper-button-prev:hover){
  transform: translateY(-1px);
  filter: brightness(1.03);
}

:global(.full-swiper .swiper-button-next::after),
:global(.full-swiper .swiper-button-prev::after){
  font-size: 16px;
  font-weight: 900;
  color: #111;
}

/* Буллеты */
:global(.full-swiper .swiper-pagination){
  bottom: 14px;
  z-index: 4;
}

:global(.full-swiper .swiper-pagination-bullet){
  width: 8px;
  height: 8px;
  opacity: .35;
  background: rgba(255,255,255,0.95);
}

:global(.full-swiper .swiper-pagination-bullet-active){
  opacity: 1;
  background: var(--accent, #4f8cff);
}

/* Планшет */
@media (max-width: 900px){
  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev){
    width: 42px;
    height: 42px;
    border-radius: 12px;
  }
}

/* Мобилка */
@media (max-width: 600px){
  :global(.full-swiper .swiper-pagination){
    bottom: 10px;
  }
  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev){
    width: 40px;
    height: 40px;
  }
}

/* Очень маленькие — прячем стрелки */
@media (max-width: 420px){
  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev){
    display: none;
  }
}
</style>
