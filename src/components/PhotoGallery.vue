<template>
  <section
    ref="sliderEl"
    id="photo"
    class="full-slider"
    aria-label="Фото галерея"
  >
    <Swiper
      v-if="slides.length"
      :key="swiperKey"
      class="full-swiper"
      :modules="swiperModules"
      :slides-per-view="1"
      :loop="slides.length > 1"
      :speed="700"
      effect="slide"
      :navigation="navigationOptions"
      :pagination="{ clickable: true }"
      :autoplay="autoplayEnabled ? autoplayOptions : false"
      @swiper="bindNavigation"
    >
      <SwiperSlide v-for="(src, i) in slides" :key="src + ':' + i">
        <div class="slide">
          <div class="clip">
            <div class="media-frame">
              <img
                class="slide-img"
                :src="src"
                :alt="`Slide ${i + 1}`"
                loading="lazy"
                decoding="async"
              />
            </div>
            <div class="shade" aria-hidden="true"></div>
          </div>
        </div>
      </SwiperSlide>
    </Swiper>

    <div v-else class="full-swiper empty-hero" aria-hidden="true">
      <div class="empty-card"></div>
      <div class="shade"></div>
    </div>

    <template v-if="slides.length > 1">
      <button
        ref="prevEl"
        type="button"
        class="slider-nav slider-nav-prev"
        aria-label="Предыдущий слайд"
      >
        <Fa icon="fa-solid fa-chevron-left" />
      </button>

      <button
        ref="nextEl"
        type="button"
        class="slider-nav slider-nav-next"
        aria-label="Следующий слайд"
      >
        <Fa icon="fa-solid fa-chevron-right" />
      </button>
    </template>
  </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Autoplay, Navigation, Pagination } from "swiper/modules";

const sliderEl = ref(null);
const prevEl = ref(null);
const nextEl = ref(null);

const API_GET = "/api/admin/photogallery/get_photo_gallery.php";
const autoplayEnabled = true;

const autoplayOptions = {
  delay: 5000,
  disableOnInteraction: false,
  pauseOnMouseEnter: true,
};

const swiperModules = autoplayEnabled
  ? [Navigation, Pagination, Autoplay]
  : [Navigation, Pagination];

const navigationOptions = {
  prevEl: null,
  nextEl: null,
};

const slides = ref([]);
const swiperKey = ref(0);

async function loadSlides() {
  try {
    const r = await fetch(API_GET);
    const j = await r.json();

    const urls =
      j?.ok && Array.isArray(j.items)
        ? j.items.map((x) => x.url).filter(Boolean)
        : [];

    slides.value = urls;
    swiperKey.value++;
  } catch (e) {
    slides.value = [];
    swiperKey.value++;
  }
}

async function bindNavigation(swiper) {
  await nextTick();

  if (!prevEl.value || !nextEl.value || !swiper?.navigation) {
    return;
  }

  swiper.params.navigation.prevEl = prevEl.value;
  swiper.params.navigation.nextEl = nextEl.value;
  swiper.originalParams.navigation.prevEl = prevEl.value;
  swiper.originalParams.navigation.nextEl = nextEl.value;
  swiper.navigation.destroy();
  swiper.navigation.init();
  swiper.navigation.update();
}

function updateLocalVars() {
  const el = sliderEl.value;
  if (!el) return;

  const sbw = window.innerWidth - document.documentElement.clientWidth;
  el.style.setProperty("--sbw", `${sbw}px`);
}

onMounted(async () => {
  updateLocalVars();
  await loadSlides();
  window.addEventListener("resize", updateLocalVars, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", updateLocalVars);
});
</script>

<style scoped>
.full-slider {
  width: calc(100vw - var(--sbw, 0px));
  margin-left: calc(50% - 50vw + (var(--sbw, 0px) / 2));
  position: relative;
  overflow: hidden;
  border-radius: 0;
  background: #0b1220;
  box-shadow: none;
}

.full-swiper {
  height: max(250px, min(52vw, 72dvh));
  min-height: 250px;
  background: #0b1220;
  overflow: hidden;
}

:global(.full-swiper .swiper),
:global(.full-swiper .swiper-wrapper),
:global(.full-swiper .swiper-slide) {
  height: 100%;
}

.slide,
.clip,
.media-frame,
.empty-card {
  position: relative;
  width: 100%;
  height: 100%;
}

.clip {
  overflow: hidden;
}

.media-frame,
.empty-card {
  overflow: hidden;
  border: 0;
  border-radius: 0;
  background: #0b1220;
  box-shadow: none;
}

.slide-img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  object-position: center;
}

.shade {
  position: absolute;
  inset: 0;
  z-index: 2;
  pointer-events: none;
  background:
    linear-gradient(180deg, rgba(2, 6, 23, 0.14) 0%, rgba(2, 6, 23, 0) 28%),
    linear-gradient(0deg, rgba(2, 6, 23, 0.22) 0%, rgba(2, 6, 23, 0) 24%);
}

.empty-hero {
  position: relative;
}

.empty-card {
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0) 32%),
    linear-gradient(135deg, #111827 0%, #0b1220 100%);
}

.slider-nav {
  position: absolute;
  top: 50%;
  z-index: 25;
  transform: translateY(-50%);
  width: 52px;
  height: 52px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(7, 12, 22, 0.46);
  color: #f8fafc;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  cursor: pointer;
  transition:
    background 0.18s ease,
    border-color 0.18s ease,
    color 0.18s ease,
    opacity 0.18s ease;
}

.slider-nav:hover {
  background: rgba(7, 12, 22, 0.64);
  border-color: rgba(255, 255, 255, 0.24);
  color: #ffffff;
}

.slider-nav:focus-visible {
  outline: none;
  background: rgba(7, 12, 22, 0.72);
  border-color: rgba(255, 255, 255, 0.32);
  color: #ffffff;
}

.slider-nav :deep(svg) {
  font-size: 18px;
}

.slider-nav-prev {
  left: clamp(12px, 2vw, 28px);
}

.slider-nav-next {
  right: clamp(12px, 2vw, 28px);
}

:global(.full-swiper .swiper-button-next),
:global(.full-swiper .swiper-button-prev) {
  display: none;
}

:global(.full-swiper .swiper-pagination) {
  bottom: 14px;
  z-index: 20;
}

:global(.full-swiper .swiper-pagination-bullet) {
  width: 24px;
  height: 3px;
  border-radius: 0;
  background: rgba(255, 255, 255, 0.36);
  opacity: 1;
}

:global(.full-swiper .swiper-pagination-bullet-active) {
  background: #fff;
}

@media (max-width: 767px) {
  .full-swiper {
    height: max(220px, min(58vw, 46dvh));
    min-height: 220px;
  }

  .slider-nav {
    width: 42px;
    height: 42px;
  }

  .slider-nav :deep(svg) {
    font-size: 15px;
  }
}

@media (max-width: 420px) {
  .full-swiper {
    min-height: 210px;
  }

  .slider-nav {
    width: 38px;
    height: 38px;
  }
}
</style>
