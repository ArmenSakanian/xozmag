<template>
  <div class="pcg" :class="{ compact }">
    <!-- нет фото -->
    <div v-if="!safeImages.length" class="pcg-empty">
      <img class="pcg-empty-img" :src="fallback" :alt="alt" />
    </div>

    <!-- есть фото -->
    <div v-else class="pcg-multi">
      <Swiper
        class="pcg-main"
        :modules="modules"
        :slides-per-view="1"
        :space-between="0"
        :loop="false"
        :watch-overflow="true"
        :navigation="!compact && safeImages.length > 1"
        :pagination="compact && safeImages.length > 1 ? { clickable: true } : false"
        :observer="true"
        :observe-parents="true"
        :resize-observer="true"
        :update-on-window-resize="true"
        @swiper="onMainSwiper"
      >
        <SwiperSlide
          v-for="(src, i) in safeImages"
          :key="src + '_' + i"
          class="pcg-main-slide"
        >
          <div class="pcg-main-frame">
            <img
              class="pcg-main-img"
              :src="src"
              :alt="alt"
              loading="lazy"
              @load="forceUpdate"
            />
          </div>
        </SwiperSlide>
      </Swiper>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import { Navigation, Pagination, A11y } from "swiper/modules";

const props = defineProps({
  images: { type: Array, default: () => [] },
  alt: { type: String, default: "" },
  compact: { type: Boolean, default: false },
  fallback: { type: String, default: "/img/no-photo.png" },
});

const modules = [Navigation, Pagination, A11y];

const safeImages = computed(() => {
  const arr = (props.images || []).filter(Boolean);
  return arr;
});

const mainSwiper = ref(null);

const onMainSwiper = (s) => {
  mainSwiper.value = s;
  forceUpdate();
};

// двойной RAF — самый надёжный способ, чтобы Swiper взял реальную ширину после layout
const forceUpdate = () => {
  const s = mainSwiper.value;
  if (!s) return;
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      try {
        s.update();
      } catch (e) {}
    });
  });
};

onMounted(() => {
  forceUpdate();
});

watch(
  () => props.images,
  () => forceUpdate(),
  { deep: true }
);

onBeforeUnmount(() => {
  mainSwiper.value = null;
});
</script>

<style scoped>
/* критично против 33554400px */
.pcg {
  width: 100%;
  height: 100%;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

.pcg-multi,
.pcg-main {
  width: 100%;
  height: 100%;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

/* нет фото */
.pcg-empty {
  width: 100%;
  height: 100%;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}
.pcg-empty-img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  max-width: 100%;
}

/* кадр с фото */
.pcg-main-frame {
  width: 100%;
  height: 100%;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

/* главное: cover + 100% */
.pcg-main-img {
  width: 100%;
  height: 100%;
  display: block;
  max-width: 100%;
  object-fit: cover;
}

/* ==== Swiper hard-limits (фикс 33554400px) ==== */
.pcg :deep(.swiper) {
  width: 100% !important;
  height: 100% !important;
  max-width: 100% !important;
  overflow: hidden;
}

.pcg :deep(.swiper-wrapper) {
  max-width: 100% !important;
}

.pcg :deep(.swiper-slide) {
  /* перебиваем inline width от Swiper */
  width: 100% !important;
  max-width: 100% !important;
  flex: 0 0 100% !important;
}

/* пагинация на мобилке */
.pcg :deep(.swiper-pagination-bullet) {
  width: 7px;
  height: 7px;
  opacity: 0.35;
}
.pcg :deep(.swiper-pagination-bullet-active) {
  opacity: 1;
}

/* стрелки (десктоп) */
.pcg :deep(.swiper-button-next),
.pcg :deep(.swiper-button-prev) {
  width: 36px;
  height: 36px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.14);
}
.pcg :deep(.swiper-button-next::after),
.pcg :deep(.swiper-button-prev::after) {
  font-size: 14px;
  font-weight: 900;
  color: #111827;
}
</style>
