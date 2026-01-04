<template>
  <div class="wrap">
    <div class="head">
      <div>
        <h1 class="h">Размеры этикеток</h1>
        <div class="sub">
          Добавляй новые размеры - печать автоматически подстроится (print и bulk).
        </div>
      </div>

      <button class="btn" @click="load">
        <Fa :icon="['fas','rotate-right']" />
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
              <Fa :icon="['fas','check']" />
              {{ form.id ? "Сохранить" : "Добавить" }}
            </button>

            <button class="btn ghost" @click="reset">
              <Fa :icon="['fas','xmark']" />
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
                <Fa :icon="['fas','pen']" />
              </button>
              <button class="icon del" @click="del(it)" title="Удалить">
                <Fa :icon="['fas','trash']" />
              </button>
            </div>
          </div>

          <div v-if="!items.length" class="empty">
            Пока нет размеров. Добавь 42x25 и 30x20 (или нажми обновить - они создаются автоматически).
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
   LABEL SIZES ADMIN - style under global :root tokens
   ========================================================= */

.wrap{
  max-width: 1240px;
  margin: 0 auto;
  padding: 18px;

  color: var(--text-main);
  background: var(--bg-main);
}

/* ---------- header ---------- */
.head{
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.h{
  margin: 0;
  font-size: 24px;
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

.sub{
  margin-top: 7px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-muted);
  max-width: 720px;
}

/* ---------- grid ---------- */
.grid{
  display: grid;
  grid-template-columns: minmax(320px, 460px) 1fr;
  gap: 14px;
  align-items: start;
}

/* ---------- cards ---------- */
.card{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 16px;
  box-shadow: var(--shadow-sm);
  min-width: 0;
}

.cardtitle{
  font-weight: 900;
  letter-spacing: -0.01em;
  color: var(--text-main);
  margin-bottom: 12px;
}

/* ---------- form ---------- */
.form{
  display: grid;
  gap: 10px;
}

.field{
  display: grid;
  gap: 6px;
}

.label{
  font-size: 13px;
  font-weight: 800;
  color: var(--text-muted);
}

.field input,
.select{
  width: 100%;
  height: 44px;
  padding: 0 12px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: #fff;
  color: var(--text-main);
  outline: none;

  /* ✅ iOS zoom fix */
  font-size: 16px;

  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.field input::placeholder{
  color: color-mix(in srgb, var(--text-muted) 70%, transparent);
}

.field input:focus,
.select:focus{
  border-color: color-mix(in srgb, var(--accent) 45%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 14%, transparent);
  background: #fff;
}

.hint{
  font-size: 12px;
  color: var(--text-muted);
}
.hint b{ color: var(--text-main); }

/* row 2 inputs */
.row{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* ---------- buttons ---------- */
.actions{
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 2px;
}

.btn{
  height: 40px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: #fff;
  color: var(--text-main);

  padding: 0 12px;
  font-weight: 900;
  cursor: pointer;

  display: inline-flex;
  gap: 10px;
  align-items: center;

  transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease, opacity .12s ease;
  box-shadow: var(--shadow-sm);
}

.btn:hover{
  background: var(--bg-soft);
}

.btn:active{
  transform: translateY(1px);
}

.btn.primary{
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border-soft));
  background: var(--accent);
  color: #fff;
  box-shadow: 0 12px 26px color-mix(in srgb, var(--accent) 22%, transparent);
}

.btn.primary:hover{
  filter: brightness(1.02);
}

.btn.ghost{
  background: transparent;
  box-shadow: none;
}

/* ---------- message ---------- */
.msg{
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  font-weight: 900;
  background: var(--bg-soft);
  color: var(--text-main);
}

.msg.success{
  border-color: color-mix(in srgb, var(--accent-2) 35%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 12%, var(--bg-panel));
}

.msg.error{
  border-color: color-mix(in srgb, var(--accent-danger) 35%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
}

/* ---------- list ---------- */
.list{
  display: grid;
  gap: 10px;
}

.rowitem{
  display: grid;
  grid-template-columns: 150px 1fr 130px 110px;
  gap: 10px;
  align-items: center;

  padding: 10px 12px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}

.headrow{
  background: #fff;
  border-style: dashed;
  color: var(--text-muted);
  font-weight: 900;
}

.mono{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  letter-spacing: 0.2px;
}

.acts{
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

/* icon buttons */
.icon{
  width: 42px;
  height: 42px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;

  display: grid;
  place-items: center;

  transition: background .12s ease, border-color .12s ease, transform .12s ease;
}

.icon:hover{
  background: var(--bg-soft);
  border-color: color-mix(in srgb, var(--text-main) 12%, var(--border-soft));
}

.icon:active{
  transform: translateY(1px);
}

.icon.edit{ color: var(--accent); }
.icon.del{ color: var(--accent-danger); }

.empty{
  padding: 14px;
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-lg);
  color: var(--text-muted);
  font-weight: 800;
  background: var(--bg-soft);
  line-height: 1.3;
}

/* ---------- responsive ---------- */
@media (max-width: 980px){
  .grid{ grid-template-columns: 1fr; }
}

@media (max-width: 520px){
  .wrap{ padding: 12px; }

  .head{
    flex-direction: column;
    align-items: flex-start;
  }

  .btn{
    width: 100%;
    justify-content: center;
  }

  .actions .btn{
    width: 100%;
    justify-content: center;
  }

  .row{ grid-template-columns: 1fr; }

  .rowitem{
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .acts{ justify-content: flex-start; }
}
</style>


