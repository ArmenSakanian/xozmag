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
      navigation
      :pagination="{ clickable: true }"
      :autoplay="autoplayEnabled ? autoplayOptions : false"
    >
      <SwiperSlide v-for="(src, i) in slides" :key="src + ':' + i">
        <div class="slide">
          <div class="clip">
            <div
              class="bg"
              :style="{ backgroundImage: `url(${src})` }"
              aria-hidden="true"
            ></div>

            <img
              class="slide-img"
              :src="src"
              :alt="`Slide ${i + 1}`"
              loading="lazy"
              decoding="async"
            />

            <div class="shade" aria-hidden="true"></div>
          </div>
        </div>
      </SwiperSlide>
    </Swiper>

    <div v-else class="full-swiper empty-hero" aria-hidden="true">
      <div class="empty-bg"></div>
      <div class="shade"></div>
    </div>
  </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, Autoplay } from "swiper/modules";

const sliderEl = ref(null);

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
  overflow-x: clip;
  border-radius: 30px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.16);
}

.full-swiper {
  height: max(240px, min(56.25vw, 70dvh));
  min-height: 240px;
  background: #0f1115;
  overflow: hidden;
}

:global(.full-swiper .swiper),
:global(.full-swiper .swiper-wrapper),
:global(.full-swiper .swiper-slide) {
  height: 100%;
}

.slide {
  position: relative;
  width: 100%;
  height: 100%;
}

.clip {
  position: absolute;
  inset: 0;
  overflow: hidden;
  background: #0f1115;
}

.bg {
  position: absolute;
  inset: -24px;
  background-size: cover;
  background-position: center;
  filter: blur(24px);
  transform: scale(1.1);
  opacity: 0.7;
}

.slide-img {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  object-position: center;
}

.shade {
  position: absolute;
  inset: 0;
  z-index: 3;
  pointer-events: none;
  background:
    radial-gradient(
      1100px 400px at 50% 72%,
      rgba(0, 0, 0, 0.18),
      rgba(0, 0, 0, 0) 55%
    ),
    linear-gradient(
      to bottom,
      rgba(0, 0, 0, 0.28) 0%,
      rgba(0, 0, 0, 0.06) 38%,
      rgba(0, 0, 0, 0.48) 100%
    );
}

.empty-hero {
  position: relative;
}

.empty-bg {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(
      900px 360px at 50% 40%,
      rgba(255, 255, 255, 0.06),
      rgba(255, 255, 255, 0) 60%
    ),
    linear-gradient(to bottom, #0f1115, #0b0d10);
}

:global(.full-swiper .swiper-button-next),
:global(.full-swiper .swiper-button-prev) {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.88);
  border: 1px solid rgba(0, 0, 0, 0.1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
  transition:
    transform 0.18s ease,
    filter 0.18s ease,
    opacity 0.18s ease;
  z-index: 20;
}

:global(.full-swiper .swiper-button-next:hover),
:global(.full-swiper .swiper-button-prev:hover) {
  transform: translateY(-1px);
}

:global(.full-swiper .swiper-pagination) {
  bottom: 14px;
  z-index: 20;
}

@media (max-width: 767px) {
  .full-slider {
    border-radius: 0;
  }

  .full-swiper {
    height: max(220px, min(56.25vw, 48dvh));
    min-height: 220px;
  }

  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev) {
    width: 40px;
    height: 40px;
    border-radius: 12px;
  }
}

@media (max-width: 420px) {
  .full-slider {
    border-radius: 0;
  }

  .full-swiper {
    min-height: 210px;
  }

  :global(.full-swiper .swiper-button-next),
  :global(.full-swiper .swiper-button-prev) {
    display: none;
  }

  :global(.full-swiper .swiper-pagination) {
    bottom: 10px;
  }
}
</style>