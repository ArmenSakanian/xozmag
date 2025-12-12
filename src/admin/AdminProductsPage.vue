<template>
  <div class="admin-page">
    <!-- ================== ADD PRODUCT ================== -->
    <h2 class="title">Добавление товара</h2>

    <div class="card form-grid">
      <input v-model="form.name" class="input" placeholder="Название *" />
      <input v-model="form.article" class="input" placeholder="Артикул" />
      <input v-model="form.brand" class="input" placeholder="Бренд" />
      <input v-model="form.type" class="input" placeholder="Тип" />
      <input
        v-model="form.price"
        type="number"
        class="input"
        placeholder="Цена"
      />
      <input v-model="form.barcode" class="input" placeholder="Штрихкод" />

      <select v-model="form.category_id" class="select">
        <option value="">Категория *</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">
          {{ c.full_name }}
        </option>
      </select>

      <textarea
        v-model="form.description"
        class="input textarea"
        placeholder="Описание"
      ></textarea>

      <button class="save-btn" @click="createProduct">Создать товар</button>
    </div>

    <!-- ================== TABLE ================== -->
    <div class="head-row">
      <h3 class="block-title">Товары</h3>
      <button class="ghost-btn" @click="loadProducts">Обновить</button>
    </div>

    <div class="table-shell">
      <div ref="tableRef" class="product-table"></div>
    </div>

    <!-- ================== ATTR MODAL ================== -->
    <div v-if="attrsModal.open" class="modal-backdrop" @click.self="closeAttrs">
      <div class="modal">
        <div class="modal-head">
          <div>
            <div class="modal-title">Характеристики</div>
            <div class="modal-sub">
              #{{ attrsModal.product.id }} · {{ attrsModal.product.name }}
            </div>
          </div>
          <button class="icon-btn" @click="closeAttrs">✕</button>
        </div>

        <div class="modal-body">
          <div v-for="(row, i) in attrsDraft" :key="i" class="attr-row">
            <input v-model="row.name" class="input" placeholder="Название" />
            <input v-model="row.value" class="input" placeholder="Значение" />
            <button class="danger-btn" @click="attrsDraft.splice(i, 1)">
              ✕
            </button>
          </div>

          <button
            class="ghost-btn"
            @click="attrsDraft.push({ name: '', value: '' })"
          >
            + Добавить
          </button>
        </div>

        <div class="modal-foot">
          <button class="ghost-btn" @click="closeAttrs">Отмена</button>
          <button class="save-btn" @click="saveAttrs">Сохранить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator_midnight.min.css";

const tableRef = ref(null);
let table;

/* ===== DATA ===== */
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

const attrsModal = ref({ open: false, product: null });
const attrsDraft = ref([]);

/* ===== LOADERS ===== */
const loadCategories = async () => {
  categories.value = await fetch(
    "/api/admin/product/get_categories_flat.php"
  ).then((r) => r.json());
};

const loadProducts = async () => {
  const data = await fetch("/api/admin/product/get_products.php").then((r) =>
    r.json()
  );

  data.forEach((p) => {
    p.attributes = p.attributes || [];
    p.__has_attrs = p.attributes.length > 0;
    p.attributes_text = p.attributes
      .map((a) => `${a.name}: ${a.value}`)
      .join(" · ");
  });

  table.setData(data);
};

/* ===== CREATE ===== */
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
    loadProducts();
  }
};

/* ===== ATTRS ===== */
const openAttrs = (p) => {
  attrsModal.value = { open: true, product: p };
  attrsDraft.value = (p.attributes || []).map((a) => ({ ...a }));
};
const closeAttrs = () => {
  attrsModal.value.open = false;
};
const saveAttrs = async () => {
  await fetch("/api/admin/product/update_product_attributes.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      id: attrsModal.value.product.id,
      attributes: attrsDraft.value,
    }),
  });
  loadProducts();
  closeAttrs();
};

/* ===== INIT TABLE ===== */
onMounted(async () => {
  await loadCategories();
  table = new Tabulator(tableRef.value, {
    layout: "fitDataStretch",
    height: "70vh",
    columns: [
      {
        title: "ID",
        field: "id",
        width: 70,
        formatter: (cell) => `<span class="t-id">#${cell.getValue()}</span>`,
      },

      {
  title: "Название",
  field: "name",
  minWidth: 650,    
  widthGrow: 4,
  headerFilter: "input",
  formatter: (cell) => {
    const value = cell.getValue() || "";
    return `
      <div class="name-edit">
        <span class="name-text">${value}</span>
        <button class="mini-btn edit-name">Изменить</button>
      </div>
    `;
  },

  cellClick: (e, cell) => {
    if (!e.target.classList.contains("edit-name")) return;

    const row = cell.getRow();
    const data = row.getData();
    const el = cell.getElement();

    const oldValue = data.name || "";

    // режим редактирования
    el.innerHTML = `
      <div class="name-edit">
        <input class="input name-input" value="${oldValue}" />
              <div class="button-save-canc">
        <button class="mini-btn save-name">Сохранить</button>
        <button class="mini-btn cancel-name">Отмена</button>
        </div>
      </div>
    `;

    const input = el.querySelector(".name-input");
    const saveBtn = el.querySelector(".save-name");
    const cancelBtn = el.querySelector(".cancel-name");

    input.focus();

    // ✅ СОХРАНИТЬ
    saveBtn.onclick = async () => {
      const newValue = input.value.trim();

      // если ничего не поменяли — просто вернём вид
      if (!newValue || newValue === oldValue) {
        row.update({ name: oldValue });
        return;
      }

      await fetch("/api/admin/product/update_product_basic.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id: data.id,
          name: newValue,
        }),
      });

      // обновляем данные строки → formatter снова покажет "Изменить"
      row.update({ name: newValue });
    };

    // ❌ ОТМЕНА
cancelBtn.onclick = () => {
  // принудительно вернуть старое значение
  cell.setValue(oldValue, true);
};

  },
},


      {
        title: "Бренд",
        field: "brand",
        headerFilter: "input",
        formatter: (cell) =>
          `<span class="t-brand">${cell.getValue() || "—"}</span>`,
      },

      {
        title: "Тип",
        field: "type",
        headerFilter: "input",
        formatter: (cell) =>
          `<span class="t-type">${cell.getValue() || "—"}</span>`,
      },

      {
        title: "Цена",
        field: "price",
        hozAlign: "right",
        formatter: (cell) => {
          const v = cell.getValue();
          if (v === null || v === undefined || v === "")
            return `<span class="t-empty">—</span>`;
          return `<span class="t-price">${v}</span>`;
        },
      },

      {
        title: "Штрихкод",
        field: "barcode",
        headerFilter: "input",
        formatter: (cell) => {
          const v = cell.getValue();
          return v
            ? `<span class="t-barcode">${v}</span>`
            : `<span class="t-empty">—</span>`;
        },
      },

      {
        title: "Категория",
        field: "category_id",
        minWidth: 470,    
        widthGrow: 4,
        formatter: (cell) => {
          const id = cell.getValue();
          const cat = categories.value.find((c) => c.id == id);

          return `
      <div class="cat-edit">
        <span class="cat-text">${cat ? cat.full_name : "—"}</span>
        <button class="mini-btn edit-cat">Изменить</button>
      </div>
    `;
        },

        cellClick: (e, cell) => {
          if (!e.target.classList.contains("edit-cat")) return;

          const row = cell.getRow();
          const data = row.getData();
          const el = cell.getElement();
          const oldCatId = data.category_id;

          el.innerHTML = `
  <div class="cat-edit">
    <select class="select cat-select">
      <option value="">— Без категории —</option>
      ${categories.value
        .map(
          (c) =>
            `<option value="${c.id}" ${
              c.id == data.category_id ? "selected" : ""
            }>${c.full_name}</option>`
        )
        .join("")}
    </select>

    <div class="button-save-canc">
      <button class="mini-btn save-cat">Сохранить</button>
      <button class="mini-btn cancel-cat">Отмена</button>
    </div>
  </div>
`;


const select = el.querySelector(".cat-select");
const saveBtn = el.querySelector(".save-cat");
const cancelBtn = el.querySelector(".cancel-cat");

saveBtn.onclick = async () => {
  const newCatId = select.value ? Number(select.value) : null;

  if (newCatId === oldCatId) {
    cell.setValue(oldCatId, true);
    return;
  }

  await fetch("/api/admin/product/update_product_basic.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      id: data.id,
      category_id: newCatId,
    }),
  });

  cell.setValue(newCatId, true);
};

// ❌ ОТМЕНА
cancelBtn.onclick = () => {
  cell.setValue(oldCatId, true);
};


        },
      },

      {
        title: "Характеристики",
        field: "attributes_text",
        formatter: (cell) => {
          const row = cell.getRow().getData();

          if (!row.__has_attrs) {
            return `<button class="mini-btn add-btn" type="button">Добавить</button>`;
          }

          return `<button class="mini-btn edit-btn" type="button">Изменить</button>`;
        },
        cellClick: (e, cell) => {
          const row = cell.getRow().getData();

          if (e.target.classList.contains("add-btn")) {
            openAttrs({
              ...row,
              attributes: [],
            });
          }

          if (e.target.classList.contains("edit-btn")) {
            openAttrs(row);
          }
        },
      },
    ],
  });
  loadProducts();
});
</script>
<style scoped>
/* ================================================= */
/* ================= PAGE =========================== */
/* ================================================= */
.admin-page {
  min-height: 100vh;
  padding: 28px 36px;
  background: #0b0e14;
  color: #e9ecf4;
  font-family: Inter, system-ui, Arial, sans-serif;
}

.title {
  font-size: 22px;
  font-weight: 800;
  margin-bottom: 14px;
}

.block-title {
  font-size: 16px;
  font-weight: 800;
}

/* ================================================= */
/* ================= FORM =========================== */
/* ================================================= */
.card {
  background: #121827;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 18px;
  margin-bottom: 22px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(220px, 1fr));
  gap: 16px 18px;
}

/* ================================================= */
/* ================= INPUTS ========================= */
/* ================================================= */
.input,
.select,
.textarea {
  background: #0f1424;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e9ecf4;
  border-radius: 12px;
  padding: 11px 13px;
  font-size: 14px;
}

.input::placeholder,
.textarea::placeholder {
  color: rgba(233, 236, 244, 0.45);
}

.input:focus,
.select:focus,
.textarea:focus {
  outline: none;
  border-color: #4f6cff;
}

/* ================================================= */
/* ================= BUTTONS ======================== */
/* ================================================= */
.save-btn {
  background: #4f6cff;
  border: none;
  color: #ffffff;
  border-radius: 14px;
  padding: 12px 18px;
  font-weight: 800;
  cursor: pointer;
}

.ghost-btn {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e9ecf4;
  border-radius: 12px;
  padding: 10px 14px;
  font-weight: 700;
  cursor: pointer;
}

/* ================================================= */
/* ================= TABLE ========================== */
/* ================================================= */
.table-shell {
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.product-table {
  width: 100%;
}

/* ---------------- TABULATOR ---------------------- */
:deep(.tabulator) {
  background: #0f1424;
  color: #e9ecf4;
  border: none;
  font-size: 13.5px;
}

:deep(.tabulator-header) {
  background: #151b2f;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

:deep(.tabulator-col-title) {
  padding: 13px 14px;
  font-weight: 800;
  color: #ffffff;
}

:deep(.tabulator-row) {
  background: #0f1424;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

:deep(.tabulator-row:nth-child(even)) {
  background: #121827;
}

:deep(.tabulator-row:hover) {
  background: #1a2140;
}

:deep(.tabulator-cell) {
  padding: 12px 14px;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ================================================= */
/* ====== РАЗНЫЕ ЦВЕТА ДЛЯ СТОЛБЦОВ ================= */
/* ================================================= */
:deep(.t-name) {
  color: #e9ecf4;
  font-weight: 800;
}

:deep(.t-brand) {
  color: #4fc3f7; /* голубой */
  font-weight: 700;
}

:deep(.t-type) {
  color: #ce93d8; /* фиолетовый */
  font-weight: 700;
}

:deep(.t-price) {
  color: #81c784; /* зелёный */
  font-weight: 800;
}

:deep(.t-barcode) {
  color: #90caf9; /* синий */
  font-weight: 700;
  letter-spacing: 0.3px;
}

:deep(.t-cat) {
  color: #ffd54f; /* жёлтый */
  font-weight: 700;
}

:deep(.t-empty) {
  color: rgba(233, 236, 244, 0.4);
}

/* ================================================= */
/* ================= mini-btn ====================== */
/* ================================================= */
:deep(.mini-btn) {
  background: #1a2140;
  border: 1px solid #4f6cff;
  color: #ffffff;
  border-radius: 10px;
  padding: 7px 14px;
  font-weight: 800;
  cursor: pointer;
}

:deep(.mini-btn:hover) {
  background: #4f6cff;
}

:deep(.edit-name),
:deep(.edit-cat)  {
  position: absolute;
  right: 10px;
}

:deep(.button-save-canc) {
display: flex;
    gap: 5px;
}

:deep(.save-name),
:deep(.save-cat) {
    background-color: green;
    border: none;
    transition: .5s;
}

:deep(.save-name:hover),
:deep(.save-cat:hover) {
    background-color: rgb(117, 255, 117);
    color: black;
}

:deep(.cancel-name),
:deep(.cancel-cat) {
  background-color: red;
  border: none;
  transition: .5s;
}

:deep(.cancel-name:hover),
:deep(.cancel-cat:hover) {
  background-color: rgb(255, 158, 158);
  color: black;

}


:deep(.name-input) {
  padding: 10px;
  border-radius: 5px;
  background-color: blanchedalmond;
  width: 70%;
}


/* ================================================= */
/* ================= MODAL ========================== */
/* ================================================= */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal {
  width: min(900px, 96vw);
  max-height: 86vh;
  display: flex;
  flex-direction: column;
  background: #121827;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-head {
  padding: 16px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-title {
  font-size: 16px;
  font-weight: 800;
}

.modal-sub {
  margin-top: 4px;
  font-size: 12px;
  color: rgba(233, 236, 244, 0.5);
}

.modal-body {
  padding: 16px 18px;
  overflow: auto;
}

.modal-foot {
  padding: 14px 18px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

/* icon-btn */
.icon-btn {
  width: 36px;
  height: 36px;
  background: #1a2140;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #ffffff;
  border-radius: 10px;
  cursor: pointer;
}

.icon-btn:hover {
  background: #e53935;
}

/* danger-btn */
.danger-btn {
  width: 36px;
  height: 36px;
  background: #2a1a1a;
  border: 1px solid #e53935;
  color: #ffffff;
  border-radius: 10px;
  cursor: pointer;
}

:deep(.name-edit),
:deep(.cat-edit) {
display: flex;
    gap: 10px;
    flex-direction: row;
}

:deep(.name-text) {
  display: block;
  padding-right: 120px; /* 👈 место под кнопки */
  white-space: normal;
  line-height: 1.3;
}

.name-input {
  min-width: 240px;
}
</style>
