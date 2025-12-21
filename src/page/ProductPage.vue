<template>
  <div class="catalog-page">
    <!-- Загрузка / ошибка -->
    <div v-if="loading" class="loading">
      <div class="loader"></div>
      <p>Загрузка товаров...</p>
    </div>
    <div v-if="error" class="error">{{ error }}</div>

    <!-- Затемнение при открытых фильтрах -->
    <div v-if="showFilters" class="filters-backdrop" @click="showFilters = false"></div>

    <!-- === ФИЛЬТРЫ === -->
    <div class="filters" :class="{ open: showFilters }">
      <div class="filters-header">
        <h2>Фильтры</h2>

        <div class="filters-actions">
          <button class="reset-btn-all" @click="resetAllFilters">Сброс</button>

          <button class="apply-btn" :class="{ disabled: !filtersChanged }" :disabled="!filtersChanged"
            @click="applyFilters">
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
          <h3 class="filter-title" @click="filterOpen.categories = !filterOpen.categories">
            Категории
            <span><i class="arrow" :class="{ open: filterOpen.categories }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.categories }">
            <div class="filter-content">
              <div v-for="cat in categoryState" :key="cat.uuid" class="category-filter"
                :class="{ disabled: cat.disabled }">
                <input type="checkbox" :id="'cat-' + cat.uuid" :checked="draftCategories.includes(cat.uuid)"
                  @change="onCategoryClick(cat)" />
                <label :for="'cat-' + cat.uuid">{{ cat.name }}</label>
              </div>
            </div>
          </div>
        </div>

        <hr />

        <!-- Фото -->
        <div class="filter-section">
          <h3 class="filter-title" @click="filterOpen.photo = !filterOpen.photo">
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
          <h3 class="filter-title" @click="filterOpen.brands = !filterOpen.brands">
            Бренд
            <span><i class="arrow" :class="{ open: filterOpen.brands }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.brands }">
            <div class="filter-content">

              <div class="filter-search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" v-model="brandSearch" placeholder="Поиск бренда..." class="filter-search" />
              </div>

              <div class="brands-scroll">
                <div v-for="b in visibleBrands" :key="b.norm" class="category-filter" :class="{ disabled: b.disabled }">
                  <input type="checkbox" :id="'brand-' + b.norm" :value="b.norm" v-model="draftBrand" />
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
          <h3 class="filter-title" @click="filterOpen.types = !filterOpen.types">
            Тип товара
            <span><i class="arrow" :class="{ open: filterOpen.types }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.types }">
            <div class="filter-content">

              <div class="filter-search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                <input type="text" v-model="typeSearch" placeholder="Поиск типа..." class="filter-search" />
              </div>

              <div class="types-scroll">
                <div v-for="t in visibleTypes" :key="t.id" class="category-filter" :class="{ disabled: t.disabled }">
                  <input type="checkbox" :id="'type-' + t.id" :value="t.id" v-model="draftType" />
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
          <h3 class="filter-title" @click="filterOpen.price = !filterOpen.price">
            Цена
            <span><i class="arrow" :class="{ open: filterOpen.price }"></i></span>
          </h3>

          <div class="filter-content-wrapper" :class="{ open: filterOpen.price }">
            <div class="filter-content">
              <div class="price-range-inputs">
                <input type="number" v-model.number="draftPrice[0]" min="0" :max="maxPrice" />
                <span class="sep">–</span>
                <input type="number" v-model.number="draftPrice[1]" min="0" :max="maxPrice" />
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

<!-- ПОИСК ПО ТОВАРАМ -->
<div class="products-top">
  <div class="products-search-row">
    <!-- Название / Артикул -->
    <div class="products-search">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input
        v-model="searchNameArticle"
        type="text"
        placeholder="Поиск: название или артикул…"
      />
      <button
        v-if="searchNameArticle"
        class="products-search-clear"
        type="button"
        @click="searchNameArticle = ''"
        title="Очистить"
      >✕</button>
    </div>

    <!-- Штрихкод -->
    <div class="products-search barcode">
      <i class="fa-solid fa-barcode" aria-hidden="true"></i>
      <input
        v-model="searchBarcode"
        type="text"
        inputmode="numeric"
        placeholder="Поиск по штрихкоду…"
      />
      <button
        v-if="searchBarcode"
        class="products-search-clear"
        type="button"
        @click="searchBarcode = ''"
        title="Очистить"
      >✕</button>
    </div>
  </div>
</div>




    <!-- === ТОВАРЫ === -->
    <div class="products-grid">
      <div v-if="!loading && filteredProducts.length === 0" class="no-products">
        Товаров по текущим выбранным фильтрам нет
      </div>

      <div v-for="item in paginatedProducts" :key="item.uuid" class="product-card">
        <!-- ==== ЕСЛИ ФОТО НЕТ ==== -->
        <div v-if="!item.images || item.images.length === 0" class="main-image-wrapper">
          <img src="/img/no-photo.png" class="product-img-big" />
        </div>

        <!-- ==== ЕСЛИ ФОТО ЕСТЬ ==== -->
        <div v-else>
          <!-- БОЛЬШОЙ SWIPER -->
          <Swiper class="main-swiper" :modules="[Navigation, Thumbs]" :navigation="true"
            :thumbs="{ swiper: thumbsSwiper[item.uuid] }" :slidesPerView="1">
            <SwiperSlide v-for="(img, index) in item.images" :key="index">
              <div class="main-image-wrapper">
                <img :src="img" class="product-img-big" />
              </div>
            </SwiperSlide>
          </Swiper>

          <!-- THUMBS SWIPER -->
          <Swiper class="thumbs-swiper" :modules="[Thumbs]" :slidesPerView="4" :spaceBetween="8" watchSlidesProgress
            @swiper="(val) => (thumbsSwiper[item.uuid] = val)">
            <SwiperSlide v-for="(img, index) in item.images" :key="'thumb-' + index" class="thumb-slide">
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
    <div class="pagination">
      <button @click="prevPage" :disabled="currentPage === 1">← Назад</button>

      <span>{{ currentPage }} / {{ totalPages }}</span>

      <button @click="nextPage" :disabled="currentPage === totalPages">Вперёд →</button>
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
const searchNameArticle = ref("");
const searchBarcode = ref("");

function normalizeSearchText(v = "") {
  return String(v ?? "")
    .toLowerCase()
    .replace(/ё/g, "е")
    .replace(/\u00A0/g, " ")
    .replace(/[^\p{L}\p{N}]+/gu, " ") // убираем знаки, оставляем буквы/цифры
    .replace(/\s+/g, " ")
    .trim();
}

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

// === ПАГИНАЦИЯ ===
const currentPage = ref(1);
const perPage = 20;

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredProducts.value.slice(start, start + perPage);
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredProducts.value.length / perPage))
);

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}

function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}


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

const filteredProducts = computed(() => {
  const qNA = normalizeSearchText(searchNameArticle.value);
  const tokensNA = qNA ? qNA.split(" ").filter(Boolean) : [];

  // штрихкод — оставляем только цифры
  const qBC = String(searchBarcode.value || "").replace(/\D/g, "").trim();

  return products.value.filter((p) => {
    const price = Number(p.price) || 0;

    // ----- твои фильтры -----
    if (
      selectedCategories.value.length &&
      !selectedCategories.value.includes(p.categoryUuid)
    ) return false;

    if (selectedBrand.value.length) {
      if (!p.brandName) return false;
      if (!selectedBrand.value.includes(normalizeBrandName(p.brandName))) return false;
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

    // ✅ 1) Поиск только по НАЗВАНИЮ или АРТИКУЛУ (по словам)
    if (tokensNA.length) {
      const hayNA = normalizeSearchText([p.name, p.article].join(" "));
      if (!tokensNA.every((t) => hayNA.includes(t))) return false;
    }

    // ✅ 2) Поиск только по ШТРИХКОДУ
    if (qBC) {
      const hayBC = String(p.barcode || "").replace(/\D/g, "");
      if (!hayBC.includes(qBC)) return false;
    }

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
  currentPage.value = 1;

}

// === СБРОС ===
function resetAllFilters() {
  draftCategories.value = [];
  draftBrand.value = [];
  draftType.value = [];
  draftPrice.value = [0, maxPrice.value];
  draftPhotoFilter.value = "all";
  currentPage.value = 1;

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

watch([searchNameArticle, searchBarcode], () => {
  currentPage.value = 1;
});


watch(
  filteredProducts,
  () => {
    // если текущая страница стала больше, чем totalPages — сдвинем назад
    if (currentPage.value > totalPages.value) currentPage.value = totalPages.value;
  },
  { deep: true }
);

</script>

<style scoped>
/* ===========================
   BASE
=========================== */
.catalog-page {
  padding: 20px;
  background: var(--bg-main);
  color: var(--text-main);
}

/* ===========================
   PAGINATION
=========================== */
.pagination {
  margin-left: 280px;
  padding: 20px;
  display: flex;
  justify-content: center;
  gap: 20px;
  align-items: center;
}

.pagination button {
  background: var(--accent);
  color: #fff;
  padding: 10px 16px;
  font-size: 16px;
  border-radius: var(--radius-md);
  border: none;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: 0.2s;
}

.pagination button:hover {
  box-shadow: var(--shadow-md);
  filter: brightness(1.02);
}

.pagination button:disabled {
  background: var(--bg-soft);
  color: var(--text-light);
  cursor: not-allowed;
  box-shadow: none;
}

/* ===========================
   ERROR / LOADING
=========================== */
.error {
  margin: 14px 0;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(220, 38, 38, 0.08);
  border: 1px solid rgba(220, 38, 38, 0.18);
  color: var(--accent-danger);
  font-weight: 700;
}

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
  color: var(--text-main);
  font-size: 18px;
  gap: 16px;
  margin: 0 auto;
}

/* АНИМАЦИЯ КРУЖОЧКА */
.loader {
  width: 48px;
  height: 48px;
  border: 5px solid rgba(27, 30, 40, 0.12);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spinner 0.9s linear infinite;
}

@keyframes spinner {
  to {
    transform: rotate(360deg);
  }
}

/* ===========================
   FILTER TITLES / CONTENT
=========================== */
.filter-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: var(--text-main);
  cursor: pointer;
  padding: 10px 0;
  font-size: 16px;
  font-weight: 900;
  user-select: none;
}

.filter-content {
  margin-bottom: 10px;
  padding-left: 5px;
}

.brands-scroll,
.types-scroll {
  max-height: 300px;
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
  max-height: 2000px;
  opacity: 1;
}

.filter-title span {
  display: flex;
  align-items: center;
}

.filter-title .arrow {
  width: 10px;
  height: 10px;
  border-right: 2px solid var(--text-muted);
  border-bottom: 2px solid var(--text-muted);
  transform: rotate(45deg);
  transition: transform 0.3s ease;
  margin-left: 8px;
}

.filter-title .arrow.open {
  transform: rotate(-135deg);
}

.clear-btn {
  margin-top: 10px;
  background: var(--bg-soft);
  color: var(--text-muted);
  border: 1px solid var(--border-soft);
  padding: 8px 10px;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: 0.2s;
}

.clear-btn:hover {
  filter: brightness(0.98);
}

/* disabled filters */
.category-filter.disabled,
.brand-filter.disabled,
.type-filter.disabled {
  opacity: 0.45;
  pointer-events: none;
}

.category-filter.disabled label,
.category-filter.disabled input,
.category-filter label.disabled,
.category-filter input[disabled] {
  opacity: 0.45;
  cursor: not-allowed;
}

/* ===========================
   PHOTO FILTER (RADIO)
=========================== */
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
  display: none;
}

.radio-check {
  width: 20px;
  height: 20px;
  border: 2px solid var(--border-soft);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  background: var(--bg-soft);
}

.radio-row input[type="radio"]:checked+.radio-check {
  background: var(--accent);
  border-color: var(--accent);
}

.radio-text {
  color: var(--text-main);
  font-size: 15px;
  user-select: none;
}

/* ===========================
   FILTER ACTION BUTTONS
=========================== */
.apply-btn,
.reset-btn-all {
  padding: 14px;
  color: #fff;
  border: none;
  border-radius: var(--radius-md);
  font-size: 16px;
  cursor: pointer;
  margin-top: 12px;
  transition: 0.2s;
  box-shadow: var(--shadow-sm);
}

.apply-btn {
  background: var(--accent);
}

.reset-btn-all {
  background: var(--accent-danger);
}

.filters-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.reset-btn-all:hover,
.apply-btn:hover {
  box-shadow: var(--shadow-md);
  filter: brightness(1.02);
}

.apply-btn.disabled {
  background: var(--bg-soft);
  color: var(--text-light);
  cursor: not-allowed;
  box-shadow: none;
}

/* show more */
.show-more-btn {
  width: 100%;
  text-align: center;
  padding: 10px 0;
  margin: 6px 0 12px 0;

  color: var(--accent);
  font-size: 14px;
  font-weight: 800;

  background: rgba(4, 0, 231, 0.06);
  border-radius: var(--radius-md);
  cursor: pointer;

  transition: all 0.25s ease;
  user-select: none;
}

.show-more-btn:hover {
  background: rgba(4, 0, 231, 0.10);
  transform: scale(1.01);
}

.show-more-btn:active {
  transform: scale(0.99);
}

.reset-button-filters {
  color: var(--accent-danger);
  background: transparent;
  text-decoration: underline;
  border: none;
  margin-bottom: 10px;
  cursor: pointer;
}

.no-products {
  color: var(--text-muted);
  font-size: 16px;
  padding: 20px;
}

/* ===========================
   FILTERS SIDEBAR
=========================== */
.filters {
  position: fixed;
  top: var(--site-header-h);
  left: 0;
  width: 260px;
  height: calc(100vh - var(--site-header-h));
  padding: 20px;
  background: var(--bg-panel);
  overflow-y: auto;
  z-index: 10;
  border-right: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
}

.filters-close-btn {
  display: none;
  width: 34px;
  height: 34px;
  border-radius: var(--radius-md);
  border: 2px solid var(--accent);
  background: transparent;
  color: var(--accent);
  font-size: 20px;
  font-weight: 900;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.25s;
}

.filters-close-btn:hover {
  background: var(--accent);
  color: #fff;
}

/* search wrapper */

.products-search-row {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 12px;
}

.products-search.barcode {
  max-width: 320px;
}

@media (max-width: 768px) {
  .products-search-row {
    grid-template-columns: 1fr;
  }
  .products-search.barcode {
    max-width: 100%;
  }
}

.filter-search-wrapper {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 20px;
  transform: translateY(-50%);
  font-size: 15px;
  color: var(--text-light);
  pointer-events: none;
}

/* filter search input */
.filter-search {
  margin-bottom: 16px;
  width: 100%;
  padding: 12px 14px 12px 42px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
  font-size: 14px;
  transition: all 0.25s ease;
  outline: none;
}

.filter-search::placeholder {
  color: var(--text-light);
}

.filter-search:hover {
  border-color: rgba(4, 0, 231, 0.25);
}

.filter-search:focus {
  border-color: rgba(4, 0, 231, 0.5);
  box-shadow: 0 0 0 3px rgba(4, 0, 231, 0.18);
  background: #eef1fb;
}

/* close inside filters (if you use it somewhere) */
.close-filters {
  display: none;
  background: none;
  border: none;
  color: var(--text-main);
  font-size: 28px;
  margin-bottom: 10px;
  cursor: pointer;
}

/* mobile filters button */
.mobile-filters-btn {
  display: none;
}

/* backdrop */
.filters-backdrop {
  display: none;
}

/* ===========================
   CATEGORY CHECKBOXES
=========================== */
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
  background: var(--bg-soft);
  border: 2px solid rgba(107, 114, 128, 0.35);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  transition: all 0.25s ease;
}

/* Активное */
.category-filter input[type="checkbox"]:checked {
  background: var(--accent);
  border-color: var(--accent);
}

/* SVG галочка */
.category-filter input[type="checkbox"]:checked::after {
  content: "";
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 640'%3E%3Cpath fill='white' d='M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z'/%3E%3C/svg%3E");
  background-position: 50% 56%;
  background-repeat: no-repeat;
}

.category-filter label {
  color: var(--text-main);
  font-size: 14px;
}

/* ===========================
   PRICE
=========================== */
.filter-price {
  margin-top: 10px;
}

.filter-price h3 {
  margin-bottom: 10px;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 900;
}

.price-range-inputs {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.price-range-inputs input {
  width: 92px;
  padding: 8px 8px;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  color: var(--text-main);
  text-align: center;
  outline: none;
}

.price-range-inputs input:focus {
  border-color: rgba(4, 0, 231, 0.5);
  box-shadow: 0 0 0 3px rgba(4, 0, 231, 0.12);
}

.sep {
  color: var(--text-light);
}

/* ===========================
   PRODUCTS SEARCH (TOP)
=========================== */
.products-top {
  margin-left: 280px;
  padding: 0 20px 10px 20px;
}

.products-search {
  display: flex;
  align-items: center;
  gap: 10px;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 14px;
  padding: 12px 14px;

  box-shadow: var(--shadow-sm);
}

.products-search i {
  color: var(--text-light);
  font-size: 15px;
}

.products-search input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;

  color: var(--text-main);
  font-size: 14px;
}

.products-search input::placeholder {
  color: var(--text-light);
}

.products-search-clear {
  border: none;
  background: var(--bg-soft);
  color: var(--text-muted);
  cursor: pointer;
  border-radius: var(--radius-md);
  padding: 6px 10px;
  transition: 0.2s;
}

.products-search-clear:hover {
  filter: brightness(0.98);
  color: var(--text-main);
}

/* ===========================
   PRODUCTS GRID
=========================== */
.products-grid {
  margin-left: 280px;
  padding: 20px;

  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

.product-card {
  background: var(--bg-panel);
  padding: 18px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
}

/* main image */
.main-image-wrapper {
  width: 100%;
  height: 220px;
  background: #fff;
  border-radius: 14px;
  margin-bottom: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  border: 1px solid var(--border-soft);
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

/* product text */
.product-name {
  color: var(--text-main);
  font-size: 16px;
  font-weight: 900;
}

.product-price {
  color: var(--accent);
  font-size: 20px;
  font-weight: 900;
}

.product-barcode {
  color: var(--text-muted);
}

.product-article {
  color: var(--text-muted);
}

.product-qty {
  color: var(--accent);
  font-weight: 900;
}

/* ===========================
   SWIPER
=========================== */
.main-swiper {
  width: 100%;
  margin-bottom: 10px;
}

.main-swiper .main-image-wrapper {
  width: 100%;
  height: 220px;
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
  display: flex;
  justify-content: center;
  align-items: center;
}

.main-swiper img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* arrows */
.swiper-button-prev,
.swiper-button-next {
  color: var(--accent);
  scale: 0.7;
}

.swiper-button-prev:hover,
.swiper-button-next:hover {
  filter: brightness(1.05);
}

/* thumbs swiper */
.thumbs-swiper {
  width: 100%;
  margin-top: 10px;
  padding-bottom: 5px;
}

.thumbs-swiper .swiper-slide {
  width: 25%;
  aspect-ratio: 1/1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 10px;
  background: var(--bg-soft);
  opacity: 0.7;
  border: 2px solid transparent;
  transition: 0.25s;
}

.thumb-img:hover {
  opacity: 1;
}

.swiper-slide-thumb-active .thumb-img {
  opacity: 1;
  border-color: var(--accent);
}

.thumbs-swiper .swiper-wrapper {
  width: 100%;
}

/* disable selection/tap highlight */
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

/* ===========================
   MOBILE
=========================== */
@media (max-width: 768px) {
  .pagination {
    margin: 0 auto;
  }

  .catalog-page {
    padding: 12px;
  }

  .products-top {
    margin-left: 0 !important;
    padding: 0 0 10px 0;
  }

  /* button filters */
  .mobile-filters-btn {
    display: block;
    width: 100%;
    background: var(--accent);
    color: #fff;
    padding: 14px;
    font-size: 16px;
    border-radius: 12px;
    margin: 0 0 15px 0;
    border: none;
    cursor: pointer;
    font-weight: 900;
    box-shadow: var(--shadow-sm);
  }

  /* backdrop */
  .filters-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(1px);
    z-index: 2500;
    opacity: 1;
  }

  /* filters panel */
  .filters {
    position: fixed;
    top: var(--site-header-h);
    left: 0;
    width: 100%;
    height: calc(100vh - var(--site-header-h));
    padding: 0;
    border-radius: 0;
    transform: translateY(100%);
    z-index: 3000;
    overflow-y: auto;
    transition: transform 0.35s ease;
  }

  .filters.open {
    transform: translateY(0);
  }

  .filters-header {
    background: var(--bg-panel);
    position: sticky;
    top: 0;
    z-index: 20;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-soft);

    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto;
    gap: 10px;
  }

  .filters-header h2 {
    color: var(--text-main);
    font-size: 18px;
    font-weight: 900;
    margin: 0;
    grid-column: 1 / 2;
    grid-row: 1 / 2;
  }

  .filters-close-btn {
    display: flex;
    grid-column: 2 / 3;
    grid-row: 1 / 2;
  }

  .filters-actions {
    grid-column: 1 / 3;
    grid-row: 2 / 3;
  }

  .filters-scroll {
    padding: 20px;
  }

  /* products grid */
  .products-grid {
    margin-left: 0 !important;
    padding: 0 !important;
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .product-card {
    max-width: 100% !important;
    overflow-x: hidden !important;
  }

  /* swiper mobile */
  .main-swiper {
    width: 100%;
    height: 220px;
    max-height: 220px;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--border-soft);
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

  .main-swiper .swiper-button-prev,
  .main-swiper .swiper-button-next {
    width: 26px;
    height: 26px;
    color: var(--accent);
  }

  .thumbs-swiper {
    width: 100%;
    margin-top: 10px;
    overflow-x: hidden !important;
  }

  .thumbs-swiper .swiper-wrapper {
    display: flex;
    overflow: hidden !important;
    max-width: 100%;
  }

  .thumbs-swiper .swiper-slide {
    width: 58px !important;
    height: 58px;
    flex-shrink: 0;
    margin-right: 8px !important;
  }
}
</style>

