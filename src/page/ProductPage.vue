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
      <!-- 🔥 ФИКСИРОВАННЫЙ ХЕДЕР ФИЛЬТРОВ -->
      <div class="filters-header">
        <h2>Фильтры</h2>
        <button class="filters-close-btn" @click="showFilters = false">
          ✕
        </button>
      </div>

      <!-- 🔥 ВНУТРЕННИЙ ПРОКРУЧИВАЕМЫЙ КОНТЕЙНЕР -->
      <div class="filters-scroll">
        <!-- === КАТЕГОРИИ === -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.categories = !filterOpen.categories"
          >
            Категории
            <span
              ><i class="arrow" :class="{ open: filterOpen.categories }"></i
            ></span>
          </h3>

          <div
            class="filter-content-wrapper"
            :class="{ open: filterOpen.categories }"
          >
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

        <!-- === БРЕНДЫ === -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.brands = !filterOpen.brands"
          >
            Бренд
            <span
              ><i class="arrow" :class="{ open: filterOpen.brands }"></i
            ></span>
          </h3>

          <div
            class="filter-content-wrapper"
            :class="{ open: filterOpen.brands }"
          >
<div class="filter-content">

  <!-- 🔍 Поиск брендов -->
<div class="filter-search-wrapper">
  <input 
    type="text"
    v-model="brandSearch"
    placeholder="Поиск бренда..."
    class="filter-search"
  >
</div>


  <div
    v-for="b in filteredBrands"
    :key="b.uuid"
    class="category-filter"
  >

                <input
                  type="checkbox"
                  :id="'brand-' + b.uuid"
                  :value="b.uuid"
                  v-model="draftBrand"
                />
                <label :for="'brand-' + b.uuid">{{ b.name }}</label>
              </div>

              <button class="reset-button-filters" @click="draftBrand = []">
                Сбросить бренд
              </button>
            </div>
          </div>
        </div>

        <hr />

        <!-- === ТИПЫ === -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.types = !filterOpen.types"
          >
            Тип товара
            <span
              ><i class="arrow" :class="{ open: filterOpen.types }"></i
            ></span>
          </h3>

          <div
            class="filter-content-wrapper"
            :class="{ open: filterOpen.types }"
          >
<div class="filter-content">

  <!-- 🔍 Поиск типа товара -->
<div class="filter-search-wrapper">
  <input 
    type="text"
    v-model="typeSearch"
    placeholder="Поиск типа..."
    class="filter-search"
  >
</div>


  <div
    v-for="t in filteredTypes"
    :key="t.id"
    class="category-filter"
  >

                <input
                  type="checkbox"
                  :id="'type-' + t.id"
                  :value="t.id"
                  v-model="draftType"
                />
                <label :for="'type-' + t.id">{{ t.name }}</label>
              </div>

              <button class="reset-button-filters" @click="draftType = []">
                Сбросить тип
              </button>
            </div>
          </div>
        </div>

        <hr />

        <!-- === ЦЕНА === -->
        <div class="filter-section">
          <h3
            class="filter-title"
            @click="filterOpen.price = !filterOpen.price"
          >
            Цена
            <span
              ><i class="arrow" :class="{ open: filterOpen.price }"></i
            ></span>
          </h3>

          <div
            class="filter-content-wrapper"
            :class="{ open: filterOpen.price }"
          >
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

        <!-- === КНОПКА ПРИМЕНИТЬ === -->
        <button
          class="apply-btn"
          :class="{ disabled: !filtersChanged }"
          :disabled="!filtersChanged"
          @click="applyFilters"
        >
          Применить
        </button>
      </div>
      <!-- /filters-scroll -->
    </div>
    <!-- /filters -->

    <!-- === КНОПКА ФИЛЬТРОВ ДЛЯ МОБИЛКИ === -->
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

const filterOpen = ref({
  categories: true,
  brands: true,
  types: true,
  price: true,
});

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

function normalizeTypeName(name = "") {
  return String(name)
    .replace(/\u00A0/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .toLowerCase();
}

const typeMap = computed(() => {
  const map = new Map();
  types.value.forEach((t) => {
    const name = t.name || "";
    map.set(t.uuid, {
      name,
      norm: normalizeTypeName(name),
    });
  });
  return map;
});

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

    if (p.brandUuid) set.add(p.brandUuid);
  });

  return brands.value.filter((b) => set.has(b.uuid));
});

const availableTypes = computed(() => {
  const byName = new Map();

  products.value.forEach((p) => {
    if (
      draftCategories.value.length &&
      !draftCategories.value.includes(p.categoryUuid)
    )
      return;

    if (draftBrand.value.length && !draftBrand.value.includes(p.brandUuid))
      return;

    const info = typeMap.value.get(p.typeUuid);
    if (!info) return;

    const { name, norm } = info;

    if (!byName.has(norm)) {
      byName.set(norm, {
        id: norm,
        name,
      });
    }
  });

  return Array.from(byName.values()).sort((a, b) =>
    a.name.localeCompare(b.name, "ru")
  );
});

const filteredBrands = computed(() => {
  const query = brandSearch.value.trim().toLowerCase();
  if (!query) return availableBrands.value;

  return availableBrands.value.filter((b) =>
    b.name.toLowerCase().includes(query)
  );
});

const filteredTypes = computed(() => {
  const query = typeSearch.value.trim().toLowerCase();
  if (!query) return availableTypes.value;

  return availableTypes.value.filter((t) =>
    t.name.toLowerCase().includes(query)
  );
});

const availableCategories = computed(() => {
  const set = new Set();

  products.value.forEach((p) => {
    if (draftBrand.value.length && !draftBrand.value.includes(p.brandUuid))
      return;

    if (draftType.value.length) {
      const info = typeMap.value.get(p.typeUuid);
      if (!info || !draftType.value.includes(info.norm)) return;
    }

    set.add(p.categoryUuid);
  });

  return categories.value.filter((c) => set.has(c.uuid));
});

const filteredProducts = computed(() => {
  return products.value.filter((p) => {
    const price = Number(p.price) || 0;

    if (
      selectedCategories.value.length &&
      !selectedCategories.value.includes(p.categoryUuid)
    )
      return false;

    if (
      selectedBrand.value.length &&
      !selectedBrand.value.includes(p.brandUuid)
    )
      return false;

    if (selectedType.value.length) {
      const info = typeMap.value.get(p.typeUuid);
      if (!info || !selectedType.value.includes(info.norm)) return false;
    }

    if (price < priceRange.value[0] || price > priceRange.value[1])
      return false;

    return true;
  });
});

function applyFilters() {
  selectedCategories.value = [...draftCategories.value];
  selectedBrand.value = [...draftBrand.value];
  selectedType.value = [...draftType.value];
  priceRange.value = [...draftPrice.value];
}

const filtersChanged = computed(() => {
  return (
    JSON.stringify(selectedCategories.value) !==
      JSON.stringify(draftCategories.value) ||
    JSON.stringify(selectedBrand.value) !== JSON.stringify(draftBrand.value) ||
    JSON.stringify(selectedType.value) !== JSON.stringify(draftType.value) ||
    JSON.stringify(priceRange.value) !== JSON.stringify(draftPrice.value)
  );
});

const categoryState = computed(() => {
  const active = new Set();

  products.value.forEach((p) => {
    if (draftBrand.value.length && !draftBrand.value.includes(p.brandUuid))
      return;

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

function onCategoryClick(cat) {
  if (!cat.disabled) {
    if (draftCategories.value.includes(cat.uuid)) {
      draftCategories.value = draftCategories.value.filter(
        (id) => id !== cat.uuid
      );
    } else {
      draftCategories.value.push(cat.uuid);
    }
    return;
  }

  draftBrand.value = [];
  draftType.value = [];
  draftCategories.value = [cat.uuid];
}

watch(
  () => [draftBrand.value, draftType.value, categories.value],
  () => {
    const valid = categoryState.value
      .filter((c) => !c.disabled)
      .map((c) => c.uuid);

    draftCategories.value = draftCategories.value.filter((id) =>
      valid.includes(id)
    );

    if (draftBrand.value.length) {
      draftCategories.value = [...valid];
    }
  },
  { immediate: true }
);

onMounted(loadData);

watch(showFilters, (v) => {
  document.body.style.overflow = v ? "hidden" : "auto";
});
</script>

<style scoped>
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
  color: white;
  cursor: pointer;
  padding: 8px 0;
  font-size: 18px;
  user-select: none;
}

.filter-content {
  margin-bottom: 10px;
  padding-left: 5px;
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

.apply-btn {
  width: 100%;
  padding: 12px;
  background: var(--accent-color);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 18px;
  cursor: pointer;
  margin-top: 15px;
  transition: 0.2s;
}

.reset-button-filters {
  color: #fff;
  background: transparent;
  text-decoration: underline;
  border: none;
  margin-bottom: 10px;
  cursor: pointer;
}

.apply-btn.disabled {
  background: #2f3237;
  color: #999;
  cursor: default;
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

.filter-search {
  width: 100%;
  padding: 10px 12px 10px 36px; /* место для иконки */
  margin-bottom: 12px;
  border-radius: 10px;
  border: 1px solid #d0d0d0;
  background: #fafafa;
  font-size: 15px;
  transition: all 0.25s ease;
  position: relative;
  outline: none;
}

.filter-search:focus {
  background: #fff;
  border-color: #7aa3ff;
  box-shadow: 0 0 0 3px rgba(122, 163, 255, 0.35);
}

/* контейнер, чтобы иконка рисовалась */
.filter-search-wrapper {
  position: relative;
}

/* Иконка 🔍 */
.filter-search-wrapper::before {
  content: "🔍";
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 15px;
  opacity: 0.6;
  pointer-events: none;
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
  width: 18px;
  height: 18px;
  appearance: none;
  background: var(--background-input);
  border: 2px solid var(--accent-color);
  border-radius: 4px;
  cursor: pointer;
  position: relative;
}

.category-filter input[type="checkbox"]:checked {
  background: var(--accent-color);
  border-color: var(--accent-color);
}

.category-filter input[type="checkbox"]:checked::after {
  content: "✔";
  color: #000;
  font-size: 12px;
  font-weight: bold;
  position: absolute;
  top: 0;
  left: 5px;
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

/* ————————————————————— */
/*   МОБИЛЬНАЯ АДАПТАЦИЯ   */
/* ————————————————————— */
@media (max-width: 768px) {
  .catalog-page {
    padding: 12px;
  }

  .filters-close-btn {
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
    background: #1c1e22;
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
    background: #1c1e22;
    z-index: 20;
    padding: 16px 20px;
    border-bottom: 1px solid #333;

    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .filters-header h2 {
    color: white;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
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
    position: sticky;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 16px;
    border-radius: 0;
    background: var(--accent-color);
    font-size: 18px;
    margin-top: 25px;
    z-index: 50;
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
