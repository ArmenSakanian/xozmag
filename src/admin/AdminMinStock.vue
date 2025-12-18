<template>
  <div class="page">
    <div class="wrap">
      <header class="head card">
        <div class="h-left">
          <h1 class="title">Импорт “Минимальный остаток”</h1>
          <p class="sub">
            Загрузи <b>XLSX</b> или <b>CSV</b> с 2 колонками:
            <b>Штрихкод</b> и <b>Минимальный остаток</b>.
            При повторном импорте по штрихкоду значение обновится.
          </p>
        </div>

        <div class="h-right">
          <button class="btn ghost" @click="downloadTemplate">
            <i class="fa-regular fa-file-lines"></i>
            Скачать шаблон (CSV)
          </button>
        </div>
      </header>

      <section class="card drop" :class="{ drag: isDrag }"
               @dragenter.prevent="onDrag(true)"
               @dragover.prevent
               @dragleave.prevent="onDrag(false)"
               @drop.prevent="onDrop">
        <input ref="fileInput" class="file" type="file" accept=".xlsx,.csv" @change="onPick" />

        <div class="drop-inner">
          <div class="icon">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </div>

          <div class="txt">
            <div class="t1">
              Перетащи файл сюда или <button class="link" @click="openPicker">выбери</button>
            </div>
            <div class="t2">Поддерживается: .xlsx, .csv — максимум 15MB</div>
          </div>

          <div class="picked" v-if="pickedName">
            <div class="p-name">
              <i class="fa-regular fa-file-excel"></i>
              {{ pickedName }}
            </div>
            <div class="p-actions">
              <button class="btn small ghost" @click="clearFile" :disabled="loading">
                <i class="fa-solid fa-xmark"></i> Убрать
              </button>
            </div>
          </div>
        </div>

        <div class="actions">
          <label class="check">
            <input type="checkbox" v-model="dryRun" :disabled="loading" />
            Проверить без записи (dry-run)
          </label>

          <button class="btn primary" :disabled="!file || loading" @click="upload">
            <i v-if="!loading" class="fa-solid fa-upload"></i>
            <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
            {{ dryRun ? "Проверить" : "Импортировать" }}
          </button>
        </div>
      </section>

      <section v-if="error" class="card state error">
        <div class="st-title">
          <i class="fa-solid fa-triangle-exclamation"></i> Ошибка
        </div>
        <div class="st-text">{{ error }}</div>
      </section>

      <section v-if="result" class="card result">
        <div class="r-top">
          <div class="r-title">
            <i class="fa-solid fa-circle-check"></i>
            Готово
            <span v-if="result.dry_run" class="badge">dry-run</span>
          </div>

          <div class="r-file">
            {{ result.file }} <span class="muted">({{ result.ext }})</span>
          </div>
        </div>

        <div class="stats">
          <div class="stat">
            <div class="k">Строк в файле</div>
            <div class="v">{{ result.rows_total_in_file }}</div>
          </div>
          <div class="stat">
            <div class="k">Распознано строк</div>
            <div class="v">{{ result.rows_parsed }}</div>
          </div>
          <div class="stat">
            <div class="k">Уникальных штрихкодов</div>
            <div class="v">{{ result.unique_barcodes }}</div>
          </div>

          <div class="stat ok">
            <div class="k">Добавлено</div>
            <div class="v">{{ result.inserted }}</div>
          </div>
          <div class="stat warn">
            <div class="k">Обновлено</div>
            <div class="v">{{ result.updated }}</div>
          </div>
          <div class="stat">
            <div class="k">Без изменений</div>
            <div class="v">{{ result.unchanged }}</div>
          </div>
        </div>

        <div class="split">
          <div class="box">
            <div class="box-title">Превью операций</div>
            <div class="table">
              <div class="tr th">
                <div>Штрихкод</div>
                <div>Мин. остаток</div>
                <div>Действие</div>
              </div>
              <div class="tr" v-for="(r, i) in result.preview" :key="i">
                <div class="mono">{{ r.barcode }}</div>
                <div>{{ r.min_stock }}</div>
                <div>
                  <span class="pill" :class="r.action">{{ actionLabel(r.action) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="box" v-if="result.invalid_preview?.length">
            <div class="box-title">Ошибки (первые)</div>
            <div class="bad">
              <div class="bad-row" v-for="(b, i) in result.invalid_preview" :key="i">
                <div class="b1">Строка {{ b.row }}</div>
                <div class="b2">
                  <span v-if="b.barcode" class="mono">{{ b.barcode }}</span>
                  <span class="muted" v-else>—</span>
                </div>
                <div class="b3">{{ b.error }}</div>
              </div>
            </div>
          </div>

          <div class="box" v-else>
            <div class="box-title">Ошибки</div>
            <div class="muted">Нет ошибок 🎉</div>
          </div>
        </div>
      </section>

      <footer class="foot muted">
        Подсказка: в Excel убедись, что колонка “Штрихкод” сохранена как <b>текст</b>, чтобы не было преобразований.
      </footer>
    </div>

    <div v-if="toast" class="toast">
      <i class="fa-solid fa-check"></i> {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const API_URL = "/api/admin/minstock/import_min_stock.php"; 

const fileInput = ref(null);
const file = ref(null);
const pickedName = ref("");
const isDrag = ref(false);

const dryRun = ref(false);
const loading = ref(false);
const error = ref("");
const result = ref(null);

const toast = ref("");
let toastTimer = null;

function showToast(msg) {
  toast.value = msg;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toast.value = ""), 1600);
}

function openPicker() {
  fileInput.value?.click();
}

function onPick(e) {
  const f = e.target.files?.[0];
  if (!f) return;
  setFile(f);
}

function onDrag(v) {
  isDrag.value = v;
}

function onDrop(e) {
  isDrag.value = false;
  const f = e.dataTransfer?.files?.[0];
  if (!f) return;
  setFile(f);
}

function setFile(f) {
  const ext = (f.name.split(".").pop() || "").toLowerCase();
  if (!["xlsx", "csv"].includes(ext)) {
    showToast("Нужен .xlsx или .csv");
    return;
  }
  if (f.size > 15 * 1024 * 1024) {
    showToast("Файл больше 15MB");
    return;
  }
  file.value = f;
  pickedName.value = f.name;
  error.value = "";
  result.value = null;
}

function clearFile() {
  file.value = null;
  pickedName.value = "";
  if (fileInput.value) fileInput.value.value = "";
}

function actionLabel(a) {
  if (a === "insert") return "добавлено";
  if (a === "update") return "обновлено";
  return "без изменений";
}

async function upload() {
  if (!file.value) return;

  loading.value = true;
  error.value = "";
  result.value = null;

  try {
    const fd = new FormData();
    fd.append("file", file.value);
    fd.append("dry_run", dryRun.value ? "1" : "0");

    const res = await fetch(API_URL, {
      method: "POST",
      body: fd,
    });

    const data = await res.json().catch(() => null);
    if (!res.ok || !data) {
      throw new Error(data?.error || `HTTP ${res.status}`);
    }
    if (!data.success) {
      throw new Error(data.error || "Неизвестная ошибка");
    }

    result.value = data;
    showToast(dryRun.value ? "Проверка выполнена" : "Импорт выполнен");
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    loading.value = false;
  }
}

function downloadTemplate() {
const csv = "\uFEFFШтрихкод;Минимальный остаток\n4607138899795,4607168329149;5\n";
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = "min_stock_template.csv";
  a.click();
  URL.revokeObjectURL(a.href);
  showToast("Шаблон скачан");
}
</script>

<style scoped>
:global(:root) {
  --bg: #f4f6fb;
  --panel: #ffffff;
  --text: #111827;
  --muted: #6b7280;
  --border: #e5e7eb;
  --accent: #0400ff;
  --danger: #dc2626;
  --shadow: 0 14px 32px rgba(15, 23, 42, 0.10);
}

.page {
  min-height: 100vh;
  background: var(--bg);
  padding: 18px;
  color: var(--text);
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}

.wrap {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  gap: 14px;
}

.card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: var(--shadow);
}

.head {
  padding: 14px 16px;
  display: flex;
  gap: 14px;
  align-items: center;
  justify-content: space-between;
}

.title {
  margin: 0;
  font-size: 20px;
  font-weight: 900;
  letter-spacing: 0.2px;
}

.sub {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.35;
  max-width: 720px;
}

.btn {
  height: 42px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: #fff;
  padding: 0 14px;
  cursor: pointer;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 800;
  color: #0f172a;
}

.btn.small {
  height: 36px;
  border-radius: 12px;
  padding: 0 12px;
  font-weight: 800;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.primary {
  border-color: rgba(4,0,255,0.25);
  background: linear-gradient(135deg, rgba(4,0,255,1), rgba(4,0,255,0.78));
  color: #fff;
  box-shadow: 0 12px 26px rgba(4,0,255,0.18);
}

.btn.ghost {
  background: rgba(15,23,42,0.03);
}

.drop {
  padding: 14px;
}

.drop.drag {
  border-color: rgba(4,0,255,0.35);
  box-shadow: 0 0 0 5px rgba(4,0,255,0.10), var(--shadow);
}

.file {
  display: none;
}

.drop-inner {
  display: grid;
  grid-template-columns: 54px 1fr auto;
  gap: 14px;
  align-items: center;
  padding: 12px;
  border: 1px dashed rgba(107,114,128,0.35);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(255,255,255,1), rgba(244,246,251,0.7));
}

.icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: rgba(4,0,255,0.08);
  border: 1px solid rgba(4,0,255,0.15);
  color: rgba(4,0,255,1);
  font-size: 20px;
}

.txt .t1 {
  font-weight: 900;
  font-size: 14px;
}

.txt .t2 {
  margin-top: 4px;
  color: var(--muted);
  font-size: 12px;
}

.link {
  border: 0;
  background: transparent;
  color: var(--accent);
  font-weight: 900;
  cursor: pointer;
  padding: 0;
}

.picked {
  display: grid;
  gap: 8px;
  justify-items: end;
}

.p-name {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 900;
  font-size: 13px;
  padding: 8px 10px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: #fff;
}

.actions {
  margin-top: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.check {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  color: var(--muted);
  font-size: 13px;
  user-select: none;
}

.state {
  padding: 14px 16px;
}

.state.error {
  border-color: rgba(220, 38, 38, 0.25);
}

.st-title {
  font-weight: 900;
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 6px;
}

.st-text {
  color: var(--muted);
  font-size: 13px;
}

.result {
  padding: 14px 16px;
}

.r-top {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 10px;
  border-bottom: 1px dashed var(--border);
}

.r-title {
  font-weight: 900;
  display: inline-flex;
  gap: 10px;
  align-items: center;
}

.badge {
  margin-left: 8px;
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid rgba(4,0,255,0.18);
  background: rgba(4,0,255,0.06);
  color: #0f172a;
}

.r-file {
  color: var(--muted);
  font-size: 12px;
}

.stats {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
}

.stat {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 10px 12px;
  background: #fff;
}

.stat .k {
  color: var(--muted);
  font-size: 12px;
}

.stat .v {
  margin-top: 6px;
  font-size: 18px;
  font-weight: 900;
}

.stat.ok {
  border-color: rgba(22,163,74,0.25);
  background: rgba(22,163,74,0.04);
}

.stat.warn {
  border-color: rgba(234,179,8,0.25);
  background: rgba(234,179,8,0.05);
}

.split {
  margin-top: 12px;
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 12px;
}

.box {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 12px;
  background: #fff;
}

.box-title {
  font-weight: 900;
  margin-bottom: 10px;
}

.table {
  display: grid;
  gap: 8px;
}

.tr {
  display: grid;
  grid-template-columns: 1.6fr 0.8fr 0.8fr;
  gap: 10px;
  align-items: center;
  padding: 10px;
  border: 1px solid rgba(229,231,235,0.9);
  border-radius: 12px;
}

.tr.th {
  background: rgba(15,23,42,0.03);
  font-weight: 900;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid var(--border);
}

.pill.insert {
  border-color: rgba(22,163,74,0.25);
  background: rgba(22,163,74,0.08);
}

.pill.update {
  border-color: rgba(234,179,8,0.25);
  background: rgba(234,179,8,0.10);
}

.pill.skip {
  border-color: rgba(107,114,128,0.25);
  background: rgba(107,114,128,0.08);
}

.bad {
  display: grid;
  gap: 8px;
}

.bad-row {
  display: grid;
  grid-template-columns: 90px 1fr 1.6fr;
  gap: 10px;
  padding: 10px;
  border-radius: 12px;
  border: 1px solid rgba(220,38,38,0.18);
  background: rgba(220,38,38,0.04);
}

.b1 { font-weight: 900; }
.b2 { color: #0f172a; }
.b3 { color: var(--muted); }

.muted {
  color: var(--muted);
}

.foot {
  text-align: center;
  font-size: 12px;
  padding: 6px 0;
}

.toast {
  position: fixed;
  right: 16px;
  bottom: 16px;
  background: rgba(15, 23, 42, 0.92);
  color: #fff;
  padding: 12px 14px;
  border-radius: 14px;
  display: flex;
  gap: 10px;
  align-items: center;
  box-shadow: 0 18px 40px rgba(0,0,0,0.25);
  z-index: 9999;
  font-weight: 800;
  font-size: 13px;
}
</style>
