<template>
  <section v-if="items.length" class="showcase-shell" aria-label="Подборка товаров">
    <div class="showcase-head">
      <div class="showcase-copy">
        <div class="showcase-kicker">Все Для Дома</div>
        <h2 class="showcase-title">{{ titleText }}</h2>
        <p class="showcase-text">
          Популярные товары и полезные позиции для дома - аккуратная витрина с быстрым переходом.
        </p>
      </div>

      <div class="showcase-nav" v-if="canNavigate">
        <button type="button" class="nav-btn" aria-label="Назад" @click="slidePrev">
          <Fa :icon="['fas', 'chevron-left']" />
        </button>
        <button type="button" class="nav-btn" aria-label="Вперед" @click="slideNext">
          <Fa :icon="['fas', 'chevron-right']" />
        </button>
      </div>
    </div>

    <Swiper
      :modules="swiperModules"
      :space-between="16"
      :slides-per-view="1"
      :slides-per-group="1"
      :loop="items.length > 4"
      :watch-overflow="true"
      :center-insufficient-slides="false"
      :breakpoints="breakpoints"
      class="showcase-swiper"
      @swiper="onSwiper"
    >
      <SwiperSlide v-for="item in items" :key="item.id">
        <article class="showcase-card">
          <div class="showcase-media">
            <img
              class="showcase-image"
              :src="item.image_url"
              :alt="item.title"
              loading="lazy"
              decoding="async"
            />

            <div v-if="item.price" class="showcase-price-badge">
              {{ formatPrice(item.price) }}
            </div>
          </div>

          <div class="showcase-body">
            <div class="showcase-name">{{ item.title }}</div>

            <div v-if="item.description" class="showcase-desc">
              {{ item.description }}
            </div>

            <a
              v-if="item.button_text && item.button_url"
              class="showcase-btn"
              :href="item.button_url"
            >
              {{ item.button_text }}
            </a>
          </div>
        </article>
      </SwiperSlide>
    </Swiper>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation } from "swiper/modules";

const API_GET = "/api/vitrina/get_home_showcase.php";
const swiperModules = [Navigation];
const swiperRef = ref(null);
const title = ref("");
const items = ref([]);

const breakpoints = {
  0: { slidesPerView: 1.1, slidesPerGroup: 1, spaceBetween: 12 },
  640: { slidesPerView: 2, slidesPerGroup: 2, spaceBetween: 14 },
  1024: { slidesPerView: 3, slidesPerGroup: 4, spaceBetween: 16 },
};

const titleText = computed(() => title.value || "Подборка товаров");
const canNavigate = computed(() => items.value.length > 3);

function onSwiper(swiper) {
  swiperRef.value = swiper;
}

function slidePrev() {
  swiperRef.value?.slidePrev();
}

function slideNext() {
  swiperRef.value?.slideNext();
}

function formatPrice(value) {
  const digits = String(value ?? "").replace(/\D+/g, "");
  if (!digits) return "";
  return `${new Intl.NumberFormat("ru-RU").format(Number(digits))} ₽`;
}

async function loadData() {
  try {
    const res = await fetch(API_GET, { headers: { Accept: "application/json" } });
    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data?.ok) {
      items.value = [];
      return;
    }

    title.value = String(data?.settings?.title || "").trim();
    items.value = Array.isArray(data?.items) ? data.items : [];
  } catch (e) {
    items.value = [];
  }
}

onMounted(loadData);
</script>

<style scoped>
.showcase-shell {
  width: 100%;
  padding: 22px;
  border-radius: 28px;
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 18px 54px rgba(15, 23, 42, 0.08);
}

.showcase-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
}

.showcase-copy {
  min-width: 0;
  max-width: 760px;
}

.showcase-kicker {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(252, 200, 34, 0.14);
  color: #c98900;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.showcase-title {
  margin: 12px 0 0;
  color: #111827;
  font-size: clamp(26px, 2.4vw, 36px);
  font-weight: 1000;
  line-height: 1.08;
  letter-spacing: -0.03em;
}

.showcase-text {
  margin: 10px 0 0;
  color: #475569;
  font-size: 15px;
  line-height: 1.6;
}

.showcase-nav {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.nav-btn {
  width: 46px;
  height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: #ffffff;
  color: #111827;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

.nav-btn:hover {
  transform: translateY(-1px);
  border-color: rgba(252, 200, 34, 0.55);
  box-shadow: 0 14px 26px rgba(15, 23, 42, 0.12);
}

.showcase-swiper {
  width: 100%;
  overflow: hidden;
}

.showcase-swiper :deep(.swiper-wrapper) {
  align-items: stretch;
}

.showcase-swiper :deep(.swiper-slide) {
  height: auto;
  display: flex;
  box-sizing: border-box;
}

.showcase-card {
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: 22px;
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
}

.showcase-media {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background: #f8fafc;
}

.showcase-image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  object-position: center;
}

.showcase-price-badge {
  position: absolute;
  left: 14px;
  bottom: 14px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  padding: 0 14px;
  border-radius: 999px;
  background: rgba(17, 24, 39, 0.92);
  color: #ffffff;
  font-size: 14px;
  font-weight: 900;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
}

.showcase-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 18px;
  min-width: 0;
  flex: 1 1 auto;
}

.showcase-name {
  font-size: 19px;
  font-weight: 900;
  line-height: 1.3;
  color: #0f172a;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.showcase-desc {
  font-size: 14px;
  line-height: 1.6;
  color: #475569;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
}

.showcase-btn {
  margin-top: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  align-self: flex-start;
  min-height: 46px;
  padding: 0 18px;
  border-radius: 14px;
  text-decoration: none;
  background: var(--secondary-accent);
  color: #111827;
  font-weight: 900;
  white-space: nowrap;
  box-shadow: 0 12px 22px rgba(252, 200, 34, 0.24);
}

.showcase-btn:hover {
  filter: brightness(0.98);
}

@media (max-width: 1023px) {
  .showcase-shell {
    padding: 18px;
    border-radius: 24px;
  }

  .showcase-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .showcase-nav {
    align-self: flex-end;
  }
}

@media (max-width: 639px) {
  .showcase-shell {
    padding: 14px;
    border-radius: 20px;
  }

  .showcase-title {
    font-size: 24px;
  }

  .showcase-text {
    font-size: 13px;
    line-height: 1.5;
  }

  .showcase-nav {
    width: 100%;
    justify-content: flex-end;
  }

  .nav-btn {
    width: 40px;
    height: 40px;
    border-radius: 12px;
  }

  .showcase-card {
    border-radius: 18px;
  }

  .showcase-price-badge {
    left: 12px;
    bottom: 12px;
    min-height: 32px;
    padding: 0 12px;
    font-size: 13px;
  }

  .showcase-body {
    gap: 10px;
    padding: 14px;
  }

  .showcase-name {
    font-size: 17px;
  }

  .showcase-desc {
    font-size: 13px;
    line-height: 1.5;
  }

  .showcase-btn {
    width: 100%;
    min-height: 44px;
  }
}
</style>
