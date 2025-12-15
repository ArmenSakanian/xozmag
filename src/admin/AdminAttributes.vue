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
.admin-page {
  padding: 24px;
  max-width: 900px;
}

.block-title {
  font-size: 26px;
  font-weight: 600;
}

.card {
  background: #1f2228;
  border-radius: 16px;
  padding: 20px;
}

.form-card {
  outline: 1px solid rgba(59, 130, 246, 0.35);
}

.card-title {
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid #2f333c;
  background: #14161a;
  color: #fff;
}

.value-row {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 12px;
}

.save-btn {
  border: 1px solid #3b82f6;
  background: transparent;
  color: #fff;
  padding: 10px 16px;
  border-radius: 12px;
  cursor: pointer;
}

.save-btn:hover {
  background: #3b82f6;
}

.ghost-btn {
  border: 1px dashed #3b82f6;
  background: transparent;
  color: #3b82f6;
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
}

.icon-btn {
  border: none;
  background: transparent;
  color: #3b82f6;
  cursor: pointer;
  border-radius: 8px;
  padding: 6px;
}

.icon-btn.danger {
  color: #ef4444;
}

.attr-item {
  padding: 12px 0;
  border-bottom: 1px solid #2a2e36;
}

.attr-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.attr-actions {
  display: flex;
  gap: 6px;
}

.attr-values {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.value-chip {
  background: #2a2e36;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 13px;
}

.attr-item.disabled {
  opacity: 0.4;
  pointer-events: none;
}

.empty {
  opacity: 0.6;
}

.mt-8 {
  margin-top: 8px;
}
.mt-24 {
  margin-top: 24px;
}

@media (max-width: 768px) {
  .admin-page {
    padding: 12px;
  }

  .block-title {
    font-size: 20px;
  }

  .card {
    padding: 14px;
    border-radius: 12px;
  }

  .card-title {
    font-size: 16px;
  }

  .form-actions {
    flex-direction: column;
  }

  .save-btn,
  .ghost-btn {
    width: 100%;
  }

  .value-row {
    flex-direction: column;
    gap: 6px;
  }

  .icon-btn {
    align-self: flex-end;
  }

  .attr-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .attr-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .attr-values {
    gap: 4px;
  }

  .value-chip {
    font-size: 12px;
    padding: 5px 8px;
  }
}
</style>
