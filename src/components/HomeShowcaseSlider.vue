<template>
  <section
    v-if="items.length"
    class="showcase-wrap"
    aria-label="Рекомендуемые товары"
    @pointerdown.stop
    @pointermove.stop
    @touchstart.stop
    @touchmove.stop
    @wheel.stop
  >
    <div class="showcase-box">
      <div class="showcase-head">
        <div class="showcase-title">{{ title || "Рекомендуем посмотреть" }}</div>
      </div>

      <Swiper
        class="showcase-swiper"
        :modules="[Navigation]"
        :navigation="items.length > 1"
        :slides-per-view="1"
        :space-between="12"
        :slides-per-group="1"
        :breakpoints="breakpoints"
        :loop="items.length > 1"
        :grab-cursor="items.length > 1"
        :nested="true"
      >
        <SwiperSlide v-for="item in items" :key="item.id">
          <article class="promo-card">
            <div class="promo-media">
              <img
                class="promo-image"
                :src="item.image_url"
                :alt="item.title"
                loading="lazy"
                decoding="async"
              />
            </div>

            <div class="promo-body">
              <div class="promo-name">{{ item.title }}</div>
              <div v-if="item.price" class="promo-price">{{ item.price }}</div>
              <p v-if="item.description" class="promo-desc">{{ item.description }}</p>

              <a
                class="promo-btn"
                :href="item.button_url"
                :target="isExternal(item.button_url) ? '_blank' : undefined"
                :rel="isExternal(item.button_url) ? 'noopener noreferrer' : undefined"
              >
                {{ item.button_text }}
              </a>
            </div>
          </article>
        </SwiperSlide>
      </Swiper>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation } from "swiper/modules";

const API_GET = "/api/vitrina/get_home_showcase.php";

const title = ref("");
const items = ref([]);

const breakpoints = {
  0: {
    slidesPerView: 1,
    spaceBetween: 12,
  },
  760: {
    slidesPerView: 2,
    spaceBetween: 14,
  },
  1100: {
    slidesPerView: 3,
    spaceBetween: 16,
  },
};

function isExternal(url) {
  return /^https?:\/\//i.test(String(url || ""));
}

async function loadShowcase() {
  try {
    const res = await fetch(API_GET, {
      method: "GET",
      headers: { Accept: "application/json" },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) return;

    title.value = String(data?.title || "").trim();
    items.value = Array.isArray(data?.items)
      ? data.items.filter((item) => item?.id && item?.title && item?.image_url && item?.button_text && item?.button_url)
      : [];
  } catch {
    title.value = "";
    items.value = [];
  }
}

onMounted(loadShowcase);
</script>

<style scoped>
.showcase-wrap {
  width: min(1120px, 96vw);
  pointer-events: auto;
}

.showcase-box {
  padding: 16px;
  border-radius: 24px;
  background: rgba(15, 23, 42, 0.34);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.28);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

.showcase-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.showcase-title {
  color: rgba(255, 255, 255, 0.96);
  font-size: clamp(18px, 2vw, 24px);
  font-weight: 900;
  line-height: 1.2;
}

.promo-card {
  min-height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.18);
}

.promo-media {
  aspect-ratio: 1.2 / 1;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.08);
}

.promo-image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.promo-body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 8px;
  padding: 14px;
}

.promo-name {
  color: #fff;
  font-size: 16px;
  font-weight: 900;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.promo-price {
  color: #fff;
  font-size: 18px;
  font-weight: 900;
  line-height: 1.2;
}

.promo-desc {
  margin: 0;
  color: rgba(255, 255, 255, 0.88);
  font-size: 13px;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.promo-btn {
  margin-top: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 10px 14px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.94);
  color: #0f172a;
  font-size: 14px;
  font-weight: 900;
  text-decoration: none;
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.promo-btn:hover {
  transform: translateY(-1px);
}

.showcase-swiper :deep(.swiper-wrapper) {
  align-items: stretch;
}

.showcase-swiper :deep(.swiper-slide) {
  height: auto;
}

.showcase-swiper :deep(.swiper-button-next),
.showcase-swiper :deep(.swiper-button-prev) {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
  color: #111827;
}

.showcase-swiper :deep(.swiper-button-next::after),
.showcase-swiper :deep(.swiper-button-prev::after) {
  font-size: 16px;
  font-weight: 900;
}

@media (max-width: 759px) {
  .showcase-box {
    padding: 12px;
    border-radius: 20px;
  }

  .showcase-title {
    font-size: 16px;
  }

  .promo-body {
    padding: 12px;
  }

  .promo-name {
    font-size: 15px;
  }

  .promo-price {
    font-size: 17px;
  }

  .promo-desc {
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .showcase-swiper :deep(.swiper-button-next),
  .showcase-swiper :deep(.swiper-button-prev) {
    display: none;
  }
}
</style>
