<template>
  <section>
    <div id="photo" class="photo-gallery">
      <h2 class="page-title">Фотографии магазина</h2>
  
      <!-- Переключатели -->
      <div class="pg-switch">
        <button
          :class="{ active: mode === 'outside' }"
          @click="setMode('outside')"
        >
          Снаружи
        </button>
  
        <button
          :class="{ active: mode === 'product' }"
          @click="setMode('product')"
        >
          Товары
        </button>
      </div>
  
      <!-- ГАЛЕРЕЯ -->
      <swiper
        :modules="[Navigation]"
        navigation
        :slides-per-view="slidesPerView"
        :space-between="16"
        :loop="photos.length > slidesPerView"
        class="pg-swiper"
      >
        <swiper-slide v-for="(img, i) in photos" :key="i">
          <img :src="img" class="pg-img" />
        </swiper-slide>
      </swiper>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from "vue";

/* ВАЖНО — правильные импорты Swiper */
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation } from "swiper/modules";

/* Стили Swiper */
import "swiper/css";
import "swiper/css/navigation";

/* Фото */
const outsidePhotos = Array.from(
  { length: 4 },
  (_, i) => `/img/photo-shop/outside${i + 1}.webp`
);

const productPhotos = Array.from(
  { length: 20 },
  (_, i) => `/img/photo-shop/product${i + 1}.webp`
);


const mode = ref("outside");

const photos = computed(() =>
  mode.value === "outside" ? outsidePhotos : productPhotos
);

/* Адаптивное количество фото */
const slidesPerView = computed(() => {
  const w = window.innerWidth;
  if (w < 600) return 2; // мобильный → 2 фото
  if (w < 1024) return 3; // планшет → 3
  return 4; // ПК → 4
});

function setMode(val) {
  mode.value = val;
}
</script>

<style scoped>
/* ===== Фотогалерея (под твой :root) ===== */

.photo-gallery{
  width: 100%;
  max-width: 980px;
  margin: 0 auto;
  padding: 18px 14px 30px;

  display: flex;
  flex-direction: column;
  align-items: center;

  color: var(--text-main);
}

.page-title{
  margin: 6px 0 14px;
  font-size: 28px;
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

/* ===== Переключатели ===== */
.pg-switch{
  display: inline-flex;
  align-items: center;
  gap: 10px;

  padding: 6px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 999px;
  box-shadow: var(--shadow-sm);

  margin: 6px 0 18px;
}

.pg-switch button{
  border: 1px solid transparent;
  background: transparent;
  color: var(--text-main);

  padding: 9px 14px;
  border-radius: 999px;
  cursor: pointer;

  font-size: 14px;
  font-weight: 800;
  letter-spacing: -0.01em;

  transition: background .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.pg-switch button:hover{
  background: var(--bg-soft);
  border-color: var(--border-soft);
  transform: translateY(-1px);
}

.pg-switch button:active{
  transform: translateY(0);
}

.pg-switch button.active{
  background: var(--accent);
  color: #fff;
  border-color: rgba(0,0,0,0.08);
  box-shadow: 0 10px 22px rgba(4, 0, 231, 0.18);
}

/* ===== Swiper контейнер ===== */
.pg-swiper{
  width: 100%;
  position: relative;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);

  padding: 14px 14px;
}

/* Внутренние отступы, чтобы стрелки не залезали на фото */
:global(.pg-swiper .swiper){
  padding: 0 6px;
}

/* Фото */
.pg-img{
  width: 100%;
  height: 240px;
  object-fit: cover;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  display: block;
}

/* ===== Стрелки Swiper ===== */
:global(.pg-swiper .swiper-button-next),
:global(.pg-swiper .swiper-button-prev){
  width: 42px;
  height: 42px;
  border-radius: 999px;

  background: var(--accent);
  border: 1px solid rgba(0,0,0,0.10);
  box-shadow: 0 12px 26px rgba(4, 0, 231, 0.18);

  transition: transform .18s ease, filter .18s ease, opacity .18s ease;
}

:global(.pg-swiper .swiper-button-next:hover),
:global(.pg-swiper .swiper-button-prev:hover){
  transform: translateY(-1px);
  filter: brightness(1.06);
}

:global(.pg-swiper .swiper-button-next:active),
:global(.pg-swiper .swiper-button-prev:active){
  transform: translateY(0);
  filter: brightness(0.98);
}

/* Иконка стрелки */
:global(.pg-swiper .swiper-button-next::after),
:global(.pg-swiper .swiper-button-prev::after){
  font-size: 16px;
  font-weight: 900;
  color: #fff;
}

/* Disabled */
:global(.pg-swiper .swiper-button-disabled){
  opacity: .35 !important;
}

/* ===== Responsive ===== */
@media (max-width: 1024px){
  .pg-img{ height: 210px; }
}

@media (max-width: 600px){
  .photo-gallery{
    padding: 14px 12px 26px;
  }

  .page-title{
    font-size: 24px;
    margin-bottom: 12px;
  }

  .pg-switch{
    width: 100%;
    justify-content: center;
  }

  .pg-switch button{
    flex: 1;
    text-align: center;
  }

  .pg-swiper{
    padding: 12px;
    border-radius: var(--radius-lg);
  }

  .pg-img{ height: 190px; }

  :global(.pg-swiper .swiper-button-next),
  :global(.pg-swiper .swiper-button-prev){
    width: 38px;
    height: 38px;
  }
}
</style>

