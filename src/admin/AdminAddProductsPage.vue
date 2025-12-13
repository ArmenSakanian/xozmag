<template>
  <div class="admin-page">
    <div class="add-wrapper">
      <h2 class="title">Добавление товара</h2>

      <div class="card add-form">
        <!-- ===== ОСНОВНОЕ ===== -->
<!-- ===== ИМПОРТ EXCEL ===== -->
<div class="form-section">
  <div class="section-title">Импорт из Excel</div>

  <div class="excel-import">
    <input
      ref="excelInput"
      type="file"
      accept=".xls,.xlsx"
      hidden
      @change="onExcelSelect"
    />

    <button class="ghost-btn" @click="excelInput.click()">
      📂 Выбрать файл
    </button>

    <span class="file-name" v-if="excelFile">
      {{ excelFile.name }}
    </span>

    <button
      class="save-btn"
      :disabled="!excelFile"
      @click="confirmExcelImport"
    >
      ⬆️ Загрузить
    </button>
  </div>
</div>


        <!-- ===== ЦЕНА И КОДЫ ===== -->
        <div class="form-section">
          <div class="section-title">Цена и идентификация</div>

          <div class="field">
            <label>Цена</label>
            <input v-model="form.price" type="number" class="input" placeholder="₽" />
          </div>

          <div class="field">
            <label>Штрихкод</label>
            <input v-model="form.barcode" class="input" placeholder="EAN / UPC" />
          </div>
        </div>

        <!-- ===== КАТЕГОРИЯ ===== -->
        <div class="form-section">
          <div class="section-title">Категория</div>

          <div class="field">
            <label>Категория *</label>
            <select v-model="form.category_id" class="select">
              <option value="">Выберите категорию</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">
                {{ c.full_name }}
              </option>
            </select>
          </div>
        </div>

        <!-- ===== ОПИСАНИЕ ===== -->
        <div class="form-section">
          <div class="section-title">Описание</div>

          <div class="field">
            <textarea
              v-model="form.description"
              class="textarea"
              placeholder="Описание товара (необязательно)"
            ></textarea>
          </div>
        </div>

        <!-- ===== ACTIONS ===== -->
        <div class="form-actions">
          <button class="save-btn" @click="createProduct">
            ➕ Создать товар
          </button>

        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";

const excelInput = ref(null);
const excelFile = ref(null);

const onExcelSelect = (e) => {
  excelFile.value = e.target.files[0] || null;
};

const confirmExcelImport = async () => {
  if (!excelFile.value) return;

  const result = await Swal.fire({
    title: "Импорт товаров",
    text: `Загрузить файл: ${excelFile.value.name}?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Да, загрузить",
    cancelButtonText: "Отмена",
    confirmButtonColor: "#4f6cff",
  });

  if (!result.isConfirmed) return;

  const fd = new FormData();
  fd.append("file", excelFile.value);

  Swal.fire({
    title: "Загрузка...",
    text: "Пожалуйста, подождите",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  try {
    const res = await fetch("/api/admin/product/import_excel.php", {
      method: "POST",
      body: fd,
    });

    const out = await res.json();

    if (!out.success) {
      throw new Error(out.error || "Ошибка импорта");
    }

    Swal.fire({
      title: "Готово!",
      text: `Импортировано товаров: ${out.count}`,
      icon: "success",
      confirmButtonColor: "#4f6cff",
    });

    // сброс
    excelFile.value = null;
    excelInput.value.value = "";
  } catch (err) {
    Swal.fire({
      title: "Ошибка",
      text: err.message,
      icon: "error",
    });
  }
};

const categories = ref([]);

const form = ref({
  name: "",
  article: "",
  brand: "",
  type: "",
  price: "",
  barcode: "",
  description: "",
  category_id: "",
});

const loadCategories = async () => {
  categories.value = await fetch(
    "/api/admin/product/get_categories_flat.php"
  ).then(r => r.json());
};

const createProduct = async () => {
  if (!form.value.name || !form.value.category_id) {
    alert("Заполни обязательные поля");
    return;
  }

  const fd = new FormData();
  Object.entries(form.value).forEach(([k, v]) => fd.append(k, v));

  const res = await fetch("/api/admin/product/create_product.php", {
    method: "POST",
    body: fd,
  });

  const out = await res.json();

  if (out.success) {
    alert("Товар успешно создан");

    form.value = {
      name: "",
      article: "",
      brand: "",
      type: "",
      price: "",
      barcode: "",
      description: "",
      category_id: "",
    };
  } else {
    alert(out.error || "Ошибка создания товара");
  }
};

const importExcel = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const fd = new FormData();
  fd.append("file", file);

  const res = await fetch("/api/admin/product/import_excel.php", {
    method: "POST",
    body: fd,
  });

  const out = await res.json();

  if (!out.success) {
    alert(out.error || "Ошибка импорта");
  } else {
    alert(`Импортировано товаров: ${out.count}`);
  }

  e.target.value = "";
};

onMounted(loadCategories);
</script>
<style scoped>
.admin-page {
  min-height: 100vh;
  padding: 40px 20px;
  background: #0b0e14;
  color: #e9ecf4;
  font-family: Inter, system-ui, Arial, sans-serif;
}

.add-wrapper {
  max-width: 680px;
  margin: 0 auto;
}

.title {
  font-size: 24px;
  font-weight: 900;
  margin-bottom: 18px;
}

.card {
  background: #121827;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 22px;
}

/* ===== SECTIONS ===== */
.form-section {
  margin-bottom: 26px;
}

.section-title {
  font-size: 14px;
  font-weight: 800;
  margin-bottom: 12px;
  color: #aab3ff;
}

/* ===== FIELDS ===== */
.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 14px;
}

.field label {
  font-size: 13px;
  font-weight: 700;
  color: rgba(233,236,244,0.7);
}

.input,
.select,
.textarea {
  background: #0f1424;
  border: 1px solid rgba(255,255,255,0.15);
  color: #e9ecf4;
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 14px;
}

.textarea {
  min-height: 90px;
  resize: vertical;
}

.input:focus,
.select:focus,
.textarea:focus {
  outline: none;
  border-color: #4f6cff;
}

/* ===== ACTIONS ===== */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 10px;
}

.save-btn {
  flex: 1;
  background: #4f6cff;
  border: none;
  color: #ffffff;
  border-radius: 14px;
  padding: 14px;
  font-weight: 900;
  cursor: pointer;
}

.ghost-btn {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.15);
  color: #e9ecf4;
  border-radius: 14px;
  padding: 14px 18px;
  font-weight: 800;
  cursor: pointer;
}

.excel-import {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.file-name {
  font-size: 13px;
  color: #aab3ff;
  font-weight: 700;
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.save-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}


/* ===== MOBILE ===== */
@media (max-width: 600px) {
  .form-actions {
    flex-direction: column;
  }
}
</style>
