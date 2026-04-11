<template>
  <section v-if="items.length" class="showcase-shell" aria-label="Подборка товаров">
    <div class="showcase-head">
      <div class="showcase-title">{{ titleText }}</div>

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
      :space-between="14"
      :slides-per-view="1"
      :slides-per-group="1"
      :loop="items.length > 2"
      :watch-overflow="true"
      :center-insufficient-slides="true"
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
          </div>

          <div class="showcase-body">
            <div class="showcase-topline">
              <div class="showcase-name">{{ item.title }}</div>
              <div v-if="item.price" class="showcase-price">{{ formatPrice(item.price) }}</div>
            </div>

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
import { computed, onMounted, ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation } from 'swiper/modules';

const API_GET = '/api/vitrina/get_home_showcase.php';
const swiperModules = [Navigation];
const swiperRef = ref(null);
const title = ref('');
const items = ref([]);

const breakpoints = {
  0: { slidesPerView: 1, spaceBetween: 10 },
  900: { slidesPerView: 2, spaceBetween: 14 },
};

const titleText = computed(() => title.value || 'Подборка товаров');
const canNavigate = computed(() => items.value.length > 1);

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
  const digits = String(value ?? '').replace(/\D+/g, '');
  if (!digits) return '';
  return `${new Intl.NumberFormat('ru-RU').format(Number(digits))} ₽`;
}

async function loadData() {
  try {
    const res = await fetch(API_GET, { headers: { Accept: 'application/json' } });
    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data?.ok) {
      items.value = [];
      return;
    }

    title.value = String(data?.settings?.title || '').trim();
    items.value = Array.isArray(data?.items) ? data.items : [];
  } catch (e) {
    items.value = [];
  }
}

onMounted(loadData);
</script>

<style scoped>
.showcase-shell {
  width: min(1180px, 94vw);
  max-width: 1180px;
  margin: 0 auto;
  padding: 14px;
  box-sizing: border-box;
  overflow: hidden;
  border-radius: 26px;
  background: rgba(15, 23, 42, 0.42);
  border: 1px solid rgba(255, 255, 255, 0.18);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  pointer-events: auto;
}

.showcase-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.showcase-title {
  color: #fff;
  font-size: clamp(16px, 1.8vw, 22px);
  font-weight: 900;
  line-height: 1.2;
  min-width: 0;
}

.showcase-nav {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.nav-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  cursor: pointer;
  transition: 0.18s ease;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
  -webkit-user-select: none;
}

.nav-btn:hover {
  background: rgba(255, 255, 255, 0.24);
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
  border-radius: 24px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.98);
  color: #111827;
  box-shadow: 0 16px 38px rgba(0, 0, 0, 0.16);
}

.showcase-media {
  width: 100%;
  aspect-ratio: 16 / 9;
  min-height: 220px;
  max-height: 320px;
  overflow: hidden;
  background: #e5e7eb;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
  flex: 0 0 auto;
}

.showcase-image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  object-position: center;
}

.showcase-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  min-width: 0;
  flex: 1 1 auto;
}

.showcase-topline {
  display: grid;
  gap: 8px;
}

.showcase-name {
  font-size: 18px;
  font-weight: 900;
  line-height: 1.35;
  color: #0f172a;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  touch-action: manipulation;
}

.showcase-price {
  font-size: 22px;
  font-weight: 900;
  color: #0f172a;
}

.showcase-desc {
  font-size: 13px;
  line-height: 1.5;
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
  min-height: 42px;
  padding: 10px 16px;
  border-radius: 14px;
  text-decoration: none;
  background: #0f172a;
  color: #fff;
  font-weight: 800;
  white-space: nowrap;
}

@media (max-width: 899px) {
  .showcase-shell {
    width: min(100%, 94vw);
    padding: 12px;
    border-radius: 22px;
  }

  .showcase-head {
    margin-bottom: 12px;
  }

  .showcase-title {
    font-size: 16px;
  }

  .nav-btn {
    width: 36px;
    height: 36px;
    border-radius: 11px;
  }

  .showcase-media {
    min-height: 200px;
    max-height: 280px;
  }

  .showcase-body {
    padding: 14px;
  }

  .showcase-name {
    font-size: 16px;
  }

  .showcase-price {
    font-size: 20px;
  }
}

@media (max-width: 640px) {
  .showcase-shell {
    padding: 10px;
    border-radius: 18px;
  }

  .showcase-head {
    gap: 10px;
    margin-bottom: 10px;
  }

  .showcase-title {
    font-size: 15px;
  }

  .nav-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
  }

  .showcase-card {
    border-radius: 18px;
  }

  .showcase-media {
    min-height: 180px;
    max-height: 240px;
  }

  .showcase-body {
    gap: 10px;
    padding: 12px;
  }

  .showcase-name {
    font-size: 15px;
  }

  .showcase-price {
    font-size: 18px;
  }

  .showcase-desc {
    font-size: 12px;
    line-height: 1.45;
    -webkit-line-clamp: 3;
  }

  .showcase-btn {
    width: 100%;
  }
}
</style>