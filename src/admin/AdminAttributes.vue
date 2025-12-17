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

      <!-- VALUES -->
      <div class="form-group">
        <label>Значения</label>

        <div v-for="(item, i) in values" :key="item.key" class="value-row">
          <input
            v-model="item.value"
            class="input"
            placeholder="Например: 1.5"
          />

          <!-- delete option -->
          <button
            v-if="item.id"
            class="icon-btn danger"
            title="Удалить значение"
            @click="deleteOption(item.id, i)"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>

          <!-- remove local (new) -->
          <button
            v-else-if="values.length > 1"
            class="icon-btn danger"
            title="Убрать поле"
            @click="removeValue(i)"
          >
            <i class="fa-solid fa-minus"></i>
          </button>
        </div>

        <button class="ghost-btn mt-8" @click="addValue">
          <i class="fa-solid fa-plus"></i>
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
              <i class="fa-solid fa-pen"></i>
            </button>

            <button
              class="icon-btn danger"
              title="Удалить характеристику"
              @click="deleteAttribute(attr.id)"
            >
              <i class="fa-solid fa-trash"></i>
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

/*
  values:
  { id?: number, value: string, key: string }
*/
const values = ref([{ value: "", key: crypto.randomUUID() }]);

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
    key: crypto.randomUUID(),
  });
};

const removeValue = (i) => {
  values.value.splice(i, 1);
};

const cancelEdit = () => {
  editId.value = null;
  attrName.value = "";
  values.value = [{ value: "", key: crypto.randomUUID() }];
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
  values: values.value.map(v => ({
    id: v.id ?? null,
    value: v.value,
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
    values: cleanValues,
  }),
}).then(r => r.json());

if (check.attribute_exists) {
  Swal.fire("Ошибка", "Характеристика с таким названием уже существует", "error");
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
    .map(v => `${v.value} (у "${v.attribute}")`)
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
}).then(r => r.json());

if (res.error) {
  Swal.fire("Ошибка", res.error, "error");
  return;
}

for (const v of cleanValues) {
  await fetch("/api/admin/attribute/create_option.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      attribute_id: res.id,
      value: v,
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
  values.value = attr.values.map((v) => ({
    id: v.id,
    value: v.value,
    key: crypto.randomUUID(),
  }));

  window.scrollTo({ top: 0, behavior: "smooth" });
};

onMounted(loadAttributes);
</script>
<style scoped>
/* ===== Base ===== */
.admin-page {
  max-width: 980px;
  margin: 0 auto;
  padding: 24px;
  min-height: 100vh;
  background: #f6f7fb; /* body белый — делаем мягкий фон внутри страницы */
  color: #111827;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}

.head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.block-title {
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0f172a;
}

/* ===== Cards ===== */
.card {
  background: #ffffff;
  border: 1px solid #e6e9f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.form-card {
  border-color: rgba(59, 130, 246, 0.35);
  box-shadow: 0 12px 34px rgba(59, 130, 246, 0.08);
}

.card-title {
  margin: 0 0 14px;
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

/* ===== Form ===== */
.form-group {
  margin-bottom: 14px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #d7dde7;
  background: #ffffff;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease,
    background 0.15s ease;
}

.input::placeholder {
  color: #94a3b8;
}

/* пустой инпут (если есть placeholder) */
.input:placeholder-shown {
  background: #fbfcff;
}

/* заполненный инпут */
.input:not(:placeholder-shown) {
  border-color: #cbd5e1;
}

/* фокус */
.input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

/* ===== Values row ===== */
.value-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.value-row .input {
  flex: 1;
}

/* ===== Buttons ===== */
.form-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
}

.save-btn {
  appearance: none;
  border: 1px solid #2563eb;
  background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
  color: #ffffff;
  padding: 10px 14px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 800;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
  transition: transform 0.08s ease, box-shadow 0.15s ease,
    filter 0.15s ease;
}

.save-btn:hover {
  filter: brightness(1.03);
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
}

.save-btn:active {
  transform: translateY(1px);
}

.ghost-btn {
  appearance: none;
  border: 1px dashed #93c5fd;
  background: #ffffff;
  color: #2563eb;
  padding: 9px 12px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 800;
  transition: background 0.15s ease, border-color 0.15s ease,
    transform 0.08s ease;
}

.ghost-btn:hover {
  background: rgba(37, 99, 235, 0.06);
  border-color: #60a5fa;
}

.ghost-btn:active {
  transform: translateY(1px);
}

.ghost-btn i,
.save-btn i {
  margin-right: 8px;
}

/* ===== Icon buttons ===== */
.icon-btn {
  appearance: none;
  border: 1px solid transparent;
  background: #f1f5f9;
  color: #2563eb;
  cursor: pointer;
  border-radius: 10px;
  padding: 8px 10px;
  transition: background 0.15s ease, transform 0.08s ease,
    border-color 0.15s ease;
}

.icon-btn:hover {
  background: #eaf1ff;
  border-color: rgba(37, 99, 235, 0.22);
}

.icon-btn:active {
  transform: translateY(1px);
}

.icon-btn.danger {
  background: #fff1f2;
  color: #e11d48;
}

.icon-btn.danger:hover {
  background: #ffe4e6;
  border-color: rgba(225, 29, 72, 0.22);
}

/* ===== List ===== */
.mt-8 {
  margin-top: 8px;
}
.mt-24 {
  margin-top: 24px;
}

.empty {
  padding: 10px 0;
  color: #64748b;
  font-size: 14px;
}

.attr-item {
  padding: 14px 0;
  border-bottom: 1px solid #edf0f6;
  transition: background 0.15s ease;
}

.attr-item:last-child {
  border-bottom: none;
}

.attr-item:hover {
  background: rgba(15, 23, 42, 0.02);
}

.attr-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.attr-name {
  font-weight: 900;
  color: #0f172a;
}

.attr-actions {
  display: flex;
  gap: 8px;
}

.attr-values {
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.value-chip {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #0f172a;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

/* Disabled state while editing another attribute */
.attr-item.disabled {
  opacity: 0.45;
  pointer-events: none;
  filter: grayscale(0.15);
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .admin-page {
    padding: 12px;
    border-radius: 0;
  }

  .block-title {
    font-size: 20px;
  }

  .card {
    padding: 14px;
    border-radius: 14px;
  }

  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .save-btn,
  .ghost-btn {
    width: 100%;
    justify-content: center;
  }

  .value-row {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .icon-btn {
    width: 100%;
  }

  .attr-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .attr-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
