<template>
  <div class="admin-page">
    <!-- ===== MOBILE ROTATE OVERLAY ===== -->
    <div v-if="showRotateOverlay" class="rotate-overlay">
      <div class="rotate-box">
        <div class="rotate-icon">📱↔️</div>
        <div class="rotate-title">Поверните телефон</div>
        <div class="rotate-text">
          Для удобной работы с таблицей используйте горизонтальный режим
        </div>
      </div>
    </div>

    <!-- ================== TABLE HEADER ================== -->
    <div class="head-row">
      <h3 class="block-title">Товары</h3>

      <div class="head-actions">
        <button
          v-if="selectedProducts.length > 0"
          class="save-btn"
          @click="openBulkCategory"
        >
          Категория ({{ selectedProducts.length }})
        </button>

        <button
          v-if="selectedProducts.length > 0"
          class="save-btn"
          @click="openBulkAttrs"
        >
          Характеристики ({{ selectedProducts.length }})
        </button>
        <button
          v-if="selectedProducts.length > 0"
          class="save-btn danger-clear"
          @click="clearSelection"
        >
          Снять выделение
        </button>

        <button class="ghost-btn" @click="loadProducts">Обновить</button>
      </div>
    </div>

    <!-- ================== TABLE ================== -->
    <div class="table-shell">
      <div ref="tableRef" class="product-table"></div>
    </div>
    <div class="goods_counter">
      <p>
        Выбранные товары:
        <span class="number">({{ selectedProducts.length }})</span>
      </p>
    </div>
    <!-- ================== CATEGORY MODAL ================== -->
    <div
      v-if="categoryModal.open"
      class="modal-backdrop"
      @click.self="closeCategory"
    >
      <div class="modal">
        <div class="modal-head">
          <div>
            <div class="modal-title">
              {{
                categoryModal.bulk
                  ? `Категория для ${selectedProducts.length} товаров`
                  : "Категория товара"
              }}
            </div>
            <div v-if="!categoryModal.bulk" class="modal-sub">
              #{{ categoryModal.product.id }} ·
              {{ categoryModal.product.name }}
            </div>
          </div>
          <button class="icon-btn" @click="closeCategory">✕</button>
        </div>

        <div class="modal-body">
          <div
            v-for="c in categories"
            :key="c.id"
            class="cat-tree-row"
            :style="{ paddingLeft: (c.level - 1) * 18 + 'px' }"
          >
            <label class="cat-radio">
              <input
                type="radio"
                name="category"
                :value="c.id"
                v-model="categoryModal.selected"
              />
              <span>{{ c.full_name }}</span>
            </label>
          </div>
        </div>

        <div class="modal-foot">
          <button class="ghost-btn" @click="removeCategory">
            Убрать категорию
          </button>

          <div style="display: flex; gap: 10px">
            <button class="ghost-btn" @click="closeCategory">Отмена</button>
            <button class="save-btn" @click="saveCategory">Сохранить</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================== ATTR MODALS ================== -->
    <!-- ⚠️ ОСТАВЛЕНЫ БЕЗ ИЗМЕНЕНИЙ (твой код) -->
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
            <select
              v-model="row.attribute_id"
              class="select"
              @change="onAttributeChange(row)"
            >
              <option value="">— Характеристика —</option>
              <option v-for="a in allAttributes" :key="a.id" :value="a.id">
                {{ a.name }}
              </option>
            </select>

            <select
              v-model="row.option_id"
              class="select"
              :disabled="!row.attribute_id"
            >
              <option value="">— Значение —</option>
              <option
                v-for="o in attributeOptions[row.attribute_id] || []"
                :key="o.id"
                :value="o.id"
              >
                {{ o.value }}
              </option>
            </select>

            <button class="danger-btn" @click="attrsDraft.splice(i, 1)">
              ✕
            </button>
          </div>

          <button
            class="ghost-btn"
            @click="attrsDraft.push({ attribute_id: null, option_id: null })"
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
    <div
      v-if="bulkAttrsModal"
      class="modal-backdrop"
      @click.self="bulkAttrsModal = false"
    >
      <div class="modal">
        <div class="modal-head">
          <div class="modal-title">
            Характеристики для {{ selectedProducts.length }} товаров
          </div>
          <button class="icon-btn" @click="bulkAttrsModal = false">✕</button>
        </div>

        <div class="modal-body">
          <!-- ⬇️ ТВОЙ СТАРЫЙ КОД (НЕ МЕНЯЕМ) -->
          <div v-for="(row, i) in bulkAttrsDraft" :key="i" class="attr-row">
            <select
              v-model="row.attribute_id"
              class="select"
              @change="onAttributeChange(row)"
            >
              <option value="">— Характеристика —</option>
              <option v-for="a in allAttributes" :key="a.id" :value="a.id">
                {{ a.name }}
              </option>
            </select>

            <select
              v-model="row.option_id"
              class="select"
              :disabled="!row.attribute_id"
            >
              <option value="">— Значение —</option>
              <option
                v-for="o in attributeOptions[row.attribute_id] || []"
                :key="o.id"
                :value="o.id"
              >
                {{ o.value }}
              </option>
            </select>

            <button class="danger-btn" @click="bulkAttrsDraft.splice(i, 1)">
              ✕
            </button>
          </div>

          <button
            class="ghost-btn"
            @click="
              bulkAttrsDraft.push({ attribute_id: null, option_id: null })
            "
          >
            + Добавить
          </button>
          <!-- ✅ НОВЫЙ БЛОК: ИНФОРМАЦИЯ О ВЫБРАННЫХ ТОВАРАХ -->
          <div class="bulk-info">
            <div class="bulk-label">Выбранные товары:</div>
            <div v-for="p in selectedProducts" :key="p.id" class="bulk-product">
              <div class="bulk-title">#{{ p.id }} · {{ p.name }}</div>
              <div
                class="bulk-title-barcode"
                @click="copyBarcode(p.barcode)"
                title="Нажмите, чтобы скопировать"
              >
                Штрих-код {{ p.barcode }}
              </div>

              <div v-if="p.attributes?.length" class="bulk-attrs">
                <div
                  v-for="a in p.attributes"
                  :key="a.attribute_id + '_' + a.option_id"
                  class="bulk-attr-row"
                >
                  <span class="name_attributes_bulk">{{ a.name }}:</span>
                  <span class="value_attributes_bulk">{{ a.value }}</span>
                </div>
              </div>

              <div v-else class="bulk-empty">— нет характеристик —</div>
            </div>
          </div>
        </div>

        <div class="modal-foot">
          <button class="ghost-btn" @click="bulkAttrsModal = false">
            Отмена
          </button>
          <button class="save-btn" @click="saveBulkAttrs">
            Применить ко всем
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator_midnight.min.css";

const tableRef = ref(null);
let table;
let lastSelectedRow = null;
let visibleRows = [];
const categories = ref([]);
const selectedProducts = ref([]);
const isMobile = window.innerWidth <= 768;
const showRotateOverlay = ref(false);

const checkOrientation = () => {
  const isMobile = window.innerWidth <= 768;
  const isPortrait = window.innerHeight > window.innerWidth;

  showRotateOverlay.value = isMobile && isPortrait;
};

onMounted(() => {
  checkOrientation();
  window.addEventListener("resize", checkOrientation);
  window.addEventListener("orientationchange", checkOrientation);
});

/* ===== CATEGORY MODAL ===== */
const categoryModal = ref({
  open: false,
  bulk: false,
  product: null,
  selected: null,
});

const loadCategories = async () => {
  categories.value = await fetch(
    "/api/admin/product/get_categories_flat.php"
  ).then((r) => r.json());
};

const openCategory = (product) => {
  categoryModal.value = {
    open: true,
    bulk: false,
    product,
    selected: product.category_id ?? null,
  };
};

const openBulkCategory = () => {
  categoryModal.value = {
    open: true,
    bulk: true,
    product: null,
    selected: null,
  };
};

const closeCategory = () => {
  categoryModal.value.open = false;
};

const saveCategory = async () => {
  const payload = categoryModal.value.bulk
    ? {
        product_ids: selectedProducts.value.map((p) => p.id),
        category_id: categoryModal.value.selected,
      }
    : {
        product_id: categoryModal.value.product.id,
        category_id: categoryModal.value.selected,
      };

  await fetch("/api/admin/product/update_product_category.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  const ids = categoryModal.value.bulk
    ? selectedProducts.value.map((p) => p.id)
    : [categoryModal.value.product.id];

  closeCategory();
  table.deselectRow();
  selectedProducts.value = [];

  await loadProducts();
  highlightRows(ids);

  Swal.fire({
    toast: true,
    position: "bottom",
    icon: "success",
    title: "Категория обновлена",
    showConfirmButton: false,
    timer: 1400,
  });
};

const removeCategory = async () => {
  const payload = categoryModal.value.bulk
    ? {
        product_ids: selectedProducts.value.map((p) => p.id),
        category_id: null,
      }
    : {
        product_id: categoryModal.value.product.id,
        category_id: null,
      };

  await fetch("/api/admin/product/update_product_category.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  closeCategory();
  loadProducts();
};

/* ===== ATTRIBUTES (ТВОЯ ЛОГИКА) ===== */
const attrsModal = ref({ open: false, product: null });
const attrsDraft = ref([]);
const bulkAttrsModal = ref(false);
const bulkAttrsDraft = ref([{ attribute_id: null, option_id: null }]);
const allAttributes = ref([]);
const attributeOptions = ref({});

const loadAllAttributes = async () => {
  allAttributes.value = await fetch(
    "/api/admin/attribute/get_attributes.php"
  ).then((r) => r.json());
};

const openBulkAttrs = () => {
  const common = getCommonAttributes(selectedProducts.value);

  bulkAttrsDraft.value = common.length
    ? common.map((a) => ({ ...a }))
    : [{ attribute_id: null, option_id: null }];

  // подгружаем options для найденных атрибутов
  bulkAttrsDraft.value.forEach((r) => {
    if (r.attribute_id) {
      loadOptions(r.attribute_id);
    }
  });

  bulkAttrsModal.value = true;
};

const clearSelection = () => {
  table.deselectRow();
  selectedProducts.value = [];
  lastSelectedRow = null;

  const headerCb = tableRef.value?.querySelector(".checkbox-all");
  if (headerCb) headerCb.checked = false;

  // ✅ ТОЛЬКО ВИДИМЫЕ СТРОКИ
  table.getRows(true).forEach((row) => {
    const checkboxCell = row.getCells()[0];
    checkboxCell.setValue(null);
  });
};

const saveBulkAttrs = async () => {
  await fetch("/api/admin/attribute/save_attributes_bulk.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      product_ids: selectedProducts.value.map((p) => p.id),
      attributes: bulkAttrsDraft.value.filter(
        (r) => r.attribute_id && r.option_id
      ), // ← может быть []
    }),
  });

  const ids = selectedProducts.value.map((p) => p.id);

  bulkAttrsModal.value = false;
  table.deselectRow();
  selectedProducts.value = [];

  await loadProducts();
  highlightRows(ids);

  Swal.fire({
    toast: true,
    position: "bottom",
    icon: "success",
    title: "Характеристики обновлены",
    showConfirmButton: false,
    timer: 1400,
  });
};

const loadOptions = async (attributeId) => {
  if (!attributeId || attributeOptions.value[attributeId]) return;
  attributeOptions.value[attributeId] = await fetch(
    `/api/admin/attribute/get_options.php?attribute_id=${attributeId}`
  ).then((r) => r.json());
};

const openAttrs = (p) => {
  attrsModal.value = { open: true, product: p };
  attrsDraft.value = (p.attributes || []).map((a) => ({
    attribute_id: a.attribute_id,
    option_id: a.option_id,
  }));

  if (!attrsDraft.value.length) {
    attrsDraft.value = [{ attribute_id: null, option_id: null }];
  }

  attrsDraft.value.forEach((r) => loadOptions(r.attribute_id));
};

const closeAttrs = () => {
  attrsModal.value.open = false;
};

const onAttributeChange = (row) => {
  row.option_id = null;
  loadOptions(row.attribute_id);
};

const saveAttrs = async () => {
  const clean = attrsDraft.value.filter((r) => r.attribute_id && r.option_id);

  await fetch("/api/admin/attribute/save_attributes.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      product_id: attrsModal.value.product.id,
      attributes: clean,
    }),
  });

  const id = attrsModal.value.product.id;

  closeAttrs();
  await loadProducts();
  highlightRows([id]);

  Swal.fire({
    toast: true,
    position: "bottom",
    icon: "success",
    title: "Характеристики сохранены",
    showConfirmButton: false,
    timer: 1400,
  });
};

/* ===== PRODUCTS ===== */
const loadProducts = async () => {
  const data = await fetch("/api/admin/product/get_products.php").then((r) =>
    r.json()
  );

  data.forEach((p) => {
    p.__selected = false;
    p.attributes = p.attributes || [];
    p.__has_attrs = p.attributes.length > 0;
    p.attributes_text = p.attributes
      .map((a) => `${a.name}: ${a.value}`)
      .join(" · ");
  });

  table.setData(data);
};

const copyBarcode = async (code) => {
  try {
    await navigator.clipboard.writeText(code);

    // опционально: визуальный фидбек
    Swal.fire({
      toast: true,
      position: "bottom",
      icon: "success",
      title: "Штрихкод скопирован",
      showConfirmButton: false,
      timer: 1200,
    });
  } catch (e) {
    console.error("Не удалось скопировать", e);
  }
};

function getCommonAttributes(products) {
  if (!products.length) return [];

  // Берём атрибуты первого товара как основу
  const baseAttrs = products[0].attributes || [];
  const result = [];

  baseAttrs.forEach((base) => {
    const sameForAll = products.every((p) =>
      (p.attributes || []).some(
        (a) =>
          a.attribute_id === base.attribute_id && a.option_id === base.option_id
      )
    );

    if (sameForAll) {
      result.push({
        attribute_id: base.attribute_id,
        option_id: base.option_id,
      });
    }
  });

  return result;
}

const highlightRows = (ids) => {
  setTimeout(() => {
    ids.forEach((id) => {
      const row = table.getRow(id);
      if (!row) return;

      const el = row.getElement();
      el.classList.add("row-updated");

      setTimeout(() => {
        el.classList.remove("row-updated");
      }, 1800);
    });
  }, 150); // ждём, пока table.setData отработает
};

const desktopColumns = [
  {
    title: "",
    field: "__select",
    width: 50,
    headerSort: false,
    hozAlign: "center",

    // ✅ чекбокс в заголовке
    titleFormatter: () =>
      `<input type="checkbox" class="checkbox checkbox-all" />`,
    titleFormatterParams: {},

    // ✅ клик по заголовку (по чекбоксу)
    headerClick: (e) => {
      const cb = e.target.closest(".checkbox-all");
      if (!cb) return;

      const rows = visibleRows;
      const allSelected = rows.length > 0 && rows.every((r) => r.isSelected());

      if (allSelected) {
        rows.forEach((r) => r.deselect());
        cb.checked = false;
      } else {
        rows.forEach((r) => r.select());
        cb.checked = true;
      }

      rows.forEach((r) => r.getCells()[0].setValue(null));
      selectedProducts.value = table.getSelectedData(true);

      lastSelectedRow = null;
    },

    // ✅ чекбоксы в строках
    formatter: (cell) => {
      const checked = cell.getRow().isSelected();
      return `<input type="checkbox" class="checkbox" ${
        checked ? "checked" : ""
      } />`;
    },

    cellClick: (e, cell) => {
      const row = cell.getRow();
      const rows = visibleRows;

      if (e.shiftKey && lastSelectedRow) {
        const start = rows.indexOf(lastSelectedRow);
        const end = rows.indexOf(row);

        if (start !== -1 && end !== -1) {
          const [from, to] = start < end ? [start, end] : [end, start];

          rows.slice(from, to + 1).forEach((r) => {
            r.select();
            r.getCells()[0].setValue(null);
          });
        }
      } else {
        row.toggleSelect();
        cell.setValue(null);
      }

      lastSelectedRow = row;
      selectedProducts.value = table.getSelectedData(true);
      const headerCb = tableRef.value?.querySelector(".checkbox-all");
      if (headerCb) {
        headerCb.checked =
          table.getRows(true).length > 0 &&
          table.getRows(true).every((r) => r.isSelected());
      }
    },
  },

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

      saveBtn.onclick = async () => {
        const newValue = input.value.trim();

        if (!newValue || newValue === oldValue) {
          cell.setValue(oldValue, true);
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

        row.update({ name: newValue });
      };

      cancelBtn.onclick = () => {
        cell.setValue(oldValue, true);
      };
    },
  },
  {
    title: "Характеристики",
    field: "attributes_text",
    formatter: (cell) => {
      const row = cell.getRow().getData();
      return row.__has_attrs
        ? `<button class="mini-btn edit-btn">Изменить</button>`
        : `<button class="mini-btn add-btn">Добавить</button>`;
    },
    cellClick: (e, cell) => {
      const row = cell.getRow().getData();
      if (e.target.classList.contains("add-btn"))
        openAttrs({ ...row, attributes: [] });
      if (e.target.classList.contains("edit-btn")) openAttrs(row);
    },
  },
  {
    title: "Категория",
    minWidth: 450,
    formatter: (cell) => {
      const p = cell.getRow().getData();
      return `
            <div class="cat-edit">
              <span class="cat-text">${
                p.category_path || "— Без категории —"
              }</span>
              <button class="mini-btn edit-cat">Изменить</button>
            </div>
          `;
    },
    cellClick: (e, cell) => {
      if (e.target.classList.contains("edit-cat")) {
        openCategory(cell.getRow().getData());
      }
    },
  },
  {
    title: "Цена",
    field: "price",
    hozAlign: "right",
    headerFilter: "input",
    formatter: (cell) =>
      cell.getValue()
        ? `<span class="t-price">${cell.getValue()}</span>`
        : `<span class="t-empty">—</span>`,
  },
  {
    title: "Штрихкод",
    field: "barcode",
    headerFilter: "input",
    formatter: (cell) =>
      cell.getValue()
        ? `<span class="t-barcode" title="Нажмите, чтобы скопировать">
           ${cell.getValue()}
         </span>`
        : `<span class="t-empty">—</span>`,

    cellClick: (e, cell) => {
      const value = cell.getValue();
      if (!value) return;

      copyBarcode(value);
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
];
const mobileColumns = [
  {
    title: "",
    width: 50,
    hozAlign: "center",
    headerSort: false,

    titleFormatter: desktopColumns[0].titleFormatter,
    headerClick: desktopColumns[0].headerClick,

    formatter: desktopColumns[0].formatter,
    cellClick: desktopColumns[0].cellClick,
  },

  {
    title: "Название",
    field: "name",
    minWidth: 260,
    formatter: desktopColumns[2].formatter,
    cellClick: desktopColumns[2].cellClick,
  },
  {
    title: "Цена",
    field: "price",
  },
  {
    title: "Характеристики",
    field: "attributes_text",
  },
  {
    title: "Штрихкод",
    field: "barcode",
  },
];

/* ===== INIT TABLE ===== */
onMounted(async () => {
  await loadCategories();
  await loadAllAttributes();

  table = new Tabulator(tableRef.value, {
    layout: "fitDataStretch",
    height: "70vh",
    selectable: true,

    columns: isMobile ? mobileColumns : desktopColumns,

    rowClick: (e, row) => {
      if (
        e.target.closest("button") ||
        e.target.tagName === "INPUT" ||
        e.target.tagName === "SELECT"
      )
        return;

      const rows = visibleRows;

      if (e.shiftKey && lastSelectedRow) {
        const start = rows.indexOf(lastSelectedRow);
        const end = rows.indexOf(row);

        if (start !== -1 && end !== -1) {
          const [from, to] = start < end ? [start, end] : [end, start];
          rows.slice(from, to + 1).forEach((r) => r.select());
        }
      } else {
        row.toggleSelect();
      }

      lastSelectedRow = row;
      row.reformat();
      selectedProducts.value = table.getSelectedData(true);
    },
  });

  table.on("dataFiltered", (filters, rows) => {
    visibleRows = rows; // 🔥 ВОТ ЧЕГО НЕ ХВАТАЛО

    table.deselectRow();
    selectedProducts.value = [];
    lastSelectedRow = null;

    const headerCb = tableRef.value?.querySelector(".checkbox-all");
    if (headerCb) headerCb.checked = false;
  });

  loadProducts();
});
</script>

<style scoped>
/* ================================================= */
/* ================= PAGE =========================== */
/* ================================================= */
.cat-tree-row {
  padding: 6px 0;
}
.cat-radio {
  display: flex;
  gap: 10px;
  align-items: center;
  cursor: pointer;
}
.admin-page {
  min-height: 100vh;
  padding: 28px 36px;
  background: #0b0e14;
  color: #e9ecf4;
  font-family: Inter, system-ui, Arial, sans-serif;
}

.block-title {
  font-size: 16px;
  font-weight: 800;
}

.head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

/* ================================================= */
/* ================= INPUTS ========================= */
/* ================================================= */
:deep(.checkbox) {
  appearance: none;
  width: 18px;
  height: 18px;
  border: 2px solid #4f6cff;
  border-radius: 5px;
  background: transparent;
  cursor: pointer;
  position: relative;
}

:deep(.checkbox:checked) {
  background: #4f6cff;
}

:deep(.checkbox:checked::after) {
  content: "";
  position: absolute;
  left: 5px;
  top: 1px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.input,
.select,
.textarea {
  cursor: pointer;
  background: #121827;
  color: #e9ecf4;
  border: 1px solid #4f6cff;
  border-radius: 12px;
  padding: 11px 13px;
  margin-top: 10px;
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
.head-actions {
  display: flex;
  gap: 30px;
  align-items: center;
}
.save-btn {
  background: #4f6cff;
  border: none;
  color: #ffffff;
  border-radius: 14px;
  padding: 12px 18px;
  font-weight: 800;
  cursor: pointer;
}

.danger-clear {
  background-color: var(--delete-color);
}

.danger-clear:hover {
  background: rgba(255, 92, 92, 0.12);
}

.ghost-btn {
  background: var(--cancel-color);
  border: none;
  color: #e9ecf4;
  margin-top: 15px;
  border-radius: 12px;
  padding: 10px 14px;
  font-weight: 700;
  cursor: pointer;
}

.attr-row {
  display: flex;
  align-items: center;
  gap: 20px;
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
:deep(.t-brand) {
  color: #4fc3f7;
  font-weight: 700;
}

:deep(.t-type) {
  color: #ce93d8;
  font-weight: 700;
}

:deep(.t-price) {
  color: #81c784;
  font-weight: 800;
}

:deep(.t-barcode) {
  color: #90caf9;
  font-weight: 700;
  letter-spacing: 0.3px;
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
:deep(.edit-cat) {
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
  transition: 0.5s;
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
  transition: 0.5s;
}

:deep(.cancel-name:hover),
:deep(.cancel-cat:hover) {
  background-color: rgb(255, 158, 158);
  color: black;
}

:deep(.name-input) {
  padding: 10px;
  border-radius: 5px;
  border: 2px solid black;
  background-color: var(--accent-color);
  width: 70%;
}

:deep(.name-edit),
:deep(.cat-edit) {
  display: flex;
  gap: 10px;
  flex-direction: row;
}

:deep(.name-text) {
  display: block;
  padding-right: 120px;
  white-space: normal;
  line-height: 1.3;
}

/* ===== HEADER FILTER STYLE ===== */
:deep(.tabulator-header-filter input) {
  appearance: none;
  border-radius: 10px;
  background-color: white !important;
  color: black !important;
  transition: 0.5s;
}

:deep(.tabulator .tabulator-header .tabulator-col) {
  background-color: #333 !important;
  transition: 0.5s;
}

:deep(.tabulator .tabulator-header .tabulator-col:hover) {
  background-color: #941b0c !important;
}

:deep(.tabulator .tabulator-header .tabulator-col:hover input) {
  background-color: black !important;
  color: white !important;
  border-radius: 0px;
}

.goods_counter {
  margin-top: 15px;
}

.number {
  color: var(--accent-color);
  font-weight: bold;
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
}

.modal {
  width: min(900px, 96vw);
  max-height: 86vh;
  display: flex;
  flex-direction: column;
  background: #121827;
  border-radius: 18px;
  margin-top: 50px;
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

.bulk-info {
  margin: 20px 0;
  padding: 14px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
}

.bulk-product {
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.02);
  margin: 10px 0;
}

.bulk-product:last-child {
  margin-bottom: 0;
}

.bulk-title {
  font-size: 14px;
  font-weight: 600;
  color: white;
  margin-bottom: 6px;
  display: flex;
  gap: 6px;
  align-items: center;
}

.bulk-title::before {
  content: "•";
  color: var(--accent-color);
  font-size: 18px;
  line-height: 1;
}

.bulk-title-barcode {
  font-size: 12px;
  color: var(--accent-color);
  opacity: 0.9;
  cursor: pointer;
  user-select: none;
  transition: 0.2s;
}

.bulk-title-barcode:hover {
  opacity: 1;
  text-decoration: underline;
}

.bulk-title-barcode:active {
  transform: scale(0.96);
}

.bulk-attrs {
  margin-left: 14px;
  padding-left: 10px;
  border-left: 2px solid rgba(255, 255, 255, 0.08);
}

.bulk-attr-row {
  font-size: 13px;
  line-height: 1.4;
  color: rgba(255, 255, 255, 0.85);
  margin-bottom: 3px;
}

.bulk-attr-row:last-child {
  margin-bottom: 0;
}

.bulk-attr-row::before {
  content: "—";
  margin-right: 6px;
  color: rgba(255, 255, 255, 0.35);
}

.bulk-empty {
  margin-left: 14px;
  font-size: 13px;
  font-style: italic;
  color: rgba(255, 255, 255, 0.45);
}

.name_attributes_bulk {
  color: #4fc3f7;
}

.value_attributes_bulk {
  color: #ff9900;
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

.danger-btn {
  width: 36px;
  height: 36px;
  background: #2a1a1a;
  border: 1px solid #e53935;
  color: #ffffff;
  border-radius: 10px;
  cursor: pointer;
}

/* ================= ВЫБРАННАЯ СТРОКА ================= */
:deep(.tabulator-row.tabulator-selected) {
  background: rgba(94, 255, 9, 0.35) !important;
  box-shadow: inset 0px 0 5px 1px #000000;
}

/* hover по выбранной */
:deep(.tabulator-row.tabulator-selected:hover) {
  background: rgb(255 1 1 / 25%) !important;
}

:deep(.tabulator-row.tabulator-selected .name-text),
:deep(.tabulator-row.tabulator-selected .t-brand),
:deep(.tabulator-row.tabulator-selected .t-price),
:deep(.tabulator-row.tabulator-selected .t-barcode),
:deep(.tabulator-row.tabulator-selected .cat-text),
:deep(.tabulator-row.tabulator-selected .t-type) {
  color: #000 !important;
  font-weight: 700;
}

:deep(.tabulator-row.row-updated) {
  animation: rowFlash 1.6s ease;
}

@keyframes rowFlash {
  0% {
    box-shadow: inset 0 0 0 9999px rgba(0, 180, 90, 0.35);
  }
  100% {
    box-shadow: inset 0 0 0 9999px rgba(0, 180, 90, 0);
  }
}

.rotate-overlay {
  position: fixed;
  inset: 0;
  background: #0e0e0ef2;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rotate-box {
  text-align: center;
  color: #fff;
  max-width: 300px;
}

.rotate-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.rotate-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
}

.rotate-text {
  font-size: 14px;
  opacity: 0.85;
}

@media (max-width: 768px) {
  .attr-row {
    gap: 5px;
  }

  .input {
    padding: 8px 5px;
    width: 40%;
  }

  .danger-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 25px;
    height: 25px;
  }
}
</style>
