<template>
  <div class="catalog-page">
    <!-- ================= DESKTOP LEFT CATEGORIES ================= -->
    <aside class="catalog-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-title">Категории</h2>
      </div>

      <nav class="sidebar-categories">
        <ul class="category-tree-root">
          <CategoryNode
            v-for="node in categoryTree"
            :key="node.id"
            :node="node"
            :selectedCategories="selectedCategories"
            @toggle-category="toggleCategory"
          />
        </ul>
      </nav>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <section class="catalog-content">
      <!-- ===== TOP AREA ===== -->
      <div class="catalog-top">
        <!-- TITLE + BREADCRUMBS -->
        <div class="catalog-heading">
          <div class="breadcrumbs">
            <span class="breadcrumb-home">Каталог</span>
            <span v-if="currentCategory" class="breadcrumb-separator">/</span>
            <span v-if="currentCategory" class="breadcrumb-current">
              {{ currentCategoryName }}
            </span>
          </div>

          <h1 class="catalog-title">
            {{ currentCategoryName || "Все товары" }}
          </h1>
        </div>

        <!-- ===== FILTERS BAR ===== -->
        <div v-if="currentCategory && !isMobile" class="filters-bar">
          <!-- BRAND -->
          <div class="filter-block filter-brand">
            <div class="filter-label">Бренд</div>
            <div class="filter-dropdown">
              <!-- HEADER -->
              <div class="filter-dropdown-head" @click="toggleFilter('brand')">
                <span>Бренд</span>
                <span class="arrow" :class="{ open: openFilters.brand }"
                  >▾</span
                >
              </div>

              <!-- BODY -->
              <div v-show="openFilters.brand" class="filter-dropdown-body">
                <label v-for="b in brands" :key="b" class="filter-checkbox">
                  <input
                    type="checkbox"
                    :value="b"
                    v-model="brandModel"
                    @change="applyFilters"
                  />
                  <span>{{ b }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- PRICE -->
          <div class="filter-block filter-price">
            <div class="filter-label">Цена</div>
            <div class="price-inputs">
              <input
                type="number"
                placeholder="От"
                v-model.number="priceFromModel"
                @change="applyFilters"
              />
              <input
                type="number"
                placeholder="До"
                v-model.number="priceToModel"
                @change="applyFilters"
              />
            </div>
          </div>

          <!-- ATTRIBUTES -->
          <div
            class="filter-block filter-attribute"
            v-for="(vals, attr) in attributeFilters"
            :key="attr"
          >
            <div class="filter-label">{{ attr }}</div>

            <div class="filter-dropdown">
              <!-- HEADER -->
              <div class="filter-dropdown-head" @click="toggleFilter(attr)">
                <span>{{ attr }}</span>
                <span class="arrow" :class="{ open: openFilters[attr] }">
                  ▾
                </span>
              </div>

              <!-- BODY -->
              <div v-show="openFilters[attr]" class="filter-dropdown-body">
                <label v-for="v in vals" :key="v" class="filter-checkbox">
                  <input
                    type="checkbox"
                    :value="v"
                    v-model="attributeModels[attr]"
                    @change="applyFilters"
                  />
                  <span>{{ v }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- ================= MOBILE FILTER BAR ================= -->
      <div v-if="isMobile && currentCategory" class="mobile-filter-bar">
        <!-- PRICE ALWAYS VISIBLE -->
        <div class="mobile-price">
          <input
            type="number"
            placeholder="От"
            v-model.number="priceFromModel"
            @change="applyFilters"
          />
          <input
            type="number"
            placeholder="До"
            v-model.number="priceToModel"
            @change="applyFilters"
          />
        </div>

        <button class="mobile-filter-btn" @click="showMobileFilters = true">
          <i class="fa-solid fa-filter"></i> Фильтры
        </button>
      </div>

      <!-- ================= PRODUCTS ================= -->
      <div class="catalog-products">
        <!-- LOADER -->
        <div v-if="loading" class="catalog-loader">
          <div class="loader-spinner"></div>
          <div class="loader-text">Загрузка товаров…</div>
        </div>

        <!-- GRID -->
        <div v-else class="products-grid">
          <article
            v-for="p in filteredProducts"
            :key="p.id"
            class="product-card"
          >
            <!-- IMAGE -->
            <div class="product-image">
              <img
                :src="p.images.length ? p.images[0] : '/img/no-photo.png'"
                alt=""
              />
            </div>

            <!-- INFO -->
            <div class="product-info">
              <div class="product-name">
                {{ p.name }}
              </div>

              <div class="product-price">{{ p.price }} ₽</div>

              <div class="product-meta">
                <div class="product-barcode">
                  {{ p.barcode }}
                </div>
                <div v-if="p.brand" class="product-brand">
                  {{ p.brand }}
                </div>
              </div>
            </div>
          </article>

          <!-- EMPTY -->
          <div v-if="filteredProducts.length === 0" class="products-empty">
            <div class="empty-title">Товары не найдены</div>
            <div class="empty-text">
              Попробуйте изменить категорию или фильтры
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ================= MOBILE FILTERS MODAL ================= -->
    <div v-if="showMobileFilters" class="mobile-filters-overlay">
      <div class="mobile-filters-panel">
        <!-- HEADER -->
        <div class="mobile-filters-header">
          <button
            v-if="mobileView !== 'root'"
            class="back-btn"
            @click="mobileView = 'root'"
          >
            <i class="fa-solid fa-arrow-left"></i>
          </button>

          <div class="title">
            {{ mobileView === "root" ? "Фильтры" : activeMobileAttr }}
          </div>

          <button
            class="close-btn"
            @click="
              showMobileFilters = false;
              mobileView = 'root';
              activeMobileAttr = null;
            "
          >
            <i class="fa-solid fa-x"></i>
          </button>
        </div>

        <!-- ROOT -->
        <div v-if="mobileView === 'root'" class="mobile-filters-list">
          <div class="mobile-filter-item" @click="mobileView = 'brand'">
            Бренд
            <i data-v-59dbf624="" class="fa-solid fa-chevron-right cat-arrow" aria-hidden="true"></i>
          </div>

          <div
            v-for="(vals, attr) in attributeFilters"
            :key="attr"
            class="mobile-filter-item"
            @click="
              activeMobileAttr = attr;
              mobileView = 'attr';
            "
          >
            {{ attr }}
            <i data-v-59dbf624="" class="fa-solid fa-chevron-right cat-arrow" aria-hidden="true"></i>
          </div>
        </div>

        <!-- BRAND -->
        <div v-if="mobileView === 'brand'" class="mobile-filter-values">
          <label v-for="b in brands" :key="b" class="filter-checkbox">
            <input
              type="checkbox"
              :value="b"
              v-model="brandModel"
              @change="applyFilters"
            />
            <span>{{ b }}</span>
          </label>
        </div>

        <!-- ATTRIBUTE -->
        <div v-if="mobileView === 'attr'" class="mobile-filter-values">
          <label
            v-for="v in attributeFilters[activeMobileAttr]"
            :key="v"
            class="filter-checkbox"
          >
            <input
              type="checkbox"
              :value="v"
              v-model="attributeModels[activeMobileAttr]"
              @change="applyFilters"
            />
            <span>{{ v }}</span>
          </label>
        </div>
      </div>
    </div>
    <!-- ================= MOBILE CATEGORIES ================= -->
    <div v-if="isMobile" class="mobile-cats-btn" @click="showMobileCats = true">
      ☰
    </div>

    <div v-if="showMobileCats" class="mobile-cats-overlay">
      <div class="mobile-cats-panel">
        <div class="mobile-cats-header">
          <span>Категории</span>
          <button @click="showMobileCats = false">✕</button>
        </div>

        <ul class="category-tree-root">
          <CategoryNode
            v-for="node in categoryTree"
            :key="node.id"
            :node="node"
            :selectedCategories="selectedCategories"
            @toggle-category="toggleCategory"
          />
        </ul>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import CategoryNode from "@/components/CategoryNode.vue";

/* ================= ROUTER ================= */
const route = useRoute();
const router = useRouter();

/* ================= STATE ================= */
const loading = ref(true);
const error = ref(null);

const products = ref([]);
const categories = ref([]);
const selectedCategories = ref([]); // ⬅ ВАЖНО
function toggleCategory(code) {
  if (selectedCategories.value.includes(code)) {
    selectedCategories.value = selectedCategories.value.filter(
      (c) => c !== code
    );
  } else {
    selectedCategories.value.push(code);
  }
}

const showMobileCats = ref(false);
const openFilters = ref({});
function toggleFilter(key) {
  const next = {};

  // закрываем все
  Object.keys(openFilters.value).forEach((k) => {
    next[k] = false;
  });

  // если кликнули по уже открытому — просто закрываем
  if (!openFilters.value[key]) {
    next[key] = true;
  }

  openFilters.value = next;
}

/* ================= URL SOURCE OF TRUTH ================= */
const currentCategory = computed(() => route.query.cat || null);
const isMobile = ref(window.innerWidth < 1024);
window.addEventListener("resize", () => {
  isMobile.value = window.innerWidth < 1024;
});
const showMobileFilters = ref(false);
const mobileView = ref("root");
// root | brand | attr
const activeMobileAttr = ref(null);
/* ================= FILTER MODELS ================= */
const brandModel = ref([]);
const priceFromModel = ref(
  route.query.price_from ? Number(route.query.price_from) : null
);
const priceToModel = ref(
  route.query.price_to ? Number(route.query.price_to) : null
);

/* динамические характеристики */
const attributeModels = ref({});
/* ================= DATA LOAD ================= */
async function loadData() {
  try {
    loading.value = true;

    /* ---- categories ---- */
    const r1 = await fetch("/api/admin/product/get_categories_flat.php");
    const rawCats = await r1.json();

    categories.value = rawCats.map((c) => ({
      id: c.id,
      name: c.name,
      code: c.code,
      parent: c.parent_id,
    }));

    /* ---- products ---- */
    const r2 = await fetch("/api/admin/product/get_products.php");
    const baseProducts = await r2.json();

    /* ---- images (evotor) ---- */
    const r3 = await fetch("/api/vitrina/evotor_catalog.php");
    const evotor = await r3.json();

    const imgMap = {};
    (evotor.products || []).forEach((p) => {
      imgMap[p.barcode] = p.images || [];
    });

    products.value = baseProducts.map((p) => ({
      ...p,
      images: imgMap[p.barcode] || [],
    }));
  } catch (e) {
    error.value = e.message || "Ошибка загрузки";
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);

/* ================= CATEGORY TREE ================= */
const categoryTree = computed(() => {
  const map = {};
  categories.value.forEach((c) => {
    map[c.id] = { ...c, children: [] };
  });

  const roots = [];

  categories.value.forEach((c) => {
    if (!c.parent) roots.push(map[c.id]);
    else map[c.parent]?.children.push(map[c.id]);
  });

  return roots;
});

/* ================= CURRENT CATEGORY NAME ================= */
const currentCategoryName = computed(() => {
  if (!currentCategory.value) return null;
  const found = categories.value.find((c) => c.code === currentCategory.value);
  return found ? found.name : null;
});

/* ================= CATEGORY SELECT ================= */
function selectCategory(cat) {
  router.push({
    query: {
      cat: cat.code,
    },
  });
}

function selectCategoryMobile(cat) {
  showMobileCats.value = false;
  selectCategory(cat);
}

/* ================= BRANDS (BY CATEGORY) ================= */
const brands = computed(() => {
  if (!currentCategory.value) return [];

  const set = new Set();

  products.value.forEach((p) => {
    if (
      typeof p.category_code === "string" &&
      p.category_code.startsWith(currentCategory.value) &&
      p.brand
    ) {
      set.add(p.brand);
    }
  });

  return Array.from(set).sort();
});

/* ================= ATTRIBUTES (BY CATEGORY) ================= */
const attributeFilters = computed(() => {
  if (!currentCategory.value) return {};

  const temp = {};

  products.value.forEach((p) => {
    if (
      typeof p.category_code !== "string" ||
      !p.category_code.startsWith(currentCategory.value)
    )
      return;

    (p.attributes || []).forEach((a) => {
      if (!a?.value) return;

      if (!temp[a.name]) temp[a.name] = new Set();
      temp[a.name].add(a.value);
    });
  });

  const res = {};
  for (const k in temp) {
    res[k] = Array.from(temp[k]).sort();
  }

  return res;
});

watch(
  attributeFilters,
  () => {
    const next = { ...attributeModels.value };

    Object.keys(attributeFilters.value).forEach((k) => {
      if (!Array.isArray(next[k])) {
        next[k] = [];
      }
    });

    attributeModels.value = next;
  },
  { immediate: true }
);

/* ================= APPLY FILTERS ================= */
function applyFilters() {
  const query = {
    cat: currentCategory.value,
    brand: brandModel.value.length ? brandModel.value : undefined,
    price_from:
      priceFromModel.value !== null ? priceFromModel.value : undefined,
    price_to: priceToModel.value !== null ? priceToModel.value : undefined,
  };

  for (const [k, v] of Object.entries(attributeModels.value)) {
    if (v.length) query[`attr_${k}`] = v;
  }

  router.push({ query });
}

/* ================= URL → MODELS ================= */
watch(
  attributeFilters,
  () => {
    const next = { ...openFilters.value };

    // атрибуты
    Object.keys(attributeFilters.value).forEach((k) => {
      if (next[k] === undefined) next[k] = false;
    });

    // бренд
    if (next.brand === undefined) next.brand = false;

    openFilters.value = next;
  },
  { immediate: true }
);
/* ================= MOBILE UX WATCHERS ================= */

// блокируем скролл при открытых фильтрах
watch(showMobileFilters, (v) => {
  document.body.style.overflow = v ? "hidden" : "";
});

// блокируем скролл при открытых категориях
watch(showMobileCats, (v) => {
  document.body.style.overflow = v ? "hidden" : "";
});

// при смене категории закрываем мобильные фильтры
watch(currentCategory, () => {
  showMobileFilters.value = false;
  mobileView.value = "root";
  activeMobileAttr.value = null;
});

/* ================= FINAL PRODUCTS ================= */
const filteredProducts = computed(() => {
  let list = products.value;

  if (selectedCategories.value.length) {
    list = list.filter((p) =>
      selectedCategories.value.some((code) => p.category_code?.startsWith(code))
    );
  } else if (currentCategory.value) {
    list = list.filter((p) =>
      p.category_code?.startsWith(currentCategory.value)
    );
  }

  if (brandModel.value.length) {
    list = list.filter((p) => brandModel.value.includes(p.brand));
  }

  if (priceFromModel.value !== null) {
    list = list.filter((p) => Number(p.price) >= priceFromModel.value);
  }

  if (priceToModel.value !== null) {
    list = list.filter((p) => Number(p.price) <= priceToModel.value);
  }

  for (const [k, arr] of Object.entries(attributeModels.value)) {
    if (!arr.length) continue;

    list = list.filter((p) =>
      p.attributes?.some((a) => a.name === k && arr.includes(a.value))
    );
  }

  return list;
});
</script>

<style scoped>
/* =========================
   ROOT / BASE
========================= */
:root {
  --bg-main: #f4f6fb;
  --bg-panel: #ffffff;
  --bg-soft: #f0f2f7;

  --text-main: #1b1e28;
  --text-muted: #6b7280;
  --text-light: #9aa1b2;

  --accent: #0400ff; /* основной акцент */
  --accent-2: #16a34a; /* вторичный */
  --accent-danger: #dc2626;

  --border-soft: #e4e7ef;
  /* --border-strong: #797979; */

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 16px;

  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.08);
  --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.12);
}

* {
  box-sizing: border-box;
}

body {
  background: var(--bg-main);
}

/* =========================
   PAGE LAYOUT
========================= */
.catalog-page {
  display: flex;
  min-height: 100vh;
  background: var(--bg-main);
  color: var(--text-main);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

/* =========================
   SIDEBAR (DESKTOP)
========================= */
.catalog-sidebar {
  width: 300px;
  background: var(--bg-panel);
  border-right: 1px solid var(--border-soft);
  padding: 22px 18px;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-soft);
  margin-bottom: 16px;
}

.sidebar-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-main);
}

.sidebar-categories {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
}

.category-tree-root {
  list-style: none;
  padding: 0;
  margin: 0;
}

/* =========================
   MAIN CONTENT
========================= */
.catalog-content {
  flex: 1;
  padding: 26px 30px;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

/* =========================
   TOP AREA
========================= */
.catalog-top {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ===== BREADCRUMBS + TITLE ===== */
.catalog-heading {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.breadcrumbs {
  font-size: 13px;
  color: var(--text-muted);
}

.breadcrumb-home {
  cursor: pointer;
}

.breadcrumb-separator {
  margin: 0 6px;
}

.breadcrumb-current {
  color: var(--text-main);
  font-weight: 500;
}

.catalog-title {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-main);
}

/* =========================
   FILTERS BAR
========================= */
.filters-bar {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 18px;
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  box-shadow: var(--shadow-sm);
  animation: fadeSlideIn 0.35s ease;
}

@keyframes fadeSlideIn {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.filter-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 180px;
}

.filter-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.filter-select {
  appearance: none;
  padding: 11px 38px 11px 14px;
  border-radius: 999px; /* 🔥 круглые */
  border: 1px solid #797979;
  background-color: #ffffff;
  font-size: 14px;
  color: var(--text-main);
  cursor: pointer;
  transition: border-color 0.25s ease, box-shadow 0.25s ease,
    background-color 0.25s ease;

  background-image: linear-gradient(
      45deg,
      transparent 50%,
      var(--text-muted) 50%
    ),
    linear-gradient(135deg, var(--text-muted) 50%, transparent 50%);
  background-position: calc(100% - 20px) calc(50% - 2px),
    calc(100% - 14px) calc(50% - 2px);
  background-size: 6px 6px;
  background-repeat: no-repeat;
}

.filter-select:hover {
  border-color: #0400ff;
}

.filter-select:focus {
  outline: none;
  border-color: #0400ff;
  background-color: #f8faff;
  border-radius: 0;
}

.price-inputs {
  display: flex;
  gap: 8px;
}

.price-inputs input {
  padding: 11px 14px;
  border-radius: 999px;
  border: 1px solid #797979;
  font-size: 14px;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.price-inputs input:focus {
  outline: none;
  border-color: #0400ff;
  background-color: #f8faff;
  border-radius: 0;
}

/* =========================
   PRODUCTS AREA
========================= */
.catalog-products {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 24px;
}

/* =========================
   PRODUCT CARD
========================= */
.product-card {
  background: var(--bg-panel);
  border-radius: 14px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease,
    border-color 0.25s ease;
  box-shadow: 0 0 14px 0px rgb(0 0 0 / 50%);
}

/* hover — очень аккуратный */
.product-card:hover {
  transform: translateY(-4px);
}

/* IMAGE — как плитка */
.product-image {
  aspect-ratio: 1 / 1; /* квадрат */
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.product-image img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

/* INFO */
.product-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* название — как подпись */
.product-name {
  font-size: 14px;
  line-height: 1.35;
  font-weight: 500;
  color: var(--text-main);
  max-height: 2.7em;
  overflow: hidden;
}

/* цена — акцент */
.product-price {
  font-size: 18px;
  font-weight: 800;
  color: var(--accent);
}

/* мета — вторично */
.product-meta {
  font-size: 11px;
  color: var(--text-light);
}

/* =========================
   EMPTY STATE
========================= */
.products-empty {
  grid-column: 1 / -1;
  background: var(--bg-panel);
  border: 1px dashed #797979;
  border-radius: var(--radius-lg);
  padding: 40px;
  text-align: center;
}

.empty-title {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 6px;
}

.empty-text {
  font-size: 14px;
  color: var(--text-muted);
}

/* =========================
   LOADER
========================= */
.catalog-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 50vh;
  gap: 14px;
}

.loader-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #dbe0ec;
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

.loader-text {
  font-size: 14px;
  color: var(--text-muted);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* =========================
   RESPONSIVE
========================= */

.filter-dropdown {
  position: relative; /* ⬅ якорь */
  border: 1px solid var(--border-soft);
  border-radius: 12px;
  background: #fff;
}

.filter-dropdown-head {
  padding: 10px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  background: #fff;
  font-size: 14px;
  font-weight: 500;
}

.filter-dropdown-head:hover {
  background: #f8faff;
}

.filter-dropdown-body {
  position: absolute; /* ⬅ выпадает поверх */
  top: calc(100% + 6px);
  left: 0;
  right: 0;

  z-index: 50;

  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;

  background: #fff;
  border: 1px solid var(--border-soft);
  border-radius: 12px;
  box-shadow: var(--shadow-md);
}

.filter-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  cursor: pointer;
}

.filter-checkbox input {
  cursor: pointer;
}

.arrow {
  transition: transform 0.25s ease;
}

.arrow.open {
  transform: rotate(180deg);
}

@media (max-width: 1024px) {
  .catalog-sidebar {
    width: 260px;
  }
  .products-grid {
    grid-template-columns: 1fr;
  }
  /* ===== MOBILE FILTER BAR ===== */
  .mobile-filter-bar {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
  }

  .mobile-price {
    width: 100%;
    display: flex;
    gap: 6px;
    flex: 1;
  }

  .mobile-price input {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #797979;
    width: 50%;
  }

  .mobile-filter-btn {
    border-radius: 10px;
    background: transparent;
    color: #000000;
    border: none;
    position: relative;
    left: 135px;
    top: 10px;
    text-decoration: underline;
  }

  /* ===== FILTER MODAL ===== */
  .mobile-filters-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 300;
  }

.mobile-filters-panel {
  position: fixed;
  inset: 0;

  width: 100%;
  height: 100vh;

  background: #fff;
  display: flex;
  flex-direction: column;
  padding: 90px 0;
  z-index: 310;
}


  /* ===== CATEGORIES ===== */
  .mobile-cats-btn {
    position: fixed;
    bottom: 16px;
    left: 16px;

    width: 52px;
    height: 52px;

    background: #000; /* ⬅ ЧЁРНЫЙ */
    color: #fff;

    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 22px;
    font-weight: 700;

    z-index: 1000; /* ⬅ поверх всего */
  }

  .mobile-cats-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 500;
  }

  .mobile-cats-panel {
    position: fixed;
    inset: 0; /* ⬅ ВЕСЬ ЭКРАН */

    background: #fff;
    padding: 90px 16px;

    overflow-y: auto;
    z-index: 1100;
  }

.mobile-filters-header {
  height: 56px;
  padding: 0 14px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  border-bottom: 1px solid var(--border-soft);
  flex-shrink: 0;

  background: #fff;
  z-index: 2;
}
.mobile-filters-list,
.mobile-filter-values {
  flex: 1;
  overflow-y: auto;
  padding: 12px 14px;
}


  .mobile-filters-header .title {
    font-size: 16px;
    font-weight: 700;
  }
  .mobile-cats-header {
    position: sticky;
    top: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    background: #fff;
    z-index: 10;
  }

  .mobile-cats-header span {
    font-size: 18px;
    font-weight: 700;
  }

  .mobile-cats-header button {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
  }

  .back-btn,
  .close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
  }

  .mobile-filter-item {
    padding: 14px;
    border-bottom: 1px solid var(--border-soft);
    display: flex;
    justify-content: space-between;
    font-size: 16px;
  }

  .mobile-filter-values {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
}

@media (max-width: 768px) {
  .product-card {
    padding: 10px;
    border-radius: 12px;
  }

  .product-price {
    font-size: 16px;
  }
  .catalog-page {
    flex-direction: column;
  }

  .catalog-sidebar {
    display: none;
  }

  .catalog-content {
    padding: 16px;
  }

  .filters-bar {
    padding: 14px;
    gap: 12px;
  }

  .product-image {
    height: 150px;
  }

  .catalog-content {
    padding-bottom: 80px;
  }
}
</style>
