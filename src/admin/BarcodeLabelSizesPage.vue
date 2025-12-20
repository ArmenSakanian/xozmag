<template>
  <div class="wrap">
    <div class="head">
      <div>
        <h1 class="h">Размеры этикеток</h1>
        <div class="sub">
          Добавляй новые размеры — печать автоматически подстроится (print и bulk).
        </div>
      </div>

      <button class="btn" @click="load">
        <i class="fa-solid fa-rotate-right"></i>
        Обновить
      </button>
    </div>

    <div class="grid">
      <!-- form -->
      <div class="card">
        <div class="cardtitle">{{ form.id ? "Редактирование" : "Добавить размер" }}</div>

        <div class="form">
          <label class="field">
            <span class="label">Значение (ключ)</span>
            <input v-model="form.value" placeholder="Например: 42x25" />
            <div class="hint">Формат: <b>ширинаxвысота</b> (мм)</div>
          </label>

          <label class="field">
            <span class="label">Название</span>
            <input v-model="form.text" placeholder="Например: 42 × 25 мм" />
          </label>

          <div class="row">
            <label class="field">
              <span class="label">Ширина (мм)</span>
              <input v-model="form.width_mm" type="number" step="0.1" />
            </label>

            <label class="field">
              <span class="label">Высота (мм)</span>
              <input v-model="form.height_mm" type="number" step="0.1" />
            </label>
          </div>

          <label class="field">
            <span class="label">Ориентация</span>
            <select v-model="form.orientation" class="select">
              <option value="L">L (альбом)</option>
              <option value="P">P (портрет)</option>
            </select>
          </label>

          <div class="actions">
            <button class="btn primary" @click="save">
              <i class="fa-solid fa-check"></i>
              {{ form.id ? "Сохранить" : "Добавить" }}
            </button>

            <button class="btn ghost" @click="reset">
              <i class="fa-solid fa-xmark"></i>
              Сброс
            </button>
          </div>

          <div v-if="msg" class="msg" :class="msgType">{{ msg }}</div>
        </div>
      </div>

      <!-- list -->
      <div class="card">
        <div class="cardtitle">Список размеров</div>

        <div class="list">
          <div class="rowitem headrow">
            <div>Ключ</div>
            <div>Название</div>
            <div>Ш × В</div>
            <div></div>
          </div>

          <div v-for="it in items" :key="it.id" class="rowitem">
            <div class="mono">{{ it.value }}</div>
            <div>{{ it.text }}</div>
            <div class="mono">{{ it.width_mm }} × {{ it.height_mm }}</div>

            <div class="acts">
              <button class="icon edit" @click="edit(it)" title="Редактировать">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="icon del" @click="del(it)" title="Удалить">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>

          <div v-if="!items.length" class="empty">
            Пока нет размеров. Добавь 42x25 и 30x20 (или нажми обновить — они создаются автоматически).
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const items = ref([]);

const form = ref({
  id: null,
  value: "42x25",
  text: "42 × 25 мм",
  width_mm: 42,
  height_mm: 25,
  orientation: "L",
});

const msg = ref("");
const msgType = ref("info");

function toast(t, type = "info") {
  msg.value = t;
  msgType.value = type;
  setTimeout(() => (msg.value = ""), 2500);
}

function reset() {
  form.value = {
    id: null,
    value: "42x25",
    text: "42 × 25 мм",
    width_mm: 42,
    height_mm: 25,
    orientation: "L",
  };
}

async function load() {
  const r = await fetch("/api/barcode/label_sizes_get.php");
  const d = await r.json();
  if (d?.status === "success") items.value = d.items || [];
}

function edit(it) {
  form.value = {
    id: Number(it.id),
    value: String(it.value),
    text: String(it.text),
    width_mm: Number(it.width_mm),
    height_mm: Number(it.height_mm),
    orientation: String(it.orientation || "L"),
  };
  window.scrollTo({ top: 0, behavior: "smooth" });
}

async function save() {
  const payload = { ...form.value };

  const r = await fetch("/api/barcode/label_sizes_upsert.php", {
    method: "POST",
    headers: { "Content-Type": "application/json; charset=utf-8" },
    body: JSON.stringify(payload),
  });

  const d = await r.json().catch(() => null);

  if (d?.status === "success") {
    toast("Сохранено", "success");
    reset();
    await load();
  } else {
    toast(d?.msg || "Ошибка сохранения", "error");
  }
}

async function del(it) {
  if (!confirm(`Удалить размер ${it.value}?`)) return;

  const r = await fetch("/api/barcode/label_sizes_delete.php?id=" + it.id);
  const d = await r.json().catch(() => null);

  if (d?.status === "success") {
    toast("Удалено", "success");
    await load();
  } else {
    toast("Ошибка удаления", "error");
  }
}

onMounted(load);
</script>

<style scoped>
/* =========================================================
   LABEL SIZES ADMIN — CONTRAST DARK UI (same style as barcodes)
   ========================================================= */

.wrap {
  --bg: #0b1220;
  --panel: #0f1a2b;
  --stroke: rgba(148, 163, 184, 0.18);
  --stroke-2: rgba(148, 163, 184, 0.28);

  --txt: rgba(255, 255, 255, 0.92);
  --muted: rgba(255, 255, 255, 0.62);
  --muted2: rgba(255, 255, 255, 0.48);

  --brand: #4f7cff;
  --brand2: #77a1ff;

  --ok: #10b981;
  --bad: #ef4444;

  --shadow: 0 14px 40px rgba(0, 0, 0, 0.38);
  --shadow-soft: 0 10px 28px rgba(0, 0, 0, 0.28);

  --r16: 16px;
  --r18: 18px;
  --r20: 20px;

  max-width: 1240px;
  margin: 0 auto;
  padding: 18px;

  color: var(--txt);
  background:
    radial-gradient(1200px 800px at 12% -10%, rgba(79,124,255,0.18), transparent 55%),
    radial-gradient(1200px 800px at 115% 10%, rgba(16,185,129,0.12), transparent 55%),
    linear-gradient(180deg, rgba(255,255,255,0.02), transparent 55%),
    var(--bg);
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 22px;
  box-shadow: var(--shadow-soft);
}

/* ---------- header ---------- */
.head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  padding: 6px 2px 2px;
}

.h {
  margin: 0;
  font-size: 24px;
  line-height: 1.1;
  font-weight: 950;
  letter-spacing: 0.2px;
  color: var(--txt);
}

.sub {
  margin-top: 7px;
  font-size: 13px;
  font-weight: 800;
  color: var(--muted);
}

/* ---------- grid ---------- */
.grid {
  display: grid;
  grid-template-columns: minmax(320px, 440px) 1fr;
  gap: 14px;
  align-items: start;
}

/* ---------- cards ---------- */
.card {
  background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent 50%), var(--panel);
  border: 1px solid var(--stroke);
  border-radius: var(--r20);
  padding: 16px;
  box-shadow: var(--shadow-soft);
  min-width: 0;
}

.cardtitle {
  font-weight: 950;
  letter-spacing: 0.2px;
  color: rgba(255,255,255,0.88);
  margin-bottom: 12px;
}

/* ---------- form ---------- */
.form {
  display: grid;
  gap: 10px;
}

.field {
  display: grid;
  gap: 6px;
}

.label {
  font-size: 13px;
  font-weight: 900;
  color: var(--muted);
}

.field input,
.select {
  width: 100%;
  padding: 12px 12px;
  border-radius: 14px;
  border: 1px solid var(--stroke);
  background: linear-gradient(180deg, rgba(255,255,255,0.04), transparent 35%), rgba(0,0,0,0.22);
  color: var(--txt);
  outline: none;
  transition: 0.15s ease;
}

.field input::placeholder {
  color: rgba(255,255,255,0.35);
}

.field input:focus,
.select:focus {
  border-color: rgba(79,124,255,0.65);
  box-shadow: 0 0 0 4px rgba(79,124,255,0.18);
  background: rgba(0,0,0,0.28);
}

.hint {
  font-size: 12px;
  color: var(--muted2);
}
.hint b { color: rgba(255,255,255,0.86); }

/* row 2 inputs */
.row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* ---------- buttons ---------- */
.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 2px;
}

.btn {
  border: 1px solid var(--stroke);
  background: rgba(255,255,255,0.04);
  color: var(--txt);
  border-radius: 14px;
  padding: 11px 14px;
  font-weight: 950;
  cursor: pointer;
  display: inline-flex;
  gap: 8px;
  align-items: center;
  transition: 0.15s ease;
  user-select: none;
  box-shadow: 0 8px 18px rgba(0,0,0,0.18);
}

.btn:hover {
  transform: translateY(-1px);
  border-color: rgba(255,255,255,0.22);
  background: rgba(255,255,255,0.06);
}

.btn:active { transform: translateY(0px); }

.btn.primary {
  background: linear-gradient(180deg, rgba(255,255,255,0.10), transparent 40%), var(--brand);
  border-color: rgba(79,124,255,0.75);
  color: #0b1220;
  box-shadow: 0 14px 28px rgba(79,124,255,0.28);
}

.btn.primary:hover {
  filter: brightness(1.06);
  border-color: rgba(119,161,255,0.9);
}

.btn.ghost { background: transparent; }

/* ---------- message ---------- */
.msg {
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.12);
  font-weight: 950;
  background: rgba(10,14,22,0.70);
  color: rgba(255,255,255,0.92);
}

.msg.success {
  border-color: rgba(16,185,129,0.42);
  background: rgba(16,185,129,0.14);
  color: rgba(255,255,255,0.92);
}

.msg.error {
  border-color: rgba(239,68,68,0.42);
  background: rgba(239,68,68,0.14);
  color: rgba(255,255,255,0.92);
}

/* ---------- list ---------- */
.list {
  display: grid;
  gap: 10px;
}

.rowitem {
  display: grid;
  grid-template-columns: 150px 1fr 130px 110px;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(0,0,0,0.16);
}

.headrow {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.10);
  color: var(--muted);
  font-weight: 950;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  letter-spacing: 0.2px;
}

.acts {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

/* icon buttons */
.icon {
  width: 42px;
  height: 42px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(0,0,0,0.20);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: 0.15s ease;
  color: rgba(255,255,255,0.88);
  box-shadow: 0 10px 22px rgba(0,0,0,0.20);
}

.icon:hover {
  border-color: rgba(255,255,255,0.22);
  background: rgba(255,255,255,0.06);
}

.icon.edit { color: rgba(119,161,255,0.95); }
.icon.del  { color: rgba(255,120,120,0.92); }

.empty {
  padding: 14px;
  border: 1px dashed rgba(255,255,255,0.18);
  border-radius: 16px;
  color: var(--muted);
  font-weight: 900;
  background: rgba(0,0,0,0.12);
  line-height: 1.3;
}

/* ---------- responsive ---------- */
@media (max-width: 980px) {
  .grid { grid-template-columns: 1fr; }
}

@media (max-width: 520px) {
  .wrap { padding: 12px; border-radius: 18px; }
  .card { padding: 14px; border-radius: 18px; }
  .btn { width: 100%; justify-content: center; }
  .actions .btn { width: 100%; }
  .row { grid-template-columns: 1fr; }

  .rowitem {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  .acts { justify-content: flex-start; }
}
</style>

