<template>
  <div class="catalog-page">

    <!-- ЗАГРУЗКА -->
    <div v-if="loading" class="loading">Загрузка...</div>
    <div v-if="error" class="error">{{ error }}</div>

    <!-- БЭКДРОП ДЛЯ МОБИЛКИ -->
    <div
      v-if="showFilters"
      class="filters-backdrop"
      @click="showFilters = false"
    ></div>

    <!-- === ЛЕВАЯ ПАНЕЛЬ ФИЛЬТРОВ === -->
    <div class="filters" :class="{ open: showFilters }">
      <button class="close-filters" @click="showFilters = false">✕</button>

      <!-- === КАТЕГОРИИ === -->
      <div class="filter-category">
        <h3>Категории</h3>

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
  :disabled="false"
/>

          <label
  :for="'cat-' + cat.uuid"
  :class="{ disabled: cat.disabled }"
>
  {{ cat.name }}
</label>

        </div>
      </div>

      <hr />

      <!-- === БРЕНДЫ === -->
      <div class="filter-block">
        <h3>Бренд</h3>

        <select v-model="draftBrand">
          <option value="">Все бренды</option>
          <option
            v-for="b in availableBrands"
            :key="b.uuid"
            :value="b.uuid"
          >
            {{ b.name }}
          </option>
        </select>
      </div>

      <hr />

      <!-- === ТИПЫ ТОВАРОВ === -->
      <div class="filter-block">
        <h3>Тип товара</h3>

        <select v-model="draftType">
          <option value="">Все типы</option>
          <option
            v-for="t in availableTypes"
            :key="t.uuid"
            :value="t.uuid"
          >
            {{ t.name }}
          </option>
        </select>
      </div>

      <hr />

      <!-- === ЦЕНА === -->
      <div class="filter-price">
        <h3>Цена</h3>

        <div class="price-range-inputs">
          <input
            type="number"
            v-model.number="draftPrice[0]"
            min="0"
            :max="maxPrice"
          >
          <span class="sep">–</span>
          <input
            type="number"
            v-model.number="draftPrice[1]"
            min="0"
            :max="maxPrice"
          >
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

    <!-- КНОПКА ФИЛЬТРОВ ДЛЯ МОБИЛКИ -->
    <button class="mobile-filters-btn" @click="showFilters = true">
      Фильтры
    </button>

    <!-- === ТОВАРЫ === -->
    <div class="products-grid">

      <!-- ЕСЛИ НИЧЕГО НЕ НАЙДЕНО -->
      <div
        v-if="!loading && filteredProducts.length === 0"
        class="no-products"
      >
        Товаров по текущим выбранным фильтрам не существует или нет в наличии
      </div>

      <!-- КАРТОЧКИ ТОВАРОВ -->
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
const selectedBrand = ref("");
const selectedType = ref("");

const priceRange = ref([0, 0]);
const maxPrice = ref(0);

// Фильтры перед применением
const draftCategories = ref([]);
const draftBrand = ref("");
const draftType = ref("");
const draftPrice = ref([0, 0]);

async function loadData() {
  try {
    const r = await fetch("/api/vitrina/evotor_catalog.php");
    const data = await r.json();

    categories.value = data.categories || [];
    brands.value = data.brands || [];
    types.value = data.types || [];

    products.value = (data.products || []).filter(p => (p.quantity ?? 0) > 0);

    if (products.value.length) {
      const prices = products.value.map(p => Number(p.price) || 0);
      maxPrice.value = Math.max(...prices);

      priceRange.value = [0, maxPrice.value];

      draftPrice.value = [0, maxPrice.value];
    }
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;

    // по умолчанию — ВСЕ категории выбраны
    draftCategories.value = categories.value.map(c => c.uuid);
  }
}

// доступные бренды по выбранным категориям и типу
const availableBrands = computed(() => {
  const set = new Set();

  products.value.forEach(p => {
    if (draftCategories.value.length && !draftCategories.value.includes(p.categoryUuid)) return;
    if (draftType.value && p.typeUuid !== draftType.value) return;

    if (p.brandUuid) set.add(p.brandUuid);
  });

  return brands.value.filter(b => set.has(b.uuid));
});

// доступные типы по категориям и брендам
const availableTypes = computed(() => {
  const set = new Set();

  products.value.forEach(p => {
    if (draftCategories.value.length && !draftCategories.value.includes(p.categoryUuid)) return;
    if (draftBrand.value && p.brandUuid !== draftBrand.value) return;

    set.add(p.typeUuid);
  });

  return types.value.filter(t => set.has(t.uuid));
});

// доступные категории по бренду и типу
const availableCategories = computed(() => {
  const set = new Set();

  products.value.forEach(p => {
    if (draftBrand.value && p.brandUuid !== draftBrand.value) return;
    if (draftType.value && p.typeUuid !== draftType.value) return;

    set.add(p.categoryUuid);
  });

  return categories.value.filter(c => set.has(c.uuid));
});

// итоговые отфильтрованные товары
const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const price = Number(p.price) || 0;

    if (selectedCategories.value.length && !selectedCategories.value.includes(p.categoryUuid)) return false;
    if (selectedBrand.value && p.brandUuid !== selectedBrand.value) return false;
    if (selectedType.value && p.typeUuid !== selectedType.value) return false;

    if (price < priceRange.value[0] || price > priceRange.value[1]) return false;

    return true;
  });
});

// КНОПКА ПРИМЕНИТЬ
function applyFilters() {
  selectedCategories.value = [...draftCategories.value];
  selectedBrand.value = draftBrand.value;
  selectedType.value = draftType.value;
  priceRange.value = [...draftPrice.value];
}

const filtersChanged = computed(() => {
  return (
    JSON.stringify(selectedCategories.value) !== JSON.stringify(draftCategories.value) ||
    selectedBrand.value !== draftBrand.value ||
    selectedType.value !== draftType.value ||
    JSON.stringify(priceRange.value) !== JSON.stringify(draftPrice.value)
  );
});

// Категории считаются доступными, но могут быть неактивны
const categoryState = computed(() => {
  const active = new Set();

  products.value.forEach(p => {
    if (draftBrand.value && p.brandUuid !== draftBrand.value) return;
    if (draftType.value && p.typeUuid !== draftType.value) return;

    active.add(p.categoryUuid);
  });

  return categories.value.map(c => ({
    ...c,
    disabled: !active.has(c.uuid)
  }));
});


function onCategoryClick(cat) {
  // если категория активна → просто меняем выбор
  if (!cat.disabled) {
    // обычное поведение
    if (draftCategories.value.includes(cat.uuid)) {
      draftCategories.value = draftCategories.value.filter(id => id !== cat.uuid);
    } else {
      draftCategories.value.push(cat.uuid);
    }
    return;
  }

  // если категорию НЕЛЬЗЯ выбрать, но пользователь кликнул
  // → сбрасываем фильтры кроме цены
  draftBrand.value = "";
  draftType.value = "";

  // выбираем ВСЕ активные категории
  draftCategories.value = categories.value.map(c => c.uuid);

  // затем делаем выбранной именно эту категорию
  draftCategories.value = [cat.uuid];
}


watch(
  () => [draftBrand.value, draftType.value, categories.value],
  () => {
    const validCategories = categoryState.value
      .filter(c => !c.disabled)
      .map(c => c.uuid);

    // удаляем из выбранных те, которые стали disabled
    draftCategories.value = draftCategories.value.filter(id =>
      validCategories.includes(id)
    );

    // если бренд выбран → автоматически выбираем ВСЕ доступные категории
    if (draftBrand.value) {
      draftCategories.value = [...validCategories];
    }
  },
  { immediate: true }
);


onMounted(loadData);
</script>



<style scoped>

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
  background: #4da3ff;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 18px;
  cursor: pointer;
  margin-top: 15px;
  transition: 0.2s;
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

/* Блоки бренда/типа/цены */
.filter-block {
  margin-top: 10px;
}

.filter-block h3 {
  margin-bottom: 10px;
  color: white;
}

/* Селекты фильтров бренда и типа */
.filter-block select {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #4da3ff;
  background: #2a2d31;
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

