<template>
  <div class="convert-root">
    <div class="convert-wrap">
      <header class="page-head">
        <div class="page-title">
          <div class="t1">Админ • Сервисные функции</div>
          <div class="t2">Конвертер → Синхронизация → YML + Импорт “Мин. остаток” + Cron</div>
        </div>

        <div class="page-badges">
          <span class="badge" :class="busy ? 'warn' : 'ok'">
            <Fa :icon="['fas', busy ? 'circle-notch' : 'circle-check']" />
            {{ busy ? "Занято" : "Готово" }}
          </span>
          <span class="badge ghost">
            <Fa :icon="['fas', 'shield-halved']" />
            Admin only
          </span>
        </div>
      </header>

      <div class="panels">
        <!-- ================= PANEL 1: CONVERTER ================= -->
        <section class="panel">
          <div class="panel-head">
            <div class="panel-ico accent">
              <Fa :icon="['far', 'image']" />

            </div>
            <div class="panel-head-txt">
              <h1 class="title">Конвертер изображений</h1>
              <p class="subtitle">.jpg, .png, .jpeg → WEBP</p>
            </div>
          </div>

          <div class="toolbar">
            <label class="check">
              <input type="checkbox" v-model="convertDryRun" :disabled="busy" />
              Проверить без записи (dry-run)
            </label>

            <button class="btn primary" :disabled="busy" @click="startConvert">
              <Fa v-if="!loadingConvert" :icon="['fas', 'play']" />
              <Fa v-else :icon="['fas', 'circle-notch']" />
              <span>{{ loadingConvert ? "Обработка…" : convertDryRun ? "Проверить" : "Начать" }}</span>
            </button>
          </div>

          <!-- PROGRESS -->
          <div class="progress-card">
            <div class="progress-row">
              <div class="progress-label">
                <span class="k">Конверсия</span>
                <span class="v">{{ convert.current }} / {{ convert.total }}</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill" :style="{ width: convertPercent + '%' }"></div>
              </div>
            </div>

            <div class="progress-row">
              <div class="progress-label">
                <span class="k">Очистка</span>
                <span class="v">{{ remove.current }} / {{ remove.total }}</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill danger" :style="{ width: removePercent + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- LOG -->
          <div class="log-top">
            <div class="log-title">
              <span>Лог</span>
              <button class="btn ghost tiny" :disabled="!logs.length" @click="copyLines(logs)">
                <Fa :icon="['far', 'copy']" /> Копировать
              </button>
            </div>
          </div>

          <div class="log-box">
            <div v-if="!logs.length" class="log-empty">Лог пуст - нажми “Начать”.</div>
            <div v-for="(l, i) in logs" :key="i" class="log-line">{{ l }}</div>
          </div>

          <div class="panel-foot muted">
            Подсказка: dry-run проверит, что будет сделано, но не запишет файлы.
          </div>
        </section>

        <!-- ================= PANEL 2: SYNC ================= -->
        <section class="panel">
          <div class="panel-head">
            <div class="panel-ico accent2">
              <Fa :icon="['fas', 'arrows-rotate']" />
            </div>
            <div class="panel-head-txt">
              <h1 class="title">Синхронизация Evotor</h1>
              <p class="subtitle">Обновление товаров в базе</p>
            </div>
          </div>

          <div class="toolbar">
            <button class="btn accent2" :disabled="busy" @click="startSync">
              <Fa v-if="!loadingSync" :icon="['fas', 'bolt']" />
              <Fa v-else :icon="['fas', 'circle-notch']" />
              <span>{{ loadingSync ? "Синхронизация…" : "Запустить" }}</span>
            </button>

            <div class="status" v-if="sync.status">
              <span class="pill" :class="sync.status">
                <Fa :icon="pillIcon(sync.status)" />
                {{ sync.statusText }}
              </span>
              <span class="muted" v-if="sync.finishedAt">• {{ sync.finishedAt }}</span>
            </div>
          </div>

          <div class="stats-grid" v-if="sync.hasResult">
            <div class="stat">
              <div class="stat-label">Добавлено</div>
              <div class="stat-val ok">{{ sync.inserted }}</div>
            </div>
            <div class="stat">
              <div class="stat-label">Обновлено</div>
              <div class="stat-val">{{ sync.updated }}</div>
            </div>
            <div class="stat">
              <div class="stat-label">Удалено</div>
              <div class="stat-val danger">{{ sync.deleted }}</div>
            </div>
          </div>

          <!-- CHANGES -->
          <div class="changes" v-if="sync.hasResult">
            <div class="change-block" v-if="createdItems.length">
              <div class="change-title add">
                <span>Добавлено ({{ sync.inserted }})</span>
                <span class="muted">• показано: {{ createdItems.length }}</span>
              </div>
              <div class="change-list">
                <div class="change-line" v-for="(it, i) in createdItems" :key="'c' + i">
                  <span class="bc">{{ it.barcode }}</span>
                  <span class="nm">{{ it.name }}</span>
                </div>
              </div>
            </div>

            <div class="change-block" v-if="updatedItems.length">
              <div class="change-title upd">
                <span>Обновлено ({{ sync.updated }})</span>
                <span class="muted">• показано: {{ updatedItems.length }}</span>
              </div>
              <div class="change-list">
                <div class="change-line" v-for="(it, i) in updatedItems" :key="'u' + i">
                  <span class="bc">{{ it.barcode }}</span>
                  <span class="nm">
                    <span class="nm-title">{{ it.name }}</span>
                    <span class="nm-meta" v-if="it.fields?.length">изменено: {{ it.fields.join(", ") }}</span>
                  </span>
                </div>
              </div>
            </div>

            <div class="change-block" v-if="deletedItems.length">
              <div class="change-title del">
                <span>Удалено ({{ sync.deleted }})</span>
                <span class="muted">• показано: {{ deletedItems.length }}</span>
              </div>
              <div class="change-list">
                <div class="change-line" v-for="(it, i) in deletedItems" :key="'d' + i">
                  <span class="bc">{{ it.barcode }}</span>
                  <span class="nm">
                    <span class="nm-title">{{ it.name }}</span>
                    <span class="nm-meta">
                      фото: удалено
                      {{
                        it.photos_deleted_count != null
                          ? it.photos_deleted_count
                          : it.photos_deleted?.length || 0
                      }},
                      не найдено
                      {{
                        it.photos_missing_count != null
                          ? it.photos_missing_count
                          : it.photos_missing?.length || 0
                      }}
                    </span>
                  </span>
                </div>
              </div>
            </div>

            <div class="hint" v-if="truncated">⚠ Показаны не все строки (ограничение).</div>
          </div>

          <!-- LOG -->
          <div class="log-top">
            <div class="log-title">
              <span>Лог</span>
              <button class="btn ghost tiny" :disabled="!syncLogs.length" @click="copyLines(syncLogs)">
                <Fa :icon="['far', 'copy']" /> Копировать
              </button>
            </div>
          </div>

          <div class="log-box sync-log">
            <div v-if="!syncLogs.length" class="log-empty">Лог пуст - нажми “Запустить”.</div>
            <div v-for="(l, i) in syncLogs" :key="i" class="log-line">{{ l }}</div>
          </div>
        </section>

        <!-- ================= PANEL 3: YML ================= -->
        <section class="panel">
          <div class="panel-head">
            <div class="panel-ico accent">
              <Fa :icon="['fas', 'file-code']" />
            </div>
            <div class="panel-head-txt">
              <h1 class="title">YML-фид</h1>
              <p class="subtitle">Генерация <b>/yml.xml</b> и <b>/yml.xml.gz</b></p>
            </div>
          </div>

          <div class="toolbar">
            <button class="btn primary" :disabled="busy" @click="startYml">
              <Fa v-if="!loadingYml" :icon="['fas', 'wand-magic-sparkles']" />
              <Fa v-else :icon="['fas', 'circle-notch']" />
              <span>{{ loadingYml ? "Генерация…" : "Сгенерировать" }}</span>
            </button>

            <div class="status" v-if="yml.status">
              <span class="pill" :class="yml.status">
                <Fa :icon="pillIcon(yml.status)" />
                {{ yml.statusText }}
              </span>
              <span class="muted" v-if="yml.finishedAt">• {{ yml.finishedAt }}</span>
            </div>
          </div>

          <div class="hint" v-if="yml.ok">
            Готовые файлы:
            <a class="yml-link" href="/yml.xml" target="_blank" rel="noopener">yml.xml</a>
            •
            <a class="yml-link" href="/yml.xml.gz" target="_blank" rel="noopener">yml.xml.gz</a>
          </div>

          <div class="log-top">
            <div class="log-title">
              <span>Лог</span>
              <button class="btn ghost tiny" :disabled="!ymlLogs.length" @click="copyLines(ymlLogs)">
                <Fa :icon="['far', 'copy']" /> Копировать
              </button>
            </div>
          </div>

          <div class="log-box sync-log">
            <div v-if="!ymlLogs.length" class="log-empty">Лог пуст - нажми “Сгенерировать”.</div>
            <div v-for="(l, i) in ymlLogs" :key="i" class="log-line">{{ l }}</div>
          </div>

        </section>

        <!-- ================= PANEL 4: MIN STOCK IMPORT ================= -->
        <section class="panel">
          <div class="panel-head">
            <div class="panel-ico accent2">
              <Fa :icon="['fas', 'file-arrow-up']" />
            </div>
            <div class="panel-head-txt">
              <h1 class="title">Импорт “Минимальный остаток”</h1>
              <p class="subtitle">
              CSV:Штрихкод + Минимальный остаток
              </p>
            </div>
          </div>

          <div class="toolbar center">
            <button class="btn ghost" :disabled="busy" @click="downloadMinTemplate">
              <Fa :icon="['far', 'file-lines']" />
              Скачать шаблон (CSV)
            </button>
          </div>

          <div
            class="drop"
            :class="{ drag: minIsDrag }"
            @dragenter.prevent="onMinDrag(true)"
            @dragover.prevent
            @dragleave.prevent="onMinDrag(false)"
            @drop.prevent="onMinDrop"
          >
            <input ref="minFileInput" class="min-file" type="file" accept=".xlsx,.csv" @change="onMinPick" />

            <div class="drop-inner">
              <div class="drop-ico"><Fa :icon="['fas', 'cloud-arrow-up']" /></div>

              <div class="drop-txt">
                <div class="drop-t1">
                  Перетащи файл сюда или
                  <button class="link" :disabled="busy" @click="openMinPicker">выбери</button>
                </div>
                <div class="drop-t2">.xlsx / .csv - максимум 15MB</div>
              </div>

              <div class="picked" v-if="minPickedName">
                <div class="picked-name">
                  <Fa :icon="['far', 'file-excel']" />
                  {{ minPickedName }}
                </div>
                <button class="btn ghost tiny" :disabled="busy" @click="clearMinFile">
                  <Fa :icon="['fas', 'xmark']" /> Убрать
                </button>
              </div>
            </div>

            <div class="toolbar">
              <label class="check">
                <input type="checkbox" v-model="minDryRun" :disabled="busy" />
                Проверить без записи (dry-run)
              </label>

              <button class="btn primary" :disabled="!minFile || busy" @click="uploadMin">
                <Fa v-if="!loadingMin" :icon="['fas', 'upload']" />
                <Fa v-else :icon="['fas', 'circle-notch']" />
                <span>{{ minDryRun ? "Проверить" : "Импортировать" }}</span>
              </button>
            </div>
          </div>

          <div v-if="minError" class="state error">
            <div class="state-title">
              <Fa :icon="['fas', 'triangle-exclamation']" />
              Ошибка
            </div>
            <div class="state-text">{{ minError }}</div>
          </div>

          <div v-if="minResult" class="result">
            <div class="result-top">
              <div class="result-title">
                <Fa :icon="['fas', 'circle-check']" />
                Готово
                <span v-if="minResult.dry_run" class="badge-inline">dry-run</span>
              </div>
              <div class="result-file">
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

                  <div class="min-tr" v-for="(r, i) in minResult.preview || []" :key="i">
                    <div class="mono">{{ r.barcode }}</div>
                    <div>{{ r.min_stock }}</div>
                    <div>
                      <span class="min-pill" :class="r.action">{{ minActionLabel(r.action) }}</span>
                    </div>
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
                      <span class="muted" v-else>-</span>
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

            <div class="panel-foot muted">
              Подсказка: колонку “Штрихкод” в Excel ставь как <b>текст</b>.
            </div>
          </div>
        </section>

        <!-- ================= PANEL 5: CRON ================= -->
        <section class="panel">
          <div class="panel-head">
            <div class="panel-ico cron">
              <Fa :icon="['fas', 'clock']" />
            </div>
            <div class="panel-head-txt">
              <h1 class="title">Автозапуск (Cron)</h1>
              <p class="subtitle">Convert → Sync → YML по расписанию</p>
            </div>
          </div>

          <div class="cron-box">
            <div class="cron-row">
              <label class="switch">
                <input type="checkbox" v-model="cronEnabled" :disabled="loadingCron || busy" />
                <span class="slider"></span>
              </label>

              <div class="cron-row-txt">
                <div class="cron-k">Включено</div>
                <div class="cron-v muted">
                  Когда включено - tick-скрипт сам запускает пайплайн по интервалу.
                </div>
              </div>
            </div>

            <div class="cron-row">
              <div class="cron-row-txt">
                <div class="cron-k">Интервал</div>
                <div class="cron-v muted">Как часто запускать пайплайн (в минутах)</div>
              </div>

              <select class="select" v-model.number="cronIntervalMin" :disabled="loadingCron || busy">
                <option :value="1">1 мин</option>
                <option :value="2">2 мин</option>
                <option :value="5">5 мин</option>
                <option :value="10">10 мин</option>
                <option :value="20">20 мин</option>
                <option :value="30">30 мин</option>
                <option :value="60">60 мин</option>
              </select>
            </div>

            <div class="cron-actions">
              <button class="btn accent2" :disabled="loadingCron || busy" @click="saveCron">
                <Fa v-if="!loadingCron" :icon="['fas', 'floppy-disk']" />
                <Fa v-else :icon="['fas', 'circle-notch']" />
                Сохранить
              </button>

              <button class="btn primary" :disabled="busy" @click="runCronNow">
                <Fa :icon="['fas', 'play']" />
                Запустить сейчас
              </button>

              <button class="btn ghost" :disabled="loadingCron || busy" @click="loadCron">
                <Fa :icon="['fas', 'rotate']" />
                Обновить статус
              </button>

              <button class="btn ghost" :disabled="loadingCron || busy" @click="loadCronLog">
                <Fa :icon="['fas', 'scroll']" />
                Обновить лог
              </button>
            </div>

            <div class="status" v-if="cronStatusText">
              <span class="pill" :class="cronPillClass">
                <Fa :icon="pillIcon(cronPillClass)" />
                {{ cronStatusText }}
              </span>

              <span class="muted" v-if="cronLastRun">• последний: {{ cronLastRun }}</span>
              <span class="muted" v-if="cronNextRun">• следующий: {{ cronNextRun }}</span>
            </div>
          </div>

          <div class="log-top">
            <div class="log-title">
              <span>Лог Cron</span>
              <button class="btn ghost tiny" :disabled="!cronLogs.length" @click="copyLines(cronLogs)">
                <Fa :icon="['far', 'copy']" /> Копировать
              </button>
            </div>
          </div>

          <div class="log-box sync-log">
            <div v-if="!cronLogs.length" class="log-empty">Лог пуст - нажми “Обновить лог”.</div>
            <div v-for="(l, i) in cronLogs" :key="i" class="log-line">{{ l }}</div>
          </div>

          <div class="panel-foot muted">
            ⚠ Cron в Linux работает минимум раз в минуту. “10 секунд” через cron нельзя - используй “Запустить сейчас”.
          </div>
        </section>
      </div>
    </div>

    <div v-if="toast" class="toast">
      <Fa :icon="toastIcon" />
      {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { FontAwesomeIcon as Fa } from "@fortawesome/vue-fontawesome";

NProgress.configure({ showSpinner: false, trickleSpeed: 120 });

/* =========================
   HELPERS
========================= */
const toast = ref("");
const toastIcon = ref(["fas", "circle-check"]);
let toastTimer = null;

function showToast(msg, icon = ["fas", "circle-check"]) {
  toast.value = msg;
  toastIcon.value = icon;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toast.value = ""), 1600);
}

async function copyLines(arr) {
  try {
    await navigator.clipboard.writeText((arr || []).join("\n"));
    showToast("Скопировано", ["far", "copy"]);
  } catch {
    showToast("Не удалось скопировать", ["fas", "triangle-exclamation"]);
  }
}

function pillIcon(status) {
  if (status === "ok") return ["fas", "circle-check"];
  if (status === "error") return ["fas", "triangle-exclamation"];
  return ["fas", "circle-notch"];
}

async function fetchJson(url, opts = {}) {
  const res = await fetch(url, {
    cache: "no-store",
    credentials: "same-origin",
    ...opts,
  });
  const data = await res.json().catch(() => null);
  if (!res.ok || !data) {
    throw new Error(data?.error || `HTTP ${res.status}`);
  }
  return data;
}

/* =========================
   COMMON LOADERS
========================= */
const loadingConvert = ref(false);
const loadingSync = ref(false);
const loadingYml = ref(false);
const loadingMin = ref(false);
const loadingCron = ref(false);

const busy = computed(
  () =>
    loadingConvert.value ||
    loadingSync.value ||
    loadingYml.value ||
    loadingMin.value ||
    loadingCron.value
);

/* =========================
   PANEL 1: CONVERT
========================= */
const logs = ref([]);
const convert = ref({ current: 0, total: 0 });
const remove = ref({ current: 0, total: 0 });
const convertDryRun = ref(false);

const convertPercent = computed(() =>
  convert.value.total ? Math.round((convert.value.current / convert.value.total) * 100) : 0
);
const removePercent = computed(() =>
  remove.value.total ? Math.round((remove.value.current / remove.value.total) * 100) : 0
);

let convertPollTimer = null;

async function startConvert() {
  if (busy.value) return;

  loadingConvert.value = true;
  logs.value = [];
  convert.value = { current: 0, total: 0 };
  remove.value = { current: 0, total: 0 };
  NProgress.start();

  try {
    const init = await fetchJson(
      `/api/admin/functions/convert_images_step.php?init=1&dry_run=${convertDryRun.value ? 1 : 0}`
    );

    if (init.already_running) logs.value.unshift("⚠ Уже запущено (LOCK). Показываю прогресс…");
    if (init.done) {
      logs.value.unshift("✔ НЕТ ФАЙЛОВ ДЛЯ ОБРАБОТКИ");
      return;
    }

    const token = String(init.token || "");
    convert.value.total = Number(init.total ?? 0);
    remove.value.total = Number(init.total ?? 0);

    logs.value.unshift(`→ init: total=${convert.value.total}, dry_run=${convertDryRun.value ? "1" : "0"}`);

    if (convertPollTimer) clearInterval(convertPollTimer);

    const poll = async () => {
      const st = await fetchJson(`/api/admin/functions/convert_images_step.php?status=1&token=${token}`);
      convert.value.current = Number(st.index ?? 0);
      remove.value.current = Number(st.index ?? 0);
      logs.value = Array.isArray(st.logs) ? st.logs : logs.value;

      NProgress.set(convertPercent.value / 100);

      if (st.done) {
        clearInterval(convertPollTimer);
        convertPollTimer = null;
        showToast("Конвертация завершена");
        NProgress.done();
        loadingConvert.value = false;
      }
    };

    await poll();
    convertPollTimer = setInterval(() => {
      poll().catch((e) => {
        logs.value.unshift("✖ ERROR: " + (e?.message || "Unknown"));
        clearInterval(convertPollTimer);
        convertPollTimer = null;
        NProgress.done();
        loadingConvert.value = false;
      });
    }, 450);
  } catch (e) {
    logs.value.unshift("✖ ERROR: " + (e?.message || "Unknown"));
    showToast("Ошибка конвертации", ["fas", "triangle-exclamation"]);
  } finally {
    if (!convertPollTimer) {
      NProgress.done();
      loadingConvert.value = false;
    }
  }
}

/* =========================
   PANEL 2: SYNC
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

async function startSync() {
  if (busy.value) return;

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
      credentials: "same-origin",
    });

    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      throw new Error("Ответ не JSON: " + text.slice(0, 200));
    }

    if (!res.ok || data?.error || data?.success === false) {
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
    showToast("Синхронизация завершена");
  } catch (e) {
    sync.value.status = "error";
    sync.value.statusText = "Ошибка";
    sync.value.finishedAt = new Date().toLocaleString();
    syncLogs.value.unshift("✖ ERROR: " + (e?.message || "Unknown"));
    showToast("Ошибка синхронизации", ["fas", "triangle-exclamation"]);
  } finally {
    NProgress.done();
    loadingSync.value = false;
  }
}

/* =========================
   PANEL 3: YML
========================= */
const API_YML_URL = "/api/admin/functions/generate_yml.php";

const ymlLogs = ref([]);
const yml = ref({ status: "", statusText: "", finishedAt: "", ok: false });

async function startYml() {
  if (busy.value) return;

  loadingYml.value = true;
  ymlLogs.value = [];
  yml.value = { status: "run", statusText: "Выполняется…", finishedAt: "", ok: false };
  NProgress.start();

  try {
    ymlLogs.value.unshift(`→ Запуск: ${API_YML_URL}`);

    const res = await fetch(API_YML_URL, {
      method: "GET",
      cache: "no-store",
      credentials: "same-origin",
    });

    const text = await res.text();

    let data = null;
    try {
      data = JSON.parse(text);
    } catch {
      data = null;
    }

    if (data) {
      if (!res.ok || data?.error || data?.success === false) throw new Error(data?.error || `HTTP ${res.status}`);
      const msg = data?.message ? String(data.message) : JSON.stringify(data);
      ymlLogs.value = msg.split(/\r?\n/).filter(Boolean);
    } else {
      if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 200)}`);
      ymlLogs.value = String(text || "OK").split(/\r?\n/).filter(Boolean);
    }

    yml.value.status = "ok";
    yml.value.statusText = "Готово";
    yml.value.finishedAt = new Date().toLocaleString();
    yml.value.ok = true;

    showToast("YML сгенерирован");
  } catch (e) {
    yml.value.status = "error";
    yml.value.statusText = "Ошибка";
    yml.value.finishedAt = new Date().toLocaleString();
    yml.value.ok = false;

    ymlLogs.value.unshift("✖ ERROR: " + (e?.message || "Unknown"));
    showToast("Ошибка генерации YML", ["fas", "triangle-exclamation"]);
  } finally {
    NProgress.done();
    loadingYml.value = false;
  }
}

/* =========================
   PANEL 4: MIN STOCK IMPORT
========================= */
const API_MIN_URL = "/api/admin/functions/import_min_stock.php";

const minFileInput = ref(null);
const minFile = ref(null);
const minPickedName = ref("");
const minIsDrag = ref(false);

const minDryRun = ref(false);
const minError = ref("");
const minResult = ref(null);

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
  if (!["xlsx", "csv"].includes(ext)) return showToast("Нужен .xlsx или .csv", ["fas", "triangle-exclamation"]);
  if (f.size > 15 * 1024 * 1024) return showToast("Файл больше 15MB", ["fas", "triangle-exclamation"]);

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
  if (!minFile.value || busy.value) return;

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
      credentials: "same-origin",
    });

    const data = await res.json().catch(() => null);
    if (!res.ok || !data) throw new Error(data?.error || `HTTP ${res.status}`);
    if (!data.success) throw new Error(data.error || "Неизвестная ошибка");

    minResult.value = data;
    showToast(minDryRun.value ? "Проверка выполнена" : "Импорт выполнен");
  } catch (e) {
    minError.value = e?.message || String(e);
    showToast("Ошибка импорта", ["fas", "triangle-exclamation"]);
  } finally {
    loadingMin.value = false;
  }
}

function downloadMinTemplate() {
  const csv = "\uFEFFШтрихкод;Минимальный остаток\n4607138899795;5\n";
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = "min_stock_template.csv";
  a.click();
  URL.revokeObjectURL(a.href);
  showToast("Шаблон скачан");
}

/* =========================
   PANEL 5: CRON CONTROL
========================= */
const API_CRON_STATUS = "/api/admin/functions/cron_autorun_status.php";
const API_CRON_SAVE = "/api/admin/functions/cron_autorun_save.php";
const API_CRON_LOG = "/api/admin/functions/cron_autorun_log.php?lines=250";
const API_CRON_RUN = "/api/admin/functions/cron_autorun_run_now.php";

const cronEnabled = ref(false);
const cronIntervalMin = ref(10);

const cronLogs = ref([]);
const cronStatusText = ref("");
const cronPillClass = ref("run");
const cronLastRun = ref("");
const cronNextRun = ref("");

async function loadCron() {
  loadingCron.value = true;
  try {
    const data = await fetchJson(API_CRON_STATUS);

    cronEnabled.value = !!data.enabled;
    cronIntervalMin.value = Number(data.interval_minutes ?? 10);

    const running = !!data.running;
    const lastOk = data.last_ok;

    if (running) {
      cronStatusText.value = "Сейчас выполняется…";
      cronPillClass.value = "run";
    } else if (lastOk === true) {
      cronStatusText.value = "Готово";
      cronPillClass.value = "ok";
    } else if (lastOk === false) {
      cronStatusText.value = "Ошибка";
      cronPillClass.value = "error";
    } else {
      cronStatusText.value = cronEnabled.value ? "Включено" : "Выключено";
      cronPillClass.value = cronEnabled.value ? "ok" : "run";
    }

    cronLastRun.value = data.last_run_ts ? new Date(data.last_run_ts * 1000).toLocaleString() : "";
    cronNextRun.value = data.next_run_ts ? new Date(data.next_run_ts * 1000).toLocaleString() : "";
  } catch (e) {
    cronStatusText.value = "Ошибка";
    cronPillClass.value = "error";
    cronLogs.value.unshift("✖ CRON STATUS ERROR: " + (e?.message || "Unknown"));
  } finally {
    loadingCron.value = false;
  }
}

async function saveCron() {
  loadingCron.value = true;
  try {
    const body = {
      enabled: !!cronEnabled.value,
      interval_minutes: Math.max(1, Number(cronIntervalMin.value || 10)),
    };

    const data = await fetchJson(API_CRON_SAVE, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });

    if (!data?.ok) throw new Error(data?.error || "save failed");

    cronLogs.value.unshift("✔ настройки сохранены");
    showToast("Cron сохранён");
    await loadCron();
  } catch (e) {
    cronLogs.value.unshift("✖ SAVE ERROR: " + (e?.message || "Unknown"));
    showToast("Ошибка сохранения", ["fas", "triangle-exclamation"]);
  } finally {
    loadingCron.value = false;
  }
}

async function loadCronLog() {
  loadingCron.value = true;
  try {
    const data = await fetchJson(API_CRON_LOG);
    cronLogs.value = Array.isArray(data.lines) ? data.lines : [];
  } catch (e) {
    cronLogs.value.unshift("✖ LOG ERROR: " + (e?.message || "Unknown"));
    showToast("Ошибка лога", ["fas", "triangle-exclamation"]);
  } finally {
    loadingCron.value = false;
  }
}

async function runCronNow() {
  if (busy.value) return;

  loadingCron.value = true;
  try {
    cronLogs.value.unshift("→ запуск сейчас…");
    const data = await fetchJson(API_CRON_RUN);

    if (data.ok === false && data.error === "already_running") {
      cronLogs.value.unshift("⚠ уже выполняется (LOCK)");
      showToast("Уже выполняется", ["fas", "circle-notch"]);
    } else if (!data.ok) {
      cronLogs.value.unshift("✖ run_now failed: exit=" + data.code);
      if (data.err) cronLogs.value.unshift(String(data.err).slice(0, 300));
      showToast("Ошибка запуска", ["fas", "triangle-exclamation"]);
    } else {
      cronLogs.value.unshift("✔ выполнено");
      showToast("Пайплайн выполнен");
    }

    await loadCronLog();
    await loadCron();
  } catch (e) {
    cronLogs.value.unshift("✖ RUN ERROR: " + (e?.message || "Unknown"));
    showToast("Ошибка запуска", ["fas", "triangle-exclamation"]);
  } finally {
    loadingCron.value = false;
  }
}

onMounted(() => {
  loadCron();
  loadCronLog();
});

onBeforeUnmount(() => {
  if (convertPollTimer) clearInterval(convertPollTimer);
  clearTimeout(toastTimer);
});
</script>

<style scoped>
/* =========================
   ROOT / HEADER
========================= */
.convert-root {
  min-height: 100dvh;
  background: var(--bg-main);
  color: var(--text-main);
  padding: clamp(12px, 2.2vw, 24px);
}

.convert-wrap {
  max-width: 1320px;
  margin: 0 auto;
}

.page-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.page-title .t1 {
  font-weight: 1000;
  font-size: 18px;
  letter-spacing: 0.2px;
}

.page-title .t2 {
  margin-top: 4px;
  color: var(--text-muted);
  font-weight: 800;
  font-size: 12px;
}

.page-badges {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  font-weight: 900;
  font-size: 12px;
}

.badge.ok {
  border-color: color-mix(in srgb, var(--accent-2) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
}

.badge.warn {
  border-color: color-mix(in srgb, var(--accent) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-soft));
}

.badge.ghost {
  opacity: 0.9;
}

.panels {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
  gap: 14px;
}

/* =========================
   PANEL
========================= */
.panel {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 18px;
  display: flex;
  flex-direction: column;
  min-height: 560px;
}

.panel-head {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}

.panel-ico {
  width: 46px;
  height: 46px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  font-size: 18px;
}

.panel-ico.accent {
  color: var(--accent);
  border-color: color-mix(in srgb, var(--accent) 18%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-soft));
}

.panel-ico.accent2 {
  color: var(--accent-2);
  border-color: color-mix(in srgb, var(--accent-2) 18%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
}

.panel-ico.cron {
  color: color-mix(in srgb, var(--accent) 85%, #ffffff);
  border-color: color-mix(in srgb, var(--accent) 18%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 8%, var(--bg-soft));
}

.title {
  margin: 0;
  font-size: 16px;
  font-weight: 1000;
  letter-spacing: 0.2px;
}

.subtitle {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 800;
  line-height: 1.35;
}

.panel-foot {
  margin-top: 10px;
  text-align: center;
  font-weight: 800;
}

/* =========================
   TOOLBAR / BUTTONS / CHECK
========================= */
.toolbar {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  margin: 10px 0 12px;
}

.toolbar.center {
  justify-content: center;
}

.check {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  color: var(--text-muted);
  font-size: 13px;
  user-select: none;
  font-weight: 900;
}

.check input {
  width: 16px;
  height: 16px;
}

.btn {
  min-height: 44px;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  cursor: pointer;
  font-weight: 1000;
  font-size: 13px;
  letter-spacing: 0.2px;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  justify-content: center;
  transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease, opacity 0.12s ease,
    background 0.12s ease, border-color 0.12s ease;
  user-select: none;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  filter: brightness(1.02);
}

.btn:active:not(:disabled) {
  transform: translateY(0px);
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn.primary {
  background: var(--accent);
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.btn.accent2 {
  background: var(--accent-2);
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.btn.ghost {
  background: var(--bg-soft);
  border-color: var(--border-soft);
  color: var(--text-main);
}

.btn.tiny {
  min-height: 34px;
  padding: 6px 10px;
  font-size: 12px;
  border-radius: 12px;
}

/* =========================
   STATUS / PILLS
========================= */
.status {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border-radius: var(--radius-lg);
  font-size: 12px;
  font-weight: 1000;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
}

.pill.run {
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 25%, var(--border-soft));
}

.pill.ok {
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent-2) 25%, var(--border-soft));
}

.pill.error {
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
}

.muted {
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 800;
}

/* =========================
   PROGRESS
========================= */
.progress-card {
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  background: var(--bg-soft);
  padding: 12px;
  display: grid;
  gap: 12px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 6px;
  font-weight: 1000;
}

.progress-track {
  height: 12px;
  background: color-mix(in srgb, var(--bg-panel) 55%, var(--bg-soft));
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--border-soft);
}

.progress-fill {
  height: 100%;
  transition: width 0.18s ease;
  background: linear-gradient(90deg, color-mix(in srgb, var(--accent) 85%, #ffffff), var(--accent));
}

.progress-fill.danger {
  background: linear-gradient(90deg, color-mix(in srgb, var(--accent-danger) 75%, #ffffff), var(--accent-danger));
}

/* =========================
   LOG
========================= */
.log-top {
  margin-top: 12px;
}

.log-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-weight: 1000;
  font-size: 12px;
  color: var(--text-muted);
}

.log-box {
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

.sync-log {
  max-height: 320px;
}

.log-line {
  padding: 3px 0;
  color: color-mix(in srgb, var(--text-main) 85%, var(--text-muted));
}

.log-empty {
  color: var(--text-muted);
  font-weight: 900;
  padding: 6px 0;
}

/* =========================
   SYNC STATS / CHANGES
========================= */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin: 10px 0 10px;
}

.stat {
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  text-align: center;
}

.stat-label {
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 4px;
  font-weight: 1000;
}

.stat-val {
  font-size: 18px;
  font-weight: 1000;
  color: var(--text-main);
}

.stat-val.ok {
  color: color-mix(in srgb, var(--accent-2) 75%, var(--text-main));
}

.stat-val.danger {
  color: color-mix(in srgb, var(--accent-danger) 75%, var(--text-main));
}

.changes {
  margin-top: 10px;
  display: grid;
  gap: 10px;
}

.change-block {
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  border-radius: var(--radius-md);
  padding: 10px;
}

.change-title {
  font-weight: 1000;
  font-size: 12px;
  margin-bottom: 8px;
  color: var(--text-main);
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.change-title.add {
  color: var(--accent-2);
}

.change-title.upd {
  color: var(--accent);
}

.change-title.del {
  color: var(--accent-danger);
}

.change-list {
  max-height: 200px;
  overflow: auto;
  border-radius: 12px;
  background: color-mix(in srgb, var(--bg-panel) 55%, var(--bg-soft));
  padding: 8px;
  border: 1px solid var(--border-soft);
}

.change-line {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 10px;
  padding: 6px 0;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 12px;
  border-bottom: 1px dashed color-mix(in srgb, var(--border-soft) 75%, transparent);
}

.change-line:last-child {
  border-bottom: none;
}

.bc {
  color: var(--text-muted);
}

.nm {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.nm-title {
  color: var(--text-main);
  font-weight: 1000;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.nm-meta {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 900;
}

.hint {
  font-size: 12px;
  color: var(--text-muted);
  text-align: center;
  margin-top: 6px;
  font-weight: 900;
}

/* =========================
   DROPZONE (MIN STOCK)
========================= */
.drop {
  padding: 14px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}

.drop.drag {
  border-color: color-mix(in srgb, var(--accent) 40%, var(--border-soft));
  box-shadow: 0 0 0 5px color-mix(in srgb, var(--accent) 12%, transparent);
}

.min-file {
  display: none;
}

.drop-inner {
  display: grid;
  grid-template-columns: 54px 1fr auto;
  gap: 14px;
  align-items: center;
  padding: 12px;
  border: 1px dashed color-mix(in srgb, var(--border-soft) 85%, var(--text-light));
  border-radius: var(--radius-md);
  background: var(--bg-panel);
}

.drop-ico {
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

.drop-t1 {
  font-weight: 1000;
  font-size: 14px;
  color: var(--text-main);
}

.drop-t2 {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 900;
}

.link {
  border: 0;
  background: transparent;
  color: var(--accent);
  font-weight: 1000;
  cursor: pointer;
  padding: 0;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.picked {
  display: grid;
  gap: 8px;
  justify-items: end;
}

.picked-name {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 1000;
  font-size: 13px;
  padding: 8px 10px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
}

/* states/results */
.state {
  margin-top: 12px;
  border-radius: var(--radius-md);
  padding: 12px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
}

.state.error {
  border-color: color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 8%, var(--bg-panel));
}

.state-title {
  font-weight: 1000;
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 6px;
  color: var(--text-main);
}

.state-text {
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 900;
}

.result {
  margin-top: 12px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  padding: 12px;
  box-shadow: var(--shadow-sm);
}

.result-top {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 10px;
  border-bottom: 1px dashed var(--border-soft);
}

.result-title {
  font-weight: 1000;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  color: var(--text-main);
}

.badge-inline {
  margin-left: 8px;
  font-size: 11px;
  padding: 4px 8px;
  border-radius: var(--radius-lg);
  border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-panel));
  color: var(--accent);
  font-weight: 1000;
}

.result-file {
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 900;
}

.min-stats {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.min-stat {
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  background: var(--bg-soft);
}

.min-stat .k {
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 1000;
}

.min-stat .v {
  margin-top: 6px;
  font-size: 18px;
  font-weight: 1000;
  color: var(--text-main);
}

.min-stat.ok {
  border-color: color-mix(in srgb, var(--accent-2) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
}

.min-stat.warn {
  border-color: rgba(234, 179, 8, 0.35);
  background: rgba(234, 179, 8, 0.1);
}

.min-split {
  margin-top: 12px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.min-box {
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 12px;
  background: var(--bg-soft);
}

.min-box-title {
  font-weight: 1000;
  margin-bottom: 10px;
  color: var(--text-main);
}

.min-table {
  display: grid;
  gap: 8px;
}

.min-tr {
  display: grid;
  grid-template-columns: 1.6fr 0.9fr 0.9fr;
  gap: 10px;
  align-items: center;
  padding: 10px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  background: var(--bg-panel);
  font-weight: 900;
}

.min-tr.th {
  background: color-mix(in srgb, var(--bg-soft) 70%, var(--bg-panel));
  font-weight: 1000;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.min-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: var(--radius-lg);
  font-size: 12px;
  font-weight: 1000;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
}

.min-pill.insert {
  border-color: color-mix(in srgb, var(--accent-2) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-soft));
}

.min-pill.update {
  border-color: rgba(234, 179, 8, 0.35);
  background: rgba(234, 179, 8, 0.12);
}

.min-pill.skip {
  border-color: color-mix(in srgb, var(--text-muted) 25%, var(--border-soft));
  background: color-mix(in srgb, var(--text-muted) 8%, var(--bg-soft));
  color: var(--text-muted);
}

.min-bad {
  display: grid;
  gap: 8px;
}

.min-bad-row {
  display: grid;
  grid-template-columns: 90px 1fr 1.6fr;
  gap: 10px;
  padding: 10px;
  border-radius: var(--radius-md);
  border: 1px solid color-mix(in srgb, var(--accent-danger) 30%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
}

.b1 {
  font-weight: 1000;
  color: var(--text-main);
}

.b2 {
  color: var(--text-main);
  font-weight: 900;
}

.b3 {
  color: var(--text-muted);
  font-weight: 900;
}

/* =========================
   CRON PANEL
========================= */
.cron-box {
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  border-radius: var(--radius-lg);
  padding: 12px;
  display: grid;
  gap: 12px;
}

.cron-row {
  display: grid;
  grid-template-columns: 56px 1fr auto;
  gap: 12px;
  align-items: center;
}

.cron-row-txt .cron-k {
  font-weight: 1000;
  color: var(--text-main);
}

.cron-row-txt .cron-v {
  margin-top: 3px;
}

.select {
  min-height: 44px;
  border-radius: 12px;
  padding: 0 10px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  font-weight: 1000;
}

.cron-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

/* switch */
.switch {
  position: relative;
  width: 54px;
  height: 32px;
  display: inline-block;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  inset: 0;
  cursor: pointer;
  background: color-mix(in srgb, var(--bg-panel) 70%, var(--bg-soft));
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  transition: 0.15s;
}

.slider:before {
  content: "";
  position: absolute;
  height: 24px;
  width: 24px;
  left: 4px;
  top: 3px;
  background: #fff;
  border-radius: var(--radius-lg);
  transition: 0.15s;
  box-shadow: var(--shadow-sm);
}

.switch input:checked + .slider {
  background: color-mix(in srgb, var(--accent-2) 25%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent-2) 35%, var(--border-soft));
}

.switch input:checked + .slider:before {
  transform: translateX(22px);
}

/* =========================
   TOAST
========================= */
.toast {
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
  font-weight: 1000;
  font-size: 13px;
}

/* =========================
   MOBILE
========================= */
@media (max-width: 900px) {
  .panel {
    min-height: auto;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .drop-inner {
    grid-template-columns: 1fr;
  }
  .picked {
    justify-items: start;
  }
  .cron-row {
    grid-template-columns: 56px 1fr;
  }
}

@media (max-width: 520px) {
  .panels {
    grid-template-columns: 1fr;
  }
  .panel {
    padding: 14px;
    border-radius: var(--radius-lg);
  }
  .change-line {
    grid-template-columns: 1fr;
  }
  .nm-title {
    white-space: normal;
  }
  .min-tr {
    grid-template-columns: 1fr;
  }
  .min-bad-row {
    grid-template-columns: 1fr;
  }
  .page-head {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
