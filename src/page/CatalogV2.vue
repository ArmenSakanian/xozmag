<template>
  <div class="catalog-page">
    <div v-if="loading" class="loading">
      <div class="loader"></div>
      <p>Загрузка товаров...</p>
    </div>

    <div v-if="error" class="error">{{ error }}</div>

    <div class="products-grid" v-if="!loading">
      <div
        v-for="item in products"
        :key="item.id"
        class="product-card"
      >
        <!-- === ЕСЛИ ФОТО НЕТ === -->
        <div v-if="!item.images || item.images.length === 0">
          <div class="main-image-wrapper">
            <img src="/img/no-photo.png" class="product-img-big" />
          </div>
        </div>

        <!-- === ЕСЛИ ФОТО ЕСТЬ === -->
        <div v-else>
          <Swiper
            class="main-swiper"
            :modules="[Navigation, Thumbs]"
            :navigation="true"
            :thumbs="{ swiper: thumbs[item.id] }"
            :slides-per-view="1"
          >
            <SwiperSlide
              v-for="(img, index) in item.images"
              :key="index"
            >
              <div class="main-image-wrapper">
                <img :src="img" class="product-img-big" />
              </div>
            </SwiperSlide>
          </Swiper>

          <!-- THUMBS -->
          <Swiper
            class="thumbs-swiper"
            :modules="[Thumbs]"
            :slides-per-view="4"
            :space-between="8"
            watch-slides-progress
            @swiper="(val) => (thumbs[item.id] = val)"
          >
            <SwiperSlide
              v-for="(img, index) in item.images"
              :key="'thumb-' + index"
              class="thumb-slide"
            >
              <img :src="img" class="thumb-img" />
            </SwiperSlide>
          </Swiper>
        </div>

        <!-- ТЕКСТОВАЯ ИНФА -->
        <h3 class="product-name">{{ item.name }}</h3>
        <div class="product-price">{{ item.price }} ₽</div>
        <div class="product-barcode">Штрихкод: {{ item.barcode }}</div>
        <div class="product-article">Артикул: {{ item.article || "—" }}</div>

        <!-- Характеристики -->
        <ul class="attr-list">
          <li v-for="(a, i) in item.attributes" :key="i">
            <b>{{ a.name }}</b>: {{ a.value }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

// === SWIPER ===
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Thumbs } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/thumbs";

// данные
const loading = ref(true);
const error = ref(null);
const products = ref([]);

const thumbs = ref({});

async function loadData() {
  try {
    // === 1. Получаем товары из твоей БД ===
    const r1 = await fetch("/api/catalog/get_products.php");
    const baseProducts = await r1.json();

    // === 2. Получаем фотки из Evotor ===
    const r2 = await fetch("/api/vitrina/evotor_catalog.php");
    const evotor = await r2.json();

    const evotorMap = {};
    (evotor.products || []).forEach((p) => {
      if (!p.barcode) return;
      evotorMap[p.barcode] = p.images || [];
    });

    // === 3. Собираем результат ===
    products.value = baseProducts.map((p) => {
      const images = evotorMap[p.barcode] || [];
      return {
        ...p,
        images,
      };
    });
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);
</script>

<style scoped>
.catalog-page {
  padding: 20px;
}

/* Сетка */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

/* Карточка */
.product-card {
  background: #1c1e22;
  padding: 18px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgb(0 0 0 / 0.25);
}

/* Фото */
.main-image-wrapper {
  width: 100%;
  height: 220px;
  background: white;
  border-radius: 14px;
  margin-bottom: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
}

.product-img-big {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* Thumbs */
.thumbs-swiper {
  width: 100%;
  margin-top: 10px;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #222;
  border-radius: 8px;
  opacity: 0.6;
  border: 2px solid transparent;
  transition: 0.25s;
}

.swiper-slide-thumb-active .thumb-img {
  opacity: 1;
  border-color: var(--accent-color);
}

/* Текст */
.product-name {
  color: white;
  font-size: 17px;
  font-weight: 600;
}

.product-price {
  color: var(--accent-color);
  font-size: 22px;
  font-weight: 700;
}

.product-barcode,
.product-article {
  color: #ccc;
}

.attr-list {
  margin-top: 10px;
  color: #eee;
  font-size: 14px;
}
</style>
