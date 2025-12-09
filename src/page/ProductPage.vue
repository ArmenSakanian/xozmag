<template>
  <div class="catalog-page">
    <!-- Загрузка / ошибка -->
    <div v-if="loading" class="loading">
      <div class="loader"></div>
      <p>Загрузка товаров...</p>
    </div>
    <div v-if="error" class="error">{{ error }}</div>

    <!-- Затемнение при открытых фильтрах -->
    <div
      v-if="showFilters"
      class="filters-backdrop"
      @click="showFilters = false"
    ></div>

    <!-- === ФИЛЬТРЫ === -->
    <div class="filters" :class="{ open: showFilters }">
      <div class="filters-header">
        <h2>Фильтры</h2>

        <div class="filters-actions">
          <button class="reset-btn-all" @click="resetAllFilters">Сброс</button>

          <button
            class="apply-btn"
            :class="{ disabled: !filtersChanged }"
            :disabled="!filtersChanged"
            @click="applyFilters"
          >
            Применить
          </button>
        </div>

        <button class="filters-close-btn" @click="showFilters = false">
          ✕
        </button>
      </div>

      <div class="filters-scroll">

        <!-- Категории -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.categories = !filterOpen.categories"
          >
            Категории
            <span><i class="arrow" :class="{ open: filterOpen.categories }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.categories }">
            <div class="filter-content">
              <div
                v-for="cat in categoryState"
                :key="cat.uuid"
                class="category-filter"
                :class="{ disabled: cat.disabled }"
              >
                <input
                  type="checkbox"
                  :id="'cat-' + cat.uuid"
                  :checked="draftCategories.includes(cat.uuid)"
                  @change="onCategoryClick(cat)"
                />
                <label :for="'cat-' + cat.uuid">{{ cat.name }}</label>
              </div>
            </div>
          </div>
        </div>

        <hr />

        <!-- Фото -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.photo = !filterOpen.photo"
          >
            Фото
            <span><i class="arrow" :class="{ open: filterOpen.photo }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.photo }">
            <div class="filter-content photo-filter">
              <label class="radio-row">
                <input type="radio" value="all" v-model="draftPhotoFilter" />
                <span class="radio-check"></span>
                <span class="radio-text">Все товары</span>
              </label>

              <label class="radio-row">
                <input type="radio" value="with" v-model="draftPhotoFilter" />
                <span class="radio-check"></span>
                <span class="radio-text">Только с фото</span>
              </label>

              <label class="radio-row">
                <input type="radio" value="without" v-model="draftPhotoFilter" />
                <span class="radio-check"></span>
                <span class="radio-text">Только без фото</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Бренды -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.brands = !filterOpen.brands"
          >
            Бренд
            <span><i class="arrow" :class="{ open: filterOpen.brands }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.brands }">
            <div class="filter-content">

              <div class="filter-search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                  type="text"
                  v-model="brandSearch"
                  placeholder="Поиск бренда..."
                  class="filter-search"
                />
              </div>

              <div class="brands-scroll">
                <div
                  v-for="b in visibleBrands"
                  :key="b.norm"
                  class="category-filter"
                  :class="{ disabled: b.disabled }"
                >
                  <input
                    type="checkbox"
                    :id="'brand-' + b.norm"
                    :value="b.norm"
                    v-model="draftBrand"
                  />
                  <label :for="'brand-' + b.norm">{{ b.name }}</label>
                </div>
              </div>

              <div class="show-more-btn" @click="showAllBrands = !showAllBrands">
                {{ showAllBrands ? "Скрыть" : "Показать все" }}
              </div>

              <button class="reset-button-filters" @click="draftBrand = []">
                Сбросить бренд
              </button>

            </div>
          </div>
        </div>

        <hr />

        <!-- Тип товара -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.types = !filterOpen.types"
          >
            Тип товара
            <span><i class="arrow" :class="{ open: filterOpen.types }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.types }">
            <div class="filter-content">

              <div class="filter-search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                <input
                  type="text"
                  v-model="typeSearch"
                  placeholder="Поиск типа..."
                  class="filter-search"
                />
              </div>

              <div class="types-scroll">
                <div
                  v-for="t in visibleTypes"
                  :key="t.id"
                  class="category-filter"
                  :class="{ disabled: t.disabled }"
                >
                  <input
                    type="checkbox"
                    :id="'type-' + t.id"
                    :value="t.id"
                    v-model="draftType"
                  />
                  <label :for="'type-' + t.id">{{ t.name }}</label>
                </div>
              </div>

              <div class="show-more-btn" @click="showAllTypes = !showAllTypes">
                {{ showAllTypes ? "Скрыть" : "Показать все" }}
              </div>

              <button class="reset-button-filters" @click="draftType = []">
                Сбросить тип
              </button>

            </div>
          </div>
        </div>

        <hr />

        <!-- Цена -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.price = !filterOpen.price"
          >
            Цена
            <span><i class="arrow" :class="{ open: filterOpen.price }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.price }">
            <div class="filter-content">
              <div class="price-range-inputs">
                <input
                  type="number"
                  v-model.number="draftPrice[0]"
                  min="0"
                  :max="maxPrice"
                />
                <span class="sep">–</span>
                <input
                  type="number"
                  v-model.number="draftPrice[1]"
                  min="0"
                  :max="maxPrice"
                />
              </div>
            </div>
          </div>
        </div>

        <hr />
      </div>
    </div>

    <!-- Кнопка фильтров -->
    <button class="mobile-filters-btn" @click="showFilters = true">
      Фильтры
    </button>

    <!-- === ТОВАРЫ === -->
    <div class="products-grid">
      <div v-if="!loading && filteredProducts.length === 0" class="no-products">
        Товаров по текущим выбранным фильтрам нет
      </div>

      <div
        v-for="item in filteredProducts"
        :key="item.uuid"
        class="product-card"
      >
        <!-- ==== ЕСЛИ ФОТО НЕТ ==== -->
        <div v-if="!item.images || item.images.length === 0" class="main-image-wrapper">
          <img src="/img/no-photo.png" class="product-img-big" />
        </div>

        <!-- ==== ЕСЛИ ФОТО ЕСТЬ ==== -->
        <div v-else>
          <!-- БОЛЬШОЙ SWIPER -->
          <Swiper
            class="main-swiper"
            :modules="[Navigation, Thumbs]"
            :navigation="true"
            :thumbs="{ swiper: thumbsSwiper[item.uuid] }"
            :slidesPerView="1"
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

          <!-- THUMBS SWIPER -->
          <Swiper
            class="thumbs-swiper"
            :modules="[Thumbs]"
            :slidesPerView="4"
            :spaceBetween="8"
            watchSlidesProgress
            @swiper="(val) => (thumbsSwiper[item.uuid] = val)"
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

        <!-- ИНФО О ТОВАРЕ -->
        <h3 class="product-name">{{ item.name }}</h3>
        <div class="product-price">{{ item.price }} ₽</div>
        <div class="product-barcode">Штрихкод: {{ item.barcode }}</div>
        <div class="product-article">Артикул: {{ item.article }}</div>
        <div class="product-qty">Остаток: {{ item.quantity }}</div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, onMounted, watch } from "vue";

// === SWIPER ===
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Thumbs } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/thumbs";

// Объект для хранения thumb-swiper для каждого товара
const thumbsSwiper = ref({});

// === ТВОИ ПЕРЕМЕННЫЕ ===
const showFilters = ref(false);

const loading = ref(true);
const error = ref(null);

const categories = ref([]);
const brands = ref([]);
const types = ref([]);
const products = ref([]);

const selectedCategories = ref([]);
const selectedBrand = ref([]);
const selectedType = ref([]);

const priceRange = ref([0, 0]);
const maxPrice = ref(0);

const draftCategories = ref([]);
const draftBrand = ref([]);
const draftType = ref([]);

const draftPrice = ref([0, 0]);
const brandSearch = ref("");
const typeSearch = ref("");

const showAllBrands = ref(false);
const showAllTypes = ref(false);

// фильтр фото
const photoFilter = ref("all");
const draftPhotoFilter = ref("all");

// вкладки фильтров (открыты/закрыты)
const filterOpen = ref({
  categories: true,
  brands: true,
  types: true,
  photo: true,
  price: true,
});

// === НОРМАЛИЗАЦИЯ ===
function normalizeTypeName(name = "") {
  return String(name)
    .replace(/\u00A0/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .toLowerCase();
}

function normalizeBrandName(name = "") {
  return String(name).trim().toLowerCase();
}

// === ЗАГРУЗКА ДАННЫХ ===
async function loadData() {
  try {
    const r = await fetch("/api/vitrina/evotor_catalog.php");
    const data = await r.json();

    categories.value = data.categories || [];
    brands.value = data.brands || [];
    types.value = data.types || [];

    products.value = (data.products || []).filter((p) => (p.quantity ?? 0) > 0);

    if (products.value.length) {
      const prices = products.value.map((p) => Number(p.price) || 0);
      maxPrice.value = Math.max(...prices);

      priceRange.value = [0, maxPrice.value];
      draftPrice.value = [0, maxPrice.value];
    }
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

// === МАП ТИПОВ ===
const typeMap = computed(() => {
  const map = new Map();
  types.value.forEach((t) => {
    const name = t.name || "";
    map.set(t.uuid, { name, norm: normalizeTypeName(name) });
  });
  return map;
});

// === БРЕНДЫ ===
const mergedBrands = computed(() =>
  brands.value.map((name) => ({
    name,
    norm: normalizeBrandName(name),
  }))
);

// бренды, доступные по выбранным фильтрам
const availableBrands = computed(() => {
  const set = new Set();

  products.value.forEach((p) => {
    if (
      draftCategories.value.length &&
      !draftCategories.value.includes(p.categoryUuid)
    )
      return;

    if (draftType.value.length) {
      const info = typeMap.value.get(p.typeUuid);
      if (!info || !draftType.value.includes(info.norm)) return;
    }

    if (p.brandName) set.add(normalizeBrandName(p.brandName));
  });

  return mergedBrands.value.filter((b) => set.has(b.norm));
});

// бренды для отображения (с disabled)
const filteredBrands = computed(() => {
  let list = mergedBrands.value.map((b) => {
    const active = products.value.some((p) => {
      if (draftType.value.length) {
        const info = typeMap.value.get(p.typeUuid);
        if (!info || !draftType.value.includes(info.norm)) return false;
      }

      if (
        draftCategories.value.length &&
        !draftCategories.value.includes(p.categoryUuid)
      )
        return false;

      return normalizeBrandName(p.brandName) === b.norm;
    });

    return { ...b, disabled: !active };
  });

  const query = brandSearch.value.trim().toLowerCase();
  if (query) list = list.filter((b) => b.name.toLowerCase().includes(query));

  return list.sort((a, b) => {
    if (a.disabled !== b.disabled) return a.disabled - b.disabled;
    return a.name.localeCompare(b.name, "ru");
  });
});

// бренды в списке (5 или все)
const visibleBrands = computed(() =>
  showAllBrands.value ? filteredBrands.value : filteredBrands.value.slice(0, 5)
);

// === ТИПЫ ===
const filteredTypes = computed(() => {
  const map = new Map();

  types.value.forEach((t) => {
    const norm = normalizeTypeName(t.name);
    map.set(norm, {
      id: norm,
      name: t.name,
      disabled: true,
    });
  });

  products.value.forEach((p) => {
    const norm = normalizeTypeName(p.typeName);

    if (
      draftCategories.value.length &&
      !draftCategories.value.includes(p.categoryUuid)
    )
      return;

    if (draftBrand.value.length) {
      if (!p.brandName) return;
      if (!draftBrand.value.includes(normalizeBrandName(p.brandName))) return;
    }

    if (map.has(norm)) map.get(norm).disabled = false;
  });

  let list = Array.from(map.values());

  const query = typeSearch.value.trim().toLowerCase();
  if (query) list = list.filter((t) => t.name.toLowerCase().includes(query));

  return list.sort((a, b) => {
    if (a.disabled !== b.disabled) return a.disabled - b.disabled;
    return a.name.localeCompare(b.name, "ru");
  });
});

const visibleTypes = computed(() =>
  showAllTypes.value ? filteredTypes.value : filteredTypes.value.slice(0, 5)
);

// === СОСТОЯНИЕ КАТЕГОРИЙ ===
const categoryState = computed(() => {
  const active = new Set();

  products.value.forEach((p) => {
    if (draftBrand.value.length) {
      return;
    }

    if (draftType.value.length) {
      const info = typeMap.value.get(p.typeUuid);
      if (!info || !draftType.value.includes(info.norm)) return;
    }

    active.add(p.categoryUuid);
  });

  return categories.value.map((c) => ({
    ...c,
    disabled: !active.has(c.uuid),
  }));
});

// клик по категории
function onCategoryClick(cat) {
  if (!cat.disabled) {
    if (draftCategories.value.includes(cat.uuid)) {
      draftCategories.value = draftCategories.value.filter((id) => id !== cat.uuid);
    } else {
      draftCategories.value.push(cat.uuid);
    }
    return;
  }

  draftBrand.value = [];
  draftType.value = [];
  draftCategories.value = [cat.uuid];
}

// === ОТФИЛЬТРОВАННЫЕ ТОВАРЫ ===
const filteredProducts = computed(() => {
  return products.value.filter((p) => {
    const price = Number(p.price) || 0;

    if (
      selectedCategories.value.length &&
      !selectedCategories.value.includes(p.categoryUuid)
    )
      return false;

    if (selectedBrand.value.length) {
      if (!p.brandName) return false;
      if (!selectedBrand.value.includes(normalizeBrandName(p.brandName)))
        return false;
    }

    if (selectedType.value.length) {
      const norm = normalizeTypeName(p.typeName);
      if (!selectedType.value.includes(norm)) return false;
    }

    if (photoFilter.value === "with") {
      if (!p.images || p.images.length === 0) return false;
    }

    if (photoFilter.value === "without") {
      if (p.images && p.images.length > 0) return false;
    }

    if (price < priceRange.value[0] || price > priceRange.value[1]) return false;

    return true;
  });
});

// === ПРИМЕНЕНИЕ ФИЛЬТРОВ ===
function applyFilters() {
  selectedCategories.value = [...draftCategories.value];
  selectedBrand.value = [...draftBrand.value];
  selectedType.value = [...draftType.value];
  priceRange.value = [...draftPrice.value];
  photoFilter.value = draftPhotoFilter.value;
}

// === СБРОС ===
function resetAllFilters() {
  draftCategories.value = [];
  draftBrand.value = [];
  draftType.value = [];
  draftPrice.value = [0, maxPrice.value];
  draftPhotoFilter.value = "all";

  applyFilters();
}

// === ФЛАГ ИЗМЕНЕНИЙ ===
const filtersChanged = computed(() => {
  return (
    JSON.stringify(selectedCategories.value) !== JSON.stringify(draftCategories.value) ||
    JSON.stringify(selectedBrand.value) !== JSON.stringify(draftBrand.value) ||
    JSON.stringify(selectedType.value) !== JSON.stringify(draftType.value) ||
    JSON.stringify(priceRange.value) !== JSON.stringify(draftPrice.value) ||
    photoFilter.value !== draftPhotoFilter.value
  );
});

// === LOADING ===
onMounted(loadData);

// === ОТКЛЮЧАТЬ СКРОЛЛ ПРИ ОТКРЫТЫХ ФИЛЬТРАХ ===
watch(showFilters, (v) => {
  document.body.style.overflow = v ? "hidden" : "auto";
});
</script>


<style scoped>
.filters-header {
  position: sticky;
}

.loading {
  width: 100%;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: white;
  font-size: 18px;
  gap: 16px;
  margin: 0 auto;
}

/* АНИМАЦИЯ КРУЖОЧКА */
.loader {
  width: 48px;
  height: 48px;
  border: 5px solid #3a3d44;
  border-top-color: var(--accent-color);
  border-radius: 50%;
  animation: spinner 0.9s linear infinite;
}

@keyframes spinner {
  to {
    transform: rotate(360deg);
  }
}

.filter-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: var(--accent-color);
  cursor: pointer;
  padding: 8px 0;
  font-size: 18px;
  user-select: none;
}

.filter-content {
  margin-bottom: 10px;
  padding-left: 5px;
}

.brands-scroll,
.types-scroll {
  max-height: 300px; /* можно 400, 500 — как хочешь */
  overflow-y: auto;
  padding-right: 5px;
}

.filter-content-wrapper {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  transition: all 0.35s ease;
}

.filter-content-wrapper.open {
  max-height: 2000px; /* можно 5000px — вообще без разницы */
  opacity: 1;
}

.filter-title span {
  display: flex;
  align-items: center;
}

.filter-title .arrow {
  width: 10px;
  height: 10px;
  border-right: 2px solid #fff;
  border-bottom: 2px solid #fff;
  transform: rotate(45deg);
  transition: transform 0.3s ease;
  margin-left: 8px;
}

.filter-title .arrow.open {
  transform: rotate(-135deg);
}

.clear-btn {
  margin-top: 10px;
  background: #2a2d31;
  color: #bbb;
  border: 1px solid #444;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}

.clear-btn:hover {
  background: #3a3f44;
}

.category-filter.disabled,
.brand-filter.disabled,
.type-filter.disabled {
  opacity: 0.4;
  pointer-events: none;
}

.category-filter.disabled label {
  opacity: 0.4;
  cursor: pointer;
}

.category-filter.disabled input {
  opacity: 0.4;
}

.category-filter label.disabled {
  color: #555;
  opacity: 0.4;
}

.category-filter input[disabled] {
  opacity: 0.4;
  cursor: not-allowed;
}

.photo-filter {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 12px;
}

.radio-row {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.radio-row input[type="radio"] {
  display: none; /* скрываем дефолт */
}

.radio-check {
  width: 20px;
  height: 20px;
  border: 2px solid white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
}

.radio-row input[type="radio"]:checked + .radio-check {
  background: var(--accent-color);
}

.radio-text {
  color: white;
  font-size: 16px;
  user-select: none;
}

.apply-btn,
.reset-btn-all {
  padding: 15px;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 18px;
  cursor: pointer;
  margin-top: 15px;
  transition: 0.2s;
}

.apply-btn {
  background: var(--accent-color);
}

.reset-btn-all {
  background: var(--delete-color);
}

.filters-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.reset-btn-all:hover {
  background: var(--accent-color);
  color: #000;
}

.show-more-btn {
  width: 100%;
  text-align: center;
  padding: 10px 0;
  margin: 6px 0 12px 0;

  color: var(--accent-color);
  font-size: 15px;
  font-weight: 600;

  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  cursor: pointer;

  transition: all 0.25s ease;
  user-select: none;
}

.show-more-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  transform: scale(1.02);
}

.show-more-btn:active {
  transform: scale(0.98);
  background: rgba(255, 255, 255, 0.18);
}

.reset-button-filters {
  color: var(--delete-color);
  background: transparent;
  text-decoration: underline;
  border: none;
  margin-bottom: 10px;
  cursor: pointer;
}

.apply-btn.disabled {
  background: #2f3237;
  color: #999;
  cursor: not-allowed;
}

.no-products {
  color: #ccc;
  font-size: 18px;
  padding: 20px;
}

.catalog-page {
  padding: 20px;
}

/* === ФИЛЬТРЫ === */
.filters {
  position: fixed;
  top: 69px; /* <<< фильтры начинаются под хедером */
  left: 0;
  width: 260px;
  height: calc(100vh - 60px); /* <<< чтобы не вылазило за экран */
  padding: 20px;
  background: var(--background-container);
  overflow-y: auto;
  z-index: 10; /* можно оставить маленький */
}

.filters-close-btn {
  display: none;
}
/* ОБЩИЙ КОНТЕЙНЕР */
.filter-search-wrapper {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 15px;
  top: 21px;
  transform: translateY(-50%);
  font-size: 16px;
  color: #8a8d92;
  pointer-events: none;
}

/* САМО ПОЛЕ ПОИСКА */
.filter-search {
  margin-bottom: 20px;
  width: 100%;
  padding: 12px 14px 12px 44px; /* место под иконку слева */
  border-radius: 12px;
  border: 1px solid #2a2d32;
  background: #1c1e22;
  color: #f2f2f2;
  font-size: 15px;
  transition: all 0.25s ease;
  outline: none;
}

.filter-search::placeholder {
  color: #8a8d92;
}

/* ХОВЕР */
.filter-search:hover {
  border-color: #3a3f45;
}

/* ФОКУС — красивое неоновое свечение */
.filter-search:focus {
  border-color: #4c7dff;
  box-shadow: 0 0 0 3px rgba(76, 125, 255, 0.35);
  background: #202328;
}

/* Кнопка закрытия внутри фильтров — по умолчанию скрыта */
.close-filters {
  display: none;
  background: none;
  border: none;
  color: white;
  font-size: 26px;
  margin-bottom: 10px;
  cursor: pointer;
}

/* Мобильная кнопка “Фильтры” — по умолчанию скрыта */
.mobile-filters-btn {
  display: none;
}

/* Затемнение фона при открытых фильтрах (мобилка) */
.filters-backdrop {
  display: none;
}

/* === КАТЕГОРИИ === */
.category-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.category-filter input[type="checkbox"] {
  width: 20px;
  height: 20px;
  appearance: none !important;
  -webkit-appearance: none !important;
  background: #1f2227;
  border: 2px solid #4e5258;
  border-radius: 5px;
  cursor: pointer;
  position: relative;
  transition: all 0.25s ease;
}

/* Активное */
.category-filter input[type="checkbox"]:checked {
  background: var(--accent-color);
  border-color: var(--accent-color);
}

/* SVG галочка */
.category-filter input[type="checkbox"]:checked::after {
  content: "";
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 640'%3E%3Cpath fill='black' d='M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z'/%3E%3C/svg%3E");
  background-position: 50% 56%; /* <<< идеальный центр */
  background-repeat: no-repeat;
}

.category-filter label {
  color: white;
}

.filter-price {
  margin-top: 10px;
}

.filter-price h3 {
  margin-bottom: 10px;
  color: white;
}

/* === ИНПУТЫ ЦЕН === */
.price-range-inputs {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.price-range-inputs input {
  width: 90px;
  padding: 7px;
  background: var(--background-input);
  border: 1px solid var(--accent-color);
  border-radius: 6px;
  color: white;
  text-align: center;
}

.sep {
  color: #aaa;
}

/* === ТОВАРЫ === */
.products-grid {
  margin-left: 280px; /* ширина фильтров */
  padding: 20px;

  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

.product-card {
  background: var(--background-container);
  padding: 18px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgb(0 0 0 / 0.25);
}

/* === ГЛАВНОЕ ИЗОБРАЖЕНИЕ === */
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
  cursor: pointer;
  transition: transform 0.25s ease;
}

.product-img-big:hover {
  transform: scale(1.03);
}

/* === МИНИАТЮРЫ === */
.thumbs {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.thumb {
  width: 50px;
  height: 50px;
  object-fit: contain;
  background: #222;
  border-radius: 8px;
  cursor: pointer;
  opacity: 0.6;
  border: 2px solid transparent;
  transition: 0.25s;
}

.thumb.active {
  border-color: var(--accent-color);
  opacity: 1;
}

.thumb:hover {
  opacity: 1;
}

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
.product-barcode {
  color: #ccc;
}
.product-article {
  color: #bbb;
}
.product-qty {
  color: var(--accent-color);
}


.main-swiper {
  width: 100%;
  margin-bottom: 10px;
}

/* Высота как у тебя сейчас */
.main-swiper .main-image-wrapper {
  width: 100%;
  height: 220px;
  border-radius: 14px;
  overflow: hidden;
  background: white;
  display: flex;
  justify-content: center;
  align-items: center;
}

.main-swiper img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* Стрелки */
.swiper-button-prev,
.swiper-button-next {
  color: var(--accent-color);
  scale: 0.7;
}

.swiper-button-prev:hover,
.swiper-button-next:hover {
  color: white;
}

/* ------------------------------ */
/*       THUMBS SWIPER            */
/* ------------------------------ */

.thumbs-swiper {
  width: 100%;
  margin-top: 10px;
  padding-bottom: 5px;
}

.thumbs-swiper .swiper-slide {
  width: 25%;        /* >>> 4 миниатюры в ряд */
  aspect-ratio: 1/1; /* квадратные превьюшки */
  display: flex;
  justify-content: center;
  align-items: center;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 8px;
  background: #222;
  opacity: 0.6;
  border: 2px solid transparent;
  transition: 0.25s;
}

.thumb-img:hover {
  opacity: 1;
}

.swiper-slide-thumb-active .thumb-img {
  opacity: 1;
  border-color: var(--accent-color);
}

/* ------------------------------ */
/*   ГОРИЗОНТАЛЬНЫЙ СКРОЛЛ = OFF   */
/* ------------------------------ */

.thumbs-swiper .swiper-wrapper {
  width: 100%;
}

/* Отключаем выделение И кликовые подсветки только у swiper */
.main-swiper,
.thumbs-swiper,
.thumbs-swiper .swiper-slide,
.thumb-img,
.swiper-button-prev,
.swiper-button-next {
    -webkit-user-select: none;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.swiper-button-prev:focus,
.swiper-button-next:focus,
.thumbs-swiper .swiper-slide:focus,
.thumb-img:focus {
    outline: none !important;
    box-shadow: none !important;
}

/* ————————————————————— */
/*   МОБИЛЬНАЯ АДАПТАЦИЯ   */
/* ————————————————————— */
@media (max-width: 768px) {

 /* Большое фото */
  .main-swiper {
    width: 100%;
    height: 220px;          /* фикс как в desktop */
    max-height: 220px;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
  }

  .main-swiper .swiper-wrapper {
    width: 100%;
    height: 100%;
  }

  .main-swiper img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  /* Стрелки */
  .main-swiper .swiper-button-prev,
  .main-swiper .swiper-button-next {
    width: 26px;
    height: 26px;
    color: var(--accent-color);
  }

  /* Миниатюры */
  .thumbs-swiper {
    width: 100%;
    margin-top: 10px;
    overflow-x: hidden !important;
  }

  .thumbs-swiper .swiper-wrapper {
    display: flex;
    overflow: hidden;
    max-width: 100%;   /* главное, чтобы wrapper не ломал верстку */
    overflow: hidden !important;
  }
.product-card {
    max-width: 100% !important;
    overflow-x: hidden !important;
  }
  .thumbs-swiper .swiper-slide {
    width: 58px !important;
    height: 58px;
    flex-shrink: 0;     /* запрещаем уменьшение */
    margin-right: 8px !important;
  }

  .thumb-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #222;
    border-radius: 6px;
    opacity: 0.6;
    transition: 0.25s;
  }

  .thumb-img.active {
    opacity: 1;
    border: 2px solid var(--accent-color);
  }

  /* Если фото нет */
  .no-photo-wrapper img {
    width: 100%;
    height: 220px;
    object-fit: contain;
    background: #fff;
    border-radius: 12px;
  }

  .catalog-page {
    padding: 12px;
  }

  .filters-close-btn {
    grid-column: 2 / 3;
    grid-row: 1 / 2;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 2px solid var(--accent-color);
    background: transparent;
    color: var(--accent-color);
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.25s;
  }
  .filters-close-btn:hover {
    background: var(--accent-color);
    color: #000;
    transform: scale(1.05);
  }

  .filters-actions {
    grid-column: 1 / 3; /* растянуть кнопки на всю ширину */
    grid-row: 2 / 3;
  }

  /* Кнопка "Фильтры" */
  .mobile-filters-btn {
    display: block;
    width: 100%;
    background: var(--accent-color);
    color: #fff;
    padding: 14px;
    font-size: 18px;
    border-radius: 12px;
    margin: 0 0 15px 0;
    border: none;
    cursor: pointer;
    font-weight: 600;
  }

  /* Затемнение */
  .filters-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(1px);
    z-index: 2500;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
  }

  .filters.open ~ .filters-backdrop {
    opacity: 1;
    pointer-events: auto;
  }

  /* Фильтры занимают 100% ширины */
  .filters {
    position: fixed;
    top: 69px; /* ниже header */
    left: 0;
    width: 100%;
    height: calc(100vh - 69px);
    padding: 0;
    border-radius: 0;
    transform: translateY(100%);
    z-index: 3000;
    overflow-y: auto;
    transition: transform 0.35s ease;
  }

  /* показать фильтры */
  .filters.open {
    transform: translateY(0);
  }

  /* ВЕРХНЯЯ ПАНЕЛЬ */
  .filters-header {
    position: sticky;
    top: 0;
    z-index: 20;
    padding: 16px 20px;
    border-bottom: 1px solid #333;

    display: grid;
    grid-template-columns: 1fr auto; /* слева текст, справа крест */
    grid-template-rows: auto auto; /* первая строка — заголовок, вторая — кнопки */
  }

  .filters-header h2 {
    color: white;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    grid-column: 1 / 2;
    grid-row: 1 / 2;
  }

  .close-filters {
    display: block;
    font-size: 28px;
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    padding: 0 10px;
    opacity: 0.8;
    transition: 0.2s;
  }

  .close-filters:hover {
    opacity: 1;
  }
  .filters-scroll {
    padding: 20px;
  }
  /* ВНУТРЕННИЙ КОНТЕНТ */
  .filters-inner {
    padding: 20px;
    padding-bottom: 120px;
  }

  .filter-title {
    font-size: 18px;
    font-weight: 600;
    padding: 12px 0;
  }

  .filter-content-wrapper {
    margin-bottom: 18px;
  }

  .category-filter input[type="checkbox"] {
    width: 22px;
    height: 22px;
    border-radius: 5px;
  }

  .category-filter {
    margin-bottom: 16px;
  }

  /* Применить */
  .apply-btn {
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 9999;
  }

  /* Сетка товаров */
  .products-grid {
    margin-left: 0 !important;
    padding: 0 !important;
    grid-template-columns: 1fr;
    gap: 14px;
  }
}
</style>
