<template>
  <div class="catalog-page">
    <div v-if="loading" class="loading">Загрузка...</div>
    <div v-if="error" class="error">{{ error }}</div>

    <!-- ЗАТЕМНЕНИЕ ФОНА ПРИ ОТКРЫТЫХ ФИЛЬТРАХ (ТОЛЬКО МОБИЛКА) -->
    <div
      v-if="showFilters"
      class="filters-backdrop"
      @click="showFilters = false"
    ></div>

    <!-- === ЛЕВАЯ ПАНЕЛЬ ФИЛЬТРОВ === -->
    <div class="filters" :class="{ open: showFilters }">
      <button class="close-filters" @click="showFilters = false">✕</button>

      <!-- Фильтр категорий -->
      <div class="filter-category">
        <h3>Категории</h3>
        <div
          v-for="cat in catalog"
          :key="cat.uuid"
          class="category-filter"
        >
          <input
            type="checkbox"
            :id="'cat-' + cat.uuid"
            v-model="selectedCategories"
            :value="cat.uuid"
          >
          <label :for="'cat-' + cat.uuid">{{ cat.name }}</label>
        </div>
      </div>

      <hr />

      <!-- === ЦЕНА === -->
      <div class="filter-price">
        <h3>Цена</h3>

        <div class="price-range-inputs">
          <input
            type="number"
            v-model.number="priceRange[0]"
            :max="maxPrice"
            min="0"
            @input="updateFromInput('min')"
          >
          <span class="sep">–</span>
          <input
            type="number"
            v-model.number="priceRange[1]"
            :max="maxPrice"
            min="0"
            @input="updateFromInput('max')"
          >
        </div>
      </div>
    </div>

    <!-- КНОПКА ФИЛЬТРОВ (ТОЛЬКО МОБИЛКА) -->
    <button class="mobile-filters-btn" @click="showFilters = true">
      Фильтры
    </button>

    <!-- === ТОВАРЫ === -->
    <div class="products-grid">
      <div
        v-for="item in currentProducts"
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
import { ref, computed, onMounted } from "vue";

const showFilters = ref(false);
const catalog = ref([]);
const selectedCategories = ref([]);
const priceRange = ref([0, 0]);
const maxPrice = ref(0);
const loading = ref(true);
const error = ref(null);

async function loadData() {
  try {
    const r = await fetch("/api/vitrina/evotor_catalog.php");
    const data = await r.json();

    if (!Array.isArray(data)) throw new Error("Неверный формат данных");

    data.forEach(cat => {
      cat.products = cat.products.filter(p => p.quantity > 0);
    });

    catalog.value = data;

    const prices = data.flatMap(c => c.products.map(p => p.price));
    maxPrice.value = Math.max(...prices);

    // диапазон по умолчанию — от 0 до максимальной цены
    priceRange.value = [0, maxPrice.value];
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);

// корректировка значений из инпутов
function updateFromInput(type) {
  let min = Number(priceRange.value[0]);
  let max = Number(priceRange.value[1]);

  if (min < 0) min = 0;
  if (max > maxPrice.value) max = maxPrice.value;

  if (type === "min" && min > max) min = max;
  if (type === "max" && max < min) max = min;

  priceRange.value = [min, max];
}

// === ФИЛЬТРОВАННЫЕ ТОВАРЫ ===
const currentProducts = computed(() => {
  let items = [];

  catalog.value.forEach(cat => {
    if (
      selectedCategories.value.length === 0 ||
      selectedCategories.value.includes(cat.uuid)
    ) {
      items.push(
        ...cat.products.filter(
          p =>
            p.price >= priceRange.value[0] &&
            p.price <= priceRange.value[1]
        )
      );
    }
  });

  return items;
});
</script>

<style scoped>
.catalog-page {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 30px;
  padding: 20px;
  align-items: start;
}

/* === ФИЛЬТРЫ === */
.filters {
  position: sticky;
  top: 20px;
  background: #1c1e22;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgb(0 0 0 / 0.2);
  height: max-content;
  transition: transform 0.35s ease-in-out;
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
  width: 100%;
  background: #4da3ff;
  color: white;
  padding: 12px;
  text-align: center;
  border-radius: 10px;
  border: none;
  font-size: 18px;
  margin-bottom: 15px;
  cursor: pointer;
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
  background: #2f3237;
  border: 2px solid #4da3ff;
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
  top: -1px;
  left: 2px;
}

.category-filter label {
  color: white;
}

.filter-price {
  margin-top: 10px;
}

.filter-price h3 {
  margin-bottom: 10px;
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
  background: #2a2d31;
  border: 1px solid #4da3ff;
  border-radius: 6px;
  color: white;
  text-align: center;
}

.sep {
  color: #aaa;
}

/* === ТОВАРЫ === */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

.product-card {
  background: #1c1e22;
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
  color: #4da3ff;
}

/* ————————————————————— */
/*   МОБИЛЬНАЯ АДАПТАЦИЯ   */
/* ————————————————————— */
@media (max-width: 768px) {
  .catalog-page {
    display: block;
    padding: 15px;
  }

  /* Кнопка "Фильтры" включается */
  .mobile-filters-btn {
    display: block;
  }

  /* Затемнение фона при открытых фильтрах */
  .filters-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1500;
  }

  /* Фильтры превращаются в выезжающее окно */
  .filters {
    position: fixed;
    top: 0;
    left: 0;
    width: 80%;
    max-width: 320px;
    height: 100vh;
    padding: 25px;
    border-radius: 0;
    transform: translateX(-100%); /* закрыто */
    z-index: 2001;
    overflow-y: auto;
  }

  .filters.open {
    transform: translateX(0);
  }

  .close-filters {
    display: block;
  }

  .products-grid {
    grid-template-columns: 1fr;
  }
}
</style>
