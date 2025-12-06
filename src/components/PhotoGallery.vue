<template>
  <div id="photo" class="photo-gallery">
    <h2 class="pg-title">
      {{ mode === "outside" ? "Фотографии магазина" : "Фотографии товаров" }}
    </h2>

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

    <div class="pg-wrapper">
      <!-- стрелка влево -->
      <button v-if="currentIndex > 0" class="pg-arrow left" @click="prev">
        ‹
      </button>

      <div 
  class="pg-view"
  @mousedown="onDragStart"
  @mousemove="onDragMove"
  @mouseup="onDragEnd"
  @mouseleave="onDragEnd"

  @touchstart="onDragStart"
  @touchmove="onDragMove"
  @touchend="onDragEnd"
>

        <div class="pg-track" :style="trackStyle">
          <div class="pg-item" v-for="(img, i) in activeImages" :key="i">
            <img :src="img" />
          </div>
        </div>
      </div>

      <!-- стрелка вправо -->
      <button
        v-if="currentIndex < maxIndex"
        class="pg-arrow right"
        @click="next"
      >
        ›
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from "vue";

const mode = ref("outside");

// импорт фото
const outsideImgs = import.meta.glob("@/assets/photo-shop/outside*.webp", {
  eager: true,
  import: "default",
});
const productImgs = import.meta.glob("@/assets/photo-shop/product*.webp", {
  eager: true,
  import: "default",
});

const outsideList = Object.values(outsideImgs);
const productList = Object.values(productImgs);

// текущий индекс
const currentIndex = ref(0);

// сколько видно фото (2 на телефоне, 4 на ПК)
const visibleCount = ref(window.innerWidth <= 768 ? 2 : 4);

// обновлять при ресайзе
window.addEventListener("resize", () => {
  visibleCount.value = window.innerWidth <= 768 ? 2 : 4;
});

// выбрать активный список
const activeImages = computed(() =>
  mode.value === "outside" ? outsideList : productList
);

// максимальный индекс
const maxIndex = computed(() =>
  Math.max(0, activeImages.value.length - visibleCount.value)
);

// стиль движения
const trackStyle = computed(() => ({
  transform: `translateX(${-currentIndex.value * (100 / visibleCount.value)}%)`,
  transition: "transform .35s ease",
}));

function next() {
  if (currentIndex.value < maxIndex.value) currentIndex.value++;
}

function prev() {
  if (currentIndex.value > 0) currentIndex.value--;
}

function setMode(m) {
  mode.value = m;
  currentIndex.value = 0;
}

// ============================
//     DRAG / SWIPE SUPPORT
// ============================
const startX = ref(0);
const deltaX = ref(0);
const isDragging = ref(false);

function onDragStart(e) {
  isDragging.value = true;
  startX.value = e.touches ? e.touches[0].clientX : e.clientX;
}

function onDragMove(e) {
  if (!isDragging.value) return;

  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  deltaX.value = clientX - startX.value;
}

function onDragEnd() {
  if (!isDragging.value) return;
  isDragging.value = false;

  // свайп вправо
  if (deltaX.value > 50) {
    prev();
  }

  // свайп влево
  if (deltaX.value < -50) {
    next();
  }

  deltaX.value = 0;
}
</script>

<style scoped>
.photo-gallery {
  max-width: 1100px;
  margin: 0 auto;
  text-align: center;
}

.pg-title {
  margin-bottom: 20px;
  font-size: 24px;
  font-weight: 600;
}

.pg-switch {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-bottom: 25px;
}

.pg-switch button {
  padding: 10px 22px;
  background: var(--background-input);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.25s;
}

.pg-switch button.active {
  background: var(--accent-color);
}

/* ОБОЛОЧКА СЛАЙДЕРА */
.pg-wrapper {
  position: relative;
  width: 100%;
}

.pg-view {
  overflow: hidden;
  width: 100%;
}

.pg-track {
  display: flex;
  width: 100%;
}

/* ЭЛЕМЕНТ ФОТО */
.pg-item {
  flex: 0 0 calc(100% / 4);
  padding: 5px;
  box-sizing: border-box;
}

.pg-item img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  border-radius: 10px;
}

/* стрелки */
.pg-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: var(--accent-color);
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  font-size: 26px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 10;
}

.pg-arrow.left {
  left: -10px;
}

.pg-arrow.right {
  right: -10px;
}

/* === МОБИЛА === */
@media (max-width: 768px) {
  .pg-item {
    flex: 0 0 calc(100% / 2);
  }

  .pg-item img {
    height: 250px;
  }

  .pg-arrow {
    width: 32px;
    height: 32px;
    font-size: 20px;
  }

  .pg-arrow.left {
    left: 10px;
  }
  .pg-arrow.right {
    right: 10px;
  }
}
</style>
