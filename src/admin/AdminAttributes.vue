<template>
  <div class="admin-page">
    <!-- ===== HEADER ===== -->
    <div class="head-row">
      <h2 class="block-title">Характеристики товаров</h2>
    </div>

    <!-- ===== CREATE FORM ===== -->
    <div class="card">
      <h3 class="card-title">Добавить характеристику</h3>

      <!-- ATTRIBUTE NAME -->
      <div class="form-group">
        <label>Название характеристики</label>
        <input
          v-model="attrName"
          class="input"
          placeholder="Например: Тип арматуры"
        />
      </div>

      <!-- VALUES -->
      <div class="form-group">
        <label>Значения характеристики</label>

        <div v-for="(val, i) in values" :key="i" class="value-row">
          <input
            v-model="values[i]"
            class="input"
            placeholder="Например: Наливная"
          />
          <button
            class="danger-btn"
            v-if="values.length > 1"
            @click="removeValue(i)"
          >
            ✕
          </button>
        </div>

        <button class="ghost-btn mt-8" @click="addValue">
          + Добавить ещё значение
        </button>
      </div>

      <!-- SAVE -->
      <button class="save-btn mt-16" @click="saveAttribute">
        Создать характеристику
      </button>
    </div>

    <!-- ===== LIST ===== -->
    <div class="card mt-24">
      <h3 class="card-title">Все характеристики</h3>

      <div v-if="attributes.length === 0" class="empty">
        Пока нет характеристик
      </div>

      <div v-for="attr in attributes" :key="attr.id" class="attr-item">
        <div class="attr-name">{{ attr.name }}</div>

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
const attrName = ref("");
const values = ref([""]);
const attributes = ref([]);

/* ===== API ===== */
const loadAttributes = async () => {
  const attrs = await fetch("/api/admin/attribute/get_attributes.php").then(
    (r) => r.json()
  );

  // для каждого атрибута подтягиваем значения
  for (const a of attrs) {
    a.values = await fetch(
      `/api/admin/attribute/get_options.php?attribute_id=${a.id}`
    ).then((r) => r.json());
  }

  attributes.value = attrs;
};

/* ===== TRANSLIT FUNCTION ===== */
const translit = (text) => {
  const map = {
    а: "a",
    б: "b",
    в: "v",
    г: "g",
    д: "d",
    е: "e",
    ё: "e",
    ж: "zh",
    з: "z",
    и: "i",
    й: "y",
    к: "k",
    л: "l",
    м: "m",
    н: "n",
    о: "o",
    п: "p",
    р: "r",
    с: "s",
    т: "t",
    у: "u",
    ф: "f",
    х: "h",
    ц: "ts",
    ч: "ch",
    ш: "sh",
    щ: "sch",
    ъ: "",
    ы: "y",
    ь: "",
    э: "e",
    ю: "yu",
    я: "ya",
  };

  return text
    .toLowerCase()
    .split("")
    .map((char) => map[char] ?? char)
    .join("")
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_|_$/g, "");
};

const saveAttribute = async () => {
  const name = attrName.value.trim();
  const filledValues = values.value.filter(v => v.trim() !== "");

  /* ❌ всё пусто */
  if (!name && filledValues.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Ничего не заполнено",
      text: "Введите название характеристики и минимум одно значение",
      timer: 3000,
      showConfirmButton: false
    });
    return;
  }

  /* ❌ есть значения, но нет заголовка */
  if (!name && filledValues.length > 0) {
    Swal.fire({
      icon: "warning",
      title: "Нет названия",
      text: "Сначала укажите название характеристики",
      timer: 3000,
      showConfirmButton: false
    });
    return;
  }

  /* ❌ есть заголовок, но нет значений */
  if (name && filledValues.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Нет значений",
      text: "Добавьте минимум одно значение характеристики",
      timer: 3000,
      showConfirmButton: false
    });
    return;
  }

  const check = await fetch(
    "/api/admin/attribute/check_before_create.php",
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: name,
        values: filledValues
      })
    }
  ).then(r => r.json());


  /* 🟡 СИТУАЦИЯ 2 */
  if (check.duplicate_values.length > 0) {
    await Swal.fire({
      icon: "warning",
      title: "Значение уже существует",
      text: `У этой характеристики уже есть: ${check.duplicate_values.join(", ")}`
    });
    return;
  }

  /* 🟠 СИТУАЦИЯ 3 */
  if (!check.attribute_exists && check.values_used_elsewhere.length > 0) {
    const list = check.values_used_elsewhere
      .map(v => `${v.value} (у "${v.attribute}")`)
      .join("\n");

    const confirm = await Swal.fire({
      icon: "warning",
      title: "Значения уже используются",
      text: list,
      showCancelButton: true,
      confirmButtonText: "Создать всё равно",
      cancelButtonText: "Отмена"
    });

    if (!confirm.isConfirmed) return;
  }

  /* 🟢 СОЗДАНИЕ */
  let attributeId = check.attribute_id;

  if (!attributeId) {
    const res = await fetch("/api/admin/attribute/create_attribute.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: attrName.value,
        slug: translit(attrName.value),
        type: "select"
      })
    }).then(r => r.json());

    attributeId = res.id;
  }

  for (const v of values.value) {
    if (!v.trim()) continue;

    await fetch("/api/admin/attribute/create_option.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        attribute_id: attributeId,
        value: v
      })
    });
  }

  await Swal.fire({
    icon: "success",
    title: "Готово",
    timer: 1200,
    showConfirmButton: false
  });

  attrName.value = "";
  values.value = [""];
  loadAttributes();
};


/* ===== UI opting ===== */
const addValue = () => values.value.push("");
const removeValue = (i) => values.value.splice(i, 1);

onMounted(loadAttributes);
</script>

<style scoped>
.admin-page {
  padding: 24px;
  max-width: 900px;
}

.head-row {
  margin-bottom: 20px;
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

.card-title {
  font-size: 18px;
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 16px;
}

label {
  display: block;
  margin-bottom: 6px;
  opacity: 0.8;
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

.save-btn {
  background: transparent;
  border: 1px solid #3b82f6;
  color: #fff;
  padding: 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: .5s
}

.save-btn:hover {
    background-color: #3b82f6;
}

.ghost-btn {
  background: transparent;
  border: 1px dashed #ffffff;
  color: #3b82f6;
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: .5s;
}
.ghost-btn:hover {
    border: 1px dashed #3b82f6;
}

.danger-btn {
  background: #ef4444;
  border: none;
  color: #fff;
  border-radius: 10px;
  padding: 0 10px;
}

.attr-item {
  padding: 12px 0;
  border-bottom: 1px solid #2a2e36;
}

.attr-name {
  font-weight: 500;
  margin-bottom: 6px;
}

.attr-values {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.value-chip {
  background: #2a2e36;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 13px;
}

.empty {
  opacity: 0.6;
}

.mt-8 {
  margin-top: 8px;
}
.mt-16 {
  margin-top: 16px;
}
.mt-24 {
  margin-top: 24px;
}
</style>
