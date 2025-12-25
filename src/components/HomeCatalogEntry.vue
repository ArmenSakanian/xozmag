<template>
  <section class="home-entry">
    <!-- SEARCH -->
    <div class="search-wrap">
<div v-if="showSearch" class="search-box">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>

        <input
          v-model="q"
          class="search-input"
          type="text"
          placeholder="Поиск товара по названию / бренду / штрихкоду…"
          @input="onInput"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @keydown.enter.prevent="enterPick"
          @focus="q && (open = true)"
          @blur="onBlur"
        />

        <button v-if="q" class="search-clear" @click="clear" title="Очистить" type="button">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- DROPDOWN -->
      <div v-if="open && q && !loadingSearch" class="dd">
        <div class="dd-list">
          <div v-if="!results.length" class="dd-empty">
            Ничего не найдено
          </div>

          <button
            v-for="(r, idx) in results"
            :key="r.id"
            class="dd-item"
            :class="{ active: idx === activeIdx }"
            @mousedown.prevent="goProduct(r)"
            :ref="(el) => (itemRefs[idx] = el)"
            type="button"
          >
            <!-- thumb -->
            <div class="dd-thumb-wrap">
              <img
                v-if="thumbUrl(r) && !thumbErr[r.id]"
                class="dd-thumb"
                :src="thumbUrl(r)"
                :alt="r.name"
                loading="lazy"
                @error="thumbErr[r.id] = true"
              />
              <div v-else class="dd-thumb dd-thumb-ph" aria-hidden="true">
                <i class="fa-regular fa-image"></i>
              </div>
            </div>

            <!-- text -->
            <div class="dd-main">
              <div class="dd-title">{{ r.name }}</div>
              <div class="dd-sub">
                <span v-if="r.brand" class="dd-pill">{{ r.brand }}</span>
                <span v-if="r.price != null" class="dd-price">{{ r.price }} ₽</span>
                <span v-if="r.barcode" class="dd-code">{{ r.barcode }}</span>
              </div>
            </div>

            <i class="fa-solid fa-chevron-right dd-arrow" aria-hidden="true"></i>
          </button>
        </div>

        <button class="dd-all" @mousedown.prevent="goAllResults" type="button">
          Показать все результаты в каталоге →
        </button>
      </div>

      <div v-if="loadingSearch" class="search-hint">
        <span class="dot"></span> Поиск…
      </div>
    </div>

    <!-- CATEGORIES 1 LEVEL -->
<div v-if="showHead" class="cats-head">      <div class="cats-title">Категории</div>
      <div class="cats-sub">Только первый уровень</div>
    </div>

<div class="cats-grid" v-if="displayCats.length">
      <button
        v-for="c in topCats"
        :key="c.id"
        class="cat-card"
        @click="goCategory(c)"
        :title="c.name"
        type="button"
      >
        <!-- ✅ square photo -->
        <div class="cat-photo">
          <img
            v-if="c.photo && !catImgErr[c.id]"
            :src="c.photo"
            :alt="c.name"
            loading="lazy"
            @error="catImgErr[c.id] = true"
          />
          <div v-else class="cat-photo-ph" aria-hidden="true">
            <i class="fa-regular fa-image"></i>
          </div>
        </div>

        <!-- ✅ text only under photo -->
        <div class="cat-text">{{ c.name }}</div>
      </button>
    </div>

    <div v-else class="cats-empty">
      Категории не загружены
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import { useRouter } from "vue-router";

const props = defineProps({
  showSearch: { type: Boolean, default: true },   
  showHead: { type: Boolean, default: true },     
  items: { type: Array, default: null },          
  navigateOnPick: { type: Boolean, default: true } 
});

const emit = defineEmits(["select-category"]);

const displayCats = computed(() => {
  // если передали items — используем их (для CatalogV2)
  if (Array.isArray(props.items) && props.items.length) return props.items;

  // иначе как раньше — свои topCats
  return topCats.value;
});

const router = useRouter();

/* ================= DATA: categories ================= */
const categories = ref([]);
const catImgErr = ref({});

async function loadCats() {
  try {
    const r = await fetch("/api/admin/product/get_categories_flat.php");
    const raw = await r.json();

    categories.value = (Array.isArray(raw) ? raw : []).map((c) => {
const photo =
  c.photo_url_abs ||
  c.photo_url ||
  (c.photo_categories ? `/photo_categories_vitrina/${c.photo_categories}` : null);


      return {
        id: c.id,
        name: c.name,
        code: c.code,
        parent: c.parent_id,
        photo,
      };
    });

    const next = { ...catImgErr.value };
    categories.value.forEach((x) => {
      if (x?.id != null) next[x.id] = false;
    });
    catImgErr.value = next;
  } catch {
    categories.value = [];
  }
}

const topCats = computed(() =>
  categories.value
    .filter((c) => !c.parent)
    .sort((a, b) => a.name.localeCompare(b.name, "ru", { sensitivity: "base" }))
);

function goCategory(cat) {
  emit("select-category", cat);

  if (props.navigateOnPick) {
    router.push({ path: "/catalogv2", query: { cat: cat.code } });
  }
}

/* ================= SEARCH ================= */
const q = ref("");
const open = ref(false);
const results = ref([]);
const loadingSearch = ref(false);
const activeIdx = ref(-1);

// ✅ для автоскролла активного пункта
const itemRefs = ref([]);
watch(activeIdx, async () => {
  await nextTick();
  const el = itemRefs.value?.[activeIdx.value];
  if (el && typeof el.scrollIntoView === "function") {
    el.scrollIntoView({ block: "nearest" });
  }
});

// ✅ запоминаем битые картинки
const thumbErr = ref({});

function thumbUrl(r) {
  if (r?.thumb) return r.thumb;
  if (r?.image) return r.image;
  if (Array.isArray(r?.images) && r.images[0]) return r.images[0];
  return "";
}

let t = null;
function onInput() {
  clearTimeout(t);

  const s = String(q.value || "").trim();
  if (!s) {
    results.value = [];
    open.value = false;
    activeIdx.value = -1;
    itemRefs.value = [];
    return;
  }

  open.value = true;
  t = setTimeout(() => doSearch(s), 220);
}

onBeforeUnmount(() => clearTimeout(t));

async function doSearch(text) {
  const s = String(text || "").trim();
  if (!s) return;

  loadingSearch.value = true;
  try {
    const r = await fetch(
      `/api/admin/product/search_products.php?q=${encodeURIComponent(s)}&limit=12`,
      { headers: { Accept: "application/json" } }
    );

    const data = await r.json();
    const list = Array.isArray(data) ? data : (data.products || data.items || []);

    const mapped = (list || [])
      .filter(Boolean)
      .slice(0, 12)
      .map((p) => ({
        id: p.id,
        name: p.name,
        price: p.price,
        brand: p.brand,
        barcode: p.barcode,
        thumb: p.thumb || p.image || null,
        images: p.images || null,
      }))
      .filter((p) => p.id != null && p.name);

    results.value = mapped;
    activeIdx.value = results.value.length ? 0 : -1;

    const nextErr = { ...thumbErr.value };
    mapped.forEach((x) => {
      if (x?.id != null) nextErr[x.id] = false;
    });
    thumbErr.value = nextErr;

    itemRefs.value = [];
  } catch {
    results.value = [];
    activeIdx.value = -1;
    itemRefs.value = [];
  } finally {
    loadingSearch.value = false;
  }
}

function clear() {
  q.value = "";
  results.value = [];
  open.value = false;
  activeIdx.value = -1;
  itemRefs.value = [];
}

function onBlur() {
  setTimeout(() => {
    open.value = false;
  }, 120);
}

function move(dir) {
  if (!results.value.length) return;
  const n = results.value.length;
  let i = activeIdx.value;
  if (i < 0) i = 0;
  i = (i + dir + n) % n;
  activeIdx.value = i;
}

function enterPick() {
  if (!open.value) return;
  const i = activeIdx.value;
  if (i >= 0 && results.value[i]) goProduct(results.value[i]);
  else goAllResults();
}

function goProduct(p) {
  router.push({ name: "product", params: { id: p.id } });
  open.value = false;
}

function goAllResults() {
  const s = String(q.value || "").trim();
  if (!s) return;
  router.push({ path: "/catalogv2", query: { q: s } });
  open.value = false;
}

onMounted(loadCats);
</script>

<style scoped>
/* ======================================================
   LAYOUT / WRAP
   ====================================================== */
.home-entry{
  width: min(1120px, 100%);
  margin: 0 auto;
  padding: 8px 10px;
  display:flex;
  flex-direction:column;
  gap: 18px;
}

/* ======================================================
   SEARCH
   ====================================================== */
.search-wrap{ position: relative; }

.search-box{
  display:flex;
  align-items:center;
  gap: 12px;

  padding: 12px 14px;
  border-radius: 18px;

  background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,.88));
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);

  transition: box-shadow .18s ease, transform .18s ease, border-color .18s ease;
}

.search-box:focus-within{
  border-color: rgba(4,0,255,.28);
  box-shadow: 0 0 0 3px rgba(4,0,255,.12), var(--shadow-md);
  transform: translateY(-1px);
}

.search-icon{
  color: var(--text-muted);
  font-size: 14px;
  flex: 0 0 auto;
  opacity: .9;
}

.search-input{
  flex:1;
  border:none;
  outline:none;
  background:transparent;
  font-size: 15px;
  color: var(--text-main);
  min-width: 0;
}

.search-input::placeholder{ color: rgba(107,114,128,.9); }

.search-clear{
  width: 36px;
  height: 36px;
  border-radius: 12px;

  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;

  display:inline-flex;
  align-items:center;
  justify-content:center;

  transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
}

.search-clear:hover{
  background: rgba(4,0,255,.05);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}
.search-clear:active{ transform: translateY(0); }

.search-hint{
  margin-top: 10px;
  font-size: 12px;
  color: var(--text-muted);
  display:flex;
  align-items:center;
  gap: 8px;
}
.search-hint .dot{
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: var(--accent);
  opacity: .7;
}

/* ======================================================
   DROPDOWN
   ====================================================== */
.dd{
  position:absolute;
  left: 0;
  right: 0;
  top: calc(100% + 10px);
  z-index: 50;

  background: rgba(255,255,255,.96);
  border: 1px solid var(--border-soft);
  border-radius: 18px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;

  display: flex;
  flex-direction: column;

  max-height: min(520px, 62vh);

  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.dd-list{
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 8px;
}

.dd-list::-webkit-scrollbar{ width: 10px; }
.dd-list::-webkit-scrollbar-thumb{
  background: rgba(17,24,39,.18);
  border-radius: 999px;
  border: 3px solid rgba(255,255,255,.9);
}
.dd-list::-webkit-scrollbar-track{ background: transparent; }

.dd-empty{
  padding: 14px 14px;
  font-size: 13px;
  font-weight: 800;
  color: var(--text-muted);
}

.dd-item{
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;

  display:flex;
  align-items: center;
  gap: 12px;

  padding: 10px 10px;
  border-radius: 14px;

  transition: background .12s ease, border-color .12s ease, transform .12s ease;
}

.dd-item:hover{
  background: rgba(4,0,255,.05);
  border-color: rgba(4,0,255,.10);
}
.dd-item.active{
  background: rgba(4,0,255,.08);
  border-color: rgba(4,0,255,.18);
}
.dd-item:active{ transform: scale(0.997); }

.dd-thumb-wrap{
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  border-radius: 14px;
  overflow: hidden;

  border: 1px solid var(--border-soft);
  background: #fff;
  display:flex;
  align-items:center;
  justify-content:center;

  box-shadow: 0 8px 18px rgba(0,0,0,.06);
}

.dd-thumb{
  width: 100%;
  height: 100%;
  object-fit: contain;
  display:block;
}

.dd-thumb-ph{
  color: var(--text-muted);
  background: linear-gradient(180deg, #fff, #f7f9ff);
  display:flex;
  align-items:center;
  justify-content:center;
  width: 100%;
  height: 100%;
}

.dd-main{
  min-width: 0;
  display:flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.dd-title{
  font-size: 13px;
  font-weight: 900;
  color: var(--text-main);
  line-height: 1.15;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dd-sub{
  display:flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.dd-pill{
  font-size: 11px;
  font-weight: 900;
  padding: 5px 10px;
  border-radius: 999px;

  background: rgba(4,0,255,.08);
  border: 1px solid rgba(4,0,255,.16);
  color: var(--text-main);

  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dd-price{
  font-size: 12px;
  font-weight: 900;
  color: var(--accent);
}

.dd-code{
  font-size: 11px;
  font-weight: 850;
  color: var(--text-muted);
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 5px 10px;
  border-radius: 999px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.dd-arrow{
  color: rgba(27,30,40,.35);
  font-size: 12px;
  flex: 0 0 auto;
  margin-left: 2px;
}

.dd-all{
  width: 100%;
  border: none;
  border-top: 1px solid var(--border-soft);
  background: rgba(255,255,255,.92);
  padding: 12px 14px;
  cursor: pointer;

  font-weight: 900;
  color: var(--accent);
  transition: background .12s ease;
}
.dd-all:hover{ background: rgba(4,0,255,.05); }

/* ======================================================
   CATEGORIES
   ====================================================== */
.cats-head{
  display:flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  padding: 2px 2px;
}

.cats-title{
  font-size: 18px;
  font-weight: 900;
  color: var(--text-main);
}

.cats-sub{
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
}

.cats-grid{
  display:grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.cat-card{
  text-align:left;
  border: 1px solid var(--border-soft);
  background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,255,255,.90));
  border-radius: var(--radius-lg);
  padding: 10px;

  box-shadow: var(--shadow-sm);
  cursor: pointer;

  display:flex;
  flex-direction: column;
  gap: 10px;

  transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}

.cat-card:hover{
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: rgba(4,0,255,.16);
}

.cat-photo{
  width: 100%;
  aspect-ratio: 1 / 1; /* ✅ квадрат */
  border-radius: 14px;
  overflow: hidden;

  border: 1px solid var(--border-soft);
  background: #fff;

  display:flex;
  align-items:center;
  justify-content:center;

  box-shadow: 0 10px 20px rgba(0,0,0,.06);
}

.cat-photo img{
  width: 100%;
  height: 100%;
  object-fit: cover; /* квадрат красиво заполняется */
  display:block;
}

.cat-photo-ph{
  width: 100%;
  height: 100%;
  display:flex;
  align-items:center;
  justify-content:center;
  color: var(--text-muted);
  background: linear-gradient(180deg, #fff, #f7f9ff);
  font-size: 18px;
}

.cat-text{
  font-size: 13px;
  font-weight: 900;
  color: var(--text-main);
  line-height: 1.2;
  padding: 2px 4px 4px 4px;

  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.4em;
}

.cats-empty{
  padding: 14px 14px;
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  color: var(--text-muted);
  font-weight: 900;
}

/* ======================================================
   RESPONSIVE
   ====================================================== */
@media (max-width: 1080px){
  .cats-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 820px){
  .cats-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 480px){
  .home-entry{ padding: 6px 6px; gap: 14px; }
  .search-box{ border-radius: 16px; padding: 11px 12px; }
  .dd{ border-radius: 16px; max-height: min(520px, 58vh); }
  .dd-thumb-wrap{ width: 44px; height: 44px; flex-basis: 44px; }
  .cats-head{ flex-direction: column; align-items: flex-start; }
  .cats-grid{ grid-template-columns: 1fr; }
}
</style>
