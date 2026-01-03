<template>
  <section id="photo" class="full-slider" aria-label="Фото галерея">
    <Swiper
      class="full-swiper"
      :modules="swiperModules"
      :slides-per-view="1"
      :loop="slides.length > 1"
      :speed="650"
      navigation
      :pagination="{ clickable: true }"
      :autoplay="autoplayEnabled ? autoplayOptions : false"
      :allow-touch-move="!uiLock"
      @swiper="onSwiper"
    >
      <SwiperSlide v-for="(src, i) in slides" :key="i">
        <div class="slide">
          <!-- clip только для картинки/blur -->
          <div class="clip">
            <div class="bg" :style="{ backgroundImage: `url(${src})` }" aria-hidden="true"></div>
            <img class="slide-img" :src="src" :alt="`Slide ${i + 1}`" loading="lazy" decoding="async" />
            <div class="shade" aria-hidden="true"></div>
          </div>
        </div>
      </SwiperSlide>
    </Swiper>

    <!-- ✅ ПОИСК — ОДИН РАЗ, ПОВЕРХ ВСЕГО SWIPER (НЕ ВНУТРИ SLIDE) -->
    <div class="search-layer" aria-label="Поиск по каталогу">
      <div
        class="search-shell"
        @pointerdown.stop
        @pointermove.stop
        @touchstart.stop
        @touchmove.stop
        @wheel.stop
      >
        <HomeSearch
          class="gallery-search"
          :show-category="false"
          :sync-route="false"
          catalog-path="/catalog"
          @ui-lock="onUiLock"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, Autoplay } from "swiper/modules";
import HomeSearch from "@/components/HomeSearch.vue";

/** ✅ включать/выключать автолистание тут */
const autoplayEnabled = true;

const autoplayOptions = {
  delay: 5000,
  disableOnInteraction: false, // мы вручную стопаем при uiLock
  pauseOnMouseEnter: true,
};

const swiperModules = autoplayEnabled
  ? [Navigation, Pagination, Autoplay]
  : [Navigation, Pagination];

const slides = [
  "/img/photo-shop/Slide1.png",
  "/img/photo-shop/Slide2.png",
  "/img/photo-shop/Slide3.png",
  "/img/photo-shop/Slide4.png",
  "/img/photo-shop/Slide5.png",
  "/img/photo-shop/Slide6.png",
];

/* header height -> css var */
const setHeaderVar = () => {
  const header = document.querySelector("header");
  const h = header ? header.offsetHeight : 0;
  document.documentElement.style.setProperty("--header-h", `${h}px`);
};

onMounted(() => {
  setHeaderVar();
  window.addEventListener("resize", setHeaderVar, { passive: true });
});
onBeforeUnmount(() => {
  window.removeEventListener("resize", setHeaderVar);
});

/* ===== Swiper control (freeze while dropdown/scanner open) ===== */
const uiLock = ref(false);
const swiperInstance = ref(null);

function onSwiper(sw) {
  swiperInstance.value = sw;
}

/** HomeSearch будет эмитить ui-lock true/false */
function onUiLock(v) {
  uiLock.value = !!v;

  const sw = swiperInstance.value;
  if (!sw) return;

  // touch move
  sw.allowTouchMove = !uiLock.value;

  // autoplay
  if (autoplayEnabled && sw.autoplay) {
    if (uiLock.value) sw.autoplay.stop();
    else sw.autoplay.start();
  }
}
</script>

<style scoped>
/* ========= ROOT SLIDER ========= */
.full-slider {
  width: 100vw;
  margin-left: calc(50% - 50vw);
  overflow-x: clip; /* без гориз.скролла */
  position: relative;
}

/* swiper wrapper */
.full-swiper {
  height: calc(100dvh - var(--header-h, 0px));
  min-height: 320px;
  background: #0f1115;
  border-bottom: 1px solid rgba(255, 255, 255, 0.10);
  overflow: visible; /* dropdown не режем */
}

/* Swiper часто ставит overflow:hidden — переопределяем */
:global(.full-swiper .swiper),
:global(.full-swiper .swiper-wrapper),
:global(.full-swiper .swiper-slide) {
  height: 100%;
  overflow: visible !important;
}

.slide {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: visible;
}

/* clip - только картинка/blur ограничиваем */
.clip {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

/* Размытый фон */
.bg {
  position: absolute;
  inset: -20px;
  background-size: cover;
  background-position: center;
  filter: blur(18px);
  transform: scale(1.08);
  opacity: 0.55;
}

/* Фото на весь экран */
.slide-img {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover; /* максимум видно */
  object-position: center;
}

/* затемнение (если надо темнее — см. ниже примечание) */
.shade {
  position: absolute;
  inset: 0;
  z-index: 3;
  pointer-events: none;
  background:
    radial-gradient(1200px 420px at 50% 70%, rgba(0, 0, 0, 0.30), rgba(0, 0, 0, 0) 55%),
    linear-gradient(to bottom, rgba(0, 0, 0, 0.46) 0%, rgba(0, 0, 0, 0.22) 42%, rgba(0, 0, 0, 0.88) 100%);
}

/* ========= SEARCH LAYER ========= */
.search-layer {
  position: absolute;
  inset: 0;
  z-index: 50;

  display: flex;
  justify-content: center;
  align-items: flex-start;

  pointer-events: none;

  /* ✅ выше и компактнее */
  padding: 10px;
  padding-top: clamp(10px, 4.2vh, 46px);
}

.search-shell {
  width: min(520px, 92vw); /* короче */
  pointer-events: auto;
}

/* ========= GLASS OVERRIDES (HomeSearch) ========= */
.gallery-search:deep(.search-wrap) {
  width: 100%;
  margin: 0 auto;
}

/* стеклянный инпут */
.gallery-search:deep(.search-box) {
  padding: 8px 10px;
  border-radius: 18px;
  min-height: 48px;

  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.32);
  box-shadow:
    0 18px 60px rgba(0, 0, 0, 0.28),
    0 1px 0 rgba(255, 255, 255, 0.18) inset;

  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

.gallery-search:deep(.search-icon) {
  color: rgba(255, 255, 255, 0.86);
  font-size: 14px;
}

/* сам инпут — без подсказок, просто поле */
.gallery-search:deep(.search-input) {
  color: rgba(255, 255, 255, 0.96);
  font-size: 15px;
}
.gallery-search:deep(.search-input::placeholder) {
  color: rgba(255, 255, 255, 0.40);
}

/* кнопки стекло */
.gallery-search:deep(.search-scan),
.gallery-search:deep(.search-clear) {
  width: 38px;
  height: 38px;
  border-radius: 14px;

  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.26);

  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);

  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.22);
  color: rgba(255, 255, 255, 0.92);
}

/* ========= DROPDOWN (glass + читаемо + норм скролл) ========= */
.gallery-search:deep(.dd) {
  z-index: 9999;

  background: rgba(255, 255, 255, 0.86);
  border: 1px solid rgba(255, 255, 255, 0.44);
  box-shadow: 0 28px 90px rgba(0, 0, 0, 0.45);

  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);

  max-height: min(56vh, 560px);
}

.gallery-search:deep(.dd-list) {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 8px 8px 12px;
}

/* читаемость */
.gallery-search:deep(.dd-title),
.gallery-search:deep(.dd-pill),
.gallery-search:deep(.dd-code) {
  color: #111827;
}
.gallery-search:deep(.dd-pill),
.gallery-search:deep(.dd-code) {
  background: rgba(17, 24, 39, 0.06);
  border: 1px solid rgba(17, 24, 39, 0.10);
}
.gallery-search:deep(.dd-price) {
  color: #0f172a;
}

/* ========= SWIPER ARROWS / BULLETS ========= */
:global(.full-swiper .swiper-button-next),
:global(.full-swiper .swiper-button-prev) {
  width: 46px;
  height: 46px;
  border-radius: 14px;

  background: rgba(255, 255, 255, 0.88);
  border: 1px solid rgba(0, 0, 0, 0.10);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);

  transition: transform .18s ease, filter .18s ease, opacity .18s ease;
  z-index: 20;
}

:global(.full-swiper .swiper-pagination) {
  bottom: 14px;
  z-index: 20;
}

/* ========= RESPONSIVE ========= */
@media (max-width: 600px) {
  .search-layer {
    padding: 8px;
    padding-top: clamp(8px, 3.2vh, 28px);
  }

  .search-shell {
    width: min(420px, 94vw);
  }

  .gallery-search:deep(.search-box) {
    min-height: 42px;
    border-radius: 16px;
    padding: 6px 9px;
  }

  .gallery-search:deep(.search-input) {
    font-size: 14px;
  }

  .gallery-search:deep(.search-scan),
  .gallery-search:deep(.search-clear) {
    width: 32px;
    height: 32px;
    border-radius: 12px;
  }

  :global(.full-swiper .swiper-pagination) {
    bottom: 10px;
  }
}

@media (max-width: 420px) {
  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev) {
    display: none;
  }
}
</style>