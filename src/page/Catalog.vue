<template>
  <div class="catalog-page">
    <section class="catalog-content">
      <div class="catalog-top">
        <div class="catalog-search">
          <HomeSearch
            :show-category="true"
            :current-category="currentCategory"
            :sync-route="true"
            route-key="q"
            catalog-path="/catalog"
            :server-limit="30"
            :dropdown-limit="12"
            @search-hits="searchHits = $event"
            @categories-loaded="onCategoriesLoaded"
            @categories-loading="catsLoading = $event"
          />

          <div v-if="searchQ && !loading" class="search-meta">
            Найдено: <b>{{ filteredProducts.length }}</b>
          </div>
        </div>

        <div class="catalog-heading">
          <div class="breadcrumbs">
            <span class="breadcrumb-home">Каталог</span>
            <span v-if="currentCategory" class="breadcrumb-separator">/</span>
            <span v-if="currentCategory" class="breadcrumb-current">
              {{ currentCategoryName }}
            </span>
          </div>

          <h1 class="catalog-title">
            <template v-if="!hasActiveCategory && !searchQ">Категории</template>
            <template v-else-if="searchQ && !hasActiveCategory"
              >Результаты поиска</template
            >
            <template v-else>{{ currentCategoryName || "Каталог" }}</template>
          </h1>
        </div>

        <!-- ===== FILTERS BAR (DESKTOP) ===== -->
        <div v-if="hasActiveCategory && !isMobile" class="filters-bar">
          <!-- PRICE (всегда) -->
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

          <!-- ✅ TYPE (только если выбрана категория 1 уровня) -->
          <div
            v-if="isRootCategorySelected === true"
            class="filter-block filter-type"
            :class="{ open: openFilters.type }"
          >
            <div class="filter-label">Тип товара</div>

            <div class="filter-dropdown">
              <div class="filter-dropdown-head" @click="toggleFilter('type')">
                <span class="filter-head-text">{{ typeHeadText }}</span>
                <span class="arrow" :class="{ open: openFilters.type }">▾</span>
              </div>

              <div
                v-show="openFilters.type"
                class="filter-dropdown-body"
                :class="{ scrollable: (typeOptions.length || 0) > 6 }"
              >
                <label class="filter-checkbox filter-all">
                  <input
                    type="checkbox"
                    :checked="!typeModel.length"
                    @change="
                      typeModel = [];
                      applyFilters();
                    "
                  />
                  <span>Все</span>
                </label>

                <label
                  v-for="c in typeOptions"
                  :key="c.id"
                  class="filter-checkbox typeopt"
                  :class="{ parent: c.hasChildren }"
                  :style="{ '--indent': (c.depth - 1) * 14 + 'px' }"
                >
                  <input
                    type="checkbox"
                    :value="String(c.code)"
                    v-model="typeModel"
                    @change="applyFilters"
                  />

                  <span class="typeopt-text">
                    <span class="typeopt-name">{{ c.name }}</span>
                  </span>
                </label>

                <div v-if="!typeOptions.length" class="dd-empty">
                  Нет подкатегорий
                </div>
              </div>
            </div>
          </div>

          <!-- ✅ BRAND (всегда) -->
          <div
            class="filter-block filter-brand"
            :class="{ open: openFilters.brand }"
          >
            <div class="filter-label">Бренд</div>
            <div class="filter-dropdown">
              <div class="filter-dropdown-head" @click="toggleFilter('brand')">
                <span class="filter-head-text">
                  {{
                    brandModel.length
                      ? brandModel.length <= 2
                        ? brandModel.join("· ")
                        : `Выбрано: ${brandModel.length}`
                      : " Все"
                  }}
                </span>
                <span class="arrow" :class="{ open: openFilters.brand }"
                  >▾</span
                >
              </div>

              <div
                v-show="openFilters.brand"
                class="filter-dropdown-body"
                :class="{ scrollable: brands.length > 6 }"
              >
                <label class="filter-checkbox filter-all">
                  <input
                    type="checkbox"
                    :checked="!brandModel.length"
                    @change="
                      brandModel = [];
                      applyFilters();
                    "
                  />
                  <span>Все</span>
                </label>

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

          <!-- ✅ PHOTO (всегда, НЕ зависит от типа) -->
          <div
            class="filter-block filter-photo"
            :class="{ open: openFilters.photo }"
          >
            <div class="filter-label">Фото</div>

            <div class="filter-dropdown">
              <div class="filter-dropdown-head" @click="toggleFilter('photo')">
                <span class="filter-head-text">{{ photoHeadText }}</span>
                <span class="arrow" :class="{ open: openFilters.photo }"
                  >▾</span
                >
              </div>

              <div v-show="openFilters.photo" class="filter-dropdown-body">
                <label class="filter-checkbox">
                  <input
                    type="radio"
                    value="all"
                    v-model="photoModel"
                    @change="applyFilters"
                  />
                  <span>Все</span>
                </label>

                <label class="filter-checkbox">
                  <input
                    type="radio"
                    value="with"
                    v-model="photoModel"
                    @change="applyFilters"
                  />
                  <span>С фото</span>
                </label>

                <label class="filter-checkbox">
                  <input
                    type="radio"
                    value="without"
                    v-model="photoModel"
                    @change="applyFilters"
                  />
                  <span>Без фото</span>
                </label>
              </div>
            </div>
          </div>

          <!-- ✅ ATTRIBUTES: только если НЕ root или на root выбран хотя бы 1 тип -->
          <template v-if="allowAttrFilters">
            <div
              class="filter-block filter-attribute"
              :class="{ open: openFilters[attr] }"
              v-for="(block, attr) in attributeFilters"
              :key="attr"
            >
              <div class="filter-label">{{ attr }}</div>

              <div class="filter-dropdown">
                <div class="filter-dropdown-head" @click="toggleFilter(attr)">
                  <span class="filter-head-text">{{
                    attributeHeadText(attr)
                  }}</span>
                  <span class="arrow" :class="{ open: openFilters[attr] }"
                    >▾</span
                  >
                </div>

                <div
                  v-show="openFilters[attr]"
                  class="filter-dropdown-body"
                  :class="{ scrollable: (block.values?.length || 0) > 6 }"
                >
                  <label class="filter-checkbox filter-all">
                    <input
                      type="checkbox"
                      :checked="isAttrAllSelected(attr)"
                      @change="selectAttrAll(attr)"
                    />
                    <span>Все</span>
                  </label>

                  <label
                    v-for="v in block.values"
                    :key="v.value"
                    class="filter-checkbox"
                  >
                    <input
                      type="checkbox"
                      :value="v.value"
                      v-model="attributeModels[attr]"
                      @change="onAttrValueChange(attr)"
                    />

                    <span class="filter-option">
                      <span
                        v-if="block.ui_render === 'color'"
                        class="color-dot"
                        :class="{ empty: !v.meta?.color }"
                        :style="
                          v.meta?.color ? { background: v.meta.color } : {}
                        "
                      ></span>

                      <span class="filter-option-text">{{ v.value }}</span>
                    </span>
                  </label>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- ================= MOBILE FILTER BAR ================= -->
        <div v-if="isMobile && hasActiveCategory" class="mobile-filter-bar">
          <div class="mfb-left">
            <div class="mfb-title">Цена</div>
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
          </div>

          <button class="mobile-filter-btn" @click="showMobileFilters = true">
            <Fa :icon="['fas', 'filter']" /> Фильтры
          </button>
        </div>
      </div>

      <!-- ================= PRODUCTS ================= -->
      <div class="catalog-products">
        <div v-if="loading" class="catalog-loader">
          <div class="loader-spinner"></div>
          <div class="loader-text">Загрузка товаров…</div>
        </div>

        <template v-else>
          <div v-if="!hasActiveCategory && !searchQ" class="categories-landing">
            <HomeCatalogEntry
              :show-head="false"
              :items="topCats"
              :navigate-on-pick="false"
              @select-category="pickCategoryFromGrid"
            />
          </div>

          <div v-else class="products-grid">
            <article
              v-for="p in visibleProducts"
              :key="p.id"
              class="product-card"
            >
              <div
                class="product-image"
                @click.stop
                @pointerdown.stop
                @mousedown.stop
                @touchstart.stop
              >
                <ProductCardGallery
                  :images="p.images"
                  :alt="p.name"
                  :compact="isMobile"
                />
              </div>

              <div class="product-info">
                <div class="product-name">{{ p.name }}</div>

                <div class="product-row">
                  <div class="product-price">{{ p.price }} ₽</div>
                  <div class="product-qty">
                    Остаток: {{ p.quantity ?? "—" }}
                  </div>
                </div>

                <div class="product-meta">
                  <span v-if="p.barcode" class="product-chip product-barcode">{{
                    p.barcode
                  }}</span>
                  <span v-if="p.article" class="product-chip product-article"
                    >Арт: {{ p.article }}</span
                  >
                </div>

                <div class="product-actions">
                  <button
                    class="product-open"
                    type="button"
                    @click.stop="openProduct(p)"
                  >
                    Открыть
                  </button>
                </div>
              </div>
            </article>

            <div v-if="filteredProducts.length === 0" class="products-empty">
              <div class="empty-title">Товары не найдены</div>
              <div class="empty-text">
                Попробуйте изменить категорию, поиск или фильтры
              </div>
            </div>
          </div>

          <div
            v-if="(hasActiveCategory || searchQ) && canLoadMore"
            class="load-more"
          >
            <button class="load-more-btn" @click="loadMore">
              Показать ещё
            </button>
          </div>
        </template>
      </div>
    </section>

    <!-- ================= MOBILE FILTERS MODAL ================= -->
    <div
      v-if="showMobileFilters"
      class="moverlay-overlay"
      @click.self="
        showMobileFilters = false;
        mobileView = 'root';
        activeMobileAttr = null;
      "
    >
      <div class="moverlay-panel">
        <div class="moverlay-header">
          <button
            class="moverlay-btn moverlay-back"
            :class="{ ghost: mobileView === 'root' }"
            :disabled="mobileView === 'root'"
            @click="mobileView = 'root'"
            title="Назад"
          >
            <Fa :icon="['fas', 'arrow-left']" />
          </button>

          <span class="moverlay-title">
            {{
              mobileView === "root"
                ? "Фильтры"
                : mobileView === "type"
                ? "Тип товара"
                : mobileView === "brand"
                ? "Бренд"
                : mobileView === "photo"
                ? "Фото"
                : activeMobileAttr || "Фильтры"
            }}
          </span>

          <button
            class="moverlay-btn moverlay-close"
            @click="
              showMobileFilters = false;
              mobileView = 'root';
              activeMobileAttr = null;
            "
            title="Закрыть"
          >
            ✕
          </button>
        </div>

        <div class="moverlay-body">
          <!-- ROOT MENU -->
          <div v-if="mobileView === 'root'" class="mfil-list">
            <div
              v-if="isRootCategorySelected === true"
              class="mfil-item"
              @click="mobileView = 'type'"
            >
              Тип товара
              <Fa class="mfil-arrow" :icon="['fas', 'chevron-right']" />
            </div>

            <div class="mfil-item" @click="mobileView = 'brand'">
              Бренд <Fa class="mfil-arrow" :icon="['fas', 'chevron-right']" />
            </div>

            <div class="mfil-item" @click="mobileView = 'photo'">
              Фото <Fa class="mfil-arrow" :icon="['fas', 'chevron-right']" />
            </div>

            <template v-if="allowAttrFilters">
              <div
                v-for="(vals, attr) in attributeFilters"
                :key="attr"
                class="mfil-item"
                @click="
                  activeMobileAttr = attr;
                  mobileView = 'attr';
                "
              >
                {{ attr }}
                <Fa class="mfil-arrow" :icon="['fas', 'chevron-right']" />
              </div>
            </template>
          </div>

          <!-- MOBILE TYPE -->
          <div v-if="mobileView === 'type'" class="mfil-values">
            <label class="filter-checkbox filter-all">
              <input
                type="checkbox"
                :checked="!typeModel.length"
                @change="
                  typeModel = [];
                  applyFilters();
                "
              />
              <span>Все</span>
            </label>

            <label
              v-for="c in typeOptions"
              :key="c.id"
              class="filter-checkbox typeopt"
              :class="{ parent: c.hasChildren }"
              :style="{ '--indent': (c.depth - 1) * 14 + 'px' }"
            >
              <input
                type="checkbox"
                :value="String(c.code)"
                v-model="typeModel"
                @change="applyFilters"
              />

              <span class="typeopt-text">
                <span class="typeopt-name">{{ c.name }}</span>
              </span>
            </label>
          </div>

          <!-- MOBILE BRAND -->
          <div v-if="mobileView === 'brand'" class="mfil-values">
            <label class="filter-checkbox filter-all">
              <input
                type="checkbox"
                :checked="!brandModel.length"
                @change="
                  brandModel = [];
                  applyFilters();
                "
              />
              <span>Все</span>
            </label>

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

          <!-- MOBILE PHOTO -->
          <div v-if="mobileView === 'photo'" class="mfil-values">
            <label class="filter-checkbox">
              <input
                type="radio"
                value="all"
                v-model="photoModel"
                @change="applyFilters"
              />
              <span>Все</span>
            </label>

            <label class="filter-checkbox">
              <input
                type="radio"
                value="with"
                v-model="photoModel"
                @change="applyFilters"
              />
              <span>С фото</span>
            </label>

            <label class="filter-checkbox">
              <input
                type="radio"
                value="without"
                v-model="photoModel"
                @change="applyFilters"
              />
              <span>Без фото</span>
            </label>
          </div>

          <!-- MOBILE ATTR -->
          <div v-if="mobileView === 'attr'" class="mfil-values">
            <label class="filter-checkbox filter-all">
              <input
                type="checkbox"
                :checked="isAttrAllSelected(activeMobileAttr)"
                @change="selectAttrAll(activeMobileAttr)"
              />
              <span>Все</span>
            </label>

            <label
              v-for="v in attributeFilters[activeMobileAttr]?.values || []"
              :key="v.value"
              class="filter-checkbox"
            >
              <input
                type="checkbox"
                :value="v.value"
                v-model="attributeModels[activeMobileAttr]"
                @change="applyFilters"
              />

              <span class="filter-option">
                <span
                  v-if="
                    attributeFilters[activeMobileAttr]?.ui_render === 'color'
                  "
                  class="color-dot"
                  :class="{ empty: !v.meta?.color }"
                  :style="v.meta?.color ? { background: v.meta.color } : {}"
                ></span>

                <span class="filter-option-text">{{ v.value }}</span>
              </span>
            </label>
          </div>
        </div>

        <div class="moverlay-footer">
          <button
            class="moverlay-main"
            @click="
              showMobileFilters = false;
              mobileView = 'root';
              activeMobileAttr = null;
            "
          >
            Готово
          </button>

          <button
            class="moverlay-ghost"
            @click="resetAllFilters"
            title="Сбросить все фильтры"
          >
            Сбросить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from "vue";
import { useHead } from "@vueuse/head";
import { useRoute, useRouter } from "vue-router";
import ProductCardGallery from "@/components/ProductCardGallery.vue";
import HomeCatalogEntry from "@/components/HomeCatalogEntry.vue";
import HomeSearch from "@/components/HomeSearch.vue";

const route = useRoute();
const router = useRouter();

/* ================= helpers ================= */
const normalize = (s) =>
  String(s || "")
    .toLowerCase()
    .replace(/ё/g, "е")
    .replace(/[^\p{L}\p{N}]+/gu, " ")
    .replace(/\s+/g, " ")
    .trim();

const toArr = (v) =>
  v == null ? [] : Array.isArray(v) ? v.map(String) : [String(v)];

const hasImages = (p) =>
  Array.isArray(p.images) && p.images.filter(Boolean).length > 0;

function getCatCodeOfProduct(p) {
  const v = p?.category_code ?? p?.categoryCode ?? p?.category ?? "";
  return String(v || "");
}
// ✅ проверка "в этой ветке категорий", а не просто startsWith
const inTree = (cc, code) => {
  cc = String(cc || "");
  code = String(code || "");
  return cc === code || cc.startsWith(code + ".");
};

/* ================= STATE ================= */
const products = ref([]);
const categories = ref([]);
const catsLoadedOnce = ref(false);
const catsLoading = ref(false);

const searchHits = ref([]);

/* ================= URL SOURCE OF TRUTH ================= */
// cat в URL теперь SLUG (или старый code для совместимости)
const currentCategoryParam = computed(() => {
  const v = route.query.cat;
  return v ? String(Array.isArray(v) ? v[0] : v) : null; // slug или "1.2"
});

// внутри страницы используем CODE (для фильтрации товаров по category_code)
const currentCategory = computed(() => {
  const raw = currentCategoryParam.value;
  if (!raw) return null;

  // старые ссылки: cat=1 или cat=1.2
  if (/^[0-9.]+$/.test(raw)) return raw;

  // новые ссылки: cat=santehnika...
  const found = categories.value.find((c) => c.slug && String(c.slug) === raw);
  return found ? String(found.code) : null;
});

// активность категории = есть cat в URL (даже если это slug и code ещё не вычислился)
const hasActiveCategory = computed(() => !!currentCategoryParam.value);

const searchQ = computed(() => {
  const v = route.query.q;
  return v ? String(Array.isArray(v) ? v[0] : v) : "";
});

const currentCategoryName = computed(() => {
  if (!currentCategory.value) return null;
  const found = categories.value.find(
    (c) => String(c.code) === String(currentCategory.value)
  );
  return found ? found.name : null;
});
const SITE = "XOZMAG.RU";

const headTitle = computed(() => {
  const q = String(searchQ.value || "").trim();
  const catName = currentCategoryName.value;

  if (!hasActiveCategory.value && !q) return `Каталог товаров — ${SITE}`;
  if (!hasActiveCategory.value && q) return `Поиск: ${q} — ${SITE}`;
  if (hasActiveCategory.value && q && catName)
    return `${catName}: поиск “${q}” — ${SITE}`;
  if (hasActiveCategory.value && catName) return `${catName} купить — ${SITE}`;

  return `Каталог — ${SITE}`;
});

const headDesc = computed(() => {
  const q = String(searchQ.value || "").trim();
  const catName = currentCategoryName.value;

  if (!hasActiveCategory.value && !q) {
    return "Каталог хозтоваров и товаров для дома: выберите категорию и найдите нужные товары по цене, бренду и наличию.";
  }
  if (!hasActiveCategory.value && q) {
    return `Результаты поиска по запросу “${q}”. Фильтры по бренду, цене и наличию.`;
  }
  if (hasActiveCategory.value && q && catName) {
    return `Результаты поиска “${q}” в категории “${catName}”. Фильтры по цене, бренду и наличию.`;
  }
  if (hasActiveCategory.value && catName) {
    return `Категория “${catName}”: цены, наличие и фильтры по бренду, цене и характеристикам.`;
  }

  return "Каталог товаров для дома и хозтоваров.";
});

useHead(() => ({
  title: headTitle.value,
  meta: [
    { name: "description", content: headDesc.value },
    { property: "og:title", content: headTitle.value },
    { property: "og:description", content: headDesc.value },
    { property: "og:type", content: "website" },
    { property: "og:url", content: `https://xozmag.ru${route.fullPath}` },
  ],
}));

/* ===== photo filter ===== */
const photoModel = ref(
  route.query.photo
    ? String(
        Array.isArray(route.query.photo)
          ? route.query.photo[0]
          : route.query.photo
      )
    : "all"
);

const photoHeadText = computed(() => {
  if (photoModel.value === "with") return "С фото";
  if (photoModel.value === "without") return "Без фото";
  return "Все";
});

/* ================= FILTER MODELS ================= */
const brandModel = ref([]);
const priceFromModel = ref(
  route.query.price_from ? Number(route.query.price_from) : null
);
const priceToModel = ref(
  route.query.price_to ? Number(route.query.price_to) : null
);
const attributeModels = ref({});

/* ✅ TYPE MODEL (для root категорий) */
const typeModel = ref([]);

/* ================= DATA LOAD (PRODUCTS ONLY WHEN CATEGORY) ================= */
const productsLoading = ref(false);
const productsLoaded = ref(false);
let productsPromise = null;

async function ensureProductsOnlyWhenCategory() {
  if (productsLoaded.value) return;
  if (productsPromise) return productsPromise;

  productsLoading.value = true;

  productsPromise = fetch("/api/admin/product/get_products.php")
    .then((r) => r.json())
    .then((baseProducts) => {
      const list = Array.isArray(baseProducts)
        ? baseProducts
        : baseProducts.products || [];

      products.value = (list || []).filter(Boolean).map((p) => {
        let images = [];

        if (Array.isArray(p.images)) {
          images = p.images.filter(Boolean);
        } else {
          const ph = p.photo ?? "";
          if (Array.isArray(ph)) images = ph.filter(Boolean);
          else if (typeof ph === "string" && ph.trim()) {
            try {
              const arr = JSON.parse(ph);
              if (Array.isArray(arr)) images = arr.filter(Boolean);
            } catch {
              if (ph.startsWith("/photo_product_vitrina/")) images = [ph];
            }
          }
        }

        return {
          ...p,
          images,
          _search: normalize(
            `${p.name || ""} ${p.brand || ""} ${p.article || ""} ${
              p.barcode || ""
            }`
          ),
        };
      });

      productsLoaded.value = true;
    })
    .catch(() => {
      products.value = [];
    })
    .finally(() => {
      productsLoading.value = false;
      productsPromise = null;
    });

  return productsPromise;
}

watch(
  hasActiveCategory,
  (hasCat) => {
    if (hasCat) ensureProductsOnlyWhenCategory();
  },
  { immediate: true }
);

const loading = computed(
  () => catsLoading.value || (hasActiveCategory.value && productsLoading.value)
);

/* ================= GET CATEGORIES FROM HomeSearch ================= */
function onCategoriesLoaded(list) {
  const arr = Array.isArray(list) ? list : [];
  categories.value = arr.map((c) => ({
    ...c,
    parent: c.parent ?? c.parent_id ?? null, // важно для дерева
    slug: c.slug ?? null, // важно для URL
  }));
  catsLoadedOnce.value = true;
}
watch(
  () => [catsLoadedOnce.value, currentCategoryParam.value, route.query.type],
  () => {
    if (!catsLoadedOnce.value) return;

    const nextQuery = { ...route.query };
    let changed = false;

    // cat: code -> slug (у тебя уже есть)
    const rawCat = currentCategoryParam.value;
    if (rawCat && /^[0-9.]+$/.test(rawCat)) {
      const f = categories.value.find((c) => String(c.code) === String(rawCat));
      if (f?.slug && f.slug !== rawCat) {
        nextQuery.cat = f.slug;
        changed = true;
      }
    }

    // type: code -> slug (добавляем)
    const t = toArr(route.query.type);
    if (t.length) {
      const mapped = t.map((x) => {
        const s = String(x);
        if (!/^[0-9.]+$/.test(s)) return s;
        const f = categories.value.find((c) => String(c.code) === s);
        return f?.slug || s;
      });

      if (mapped.join("|") !== t.map(String).join("|")) {
        nextQuery.type = mapped;
        changed = true;
      }
    }

    if (changed) router.replace({ query: nextQuery });
  },
  { immediate: true }
);


/* ================= TREE FROM FLAT ================= */
const treeData = computed(() => {
  const byId = new Map();
  categories.value.forEach((c) =>
    byId.set(String(c.id), { ...c, children: [] })
  );

  const roots = [];
  categories.value.forEach((c) => {
    const n = byId.get(String(c.id));
    if (!n) return;
    if (!c.parent) roots.push(n);
    else byId.get(String(c.parent))?.children.push(n);
  });

  const sortNode = (n) => {
    n.children.sort((a, b) =>
      a.name.localeCompare(b.name, "ru", { sensitivity: "base" })
    );
    n.children.forEach(sortNode);
  };

  roots.sort((a, b) =>
    a.name.localeCompare(b.name, "ru", { sensitivity: "base" })
  );
  roots.forEach(sortNode);

  const byCode = new Map();
  for (const n of byId.values()) byCode.set(String(n.code), n);

  return { roots, byId, byCode };
});

const topCats = computed(() => treeData.value.roots);

const selectedCatNode = computed(() => {
  if (!currentCategory.value) return null;
  return treeData.value.byCode.get(String(currentCategory.value)) || null;
});

/* ✅ root? (null пока категории не загрузились — чтобы не мигало) */
const isRootCategorySelected = computed(() => {
  if (!hasActiveCategory.value) return false;
  if (!catsLoadedOnce.value) return null;
  const n = selectedCatNode.value;
  if (!n) return false;
  return !n.parent;
});

/* ✅ атрибуты: только если НЕ root или root + выбран хотя бы 1 type */
const allowAttrFilters = computed(() => {
  if (!hasActiveCategory.value) return false;
  if (isRootCategorySelected.value === null) return false;
  return isRootCategorySelected.value === false || typeModel.value.length > 0;
});

/* ✅ TYPE options = ВСЕ потомки root категории (1.1, 1.1.1, ...) */
const typeOptions = computed(() => {
  if (isRootCategorySelected.value !== true) return [];
  const root = selectedCatNode.value;
  if (!root) return [];

  const res = [];
  const walk = (node, depth) => {
    res.push({
      id: node.id,
      code: node.code,
      name: node.name,
      depth,
      hasChildren: (node.children || []).length > 0,
    });
    (node.children || []).forEach((ch) => walk(ch, depth + 1));
  };

  (root.children || []).forEach((ch) => walk(ch, 1));
  return res;
});

const typeCodeToName = computed(() => {
  const m = new Map();
  (typeOptions.value || []).forEach((c) => m.set(String(c.code), c.name));
  return m;
});

const typeHeadText = computed(() => {
  if (!typeModel.value.length) return "Все";
  const names = typeModel.value.map(
    (code) => typeCodeToName.value.get(String(code)) || String(code)
  );
  if (names.length <= 2) return names.join(" · ");
  return `Выбрано: ${names.length}`;
});

/* ================= MOBILE detect ================= */
const isMobile = ref(false);
const handleResize = () => (isMobile.value = window.innerWidth < 1024);

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize, { passive: true });
});
onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
  unlockBody();
});

/* ================= CATEGORY GRID PICK ================= */
function pickCategoryFromGrid(cat) {
  const code = String(cat?.code || "");
  if (!code) return;

  const slug = String(cat?.slug || "");
  if (slug) router.push({ path: "/catalog", query: { cat: slug } });
  else router.push({ path: "/catalog", query: { cat: code } }); // fallback
}

/* ================= PRODUCTS SCOPE ================= */
const productsInCurrentCat = computed(() => {
  if (!currentCategory.value) return [];
  const pref = String(currentCategory.value);
  return products.value.filter((p) => inTree(getCatCodeOfProduct(p), pref));
});

const productsInTypeScope = computed(() => {
  let list = productsInCurrentCat.value;

  if (isRootCategorySelected.value === true && typeModel.value.length) {
    const sel = typeModel.value.map(String);
    list = list.filter((p) => {
      const cc = getCatCodeOfProduct(p);
      return sel.some((code) => inTree(cc, code));
    });
  }

  return list;
});

/* ================= BRANDS / ATTRS ================= */
const brands = computed(() => {
  const set = new Set();
  productsInTypeScope.value.forEach((p) => p.brand && set.add(p.brand));
  return Array.from(set).sort((a, b) =>
    a.localeCompare(b, "ru", { sensitivity: "base" })
  );
});

const attributeFilters = computed(() => {
  if (!allowAttrFilters.value) return {};

  const temp = {};
  productsInTypeScope.value.forEach((p) => {
    (p.attributes || []).forEach((a) => {
      if (!a?.name || !a?.value) return;

      if (!temp[a.name])
        temp[a.name] = { ui_render: a.ui_render || "text", map: new Map() };
      if (a.ui_render === "color") temp[a.name].ui_render = "color";

      let metaObj = a.meta ?? null;
      if (typeof metaObj === "string") {
        try {
          metaObj = JSON.parse(metaObj);
        } catch {
          metaObj = null;
        }
      }

      const existed = temp[a.name].map.get(a.value);
      if (!existed)
        temp[a.name].map.set(a.value, { value: a.value, meta: metaObj });
      else if (!existed.meta?.color && metaObj?.color) existed.meta = metaObj;
    });
  });

  const res = {};
  for (const k in temp) {
    res[k] = {
      ui_render: temp[k].ui_render,
      values: Array.from(temp[k].map.values()).sort((x, y) =>
        String(x.value).localeCompare(String(y.value), "ru", {
          sensitivity: "base",
        })
      ),
    };
  }
  return res;
});

/* ================= UI open/close ================= */
const openFilters = ref({ type: false, brand: false, photo: false });

watch(
  attributeFilters,
  () => {
    const nextAttrs = { ...attributeModels.value };
    Object.keys(attributeFilters.value).forEach((k) => {
      if (!Array.isArray(nextAttrs[k])) nextAttrs[k] = [];
    });
    attributeModels.value = nextAttrs;

    const nextOpen = {
      type: openFilters.value.type ?? false,
      brand: openFilters.value.brand ?? false,
      photo: openFilters.value.photo ?? false,
    };
    Object.keys(attributeFilters.value).forEach(
      (k) => (nextOpen[k] = openFilters.value[k] ?? false)
    );
    openFilters.value = nextOpen;
  },
  { immediate: true }
);

function toggleFilter(key) {
  const next = {};
  Object.keys(openFilters.value).forEach((k) => (next[k] = false));
  if (!openFilters.value[key]) next[key] = true;
  openFilters.value = next;
}

/* ================= attrs helpers ================= */
function attributeHeadText(attr) {
  const selected = attributeModels.value[attr] || [];
  if (!selected.length) return "Все";
  if (selected.length <= 2) return selected.join(" · ");
  return `Выбрано: ${selected.length}`;
}
function isAttrAllSelected(attr) {
  if (!attr) return true;
  return !attributeModels.value[attr]?.length;
}
function selectAttrAll(attr) {
  if (!attr) return;
  attributeModels.value[attr] = [];
  applyFilters();
}
function onAttrValueChange() {
  applyFilters();
}

/* ================= APPLY FILTERS (router.replace) ================= */
const syncingFromRoute = ref(false);

function applyFilters() {
  if (syncingFromRoute.value) return;

  const qRaw = String(searchQ.value || "").trim();

  if (!hasActiveCategory.value) {
    router.replace({ query: { q: qRaw || undefined } });
    return;
  }

  const query = {
    cat: (() => {
      const raw = currentCategoryParam.value;
      if (!raw) return undefined;

      // если уже slug — оставляем
      if (!/^[0-9.]+$/.test(raw)) return raw;

      // если code — пытаемся заменить на slug
      const found = categories.value.find(
        (c) => String(c.code) === String(raw)
      );
      return found?.slug || raw;
    })(),
    q: qRaw || undefined,

    // ✅ type только на root
type:
  isRootCategorySelected.value === true && typeModel.value.length
    ? typeModel.value.map((code) => {
        const found = categories.value.find(
          (c) => String(c.code) === String(code)
        );
        return found?.slug || String(code); // fallback если slug нет
      })
    : undefined,


    // ✅ brand всегда
    brand: brandModel.value.length ? brandModel.value : undefined,

    // ✅ цена всегда
    price_from:
      priceFromModel.value !== null ? priceFromModel.value : undefined,
    price_to: priceToModel.value !== null ? priceToModel.value : undefined,

    // ✅ photo всегда (НЕ зависит от типа)
    photo: photoModel.value !== "all" ? photoModel.value : undefined,
  };

  // ✅ attrs только если разрешены
  if (allowAttrFilters.value) {
    for (const [k, v] of Object.entries(attributeModels.value)) {
      if (Array.isArray(v) && v.length) query[`attr_${k}`] = v;
    }
  }

  router.replace({ query });
}

/* ================= URL → MODELS ================= */
watch(
  () => route.query,
  (q) => {
    syncingFromRoute.value = true;

typeModel.value = toArr(q.type)
  .map((v) => {
    const s = String(v || "");

    // старые ссылки: type=1.2
    if (/^[0-9.]+$/.test(s)) return s;

    // новые ссылки: type=slug
    const found = categories.value.find((c) => String(c.slug) === s);
    return found ? String(found.code) : null;
  })
  .filter(Boolean);
    brandModel.value = toArr(q.brand);

    priceFromModel.value =
      q.price_from != null && q.price_from !== ""
        ? Number(Array.isArray(q.price_from) ? q.price_from[0] : q.price_from)
        : null;

    priceToModel.value =
      q.price_to != null && q.price_to !== ""
        ? Number(Array.isArray(q.price_to) ? q.price_to[0] : q.price_to)
        : null;

    const nextAttrs = { ...attributeModels.value };
    Object.keys(q).forEach((key) => {
      if (!key.startsWith("attr_")) return;
      const name = key.slice(5);
      nextAttrs[name] = toArr(q[key]);
    });
    attributeModels.value = nextAttrs;

    photoModel.value = q.photo
      ? String(Array.isArray(q.photo) ? q.photo[0] : q.photo)
      : "all";

    syncingFromRoute.value = false;
  },
  { immediate: true }
);

/* если ушли с root на подкатегорию — typeModel очищаем и выкидываем из URL */
watch(isRootCategorySelected, (isRoot) => {
  if (isRoot === false && typeModel.value.length) {
    typeModel.value = [];
    applyFilters();
  }
});

/* если запретили attrs — сбрасываем ТОЛЬКО атрибуты (фото НЕ трогаем) */
watch(allowAttrFilters, (ok, prev) => {
  if (prev && !ok) {
    let changed = false;

    const next = { ...attributeModels.value };
    Object.keys(next).forEach((k) => {
      if (Array.isArray(next[k]) && next[k].length) {
        next[k] = [];
        changed = true;
      }
    });
    attributeModels.value = next;

    if (changed) applyFilters();
  }
});

/* при смене категории — сбрасываем фильтры (поиск остаётся в URL) */
watch(currentCategoryParam, () => {
  typeModel.value = [];
  brandModel.value = [];
  priceFromModel.value = null;
  priceToModel.value = null;
  photoModel.value = "all";

  const next = { ...attributeModels.value };
  Object.keys(next).forEach((k) => (next[k] = []));
  attributeModels.value = next;

  applyFilters();
});

const productsById = computed(() => {
  const m = new Map();
  products.value.forEach((p) => m.set(String(p.id), p));
  return m;
});

const mergedSearchProducts = computed(() => {
  if (!searchHits.value.length) return [];

  const map = productsById.value;

  return searchHits.value
    .map((hit) => {
      const full = map.get(String(hit.id));

      const hitImages = Array.isArray(hit.images)
        ? hit.images.filter(Boolean)
        : [];
      const hitThumb = hit.thumb ? [hit.thumb] : [];
      const fromHit = hitImages.length ? hitImages : hitThumb;

      if (full) {
        const images = hasImages(full) ? full.images : fromHit;
        return {
          ...full,
          name: full.name ?? hit.name,
          brand: full.brand ?? hit.brand,
          price: full.price ?? hit.price,
          barcode: full.barcode ?? hit.barcode,
          images,
        };
      }

      return {
        id: hit.id,
        name: hit.name,
        price: hit.price,
        brand: hit.brand,
        barcode: hit.barcode,
        images: fromHit,
        attributes: [],
        category_code: "",
        _search: normalize(
          `${hit.name || ""} ${hit.brand || ""} ${hit.barcode || ""}`
        ),
      };
    })
    .filter((p) => p?.id != null && p?.name);
});

/* ================= FILTERED PRODUCTS ================= */
const filteredProducts = computed(() => {
  let list = [];

  const qRaw = String(searchQ.value || "").trim();
  const hasQ = !!qRaw;

  if (hasQ) list = mergedSearchProducts.value;
  else if (hasActiveCategory.value) list = productsInCurrentCat.value;
  else list = [];

  // ✅ если есть cat — ограничиваем в пределах выбранной категории
  if (hasActiveCategory.value) {
    const pref = String(currentCategory.value);
    list = list.filter((p) => inTree(getCatCodeOfProduct(p), pref));
  }

  // ✅ TYPE (root)
  if (isRootCategorySelected.value === true && typeModel.value.length) {
    const sel = typeModel.value.map(String);
    list = list.filter((p) => {
      const cc = getCatCodeOfProduct(p);
      return sel.some((code) => inTree(cc, code));
    });
  }

  // ✅ BRAND (всегда)
  if (brandModel.value.length) {
    list = list.filter((p) => brandModel.value.includes(p.brand));
  }

  // ✅ цена всегда
  if (priceFromModel.value !== null)
    list = list.filter((p) => Number(p.price) >= priceFromModel.value);
  if (priceToModel.value !== null)
    list = list.filter((p) => Number(p.price) <= priceToModel.value);

  // ✅ фото — ВСЕГДА (НЕ зависит от типа)
  if (photoModel.value === "with") list = list.filter((p) => hasImages(p));
  else if (photoModel.value === "without")
    list = list.filter((p) => !hasImages(p));

  // ✅ атрибуты — только когда разрешено
  if (allowAttrFilters.value) {
    for (const [k, arr] of Object.entries(attributeModels.value)) {
      if (!Array.isArray(arr) || !arr.length) continue;
      list = list.filter((p) =>
        p.attributes?.some((a) => a.name === k && arr.includes(a.value))
      );
    }
  }

  return list;
});

/* ================= pagination ================= */
const step = computed(() => (isMobile.value ? 10 : 24));
const displayLimit = ref(step.value);

watch(
  () => [isMobile.value, currentCategory.value, route.fullPath],
  () => {
    displayLimit.value = step.value;
  }
);

const visibleProducts = computed(() =>
  filteredProducts.value.slice(0, displayLimit.value)
);
const canLoadMore = computed(
  () => filteredProducts.value.length > displayLimit.value
);

function loadMore() {
  displayLimit.value += step.value;
}

/* ================= mobile modal helpers ================= */
const showMobileFilters = ref(false);
const mobileView = ref("root");
const activeMobileAttr = ref(null);

function resetAllFilters() {
  typeModel.value = [];
  brandModel.value = [];
  priceFromModel.value = null;
  priceToModel.value = null;
  photoModel.value = "all";

  const next = { ...attributeModels.value };
  Object.keys(next).forEach((k) => (next[k] = []));
  attributeModels.value = next;

  openFilters.value = { type: false, brand: false, photo: false };
  showMobileFilters.value = false;
  mobileView.value = "root";
  activeMobileAttr.value = null;

  applyFilters();
}

/* ================= navigation ================= */
function openProduct(p) {
  router.push({ name: "product", params: { slug: p.slug || String(p.id) } });

}

/* ================= BODY LOCK (only filters modal) ================= */
let savedScrollY = 0;

function lockBody() {
  savedScrollY = window.scrollY || 0;
  document.body.style.position = "fixed";
  document.body.style.top = `-${savedScrollY}px`;
  document.body.style.left = "0";
  document.body.style.right = "0";
  document.body.style.width = "100%";
  document.body.style.overflow = "hidden";
  document.body.style.touchAction = "none";
}
function unlockBody() {
  document.body.style.position = "";
  document.body.style.top = "";
  document.body.style.left = "";
  document.body.style.right = "";
  document.body.style.width = "";
  document.body.style.overflow = "";
  document.body.style.touchAction = "";
  window.scrollTo(0, savedScrollY);
}

watch(showMobileFilters, (open) => {
  if (open) lockBody();
  else unlockBody();
});
</script>

<style scoped>
/* ====== shared small ui ====== */
.color-dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  border: 1px solid #d1d5db;
  display: inline-block;
  flex: 0 0 12px;
}
.color-dot.empty {
  background: repeating-linear-gradient(
    45deg,
    #f3f4f6,
    #f3f4f6 3px,
    #e5e7eb 3px,
    #e5e7eb 6px
  );
}
.filter-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.dd-empty {
  padding: 10px 10px;
  font-size: 12px;
  font-weight: 850;
  color: var(--text-muted);
}

/* ========================= PAGE ========================= */
.catalog-page {
  display: flex;
  min-height: 100vh;
  background: var(--bg-main);
  color: var(--text-main);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

/* ========================= MAIN ========================= */
.catalog-content {
  flex: 1;
  padding: 26px 30px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.catalog-top {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ========================= SEARCH WRAPPER (без input стилей!) ========================= */
.catalog-search {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
}

.search-meta {
  font-size: 12px;
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* ========================= HEADING ========================= */
.catalog-heading {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.breadcrumbs {
  font-size: 13px;
  color: var(--text-muted);
}
.breadcrumb-separator {
  margin: 0 6px;
}
.breadcrumb-current {
  color: var(--text-main);
  font-weight: 600;
}
.catalog-title {
  font-size: 28px;
  font-weight: 900;
  color: var(--text-main);
}

/* ========================= FILTERS DESKTOP ========================= */
.filters-bar {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 18px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
  box-shadow: var(--shadow-sm);
}

.filter-price {
  grid-column: span 2;
}

.filter-block {
  min-width: 180px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  background: #fff;
  border-radius: 14px;
  padding: 10px 12px;
  border: 1px solid #e4e7ef;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
  position: relative;
  z-index: 1;
}

.filter-type {
  grid-column: span 2;
  min-width: 320px;
}
.filter-block:hover {
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}
.filter-block.open {
  z-index: 200;
}

.filter-label {
  font-size: 11px;
  font-weight: 800;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding-left: 6px;
}

.filter-head-text {
  font-size: 14px;
  color: #1b1e28;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.price-inputs {
  display: flex;
  gap: 8px;
}
.price-inputs input {
  padding: 11px 14px;
  border-radius: 12px;
  border: 1px solid #e4e7ef;
  width: 50%;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.price-inputs input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(4, 0, 255, 0.14);
}

.filter-dropdown {
  position: relative;
  border-radius: 12px;
  background: linear-gradient(180deg, #fff, #f9faff);
  border: 1px solid #e4e7ef;
}

.filter-dropdown-head {
  padding: 12px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: 650;
  color: #1b1e28;
  transition: background 0.2s ease;
}
.filter-dropdown-head:hover {
  background: rgba(4, 0, 255, 0.05);
}

.filter-dropdown-body {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  z-index: 210;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e4e7ef;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.14);
}
.filter-dropdown-body.scrollable {
  max-height: min(260px, 50vh);
  overflow-y: auto;
  padding-right: 6px;
}

.filter-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #1b1e28;
  padding: 6px 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.filter-checkbox:hover {
  background: rgba(4, 0, 255, 0.06);
}
.filter-checkbox input {
  accent-color: var(--accent);
  cursor: pointer;
  margin-top: 2px;
}
.typeopt-text {
  position: relative;
  display: flex;
  align-items: center;

  /* КЛЮЧ: чтобы текст не пропадал на мобиле */
  flex: 1;
  min-width: 0;

  /* отступ по уровню дерева */
  padding-left: calc(var(--indent) + 14px);
}
.typeopt-text::before {
  content: "";
  position: absolute;
  left: var(--indent);
  top: 50%;
  transform: translateY(-50%);
  width: 10px;
  height: 1px;
  background: rgba(15, 23, 42, 0.18);
}
.typeopt-text::after {
  content: "";
  position: absolute;
  left: var(--indent);
  top: -10px;
  bottom: -10px;
  width: 1px;
  background: rgba(15, 23, 42, 0.12);
}
.typeopt-mark {
  opacity: 0.55;
  font-size: 12px;
  flex: 0 0 auto;
}

.typeopt-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.typeopt.parent .typeopt-name {
  font-weight: 900;
}

.filter-all {
  font-weight: 800;
  padding-bottom: 8px;
  margin-bottom: 8px;
  border-bottom: 1px dashed #e4e7ef;
}

.arrow {
  font-size: 12px;
  color: var(--accent);
  transition: transform 0.25s ease;
}
.arrow.open {
  transform: rotate(180deg);
}

/* ========================= MOBILE FILTER BAR ========================= */
.mobile-filter-bar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}
.mfb-left {
  flex: 1;
  min-width: 0;
}
.mfb-title {
  font-size: 12px;
  font-weight: 900;
  color: var(--text-muted);
  margin-bottom: 6px;
}
.mobile-price {
  display: flex;
  gap: 8px;
}
.mobile-price input {
  width: 50%;
  padding: 11px 12px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  box-shadow: var(--shadow-sm);
  font-size: 14px;
}
.mobile-price input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(4, 0, 255, 0.12);
}

.mobile-filter-btn {
  flex: 0 0 auto;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
  padding: 12px 14px;
  border-radius: 999px;
  cursor: pointer;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.mobile-filter-btn:active {
  transform: scale(0.99);
}

/* ========================= PRODUCTS ========================= */
.catalog-products {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 18px;
}

.product-card {
  min-width: 0;
  background: var(--bg-panel);
  border-radius: 16px;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: default;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.product-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

.product-image {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--border-soft);
  background: #fff;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 0;
}

.product-image :deep(.pg),
.product-image :deep(.pcg),
.product-image :deep(.swiper),
.product-image :deep(.swiper-wrapper),
.product-image :deep(.swiper-slide) {
  width: 100% !important;
  height: 100% !important;
}
.product-image :deep(.swiper-slide) {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}
.product-image :deep(img) {
  width: 100% !important;
  height: 100% !important;
  max-width: 100% !important;
  max-height: 100% !important;
  object-fit: contain !important;
  object-position: center !important;
  display: block !important;
}

.product-card :deep(.swiper-button-next),
.product-card :deep(.swiper-button-prev) {
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.product-card:hover :deep(.swiper-button-next),
.product-card:hover :deep(.swiper-button-prev) {
  opacity: 1;
  pointer-events: auto;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: default;
  flex: 1;
}

.product-name {
  font-size: 14px;
  line-height: 1.35;
  font-weight: 650;
  color: var(--text-main);
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.4em;
}

.product-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
}

.product-price {
  font-size: 18px;
  font-weight: 800;
  color: var(--accent);
  letter-spacing: -0.01em;
}

.product-qty {
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
}

.product-meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
}

.product-chip {
  font-size: 12px;
  color: var(--text-muted);
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 6px 10px;
  border-radius: 999px;
}

.product-barcode {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
}

.product-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: auto;
}

.product-open {
  border: 1px solid rgba(4, 0, 255, 0.22);
  background: rgba(4, 0, 255, 0.08);
  color: var(--accent);
  padding: 10px 14px;
  border-radius: 999px;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.product-open:hover {
  background: rgba(4, 0, 255, 0.12);
  box-shadow: var(--shadow-sm);
}

.product-open:active {
  transform: scale(0.99);
}

/* EMPTY / LOADER / LOADMORE */
.products-empty {
  background: var(--bg-panel);
  border: 1px dashed #cbd5e1;
  border-radius: var(--radius-lg);
  padding: 40px;
  text-align: center;
}
.empty-title {
  font-size: 18px;
  font-weight: 850;
  margin-bottom: 6px;
}
.empty-text {
  font-size: 14px;
  color: var(--text-muted);
}

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

.load-more {
  margin-top: 18px;
  display: flex;
  justify-content: center;
}
.load-more-btn {
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
  padding: 12px 16px;
  border-radius: 999px;
  cursor: pointer;
  font-weight: 900;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.load-more-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

/* ========================= MOBILE OVERLAY (filters) ========================= */
.moverlay-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.38);
  z-index: 400;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 12px;
}

.moverlay-panel {
  width: min(520px, 100%);
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid var(--border-soft);
  border-radius: 18px;
  box-shadow: 0 28px 80px rgba(0, 0, 0, 0.22);
  overflow: hidden;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.moverlay-header {
  display: grid;
  grid-template-columns: 46px 1fr 46px;
  align-items: center;
  gap: 8px;
  padding: 12px 12px;
  border-bottom: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.92);
}

.moverlay-title {
  text-align: center;
  font-weight: 900;
  font-size: 14px;
  color: var(--text-main);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.moverlay-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}
.moverlay-btn.ghost {
  opacity: 0.35;
}

.moverlay-body {
  max-height: min(64vh, 520px);
  overflow: auto;
  padding: 12px;
}

.moverlay-footer {
  display: flex;
  gap: 10px;
  padding: 12px;
  border-top: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.92);
}

.moverlay-main {
  flex: 1;
  border: none;
  border-radius: 14px;
  padding: 12px 14px;
  background: var(--accent);
  color: #fff;
  font-weight: 900;
  cursor: pointer;
}
.moverlay-ghost {
  flex: 0 0 auto;
  border: 1px solid var(--border-soft);
  background: #fff;
  border-radius: 14px;
  padding: 12px 14px;
  font-weight: 900;
  cursor: pointer;
}

.mfil-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.mfil-item {
  border: 1px solid var(--border-soft);
  background: #fff;
  border-radius: 14px;
  padding: 12px 12px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}
.mfil-arrow {
  opacity: 0.45;
}
.mfil-values {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.categories-landing {
  width: 100%;
  display: flex;
  justify-content: center;
}
.categories-landing :deep(.home-entry) {
  width: min(1120px, 100%);
  padding: 0;
  margin: 0;
}

/* ================= MOBILE ================= */
@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }
}
@media (max-width: 768px) {
  .catalog-content {
    padding: 16px;
    padding-bottom: 90px;
  }
  .products-grid {
    grid-template-columns: 1fr;
  }
  .catalog-title {
    font-size: 24px;
  }
  .product-price {
    font-size: 17px;
  }
  .typeopt-name {
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
    overflow-wrap: anywhere;
  }
}

/* disable selection только на элементах фильтра (desktop) */
.filters-bar,
.filter-block,
.filter-label,
.filter-dropdown,
.filter-dropdown-head,
.filter-dropdown-body,
.filter-checkbox,
.filter-checkbox span,
.filter-head-text,
.arrow {
  user-select: none;
  -webkit-user-select: none;
  -ms-user-select: none;
}
input,
input[type="checkbox"],
input[type="number"] {
  user-select: auto;
}
</style>
