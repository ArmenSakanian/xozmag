<template>
  <div class="page" :style="{ '--appH': appH + 'px' }">
    <header ref="topRef" class="top">

      <!-- controls: LIST -->
      <div class="controls" v-if="viewMode === 'list'">
        <div class="search">
          <Fa :icon="['fas','magnifying-glass']" />
          <input
            v-model="q"
            class="input"
            placeholder="Поиск поставщика…"
            autocomplete="off"
            inputmode="search"
          />
          <button v-if="q" class="xbtn" @click="q = ''" title="Очистить">
            <Fa :icon="['fas','xmark']" />
          </button>
        </div>

        <button class="btn" :disabled="loading" @click="loadList(true)">
          <Fa :icon="['fas','rotate']" />
          Обновить
        </button>
      </div>

      <!-- controls: ITEMS -->
      <div class="controls" v-else>
        <div class="search">
          <Fa :icon="['fas','magnifying-glass']" />
          <input
            v-model="itemsQ"
            class="input"
            placeholder="Поиск товара…"
            autocomplete="off"
            inputmode="search"
          />
          <button
            v-if="itemsQ"
            class="xbtn"
            @click="itemsQ = ''"
            title="Очистить"
          >
            <Fa :icon="['fas','xmark']" />
          </button>
        </div>

        <div class="btns-right">
          <button class="btn" :disabled="itemsLoading" @click="reloadItems">
            <Fa :icon="['fas','rotate']" />
            Обновить товары
          </button>

          <button
            class="btn primary"
            :disabled="exporting || itemsLoading"
            @click="exportExcel"
          >
            <Fa v-if="!exporting" :icon="['fas','file-excel']" />
            <Fa v-else :icon="['fas','circle-notch']" />
            Выгрузить Excel
          </button>
        </div>
      </div>
    </header>

    <!-- ✅ ВАЖНО: контент отдельным скроллом -->
    <main class="content">
      <!-- ================= LIST VIEW ================= -->
      <section v-if="viewMode === 'list'">
        <section v-if="loading" class="state card">
          <div class="spinner"></div>
          <div class="state-text">Загружаю контрагентов…</div>
        </section>

        <section v-else-if="error" class="state card error">
          <div class="state-title">
            <Fa :icon="['fas','triangle-exclamation']" />
            Ошибка
          </div>
          <div class="state-text">{{ error }}</div>
          <button class="btn" @click="loadList(true)">
            <Fa :icon="['fas','rotate']" />
            Повторить
          </button>
        </section>

        <section v-else class="grid">
          <div class="card list-card">
            <div class="list-head">
              <div class="count">
                Поставщиков: <b>{{ filtered.length }}</b>
              </div>
              <div class="hint">
                Нажми <b>«Открыть»</b> — покажет товары на сайте. Сверху будет
                кнопка <b>«Выгрузить Excel»</b>.
              </div>
            </div>

            <div v-if="filtered.length === 0" class="empty">
              Ничего не найдено по запросу «{{ q }}»
            </div>

            <div v-else class="rows">
              <div v-for="c in filtered" :key="c.key" class="row">
                <div class="left">
                  <div class="name">
                    <span class="dot"></span>
                    {{ c.name }}
                  </div>

                  <div class="meta">
                    <span class="pill"
                      >групп(2ур): <b>{{ c.uuids?.length ?? 0 }}</b></span
                    >
                    <span class="pill"
                      >категорий(1ур):
                      <b>{{ c.categoryUuids?.length ?? 0 }}</b></span
                    >
                  </div>

                  <div v-if="c.categoryUuids?.length" class="cats">
                    <span
                      v-for="cu in c.categoryUuids"
                      :key="cu"
                      class="chip"
                      :title="categoryNameByUuid[cu] || cu"
                    >
                      {{ categoryNameByUuid[cu] || cu }}
                    </span>
                  </div>
                </div>

                <div class="right">
                  <button
                    class="btn primary"
                    :disabled="openingKey === c.key"
                    @click="openContragent(c)"
                  >
                    <Fa
                      v-if="openingKey !== c.key"
                     
                     :icon="['fas','folder-open']" />
                    <Fa v-else :icon="['fas','circle-notch']" />
                    Открыть
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </section>

      <!-- ================= ITEMS VIEW ================= -->
      <section v-else class="grid">
        <div class="card items-card">
          <div class="items-head">
            <div class="items-stats">
              <div class="stat">
                Товаров: <b>{{ itemsFiltered.length }}</b>
              </div>
              <div class="stat muted" v-if="itemsMeta?.generatedAt">
                Обновлено: <b>{{ itemsMeta.generatedAt }}</b>
              </div>
            </div>

            <div class="items-hint">
              Название, Остаток, Штрихкод, Артикул, <b>Минимальный остаток</b>.
            </div>
          </div>

          <div v-if="itemsLoading" class="state inner">
            <div class="spinner"></div>
            <div class="state-text">Загружаю товары…</div>
          </div>

          <div v-else-if="itemsError" class="state inner error">
            <div class="state-title">
              <Fa :icon="['fas','triangle-exclamation']" />
              Ошибка
            </div>
            <div class="state-text">{{ itemsError }}</div>
            <button class="btn" @click="reloadItems">
              <Fa :icon="['fas','rotate']" />
              Повторить
            </button>
          </div>

          <div v-else-if="itemsFiltered.length === 0" class="empty big">
            Ничего не найдено по запросу «{{ itemsQ }}»
          </div>

          <div v-else class="items-grid">
            <div
              v-for="p in itemsFiltered"
              :key="p.uuid || p.barcode + '_' + p.name"
              class="p-card"
            >
              <div class="p-top">
                <div class="p-left">
                  <div class="thumb">
                    <img
                      :src="p.image || '/img/no-photo.png'"
                      alt=""
                      loading="lazy"
                    />
                  </div>

                  <div class="p-name">{{ p.name }}</div>
                </div>
                
              </div>

              <div class="p-meta">
                <div class="kv">
                                  <div class="badge" :class="{ danger: Number(p.quantity) <= 0 }">
                  Остаток: <b><span class="quantity">{{ p.quantity }}</span></b>
                </div>
                </div>
                <div class="kv">
                  <div class="k">Штрихкод</div>
                  <div class="v mono">{{ p.barcode }}</div>
                </div>

                <div class="kv">
                  <div class="k">Артикул</div>
                  <div class="v mono">{{ p.article || "—" }}</div>
                </div>

                <div class="kv">
                  <div class="k">Минимальный остаток</div>
                  <div class="v mono">{{ p.min_stock }}</div>
                </div>
              </div>

              <div class="p-actions">
                <button class="btn mini" @click="copyText(p.barcode)">
                  <Fa :icon="['far','copy']" />
                  Копировать штрихкод
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <div v-if="toast" class="toast">
      <Fa :icon="['fas','check']" />
      {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const API_URL = "/api/admin/order/order.php";

// ===== list =====
const loading = ref(false);
const error = ref("");
const q = ref("");

const categories = ref([]);
const contragents = ref([]);

// ===== view =====
const viewMode = ref("list");
const selectedContragent = ref(null);
const openingKey = ref("");

// ===== items =====
const itemsLoading = ref(false);
const itemsError = ref("");
const items = ref([]);
const itemsQ = ref("");
const itemsMeta = ref(null);

// ===== export =====
const exporting = ref(false);

// ===== toast =====
const toast = ref("");
let toastTimer = null;

function showToast(msg) {
  toast.value = msg;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toast.value = ""), 1600);
}

const categoryNameByUuid = computed(() => {
  const map = {};
  for (const c of categories.value || []) map[c.uuid] = c.name;
  return map;
});

const filtered = computed(() => {
  const list = contragents.value || [];
  const s = q.value.trim().toLowerCase();
  if (!s) return list;
  return list.filter((x) =>
    String(x.name || "")
      .toLowerCase()
      .includes(s)
  );
});

const itemsFiltered = computed(() => {
  const list = items.value || [];
  const s = itemsQ.value.trim().toLowerCase();
  if (!s) return list;
  return list.filter((p) => {
    const hay = [p?.name, p?.barcode, p?.article, p?.min_stock, p?.quantity]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();
    return hay.includes(s);
  });
});

async function loadList(force = false) {
  loading.value = true;
  error.value = "";

  try {
    const params = new URLSearchParams({ mode: "list" });
    if (force) params.set("nocache", "1");

    const res = await fetch(`${API_URL}?${params.toString()}`, {
      method: "GET",
      headers: { Accept: "application/json" },
    });

    if (!res.ok) {
      const t = await res.text().catch(() => "");
      throw new Error(`HTTP ${res.status}. ${t}`.trim());
    }

    const data = await res.json();

    categories.value = Array.isArray(data?.categories) ? data.categories : [];
    contragents.value = Array.isArray(data?.contragents)
      ? data.contragents
      : [];
    contragents.value.sort((a, b) =>
      String(a.name || "").localeCompare(String(b.name || ""), "ru")
    );
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    loading.value = false;
  }
}

async function openContragent(c) {
  if (!c?.key) return;
  openingKey.value = c.key;

  selectedContragent.value = c;
  viewMode.value = "items";
  itemsQ.value = "";
  items.value = [];
  itemsMeta.value = null;

  await loadItems(c.key, true);
  openingKey.value = "";
}

function backToList() {
  viewMode.value = "list";
  selectedContragent.value = null;
  items.value = [];
  itemsError.value = "";
  itemsLoading.value = false;
  itemsMeta.value = null;
}

async function loadItems(key, force = false) {
  itemsLoading.value = true;
  itemsError.value = "";

  try {
    const params = new URLSearchParams({ mode: "items", key });
    if (force) params.set("nocache", "1");

    const res = await fetch(`${API_URL}?${params.toString()}`, {
      method: "GET",
      headers: { Accept: "application/json" },
    });

    if (!res.ok) {
      const t = await res.text().catch(() => "");
      throw new Error(`HTTP ${res.status}. ${t}`.trim());
    }

    const data = await res.json();
    const list = Array.isArray(data?.items) ? data.items : [];

    list.sort((a, b) =>
      String(a.name || "").localeCompare(String(b.name || ""), "ru")
    );
    items.value = list;

    itemsMeta.value = {
      generatedAt: data?.generatedAt || "",
      count: data?.count ?? list.length,
      contragent: data?.contragent || null,
    };
  } catch (e) {
    itemsError.value = e?.message || String(e);
  } finally {
    itemsLoading.value = false;
  }
}

function reloadItems() {
  const key = selectedContragent.value?.key;
  if (!key) return;
  loadItems(key, true);
}

function exportExcel() {
  const key = selectedContragent.value?.key;
  if (!key) return;

  exporting.value = true;
  const params = new URLSearchParams({ mode: "export", key });
  window.location.href = `${API_URL}?${params.toString()}`;
  setTimeout(() => (exporting.value = false), 900);
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(String(text || ""));
    showToast("Скопировано");
  } catch {
    showToast("Не получилось скопировать");
  }
}

/**
 * ✅ Мобильный фикс клавиатуры:
 * visualViewport.height уменьшается при открытии клавиатуры,
 * мы используем это как высоту приложения.
 */
const topRef = ref(null);
const appH = ref(0);

function updateAppHeight() {
  const h = window.visualViewport?.height ?? window.innerHeight;
  appH.value = Math.round(h);
  // чуть позже, чтобы браузер успел переложить layout
  nextTick(() => {});
}

onMounted(() => {
  loadList(false);
  updateAppHeight();

  const vv = window.visualViewport;
  if (vv) vv.addEventListener("resize", updateAppHeight);

  window.addEventListener("resize", updateAppHeight);
  window.addEventListener("orientationchange", updateAppHeight);
});

onBeforeUnmount(() => {
  const vv = window.visualViewport;
  if (vv) vv.removeEventListener("resize", updateAppHeight);

  window.removeEventListener("resize", updateAppHeight);
  window.removeEventListener("orientationchange", updateAppHeight);
});
</script>

<style scoped>
/* =========================
   LAYOUT: header fixed, content scrolls
========================= */
.page{
  --pad: clamp(12px, 2.2vw, 14px);

  height: var(--appH, 100dvh);
  min-height: var(--appH, 100dvh);

  display: flex;
  flex-direction: column;
  overflow: hidden;

  background: var(--bg-main);
  color: var(--text-main);
}

/* header stays on top */
.top{
  flex: 0 0 auto;
  padding: var(--pad) var(--pad) 10px;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;

  background: var(--bg-main);
}

/* ✅ separate scroll area */
.content{
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;

  padding: 0 var(--pad) calc(var(--pad) + env(safe-area-inset-bottom));
}

/* =========================
   CONTROLS
========================= */
.controls{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: space-between;
  margin-top: 12px;
}

.btns-right{
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.search{
  position: relative;
  flex: 1;
  min-width: 260px;
  max-width: 560px;
}

.search i.fa-magnifying-glass{
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 14px;
}

/* ✅ iOS: font-size >= 16px to avoid zoom */
.input{
  width: 100%;
  height: 44px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 0 44px 0 38px;
  background: var(--bg-panel);
  color: var(--text-main);
  outline: none;
  box-shadow: 0 1px 0 rgba(0,0,0,.02);
  font-size: 16px;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.input::placeholder{ color: var(--text-muted); }

.input:focus{
  border-color: color-mix(in srgb, var(--accent) 45%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 14%, transparent);
}

.xbtn{
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  height: 32px;
  width: 36px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
  cursor: pointer;
  transition: background .12s ease, border-color .12s ease, transform .12s ease;
}

.xbtn:hover{
  background: color-mix(in srgb, var(--bg-soft) 70%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--text-main) 12%, var(--border-soft));
}

.xbtn:active{ transform: translateY(-50%) scale(.98); }

/* =========================
   GRID + CARD
========================= */
.grid{
  max-width: 1200px;
  margin: 14px auto 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
  align-items: start;
}

.card{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.list-card,
.items-card{
  padding: 12px;
}

/* =========================
   LIST HEAD
========================= */
.list-head{
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  padding: 8px 8px 12px;
  border-bottom: 1px dashed var(--border-soft);
}

.count{
  font-size: 13px;
  color: var(--text-muted);
}

.hint{
  font-size: 12px;
  color: var(--text-muted);
  text-align: right;
  max-width: 560px;
}

/* =========================
   ROWS
========================= */
.rows{
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 10px 6px 6px;
}

.row{
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;

  border: 1px solid color-mix(in srgb, var(--border-soft) 90%, transparent);
  border-radius: var(--radius-lg);
  padding: 12px;
  background: color-mix(in srgb, var(--bg-panel) 85%, var(--bg-soft));
  transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}

.row:hover{
  border-color: color-mix(in srgb, var(--accent) 26%, var(--border-soft));
  box-shadow: var(--shadow-sm);
}

.left .name{
  font-weight: 900;
  font-size: 14px;
  display: flex;
  gap: 10px;
  align-items: center;
}

.dot{
  height: 10px;
  width: 10px;
  border-radius: 999px;
  background: var(--accent);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 16%, transparent);
}

.meta{
  margin-top: 8px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.pill{
  font-size: 12px;
  color: var(--text-muted);
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  padding: 6px 10px;
  border-radius: 999px;
}

.cats{
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.chip{
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--accent) 22%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 9%, var(--bg-panel));
  color: var(--text-main);

  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.right{
  display: flex;
  gap: 10px;
  align-items: center;
}

/* =========================
   BUTTONS
========================= */
.btn{
  height: 40px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  padding: 0 12px;
  cursor: pointer;

  display: inline-flex;
  gap: 10px;
  align-items: center;

  font-weight: 900;
  color: var(--text-main);
  transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease, opacity .12s ease;
}

.btn:hover:not(:disabled){
  background: color-mix(in srgb, var(--bg-panel) 70%, var(--bg-soft));
  box-shadow: var(--shadow-sm);
}

.btn:active:not(:disabled){ transform: translateY(1px); }

.btn:disabled{
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.primary{
  border-color: color-mix(in srgb, var(--accent) 40%, var(--border-soft));
  background: var(--accent);
  color: #fff;
  box-shadow: 0 10px 22px color-mix(in srgb, var(--accent) 22%, transparent);
}

.btn.primary:hover:not(:disabled){
  box-shadow: 0 14px 26px color-mix(in srgb, var(--accent) 26%, transparent);
  filter: brightness(1.02);
}

.btn.mini{
  height: 36px;
  border-radius: var(--radius-md);
  font-size: 12px;
  padding: 0 10px;
}

/* =========================
   STATES
========================= */
.state{
  padding: 18px;
  display: grid;
  justify-items: center;
  gap: 10px;
}

.state.inner{
  margin: 10px auto 14px;
  padding: 14px;
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-lg);
  width: calc(100% - 24px);
  background: var(--bg-soft);
}

.state.error{
  border-color: color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
}

.state-title{
  font-weight: 900;
  display: flex;
  gap: 10px;
  align-items: center;
}

.state-text{
  color: var(--text-muted);
  font-size: 13px;
  text-align: center;
}

.spinner{
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 3px solid color-mix(in srgb, var(--accent) 18%, transparent);
  border-top-color: var(--accent);
  animation: spin .85s linear infinite;
}

@keyframes spin{ to{ transform: rotate(360deg); } }

.empty{
  padding: 18px 10px;
  color: var(--text-muted);
  text-align: center;
}

.empty.big{ padding: 24px 10px; }

/* =========================
   ITEMS HEAD
========================= */
.items-head{
  display: flex;
  gap: 12px;
  align-items: flex-start;
  justify-content: space-between;
  padding: 8px 8px 12px;
  border-bottom: 1px dashed var(--border-soft);
}

.items-stats{
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.stat{ font-size: 13px; }

.stat.muted{
  color: var(--text-muted);
  font-size: 12px;
}

.items-hint{
  font-size: 12px;
  color: var(--text-muted);
  text-align: right;
  max-width: 560px;
}

/* =========================
   ITEMS GRID
========================= */
.items-grid{
  padding: 12px 6px 6px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

@media (max-width: 1000px){
  .items-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px){
  .items-grid{ grid-template-columns: 1fr; }
}

.p-card{
  border: 1px solid color-mix(in srgb, var(--border-soft) 90%, transparent);
  border-radius: var(--radius-lg);
  padding: 12px;
  background: color-mix(in srgb, var(--bg-panel) 85%, var(--bg-soft));
  display: grid;
  gap: 10px;
}

.p-top{
  display: flex;
  gap: 10px;
  align-items: flex-start;
  justify-content: space-between;
}

.p-left{
  display: flex;
  gap: 10px;
  align-items: flex-start;
  min-width: 0;
}

.thumb{
  width: 100px;
  height: 100px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  display: grid;
  place-items: center;
  overflow: hidden;
  flex: 0 0 auto;
}

.thumb img{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.p-name{
  font-weight: 900;
  font-size: 13px;
  line-height: 1.25;
  min-width: 0;
}

/* stock badge */
.badge{
  font-size: 12px;
  border: 1px solid color-mix(in srgb, var(--accent-2) 28%, var(--border-soft));
  padding: 6px 10px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-panel));
  color: var(--text-main);
  white-space: nowrap;

  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: 6px;
}

.badge .quantity{
  font-weight: 900;
  font-size: 14px;
}

.badge.danger{
  border-color: color-mix(in srgb, var(--accent-danger) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
}

.p-meta{
  display: grid;
  gap: 8px;
}

.kv{
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 10px;
  align-items: center;
}

@media (max-width: 640px){
  .kv{ grid-template-columns: 120px 1fr; }
}

.k{
  color: var(--text-muted);
  font-size: 12px;
}

.v{
  font-size: 12px;
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--text-main);
}

.mono{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.p-actions{
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* =========================
   MOBILE
========================= */
@media (max-width: 640px){
  .controls{
    flex-direction: column;
    align-items: stretch;
  }

  .search{ max-width: none; }

  .btns-right{ justify-content: stretch; }
  .btns-right .btn{
    width: 100%;
    justify-content: center;
  }

  .row{ grid-template-columns: 1fr; }
  .right .btn{
    width: 100%;
    justify-content: center;
  }

  .hint{ text-align: left; }
}

/* =========================
   TOAST
========================= */
.toast{
  position: fixed;
  right: 16px;
  bottom: 16px;

  background: var(--bg-panel);
  color: var(--text-main);

  padding: 12px 14px;
  border-radius: var(--radius-lg);

  display: flex;
  gap: 10px;
  align-items: center;

  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-soft);
  z-index: 9999;

  font-weight: 900;
  font-size: 13px;
}
</style>
