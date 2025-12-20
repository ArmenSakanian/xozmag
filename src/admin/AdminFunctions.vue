<template>
  <div class="convert-root">
    <div class="convert-wrap">
      <div class="panels">
        <!-- ================= PANEL 1: CONVERTER ================= -->
        <section class="panel">
          <h1 class="title">Конвертер изображений</h1>
          <p class="subtitle">.jpg, .png, .jpeg → WEBP</p>

          <button
            class="main-btn"
            :disabled="loadingConvert || loadingSync || loadingMin"
            @click="start"
          >
            {{ loadingConvert ? "Обработка…" : "Начать преобразование" }}
          </button>

          <!-- CONVERT -->
          <div class="progress-section">
            <div class="progress-header">
              <span>Конверсия</span>
              <span>{{ convert.current }} / {{ convert.total }}</span>
            </div>
            <div class="progress-track">
              <div class="progress-fill convert" :style="{ width: convertPercent + '%' }"></div>
            </div>
          </div>

          <!-- DELETE -->
          <div class="progress-section">
            <div class="progress-header">
              <span>Очистка</span>
              <span>{{ remove.current }} / {{ remove.total }}</span>
            </div>
            <div class="progress-track">
              <div class="progress-fill remove" :style="{ width: removePercent + '%' }"></div>
            </div>
          </div>

          <!-- LOG (convert) -->
          <div class="log-box">
            <div v-for="(l, i) in logs" :key="i" class="log-line">
              {{ l }}
            </div>
          </div>
        </section>

        <!-- ================= PANEL 2: SYNC ================= -->
        <section class="panel">
          <h1 class="title">Синхронизация Evotor</h1>
          <p class="subtitle">Запуск товаров из базы</p>

          <button
            class="sync-btn"
            :disabled="loadingSync || loadingConvert || loadingMin"
            @click="startSync"
          >
            {{ loadingSync ? "Синхронизация…" : "Запустить синхронизацию" }}
          </button>

          <div class="sync-status" v-if="sync.status">
            <span class="pill" :class="sync.status">{{ sync.statusText }}</span>
            <span class="muted" v-if="sync.finishedAt">• {{ sync.finishedAt }}</span>
          </div>

          <div class="stats-grid" v-if="sync.hasResult">
            <div class="stat">
              <div class="stat-label">Добавлено</div>
              <div class="stat-val">{{ sync.inserted }}</div>
            </div>
            <div class="stat">
              <div class="stat-label">Обновлено</div>
              <div class="stat-val">{{ sync.updated }}</div>
            </div>
            <div class="stat">
              <div class="stat-label">Удалено</div>
              <div class="stat-val">{{ sync.deleted }}</div>
            </div>
          </div>

          <!-- ===== CHANGES LISTS ===== -->
          <div class="changes" v-if="sync.hasResult">
            <div class="change-block" v-if="createdItems.length">
              <div class="change-title add">Добавлено ({{ createdItems.length }})</div>
              <div class="change-list">
                <div class="change-line" v-for="(it, i) in createdItems" :key="'c'+i">
                  <span class="bc">{{ it.barcode }}</span>
                  <span class="nm">{{ it.name }}</span>
                </div>
              </div>
            </div>

            <div class="change-block" v-if="updatedItems.length">
              <div class="change-title upd">Обновлено ({{ updatedItems.length }})</div>
              <div class="change-list">
                <div class="change-line" v-for="(it, i) in updatedItems" :key="'u'+i">
<span class="bc">{{ it.barcode }}</span>

<span class="nm">
  <span class="nm-title">{{ it.name }}</span>
  <span class="nm-meta" v-if="it.fields?.length">
    изменено: {{ it.fields.join(", ") }}
  </span>
</span>

                </div>
              </div>
            </div>

            <div class="change-block" v-if="deletedItems.length">
              <div class="change-title del">Удалено ({{ deletedItems.length }})</div>
              <div class="change-list">
                <div class="change-line" v-for="(it, i) in deletedItems" :key="'d'+i">
<span class="bc">{{ it.barcode }}</span>

<span class="nm">
  <span class="nm-title">{{ it.name }}</span>

  <span class="nm-meta">
    фото: удалено {{ it.photos_deleted_count ?? (it.photos_deleted?.length || 0) }},
    не найдено {{ it.photos_missing_count ?? (it.photos_missing?.length || 0) }}
  </span>
</span>
                </div>
              </div>
            </div>

            <div class="hint" v-if="truncated">⚠ Показаны не все строки (ограничение).</div>
          </div>

          <!-- LOG (sync) -->
          <div class="log-box sync-log">
            <div v-for="(l, i) in syncLogs" :key="i" class="log-line">
              {{ l }}
            </div>
          </div>
        </section>

        <!-- ================= PANEL 3: MIN STOCK IMPORT ================= -->
        <section class="panel">
          <h1 class="title">Импорт “Минимальный остаток”</h1>
          <p class="subtitle">
            Загрузи <b>XLSX</b> или <b>CSV</b>: <b>Штрихкод</b> + <b>Минимальный остаток</b>.
            Повторный импорт по штрихкоду обновит значение.
          </p>

          <div class="min-toolbar">
            <button class="min-btn ghost" :disabled="loadingMin || loadingConvert || loadingSync" @click="downloadMinTemplate">
              <i class="fa-regular fa-file-lines"></i>
              Скачать шаблон (CSV)
            </button>
          </div>

          <div
            class="min-drop"
            :class="{ drag: minIsDrag }"
            @dragenter.prevent="onMinDrag(true)"
            @dragover.prevent
            @dragleave.prevent="onMinDrag(false)"
            @drop.prevent="onMinDrop"
          >
            <input
              ref="minFileInput"
              class="min-file"
              type="file"
              accept=".xlsx,.csv"
              @change="onMinPick"
            />

            <div class="min-drop-inner">
              <div class="min-icon">
                <i class="fa-solid fa-cloud-arrow-up"></i>
              </div>

              <div class="min-txt">
                <div class="min-t1">
                  Перетащи файл сюда или
                  <button class="min-link" :disabled="loadingMin || loadingConvert || loadingSync" @click="openMinPicker">
                    выбери
                  </button>
                </div>
                <div class="min-t2">.xlsx / .csv — максимум 15MB</div>
              </div>

              <div class="min-picked" v-if="minPickedName">
                <div class="min-pname">
                  <i class="fa-regular fa-file-excel"></i>
                  {{ minPickedName }}
                </div>
                <div class="min-pactions">
                  <button class="min-btn small ghost" @click="clearMinFile" :disabled="loadingMin || loadingConvert || loadingSync">
                    <i class="fa-solid fa-xmark"></i> Убрать
                  </button>
                </div>
              </div>
            </div>

            <div class="min-actions">
              <label class="min-check">
                <input type="checkbox" v-model="minDryRun" :disabled="loadingMin || loadingConvert || loadingSync" />
                Проверить без записи (dry-run)
              </label>

              <button
                class="min-btn primary"
                :disabled="!minFile || loadingMin || loadingConvert || loadingSync"
                @click="uploadMin"
              >
                <i v-if="!loadingMin" class="fa-solid fa-upload"></i>
                <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
                {{ minDryRun ? "Проверить" : "Импортировать" }}
              </button>
            </div>
          </div>

          <div v-if="minError" class="min-state error">
            <div class="min-st-title">
              <i class="fa-solid fa-triangle-exclamation"></i> Ошибка
            </div>
            <div class="min-st-text">{{ minError }}</div>
          </div>

          <div v-if="minResult" class="min-result">
            <div class="min-r-top">
              <div class="min-r-title">
                <i class="fa-solid fa-circle-check"></i>
                Готово
                <span v-if="minResult.dry_run" class="min-badge">dry-run</span>
              </div>
              <div class="min-r-file">
                {{ minResult.file }} <span class="muted">({{ minResult.ext }})</span>
              </div>
            </div>

            <div class="min-stats">
              <div class="min-stat">
                <div class="k">Строк в файле</div>
                <div class="v">{{ minResult.rows_total_in_file }}</div>
              </div>
              <div class="min-stat">
                <div class="k">Распознано строк</div>
                <div class="v">{{ minResult.rows_parsed }}</div>
              </div>
              <div class="min-stat">
                <div class="k">Уникальных штрихкодов</div>
                <div class="v">{{ minResult.unique_barcodes }}</div>
              </div>
              <div class="min-stat ok">
                <div class="k">Добавлено</div>
                <div class="v">{{ minResult.inserted }}</div>
              </div>
              <div class="min-stat warn">
                <div class="k">Обновлено</div>
                <div class="v">{{ minResult.updated }}</div>
              </div>
              <div class="min-stat">
                <div class="k">Без изменений</div>
                <div class="v">{{ minResult.unchanged }}</div>
              </div>
            </div>

            <div class="min-split">
              <div class="min-box">
                <div class="min-box-title">Превью операций</div>
                <div class="min-table">
                  <div class="min-tr th">
                    <div>Штрихкод</div>
                    <div>Мин. остаток</div>
                    <div>Действие</div>
                  </div>
                  <div class="min-tr" v-for="(r, i) in (minResult.preview || [])" :key="i">
                    <div class="mono">{{ r.barcode }}</div>
                    <div>{{ r.min_stock }}</div>
                    <div><span class="min-pill" :class="r.action">{{ minActionLabel(r.action) }}</span></div>
                  </div>
                </div>
              </div>

              <div class="min-box" v-if="minResult.invalid_preview?.length">
                <div class="min-box-title">Ошибки (первые)</div>
                <div class="min-bad">
                  <div class="min-bad-row" v-for="(b, i) in minResult.invalid_preview" :key="i">
                    <div class="b1">Строка {{ b.row }}</div>
                    <div class="b2">
                      <span v-if="b.barcode" class="mono">{{ b.barcode }}</span>
                      <span class="muted" v-else>—</span>
                    </div>
                    <div class="b3">{{ b.error }}</div>
                  </div>
                </div>
              </div>

              <div class="min-box" v-else>
                <div class="min-box-title">Ошибки</div>
                <div class="muted">Нет ошибок 🎉</div>
              </div>
            </div>

            <div class="min-foot muted">
              Подсказка: колонку “Штрихкод” в Excel ставь как <b>текст</b>.
            </div>
          </div>
        </section>
      </div>
    </div>

    <div v-if="minToast" class="toast">
      <i class="fa-solid fa-check"></i> {{ minToast }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

NProgress.configure({
  showSpinner: false,
  trickleSpeed: 120,
});

/* =========================
   COMMON
========================= */
const loadingConvert = ref(false);
const loadingSync = ref(false);

/* =========================
   CONVERT
========================= */
const logs = ref([]);
const convert = ref({ current: 0, total: 0 });
const remove = ref({ current: 0, total: 0 });

const convertPercent = computed(() =>
  convert.value.total ? Math.round((convert.value.current / convert.value.total) * 100) : 0
);
const removePercent = computed(() =>
  remove.value.total ? Math.round((remove.value.current / remove.value.total) * 100) : 0
);

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const start = async () => {
  loadingConvert.value = true;
  logs.value = [];
  convert.value = { current: 0, total: 0 };
  remove.value = { current: 0, total: 0 };

  NProgress.start();
  let index = 0;

  try {
    while (true) {
      const res = await fetch(`/api/admin/functions/convert_images_step.php?index=${index}`, {
        cache: "no-store",
      });
      const data = await res.json();

      if (data.done) {
        convert.value.total = data.total;
        remove.value.total = data.total;
        break;
      }

      convert.value.total = data.total;
      convert.value.current = data.index;
      remove.value.current = data.index;

      index = data.index;

      logs.value.unshift(
        `${String(data.status || "").toUpperCase().padEnd(10)} | ${data.file}`
      );
      NProgress.set(convertPercent.value / 100);

      await sleep(18);
    }

    logs.value.unshift("✔ ALL FILES PROCESSED");
  } catch (e) {
    logs.value.unshift("✖ ERROR: " + (e?.message || "Unknown"));
  } finally {
    NProgress.done();
    loadingConvert.value = false;
  }
};

/* =========================
   SYNC EVOTOR → DB
========================= */
const syncLogs = ref([]);
const createdItems = ref([]);
const updatedItems = ref([]);
const deletedItems = ref([]);
const truncated = ref(false);

const sync = ref({
  status: "",
  statusText: "",
  hasResult: false,
  inserted: 0,
  updated: 0,
  deleted: 0,
  finishedAt: "",
});

const startSync = async () => {
  createdItems.value = [];
  updatedItems.value = [];
  deletedItems.value = [];
  truncated.value = false;

  loadingSync.value = true;
  syncLogs.value = [];
  sync.value = {
    status: "run",
    statusText: "Выполняется…",
    hasResult: false,
    inserted: 0,
    updated: 0,
    deleted: 0,
    finishedAt: "",
  };

  NProgress.start();

  try {
    syncLogs.value.unshift("→ Запуск: /api/admin/functions/sync_evotor.php");

    const res = await fetch("/api/admin/functions/sync_evotor.php", {
      method: "GET",
      cache: "no-store",
    });

    const text = await res.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch {
      throw new Error("Ответ не JSON: " + text.slice(0, 200));
    }

    if (!res.ok || data?.error) {
      throw new Error(data?.error || `HTTP ${res.status}`);
    }

    const inserted = Number(data.inserted ?? 0);
    const updated = Number(data.updated ?? 0);
    const deleted = Number(data.deleted ?? 0);

    createdItems.value = Array.isArray(data.insertedItems) ? data.insertedItems : [];
    updatedItems.value = Array.isArray(data.updatedItems) ? data.updatedItems : [];
    deletedItems.value = Array.isArray(data.deletedItems) ? data.deletedItems : [];
    truncated.value = !!data.truncated;

    sync.value.hasResult = true;
    sync.value.inserted = inserted;
    sync.value.updated = updated;
    sync.value.deleted = deleted;

    sync.value.status = "ok";
    sync.value.statusText = "Готово";
    sync.value.finishedAt = new Date().toLocaleString();

    syncLogs.value.unshift("✔ success: true");
    syncLogs.value.unshift(`• inserted: ${inserted}`);
    syncLogs.value.unshift(`• updated:  ${updated}`);
    syncLogs.value.unshift(`• deleted:  ${deleted}`);
  } catch (e) {
    sync.value.status = "error";
    sync.value.statusText = "Ошибка";
    sync.value.finishedAt = new Date().toLocaleString();
    syncLogs.value.unshift("✖ ERROR: " + (e?.message || "Unknown"));
  } finally {
    NProgress.done();
    loadingSync.value = false;
  }
};

/* =========================
   MIN STOCK IMPORT
========================= */
const API_MIN_URL = "/api/admin/functions/import_min_stock.php";

const loadingMin = ref(false);
const minFileInput = ref(null);
const minFile = ref(null);
const minPickedName = ref("");
const minIsDrag = ref(false);

const minDryRun = ref(false);
const minError = ref("");
const minResult = ref(null);

const minToast = ref("");
let minToastTimer = null;

function showMinToast(msg) {
  minToast.value = msg;
  clearTimeout(minToastTimer);
  minToastTimer = setTimeout(() => (minToast.value = ""), 1600);
}

function openMinPicker() {
  minFileInput.value?.click();
}

function onMinPick(e) {
  const f = e.target.files?.[0];
  if (!f) return;
  setMinFile(f);
}

function onMinDrag(v) {
  minIsDrag.value = v;
}

function onMinDrop(e) {
  minIsDrag.value = false;
  const f = e.dataTransfer?.files?.[0];
  if (!f) return;
  setMinFile(f);
}

function setMinFile(f) {
  const ext = (f.name.split(".").pop() || "").toLowerCase();
  if (!["xlsx", "csv"].includes(ext)) {
    showMinToast("Нужен .xlsx или .csv");
    return;
  }
  if (f.size > 15 * 1024 * 1024) {
    showMinToast("Файл больше 15MB");
    return;
  }
  minFile.value = f;
  minPickedName.value = f.name;
  minError.value = "";
  minResult.value = null;
}

function clearMinFile() {
  minFile.value = null;
  minPickedName.value = "";
  if (minFileInput.value) minFileInput.value.value = "";
}

function minActionLabel(a) {
  if (a === "insert") return "добавлено";
  if (a === "update") return "обновлено";
  return "без изменений";
}

async function uploadMin() {
  if (!minFile.value) return;

  loadingMin.value = true;
  minError.value = "";
  minResult.value = null;

  try {
    const fd = new FormData();
    fd.append("file", minFile.value);
    fd.append("dry_run", minDryRun.value ? "1" : "0");

    const res = await fetch(API_MIN_URL, {
      method: "POST",
      body: fd,
    });

    const data = await res.json().catch(() => null);
    if (!res.ok || !data) throw new Error(data?.error || `HTTP ${res.status}`);
    if (!data.success) throw new Error(data.error || "Неизвестная ошибка");

    minResult.value = data;
    showMinToast(minDryRun.value ? "Проверка выполнена" : "Импорт выполнен");
  } catch (e) {
    minError.value = e?.message || String(e);
  } finally {
    loadingMin.value = false;
  }
}

function downloadMinTemplate() {
  // пример 1 строки: штрихкод;минимальный_остаток
  const csv = "\uFEFFШтрихкод;Минимальный остаток\n4607138899795;5\n";
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = "min_stock_template.csv";
  a.click();
  URL.revokeObjectURL(a.href);
  showMinToast("Шаблон скачан");
}
</script>

<style scoped>
/* =========================
   ROOT / LAYOUT
========================= */
.convert-root{
  min-height: 100dvh;
  background: var(--bg-main);
  color: var(--text-main);
  padding: clamp(12px, 2.2vw, 24px);
}

.convert-wrap{
  max-width: 1320px;
  margin: 0 auto;
}

.panels{
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
  gap: 14px;
}

/* =========================
   PANEL CARD
========================= */
.panel{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 18px;
  display: flex;
  flex-direction: column;
  min-height: 520px;
}

/* titles */
.title{
  margin: 0;
  text-align: center;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: .2px;
  color: var(--text-main);
}

.subtitle{
  margin: 6px 0 14px;
  text-align: center;
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.35;
}

/* =========================
   BUTTONS
========================= */
.main-btn,
.sync-btn{
  width: 100%;
  min-height: 44px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  cursor: pointer;
  font-weight: 900;
  font-size: 13px;
  letter-spacing: .2px;
  transition: transform .12s ease, box-shadow .12s ease, filter .12s ease, opacity .12s ease, background .12s ease, border-color .12s ease;
  user-select: none;
}

.main-btn{
  background: var(--accent);
  color: #fff;
  box-shadow: var(--shadow-sm);
  margin-bottom: 14px;
}

.sync-btn{
  background: var(--accent-2);
  color: #fff;
  box-shadow: var(--shadow-sm);
  margin-bottom: 12px;
}

.main-btn:hover:not(:disabled),
.sync-btn:hover:not(:disabled){
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  filter: brightness(1.02);
}

.main-btn:active:not(:disabled),
.sync-btn:active:not(:disabled){
  transform: translateY(0px);
}

.main-btn:disabled,
.sync-btn:disabled{
  opacity: .55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.main-btn:focus-visible,
.sync-btn:focus-visible{
  outline: none;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent), var(--shadow-md);
}

/* =========================
   PROGRESS
========================= */
.progress-section{
  margin-bottom: 14px;
}

.progress-header{
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 6px;
  font-weight: 800;
}

.progress-track{
  height: 12px;
  background: var(--bg-soft);
  border-radius: 999px;
  overflow: hidden;
  border: 1px solid var(--border-soft);
}

.progress-fill{
  height: 100%;
  transition: width .18s ease;
}

.progress-fill.convert{
  background: linear-gradient(90deg,
    color-mix(in srgb, var(--accent) 85%, #ffffff),
    var(--accent)
  );
}

.progress-fill.remove{
  background: linear-gradient(90deg,
    color-mix(in srgb, var(--accent-danger) 75%, #ffffff),
    var(--accent-danger)
  );
}

/* =========================
   LOG
========================= */
.log-box{
  margin-top: 12px;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 12px;
  max-height: 260px;
  overflow: auto;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
  color: var(--text-main);
}

.panel .log-box{ margin-top: auto; }

.log-line{
  padding: 3px 0;
  color: color-mix(in srgb, var(--text-main) 85%, var(--text-muted));
}

.sync-log{ max-height: 320px; }

/* =========================
   SYNC STATUS / STATS
========================= */
.sync-status{
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 10px;
}

.pill{
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
}

.pill.run{
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 25%, var(--border-soft));
}

.pill.ok{
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent-2) 25%, var(--border-soft));
}

.pill.error{
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
}

.muted{
  color: var(--text-muted);
  font-size: 12px;
}

.stats-grid{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin: 10px 0 10px;
}

.stat{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  text-align: center;
}

.stat-label{
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 4px;
  font-weight: 800;
}

.stat-val{
  font-size: 18px;
  font-weight: 900;
  color: var(--text-main);
}

/* =========================
   CHANGES LISTS
========================= */
.changes{
  margin-top: 10px;
  display: grid;
  gap: 10px;
}

.change-block{
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  border-radius: var(--radius-md);
  padding: 10px;
}

.change-title{
  font-weight: 900;
  font-size: 12px;
  margin-bottom: 8px;
  color: var(--text-main);
}

.change-title.add{ color: var(--accent-2); }
.change-title.upd{ color: var(--accent); }
.change-title.del{ color: var(--accent-danger); }

.change-list{
  max-height: 200px;
  overflow: auto;
  border-radius: 12px;
  background: color-mix(in srgb, var(--bg-panel) 55%, var(--bg-soft));
  padding: 8px;
  border: 1px solid var(--border-soft);
}

.change-line{
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 10px;
  padding: 6px 0;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 12px;
  border-bottom: 1px dashed color-mix(in srgb, var(--border-soft) 75%, transparent);
}
.change-line:last-child{ border-bottom: none; }

.bc{ color: var(--text-muted); }

.nm{
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.nm-title{
  color: var(--text-main);
  font-weight: 800;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.nm-meta{
  font-size: 11px;
  color: var(--text-muted);
}

.hint{
  font-size: 12px;
  color: var(--text-muted);
  text-align: center;
  margin-top: 6px;
}

/* =========================
   MIN STOCK PANEL
========================= */
.min-toolbar{
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
}

.min-btn{
  min-height: 42px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  padding: 0 14px;
  cursor: pointer;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 900;
  color: var(--text-main);
  transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease, opacity .12s ease;
}

.min-btn.small{
  min-height: 36px;
  border-radius: var(--radius-md);
  padding: 0 12px;
}

.min-btn:disabled{
  opacity: .55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.min-btn.ghost{
  background: var(--bg-soft);
}

.min-btn.ghost:hover:not(:disabled){
  background: color-mix(in srgb, var(--bg-soft) 70%, var(--bg-panel));
}

.min-btn.primary{
  background: var(--accent);
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.min-btn.primary:hover:not(:disabled){
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  filter: brightness(1.02);
}

/* dropzone */
.min-drop{
  padding: 14px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}

.min-drop.drag{
  border-color: color-mix(in srgb, var(--accent) 40%, var(--border-soft));
  box-shadow: 0 0 0 5px color-mix(in srgb, var(--accent) 12%, transparent);
}

.min-file{ display: none; }

.min-drop-inner{
  display: grid;
  grid-template-columns: 54px 1fr auto;
  gap: 14px;
  align-items: center;
  padding: 12px;
  border: 1px dashed color-mix(in srgb, var(--border-soft) 85%, var(--text-light));
  border-radius: var(--radius-md);
  background: var(--bg-panel);
}

.min-icon{
  width: 54px;
  height: 54px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-panel));
  border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border-soft));
  color: var(--accent);
  font-size: 20px;
}

.min-t1{
  font-weight: 900;
  font-size: 14px;
  color: var(--text-main);
}

.min-t2{
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 12px;
}

.min-link{
  border: 0;
  background: transparent;
  color: var(--accent);
  font-weight: 900;
  cursor: pointer;
  padding: 0;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.min-picked{
  display: grid;
  gap: 8px;
  justify-items: end;
}

.min-pname{
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 900;
  font-size: 13px;
  padding: 8px 10px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
}

/* bottom actions */
.min-actions{
  margin-top: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.min-check{
  display: inline-flex;
  gap: 10px;
  align-items: center;
  color: var(--text-muted);
  font-size: 13px;
  user-select: none;
}

.min-check input{
  width: 16px;
  height: 16px;
}

/* states */
.min-state{
  margin-top: 12px;
  border-radius: var(--radius-md);
  padding: 12px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
}

.min-state.error{
  border-color: color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 8%, var(--bg-panel));
}

.min-st-title{
  font-weight: 900;
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 6px;
  color: var(--text-main);
}

.min-st-text{
  color: var(--text-muted);
  font-size: 13px;
}

.min-result{
  margin-top: 12px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  padding: 12px;
  box-shadow: var(--shadow-sm);
}

.min-r-top{
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 10px;
  border-bottom: 1px dashed var(--border-soft);
}

.min-r-title{
  font-weight: 900;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  color: var(--text-main);
}

.min-badge{
  margin-left: 8px;
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-panel));
  color: var(--accent);
  font-weight: 900;
}

.min-r-file{
  color: var(--text-muted);
  font-size: 12px;
}

.min-stats{
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.min-stat{
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  background: var(--bg-soft);
}

.min-stat .k{
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 800;
}

.min-stat .v{
  margin-top: 6px;
  font-size: 18px;
  font-weight: 900;
  color: var(--text-main);
}

.min-stat.ok{
  border-color: color-mix(in srgb, var(--accent-2) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
}
.min-stat.warn{
  border-color: rgba(234, 179, 8, 0.35);
  background: rgba(234, 179, 8, 0.10);
}

.min-split{
  margin-top: 12px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.min-box{
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 12px;
  background: var(--bg-soft);
}

.min-box-title{
  font-weight: 900;
  margin-bottom: 10px;
  color: var(--text-main);
}

.min-table{
  display: grid;
  gap: 8px;
}

.min-tr{
  display: grid;
  grid-template-columns: 1.6fr 0.9fr 0.9fr;
  gap: 10px;
  align-items: center;
  padding: 10px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  background: var(--bg-panel);
}

.min-tr.th{
  background: color-mix(in srgb, var(--bg-soft) 70%, var(--bg-panel));
  font-weight: 900;
  color: var(--text-main);
}

.mono{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.min-pill{
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
}

.min-pill.insert{
  border-color: color-mix(in srgb, var(--accent-2) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
  color: color-mix(in srgb, var(--accent-2) 85%, #000);
}
.min-pill.update{
  border-color: rgba(234, 179, 8, 0.35);
  background: rgba(234, 179, 8, 0.12);
}
.min-pill.skip{
  border-color: color-mix(in srgb, var(--text-muted) 25%, var(--border-soft));
  background: color-mix(in srgb, var(--text-muted) 8%, var(--bg-soft));
  color: var(--text-muted);
}

.min-bad{
  display: grid;
  gap: 8px;
}

.min-bad-row{
  display: grid;
  grid-template-columns: 90px 1fr 1.6fr;
  gap: 10px;
  padding: 10px;
  border-radius: var(--radius-md);
  border: 1px solid color-mix(in srgb, var(--accent-danger) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
}

.b1{ font-weight: 900; color: var(--text-main); }
.b2{ color: var(--text-main); }
.b3{ color: var(--text-muted); }

.min-foot{
  text-align: center;
  font-size: 12px;
  padding: 10px 0 2px;
  color: var(--text-muted);
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
  border-radius: var(--radius-md);
  display: flex;
  gap: 10px;
  align-items: center;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-soft);
  z-index: 9999;
  font-weight: 900;
  font-size: 13px;
}

/* =========================
   MOBILE
========================= */
@media (max-width: 900px){
  .panel{ min-height: auto; }
  .stats-grid{ grid-template-columns: 1fr; }
  .min-drop-inner{ grid-template-columns: 1fr; }
  .min-picked{ justify-items: start; }
}

@media (max-width: 520px){
  .panels{ grid-template-columns: 1fr; }
  .panel{ padding: 14px; border-radius: var(--radius-lg); }

  .change-line{ grid-template-columns: 1fr; }
  .nm-title{ white-space: normal; }

  .min-actions{ flex-direction: column; align-items: stretch; }
  .min-btn{ width: 100%; justify-content: center; }
  .min-tr{ grid-template-columns: 1fr; }
  .min-bad-row{ grid-template-columns: 1fr; }
}
</style>
