<template>
  <div class="admin-page">
    <!-- ===== HEADER ===== -->
    <div class="head-row">
      <h2 class="block-title">Характеристики товаров</h2>
    </div>

    <!-- ===== FORM ===== -->
    <div class="card form-card">
      <h3 class="card-title">
        {{
          editId ? "Редактировать характеристику" : "Добавить характеристику"
        }}
      </h3>

      <!-- NAME -->
      <div class="form-group">
        <label>Название характеристики</label>
        <input v-model="attrName" class="input" placeholder="Например: Длина" />
      </div>
      <!-- UI RENDER -->
      <div class="form-group">
        <label>Отображение</label>
        <select v-model="uiRender" class="input">
          <option value="text">Текст</option>
          <option value="color">Цвет (кружок)</option>
        </select>
      </div>

      <!-- VALUES -->
      <div class="form-group">
        <label>Значения</label>

        <div v-for="(item, i) in values" :key="item.key" class="value-row">
          <!-- текст значения -->
          <input
            v-model="item.value"
            class="input"
            placeholder="Например: 1.5"
          />

          <!-- COLOR META (вставляешь сюда) -->
          <div v-if="uiRender === 'color'" class="color-meta">
<input
  v-model="item.color"
  class="input"
  placeholder="#RRGGBB или rgb(122, 88, 56)"
  @blur="item.color = toHexForPicker(item.color) || item.color.trim()"
/>
<input
  type="color"
  :value="toHexForPicker(item.color) || '#000000'"
  @input="item.color = $event.target.value"
  title="Выбрать цвет"
/>

          </div>

          <!-- delete option -->
          <button
            v-if="item.id"
            class="icon-btn danger"
            title="Удалить значение"
            @click="deleteOption(item.id, i)"
          >
            <Fa :icon="['fas','xmark']" />
          </button>

          <!-- remove local (new) -->
          <button
            v-else-if="values.length > 1"
            class="icon-btn danger"
            title="Убрать поле"
            @click="removeValue(i)"
          >
            <Fa :icon="['fas','minus']" />
          </button>
        </div>

        <button class="ghost-btn mt-8" @click="addValue">
          <Fa :icon="['fas','plus']" />
          Добавить значение
        </button>
      </div>

      <!-- ACTIONS -->
      <div class="form-actions">
        <button class="save-btn" @click="saveAttribute">
          {{ editId ? "Сохранить изменения" : "Создать характеристику" }}
        </button>

        <button v-if="editId" class="ghost-btn" @click="cancelEdit">
          Отмена
        </button>
      </div>
    </div>

    <!-- ===== LIST ===== -->
    <div class="card mt-24">
      <h3 class="card-title">Все характеристики</h3>

      <div v-if="attributes.length === 0" class="empty">
        Пока нет характеристик
      </div>

      <div
        v-for="attr in attributes"
        :key="attr.id"
        class="attr-item"
        :class="{ disabled: editId && editId !== attr.id }"
      >
        <div class="attr-head">
          <div class="attr-name">{{ attr.name }}</div>

          <div class="attr-actions">
            <button
              class="icon-btn"
              title="Редактировать"
              @click="editAttribute(attr)"
            >
              <Fa :icon="['fas','pen']" />
            </button>

            <button
              class="icon-btn danger"
              title="Удалить характеристику"
              @click="deleteAttribute(attr.id)"
            >
              <Fa :icon="['fas','trash']" />
            </button>
          </div>
        </div>

        <div class="attr-values">
          <span v-for="v in attr.values" :key="v.id" class="value-chip">
            {{ v.value }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";

/* ===== STATE ===== */
const attributes = ref([]);
const attrName = ref("");
const editId = ref(null);
const uiRender = ref("text");
const clamp255 = (n) => Math.max(0, Math.min(255, n));

const rgbToHex = (r, g, b) => {
  const to2 = (x) => clamp255(x).toString(16).padStart(2, "0");
  return `#${to2(r)}${to2(g)}${to2(b)}`;
};

const toHexForPicker = (input) => {
  if (!input) return "";
  const s = String(input).trim();

  // hex: #fff или #ffffff
  if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(s)) {
    if (s.length === 4) {
      // #abc -> #aabbcc
      return "#" + s.slice(1).split("").map((c) => c + c).join("");
    }
    return s.toLowerCase();
  }

  // hex без решетки: fff / ffffff
  if (/^([0-9a-f]{3}|[0-9a-f]{6})$/i.test(s)) {
    const h = s.length === 3 ? s.split("").map((c) => c + c).join("") : s;
    return ("#" + h).toLowerCase();
  }

  // rgb(...) или rgba(...)
  const m = s.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*([0-9.]+))?\s*\)$/i);
  if (m) {
    const r = parseInt(m[1], 10);
    const g = parseInt(m[2], 10);
    const b = parseInt(m[3], 10);
    return rgbToHex(r, g, b);
  }

  return "";
};

/*
  values:
  { id?: number, value: string, key: string }
*/
const values = ref([{ value: "", color: "", key: crypto.randomUUID() }]);

/* ===== LOAD ===== */
const loadAttributes = async () => {
  const attrs = await fetch("/api/admin/attribute/get_attributes.php").then(
    (r) => r.json()
  );

  for (const a of attrs) {
    a.values = await fetch(
      `/api/admin/attribute/get_options.php?attribute_id=${a.id}`
    ).then((r) => r.json());
  }

  attributes.value = attrs;
};

/* ===== ACTIONS ===== */
const addValue = () => {
  values.value.push({
    value: "",
    color: "",
    key: crypto.randomUUID(),
  });
};

const removeValue = (i) => {
  values.value.splice(i, 1);
};

const cancelEdit = () => {
  editId.value = null;
  attrName.value = "";
  uiRender.value = "text";
values.value = [{ value: "", color: "", key: crypto.randomUUID() }];
};

/* ===== DELETE OPTION ===== */
const deleteOption = async (optionId, index) => {
  const confirm = await Swal.fire({
    icon: "warning",
    title: "Удалить значение?",
    showCancelButton: true,
    confirmButtonText: "Удалить",
    cancelButtonText: "Отмена",
  });

  if (!confirm.isConfirmed) return;

  const res = await fetch("/api/admin/attribute/delete_option.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ option_id: optionId }),
  }).then((r) => r.json());

  if (res.error) {
    Swal.fire("Ошибка", res.error, "error");
    return;
  }

  values.value.splice(index, 1);
};

/* ===== DELETE ATTRIBUTE ===== */
const deleteAttribute = async (attributeId) => {
  const confirm = await Swal.fire({
    icon: "warning",
    title: "Удалить характеристику?",
    text: "Будут удалены все значения и привязки к товарам",
    showCancelButton: true,
    confirmButtonText: "Удалить",
    cancelButtonText: "Отмена",
  });

  if (!confirm.isConfirmed) return;

  const res = await fetch("/api/admin/attribute/delete_attribute.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ attribute_id: attributeId }),
  }).then((r) => r.json());

  if (res.error) {
    Swal.fire("Ошибка", res.error, "error");
    return;
  }

  loadAttributes();
};

/* ===== SAVE ===== */
const saveAttribute = async () => {
  const name = attrName.value.trim();
  const cleanValues = values.value
    .map((v) => v.value.trim())
    .filter((v) => v !== "");

  if (!name || cleanValues.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Заполните название и значения",
      timer: 2000,
      showConfirmButton: false,
    });
    return;
  }

  /* ===== EDIT ===== */
  if (editId.value) {
    const res = await fetch("/api/admin/attribute/edit_attribute.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        attribute_id: editId.value,
        name,
        slug: name.toLowerCase().replace(/\s+/g, "_"),
        type: "select",
        ui_render: uiRender.value,
        values: values.value.map((v) => ({
          id: v.id ?? null,
          value: v.value,
          meta:
            uiRender.value === "color"
              ? { color: (v.color || "").trim() }
              : null,
        })),
      }),
    }).then((r) => r.json());

    if (res.error) {
      Swal.fire("Ошибка", res.error, "error");
      return;
    }
    Swal.fire({
      icon: "success",
      title: "Сохранено",
      timer: 1200,
      showConfirmButton: false,
    });
    cancelEdit();
    loadAttributes();
    return;
  }

  /* ===== CREATE ===== */
  /* ===== CHECK BEFORE CREATE ===== */
const check = await fetch("/api/admin/attribute/check_before_create.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
body: JSON.stringify({
  name,
  slug: name.toLowerCase().replace(/\s+/g, "_"),
  type: "select",
  ui_render: uiRender.value,
}),
}).then((r) => r.json());


  if (check.attribute_exists) {
    Swal.fire(
      "Ошибка",
      "Характеристика с таким названием уже существует",
      "error"
    );
    return;
  }

  if (check.duplicate_values.length > 0) {
    Swal.fire(
      "Ошибка",
      `Дублирующиеся значения: ${check.duplicate_values.join(", ")}`,
      "error"
    );
    return;
  }

  if (check.values_used_elsewhere.length > 0) {
    const text = check.values_used_elsewhere
      .map((v) => `${v.value} (у "${v.attribute}")`)
      .join("\n");

    const confirm = await Swal.fire({
      icon: "warning",
      title: "Значения уже используются",
      text,
      showCancelButton: true,
      confirmButtonText: "Продолжить",
      cancelButtonText: "Отмена",
    });

    if (!confirm.isConfirmed) return;
  }

  /* ===== CREATE ATTRIBUTE ===== */
  const res = await fetch("/api/admin/attribute/create_attribute.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      name,
      slug: name.toLowerCase().replace(/\s+/g, "_"),
      type: "select",
    }),
  }).then((r) => r.json());

  if (res.error) {
    Swal.fire("Ошибка", res.error, "error");
    return;
  }

  const cleanRows = values.value
    .map((v) => ({
      value: (v.value || "").trim(),
      color: (v.color || "").trim(),
    }))
    .filter((r) => r.value !== "");

  for (const row of cleanRows) {
    await fetch("/api/admin/attribute/create_option.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        attribute_id: res.id,
        value: row.value,
        meta: uiRender.value === "color" ? { color: row.color } : null,
      }),
    });
  }

  Swal.fire({
    icon: "success",
    title: "Создано",
    timer: 1200,
    showConfirmButton: false,
  });
  cancelEdit();
  loadAttributes();
};

const editAttribute = (attr) => {
  editId.value = attr.id;
  attrName.value = attr.name;
  uiRender.value = attr.ui_render || "text";

  values.value = attr.values.map((v) => ({
    id: v.id,
    value: v.value,
    color: v.meta?.color || "",
    key: crypto.randomUUID(),
  }));

  window.scrollTo({ top: 0, behavior: "smooth" });
};

onMounted(loadAttributes);
</script>
<style scoped>
/* ===== Base ===== */
.admin-page{
  max-width: 980px;
  margin: 0 auto;
  padding: 24px;
  min-height: 100vh;
  background: var(--bg-main);
  color: var(--text-main);
}

/* ===== Header ===== */
.head-row{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.block-title{
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

/* ===== Cards ===== */
.card{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 18px;
  box-shadow: var(--shadow-sm);
}

.form-card{
  border-color: color-mix(in srgb, var(--accent) 28%, var(--border-soft));
  box-shadow: var(--shadow-md);
}

.card-title{
  margin: 0 0 14px;
  font-size: 16px;
  font-weight: 800;
  color: var(--text-main);
}

/* ===== Form ===== */
.form-group{ margin-bottom: 14px; }

.form-group label{
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-muted);
}

.input{
  width: 100%;
  padding: 10px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.input::placeholder{ color: var(--text-light); }

/* чуть мягче, когда пусто */
.input:placeholder-shown{
  background: color-mix(in srgb, var(--bg-soft) 55%, var(--bg-panel));
}

.input:focus{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
  background: var(--bg-panel);
}

/* ===== Values row ===== */
.value-row{
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.value-row .input{ flex: 1; }

/* блок цвета (текст + пикер) */
.color-meta{
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 0 0 auto;
}

.color-meta input[type="color"]{
  width: 44px;
  height: 44px;
  padding: 0;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  cursor: pointer;
}

.color-meta input[type="color"]:focus-visible{
  outline: none;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
}

/* ===== Buttons ===== */
.form-actions{
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
}

.save-btn{
  appearance: none;
  border: 1px solid color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  background: var(--accent);
  color: #fff;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 800;
  box-shadow: var(--shadow-sm);
  transition: transform .08s ease, box-shadow .15s ease, filter .15s ease, background .15s ease;
}

.save-btn:hover{
  filter: brightness(1.03);
  box-shadow: var(--shadow-md);
}

.save-btn:active{ transform: translateY(1px); }

.ghost-btn{
  appearance: none;
  border: 1px dashed color-mix(in srgb, var(--accent) 38%, var(--border-soft));
  background: var(--bg-panel);
  color: var(--accent);
  padding: 9px 12px;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 800;
  transition: background .15s ease, border-color .15s ease, transform .08s ease;
}

.ghost-btn:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
}

.ghost-btn:active{ transform: translateY(1px); }


/* ===== Icon buttons ===== */
.icon-btn{
  appearance: none;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--accent);
  cursor: pointer;
  border-radius: var(--radius-md);
  padding: 8px 10px;
  transition: background .15s ease, transform .08s ease, border-color .15s ease;
}

.icon-btn:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
}

.icon-btn:active{ transform: translateY(1px); }

.icon-btn.danger{
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-soft));
  color: var(--accent-danger);
  border-color: color-mix(in srgb, var(--accent-danger) 22%, var(--border-soft));
}

.icon-btn.danger:hover{
  background: color-mix(in srgb, var(--accent-danger) 14%, var(--bg-soft));
}

/* ===== List ===== */
.mt-8{ margin-top: 8px; }
.mt-24{ margin-top: 24px; }

.empty{
  padding: 10px 0;
  color: var(--text-muted);
  font-size: 14px;
}

.attr-item{
  padding: 14px 0;
  border-bottom: 1px solid var(--border-soft);
  transition: background .15s ease;
}

.attr-item:last-child{ border-bottom: none; }

.attr-item:hover{
  background: color-mix(in srgb, var(--bg-soft) 55%, transparent);
}

.attr-head{
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.attr-name{
  font-weight: 900;
  color: var(--text-main);
}

.attr-actions{
  display: flex;
  gap: 8px;
}

.attr-values{
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.value-chip{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  color: var(--text-main);
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

/* Disabled state while editing another attribute */
.attr-item.disabled{
  opacity: 0.45;
  pointer-events: none;
  filter: grayscale(0.15);
}

/* ===== Responsive ===== */
@media (max-width: 768px){
  .admin-page{
    padding: 12px;
    border-radius: 0;
  }

  .block-title{ font-size: 20px; }

  .card{
    padding: 14px;
    border-radius: var(--radius-lg);
  }

  .form-actions{
    flex-direction: column;
    align-items: stretch;
  }

  .save-btn,
  .ghost-btn{
    width: 100%;
    justify-content: center;
  }

  .value-row{
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .color-meta{
    width: 100%;
  }

  .color-meta .input{
    flex: 1;
  }

  .color-meta input[type="color"]{
    width: 100%;
    height: 44px;
    border-radius: var(--radius-md);
  }

  .icon-btn{
    width: 100%;
  }

  .attr-head{
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .attr-actions{
    width: 100%;
    justify-content: flex-end;
  }
}

@media (prefers-reduced-motion: reduce){
  .save-btn,
  .ghost-btn,
  .icon-btn,
  .input,
  .attr-item{
    transition: none !important;
  }
}
</style>

