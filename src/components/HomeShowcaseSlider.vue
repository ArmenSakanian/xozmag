<template>
  <section v-if="items.length" class="showcase-root" aria-label="Подборка товаров">
    <div class="showcase-head">
      <div class="showcase-head-main">
        <span class="showcase-kicker">Подборка</span>
        <h2 class="showcase-title">{{ titleText }}</h2>
        <div class="showcase-sub">
          Популярные товары и полезные позиции с быстрым переходом без лишних промежуточных экранов.
        </div>
      </div>

      <div class="showcase-side">

        <div v-if="canNavigate" class="showcase-nav">
          <button type="button" class="showcase-nav-btn" aria-label="Назад" @click="slidePrev">
            <Fa :icon="['fas', 'chevron-left']" />
          </button>
          <button type="button" class="showcase-nav-btn" aria-label="Вперед" @click="slideNext">
            <Fa :icon="['fas', 'chevron-right']" />
          </button>
        </div>
      </div>
    </div>

    <Swiper
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
      <SwiperSlide v-for="(item, index) in items" :key="item.id">
        <article class="showcase-card">
          <div class="showcase-media">
            <img
              v-if="item.image_url && !imgErr[item.id]"
              class="showcase-image"
              :src="item.image_url"
              :alt="item.title"
              loading="lazy"
              decoding="async"
              @error="imgErr[item.id] = true"
            />

            <div v-else class="showcase-visual-ph" aria-hidden="true">
              <Fa :icon="['far', 'image']" />
            </div>

            <div class="showcase-media-shade"></div>
          </div>

          <div class="showcase-body">
            <div class="showcase-topline">
              <span>Товар {{ String(index + 1).padStart(2, '0') }}</span>
              <span v-if="item.price" class="showcase-price">{{ formatPrice(item.price) }}</span>
            </div>

            <div class="showcase-name" :title="item.title">{{ item.title }}</div>

            <div v-if="item.description" class="showcase-desc" :title="item.description">
              {{ item.description }}
            </div>

            <a
              v-if="item.button_text && item.button_url"
              class="showcase-btn"
              :href="item.button_url"
            >
              <span class="showcase-btn-text" :title="item.button_text">{{ item.button_text }}</span>
              <span class="showcase-btn-ic" aria-hidden="true">
                <Fa :icon="['fas', 'chevron-right']" />
              </span>
            </a>
          </div>
        </article>
      </SwiperSlide>
    </Swiper>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";

const API_GET = "/api/vitrina/get_home_showcase.php";
const swiperRef = ref(null);
const title = ref("");
const items = ref([]);
const imgErr = ref({});

const breakpoints = {
  0: { slidesPerView: 1.08, slidesPerGroup: 1, spaceBetween: 12 },
  640: { slidesPerView: 2, slidesPerGroup: 2, spaceBetween: 14 },
  1024: { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: 16 },
  1280: { slidesPerView: 4, slidesPerGroup: 4, spaceBetween: 18 },
};

const titleText = computed(() => title.value || "Подборка товаров");
const canNavigate = computed(() => items.value.length > 4);

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

watch(
  items,
  (val) => {
    const next = {};
    (Array.isArray(val) ? val : []).forEach((item) => {
      if (item?.id != null) next[item.id] = false;
    });
    imgErr.value = next;
  },
  { immediate: true }
);

onMounted(loadData);
</script>

<style scoped>
.showcase-root {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.showcase-head {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(320px, 420px);
  gap: 20px;
  align-items: end;
}

.showcase-head-main {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.showcase-kicker {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(15, 23, 42, 0.04);
  color: #334155;
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.showcase-title {
  margin: 0;
  font-size: clamp(26px, 3vw, 40px);
  line-height: 1;
  letter-spacing: -0.04em;
  font-weight: 1000;
  color: #0f172a;
}

.showcase-sub {
  max-width: 760px;
  font-size: 14px;
  line-height: 1.55;
  font-weight: 700;
  color: #475569;
}

.showcase-side {
  display: flex;
  flex-direction: column;
  gap: 14px;
}


.showcase-nav {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.showcase-nav-btn {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: #0f172a;
  color: #ffffff;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
}

.showcase-nav-btn:hover {
  transform: translateY(-2px);
  filter: brightness(1.05);
  box-shadow: 0 18px 34px rgba(15, 23, 42, 0.22);
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
  position: relative;
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #0f172a;
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.14);
  transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
}

.showcase-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 24px 44px rgba(15, 23, 42, 0.2);
  filter: saturate(1.02);
}

.showcase-media {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0) 42%),
    linear-gradient(135deg, #111827 0%, #0f172a 100%);
}

.showcase-visual-ph {
  position: absolute;
  inset: 0;
}

.showcase-media img,
.showcase-visual-ph {
  width: 100%;
  height: 100%;
}

.showcase-image {
  display: block;
  object-fit: contain;
  object-position: center;
  background: transparent;
  transform: scale(1.001);
  transition: transform 0.28s ease;
}

.showcase-card:hover .showcase-image {
  transform: scale(1.02);
}

.showcase-media-shade {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(180deg, rgba(2, 6, 23, 0.04) 0%, rgba(2, 6, 23, 0.14) 100%),
    linear-gradient(90deg, rgba(2, 6, 23, 0.16) 0%, rgba(2, 6, 23, 0) 50%, rgba(2, 6, 23, 0.16) 100%);
  pointer-events: none;
}

.showcase-visual-ph {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: rgba(255, 255, 255, 0.72);
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0) 30%),
    linear-gradient(135deg, #111827 0%, #0f172a 100%);
}

.showcase-body {
  position: relative;
  z-index: 1;
  width: 100%;
  margin-top: 0;
  padding: 20px;
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  gap: 12px;
  background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
}

.showcase-topline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 11px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.62);
}

.showcase-price {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: none;
}

.showcase-name {
  min-height: 3.48em;
  font-size: 22px;
  line-height: 1.16;
  font-weight: 900;
  letter-spacing: -0.03em;
  color: #ffffff;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.showcase-desc {
  font-size: 14px;
  line-height: 1.58;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.84);
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 4;
  overflow: hidden;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.showcase-btn {
  margin-top: auto;
  display: inline-flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  min-height: 44px;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
  text-decoration: none;
  font-size: 13px;
  font-weight: 800;
  color: rgba(255, 255, 255, 0.92);
}

.showcase-btn-text {
  flex: 1 1 auto;
  min-width: 0;
  line-height: 1.45;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.showcase-btn-ic {
  align-self: center;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

@media (max-width: 920px) {
  .showcase-head {
    grid-template-columns: 1fr;
    align-items: stretch;
  }

  .showcase-side {
    gap: 12px;
  }

  .showcase-nav {
    justify-content: space-between;
  }
}

@media (max-width: 640px) {
  .showcase-root {
    gap: 16px;
  }

  .showcase-title {
    font-size: 28px;
  }

  .showcase-sub {
    font-size: 13px;
  }

  .showcase-nav-btn {
    width: 40px;
    height: 40px;
  }

  .showcase-card {
    min-height: 100%;
  }

  .showcase-body {
    padding: 16px;
  }

  .showcase-name {
    font-size: 20px;
  }

  .showcase-desc {
    font-size: 13px;
    line-height: 1.5;
  }
}
</style>
