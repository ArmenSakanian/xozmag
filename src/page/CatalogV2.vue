<template>
  <div class="catalog-wrapper">

    <!-- === ФИЛЬТРЫ === -->
    <aside class="filters-panel">

      <!-- ПОИСК -->
      <div class="filter-block">
        <input
          v-model="search"
          class="search-input"
          placeholder="Поиск товара..."
        />
      </div>

      <!-- === КАТЕГОРИИ === -->
      <div class="filter-block">
        <h3 class="filter-title">Категории</h3>

        <ul class="category-tree">
          <CategoryNode
            v-for="n in categoryTree"
            :key="n.id"
            :node="n"
            :selected="selectedCategories"
            :expanded="expandedCategories"
            @toggle="toggleCategory"
          />
        </ul>
      </div>

      <!-- === ФОТО === -->
      <div class="filter-block">
        <h3 class="filter-title">Фото</h3>
        <select v-model="photoFilter" class="select-box">
          <option value="">Все</option>
          <option value="with">С фото</option>
          <option value="without">Без фото</option>
        </select>
      </div>

      <!-- === ЦЕНА === -->
      <div class="filter-block">
        <h3 class="filter-title">Цена</h3>
        <div class="price-row">
          <input class="input-box" type="number" v-model.number="minPrice" placeholder="От" />
          <input class="input-box" type="number" v-model.number="maxPrice" placeholder="До" />
        </div>
      </div>

      <!-- === БРЕНДЫ === -->
      <div class="filter-block" v-if="brands.length">
        <h3 class="filter-title">Бренды</h3>

        <input
          v-model="brandSearch"
          class="search-brands"
          placeholder="Поиск бренда..."
        />

        <div class="scroll-list">
          <label
            v-for="b in filteredBrands"
            :key="b"
            class="check-row"
          >
            <input type="checkbox" :value="b" v-model="selectedBrands" />
            <span>{{ b }}</span>
          </label>
        </div>
      </div>

      <!-- === ХАРАКТЕРИСТИКИ === -->
      <div class="filter-block" v-for="(vals, attr) in attributeFilters" :key="attr">
        <h3 class="filter-title">{{ attr }}</h3>

        <div class="scroll-list small-scroll">
          <label
            v-for="v in vals"
            :key="v"
            class="check-row"
          >
            <input
              type="checkbox"
              :value="v"
              v-model="selectedAttributes[attr]"
            />
            <span>{{ v }}</span>
          </label>
        </div>
      </div>

    </aside>

    <!-- === ТОВАРЫ === -->
    <main class="products-area">

      <div v-if="loading" class="loading-center">
        <div class="loader"></div>
      </div>

      <div class="products-grid" v-if="!loading">
        <div
          v-for="p in filteredProducts"
          :key="p.id"
          class="product-card"
        >

          <div class="img-box">
            <img
              :src="p.images.length ? p.images[0] : '/img/no-photo.png'"
              class="img"
            />
          </div>

          <div class="card-info">
            <div class="name">{{ p.name }}</div>
            <div class="price">{{ p.price }} ₽</div>

            <div class="small">
              <span>{{ p.barcode }}</span>
              <span v-if="p.brand">Бренд: {{ p.brand }}</span>
            </div>
          </div>

        </div>

        <div v-if="filteredProducts.length === 0" class="empty">
          Нет товаров
        </div>

      </div>

    </main>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import CategoryNode from "@/components/CategoryNode.vue";

const route = useRoute();

const loading = ref(true);
const error = ref(null);

const products = ref([]);
const categories = ref([]);

const selectedCategories = ref([]);
const expandedCategories = ref([]);

const search = ref("");
const photoFilter = ref("");

const selectedBrands = ref([]);
const brandSearch = ref("");

const minPrice = ref(null);
const maxPrice = ref(null);

const selectedAttributes = ref({});
const attributeFilters = ref({});

// ================= LOAD DATA =================
async function loadData() {
  try {
    const r1 = await fetch("/api/admin/product/get_categories_flat.php");
    const rawCats = await r1.json();

    categories.value = rawCats.map(c => ({
      id: c.id,
      name: c.name,
      code: c.level_code,
      parent: c.parent_id
    }));

    const r2 = await fetch("/api/admin/product/get_products.php");
    const base = await r2.json();

    const r3 = await fetch("/api/vitrina/evotor_catalog.php");
    const ev = await r3.json();

    const map = {};
    (ev.products || []).forEach(p => {
      map[p.barcode] = p.images || [];
    });

    products.value = base.map(p => ({
      ...p,
      images: map[p.barcode] || []
    }));

    buildAttributes(products.value);

  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

// ========== ВЫЧИСЛЕНИЕ ПУТИ ДЛЯ РАСКРЫТИЯ ==========
function expandCategoryPath(code) {
  const parts = code.split(".").filter(Boolean);
  const res = [];
  let c = "";

  parts.forEach(p => {
    c += "." + p;
    res.push(c);
  });

  return res;
}

// ========== onMounted (галочка + раскрытие) ==========
onMounted(async () => {
  await loadData();

  const catFromUrl = route.query.cat;

  if (catFromUrl) {
    if (!selectedCategories.value.includes(catFromUrl)) {
      selectedCategories.value.push(catFromUrl);
    }

    expandedCategories.value = expandCategoryPath(catFromUrl);
  }
});

// ========== CATEGORY TREE ==========

const categoryTree = computed(() => {
  const map = {};
  categories.value.forEach(c => map[c.id] = { ...c, children: [] });

  const roots = [];

  categories.value.forEach(c => {
    if (!c.parent) roots.push(map[c.id]);
    else map[c.parent]?.children.push(map[c.id]);
  });

  return roots;
});

function toggleCategory(cat) {
  const code = cat.code;
  if (selectedCategories.value.includes(code)) {
    selectedCategories.value = selectedCategories.value.filter(x => x !== code);
  } else {
    selectedCategories.value.push(code);
  }
}

// ========== ATTRIBUTES ==========
function buildAttributes(list) {
  const temp = {};

  list.forEach(p => {
    (p.attributes || []).forEach(a => {
      if (!temp[a.name]) temp[a.name] = new Set();
      temp[a.name].add(a.value);
    });
  });

  for (const k in temp) {
    attributeFilters.value[k] = Array.from(temp[k]);
    selectedAttributes.value[k] = [];
  }
}

// ========== BRANDS ==========
const brands = computed(() =>
  Array.from(new Set(products.value.map(p => p.brand).filter(Boolean)))
);

const filteredBrands = computed(() => {
  if (!brandSearch.value.trim()) return brands.value;
  const s = brandSearch.value.toLowerCase();
  return brands.value.filter(b => b.toLowerCase().includes(s));
});

// ========== FINAL FILTER ==========
const filteredProducts = computed(() => {
  return products.value.filter(p => {

    if (search.value) {
      const s = search.value.toLowerCase();
      if (!p.name.toLowerCase().includes(s) && !p.barcode.includes(s))
        return false;
    }

    if (selectedCategories.value.length) {
      const ok = selectedCategories.value.some(code =>
        p.category_code?.startsWith(code)
      );
      if (!ok) return false;
    }

    if (photoFilter.value === "with" && !p.images.length) return false;
    if (photoFilter.value === "without" && p.images.length) return false;

    const price = Number(p.price);
    if (minPrice.value && price < minPrice.value) return false;
    if (maxPrice.value && price > maxPrice.value) return false;

    if (selectedBrands.value.length && !selectedBrands.value.includes(p.brand))
      return false;

    for (const attr in selectedAttributes.value) {
      const vals = selectedAttributes.value[attr];
      if (!vals.length) continue;

      const ok = p.attributes?.some(a =>
        a.name === attr && vals.includes(a.value)
      );

      if (!ok) return false;
    }

    return true;
  });
});
</script>

<style scoped>
/* ===== LAYOUT ===== */
.catalog-wrapper {
  display: flex;
  background: #0f0f11;
  color: white;
  min-height: 100vh;
  font-family: "Inter", sans-serif;
}

.filters-panel {
  width: 280px;
  padding: 22px;
  background: #15161a;
  border-right: 1px solid rgba(255,255,255,0.06);
  overflow-y: auto;
}

.filter-block {
  margin-bottom: 28px;
}

.filter-title {
  color: var(--accent-color);
  font-size: 15px;
  margin-bottom: 8px;
}

/* INPUTS */
.search-input,
.select-box,
.search-brands,
.input-box {
  width: 100%;
  padding: 10px 12px;
  background: #1d1f24;
  border: 1px solid #2d3036;
  border-radius: 10px;
  color: white;
  font-size: 14px;
  transition: .2s;
}

.search-input:focus,
.select-box:focus,
.search-brands:focus,
.input-box:focus {
  border-color: var(--accent-color);
  box-shadow: 0 0 0 2px rgba(255,0,80,0.2);
}

/* PRICE */
.price-row {
  display: flex;
  gap: 10px;
}

/* SCROLL LISTS */
.scroll-list {
  max-height: 160px;
  overflow-y: auto;
  background: #1b1d21;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #2b2d32;
}

.small-scroll {
  max-height: 120px;
}

/* CHECKBOXES */
.check-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.check-row input {
  appearance: none;
  width: 17px;
  height: 17px;
  background: #1b1d21;
  border: 2px solid #555;
  border-radius: 4px;
  cursor: pointer;
  transition: .2s;
}

.check-row input:checked {
  background: var(--accent-color);
  border-color: var(--accent-color);
}

.check-row input:checked::after {
  content: "✔";
  display: block;
  text-align: center;
  font-size: 12px;
  color: black;
  font-weight: 900;
}

/* PRODUCTS */
.products-area {
  flex: 1;
  padding: 26px;
}

.products-grid {
  display: grid;
  gap: 22px;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

.product-card {
  background: #16171b;
  border-radius: 14px;
  padding: 14px;
  border: 1px solid rgba(255,255,255,0.05);
  transition: .25s;
}

.product-card:hover {
  transform: translateY(-5px);
  border-color: var(--accent-color);
  box-shadow: 0 8px 20px rgba(255,0,80,0.25);
}

.img-box {
  height: 190px;
  background: #0d0e10;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.img {
  max-width: 100%;
  max-height: 100%;
}
</style>
