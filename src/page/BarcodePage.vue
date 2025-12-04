<template>
  <div class="page">
    <!-- === ПАНЕЛЬ ДЛЯ ВЫБРАННЫХ === -->
    <div v-if="selectedIds.length >= 2" class="selected-controls">
      <!-- БЛОК 1 — РАЗМЕР -->
      <div class="bulk-block bulk-size">
        <p class="bulk-title">Размер этикетки:</p>

        <select
          v-model="bulkSize"
          @change="applyBulkSize"
          class="bulk-size-select"
        >
          <option v-for="s in labelSizes" :key="s.value" :value="s.value">
            {{ s.text }}
          </option>
        </select>
        <i class="fa-solid fa-chevron-down select-arrow" aria-hidden="true"></i>
      </div>

      <!-- БЛОК 2 — НАЗВАНИЕ -->
      <div class="bulk-block bulk-name">

        <label class="param-row">
          <input
            type="checkbox"
            v-model="bulkWithName"
            @change="applyBulkName"
          />
          Печатать название
        </label>
      </div>

      <!-- БЛОК 3 — ЦЕНА -->
      <div class="bulk-block bulk-price">

        <label class="param-row">
          <input
            type="checkbox"
            v-model="bulkWithPrice"
            @change="applyBulkPrice"
          />
          Печатать цену
        </label>
      </div>

      <!-- КНОПКА ПЕЧАТЬ -->
      <button class="floating-print" @click="printSelected">
        <i class="fa-solid fa-print"></i>
        Печать выбранных ({{ selectedIds.length }})
      </button>

      <!-- СБРОС -->
      <button class="cancel-edit-btn" @click="clearSelected">
        <i class="fa-solid fa-xmark"></i>
        Снять выделение
      </button>
      <button class="delete-selected-btn" @click="deleteSelected">
        <i class="fa-solid fa-trash"></i>
        Удалить выбранные ({{ selectedIds.length }})
      </button>
      <button class="button-main button-export" @click="exportSelected">
  <i class="fa-solid fa-file-excel"></i>
  Экспорт ({{ selectedIds.length }})
</button>

    </div>

    <!-- === ВЕРХ === -->
    <div class="top-row">
      <!-- СОЗДАНИЕ / РЕДАКТ -->
      <div class="create-box">
        <div v-if="message" :class="['msg-absolute', messageType]">
          {{ message }}
        </div>

        <h2 class="block-title">
          {{ editMode ? "Редактировать штрихкод" : "Создать штрихкод" }}
        </h2>

        <div class="create-row">
          <input v-model="name" placeholder="Название" />
          <input v-model="article" placeholder="Артикул" />
          <input v-model="stock" placeholder="Текущий остаток" />
          <input v-model="contractor" placeholder="Контрагент" />
          <input v-model="price" placeholder="Цена (необязательно)" />

          <button
            class="button-main"
            @click="editMode ? saveEdit() : createBarcode()"
          >
            {{ editMode ? "Сохранить" : "Создать" }}
          </button>

          <button
            v-if="!manualMode && !editMode"
            class="button-main"
            @click="manualMode = true"
          >
            Создать вручную
          </button>

          <button
            v-if="manualMode && !editMode"
            class="cancel-edit-btn"
            @click="cancelManualMode"
          >
            Отменить
          </button>

          <button v-if="editMode" class="cancel-edit-btn" @click="cancelEdit">
            Отменить
          </button>
        </div>

        <div v-if="manualMode" class="manual-field">
          <input
            v-model="manualBarcode"
            placeholder="Введите штрихкод вручную"
          />
        </div>

        <!-- Фото -->
        <div class="photo-section">
          <div v-if="!photoPreview" class="photo-btn" @click="openCameraModal">
            <i class="fa-solid fa-camera"></i> Сделать фото
          </div>

          <div v-else class="photo-controls">
            <img :src="photoPreview" class="photo-thumb" />

            <div class="photo-buttons-row">
              <div class="photo-btn" @click="openCameraModal">
                <i class="fa-solid fa-camera-rotate"></i> Переснять
              </div>

              <div class="photo-delete" @click="removePhoto">
                <i class="fa-solid fa-trash"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- ПОИСК -->
      <div class="search-container">
        <h2 class="block-title">Поиск</h2>
        <input
          v-model="search"
          class="search-input"
          placeholder="Поиск"
          @input="searchChanged"
        />
      </div>
    </div>

    <!-- === СПИСОК === -->
    <div class="list-section">
      <div class="grid">
        <div class="card" v-for="item in barcodes" :key="item.id">
          <div class="card-tools">
            <div
              class="card-tool-btn card-tool-edit"
              @click.stop="startEdit(item)"
            >
              <i class="fa-solid fa-pen"></i>
            </div>
            <div
              class="card-tool-btn card-tool-delete"
              @click.stop="deleteItem(item.id)"
            >
              <i class="fa-solid fa-trash"></i>
            </div>
          </div>

          <div class="card-checkbox">
            <label
              class="button-main select-button"
              :class="selectedIds.includes(item.id) ? 'active' : ''"
              @click.stop="toggleSelect(item.id)"
            >
              {{ selectedIds.includes(item.id) ? "Выбрано" : "Выбрать"
              }}<i
                v-if="selectedIds.includes(item.id)"
                class="fa-solid fa-check select-icon"
              ></i>
            </label>

            <input
              type="checkbox"
              :value="item.id"
              v-model="selectedIds"
              class="hidden-checkbox"
            />
          </div>

          <div class="card-left">
            <svg :id="'g-' + item.id"></svg>

            <p class="code" v-html="highlight(item.barcode, search)"></p>

            <div class="print-params">
              <div class="param-row-container">
                <label class="param-row">
                  <input type="checkbox" v-model="item._withName" />
                  Название
                </label>

                <label class="param-row">
                  <input type="checkbox" v-model="item._withPrice" />
                  Цена
                </label>
              </div>
              <div class="label-size-box">
                <div class="select-wrap">
                  <select v-model="item._size" class="label-size-select">
                    <option
                      v-for="s in labelSizes"
                      :key="s.value"
                      :value="s.value"
                    >
                      {{ s.text }}
                    </option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-arrow"></i>
                </div>

                <button class="button-main" @click.stop="openPrint(item)">
                  <i class="fa-solid fa-print"></i> Печать
                </button>
              </div>
            </div>
          </div>

          <div class="card-right">
            <div class="card-information">
              <div class="information-row">
                <div class="information-title"><b>Товар:</b><i class="fa-solid fa-copy copy-icon" @click="copy(item.product_name)"></i></div>
                <div
                  class="information-text"
                  v-html="highlight(item.product_name, search)"
                ></div>
              </div>

              <div class="information-row">
                <div class="information-title"><b>Артикул:</b><i class="fa-solid fa-copy copy-icon" @click="copy(item.product_name)"></i></div>
                <div
                  class="information-text"
                  v-html="highlight(item.sku, search)"
                ></div>
              </div>

              <div class="information-row">
  <div class="information-title"><b>Остаток:</b><i class="fa-solid fa-copy copy-icon" @click="copy(item.product_name)"></i></div>
  <div class="information-text">{{ item.stock }}</div>
</div>


              <div class="information-row">
                <div class="information-title"><b>Контрагент:</b><i class="fa-solid fa-copy copy-icon" @click="copy(item.product_name)"></i></div>
                <div
                  class="information-text"
                  v-html="highlight(item.contractor, search)"
                ></div>
              </div>

              <div class="information-row">
                <div class="information-title"><b>Цена:</b><i class="fa-solid fa-copy copy-icon" @click="copy(item.product_name)"></i></div>
                <div
                  class="information-text"
                  v-html="highlight(item.price, search)"
                ></div>
              </div>
            </div>

            <!-- Настройки печати одной -->
            <div class="card-photo-box" v-if="item.photo">
              <img
                :src="item.photo"
                class="card-photo"
                @click.stop="openPhoto(item.photo)"
              />
            </div>
            <p v-else class="no-photo-text">Без фото</p>
          </div>
        </div>
      </div>
    </div>

    <!-- МОДАЛЫ -->
    <div v-if="cameraOpen" class="camera-overlay">
      <div class="camera-window">
        <video ref="video" autoplay playsinline class="cam-video"></video>

        <button class="btn-capture" @click="takePhoto">
          <i class="fa-solid fa-camera"></i>
        </button>

        <button class="btn-close" @click="closeCameraModal">Закрыть</button>
      </div>
    </div>

    <div v-if="photoModalOpen" class="photo-modal-overlay" @click="closePhoto">
      <div class="photo-modal-content" @click.stop>
        <img :src="photoModalSrc" class="photo-modal-img" />
        <button class="photo-modal-close" @click="closePhoto">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from "vue";
import JsBarcode from "jsbarcode";

const bulkWithName = ref(false);
const bulkWithPrice = ref(false);

const manualMode = ref(false);
const manualBarcode = ref("");

const labelSizes = [
  { value: "30x20", text: "30 × 20 мм" },
  { value: "42x25", text: "42 × 25 мм" },
];

function toggleSelect(id) {
  const arr = selectedIds.value;
  const idx = arr.indexOf(id);

  if (idx === -1) {
    arr.push(id); // выбрать
  } else {
    arr.splice(idx, 1); // убрать выбор
  }
}

function highlight(text, search) {
  if (!text) return "";
  if (!search) return text;

  const escaped = search.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const regex = new RegExp(escaped, "gi");

  return text.replace(regex, (match) => {
    return `<span class="highlight-row">${match}</span>`;
  });
}

function copy(text) {
  if (!text) return;
  navigator.clipboard.writeText(text.toString());
  showMessage("Скопировано!", "success");
}

const bulkSize = ref(labelSizes[0].value);

const message = ref("");
const messageType = ref("");

const showMessage = (t, type = "info") => {
  message.value = t;
  messageType.value = type;
  setTimeout(() => (message.value = ""), 3000);
};

function applyBulkName() {
  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) {
      i._withName = bulkWithName.value;
    }
  });
}

function applyBulkPrice() {
  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) {
      i._withPrice = bulkWithPrice.value;
    }
  });
}

const editMode = ref(false);
const editId = ref(null);

const name = ref("");
const article = ref("");
const stock = ref("");
const contractor = ref("");
const price = ref("");

const photoFile = ref(null);
const photoPreview = ref(null);

function removePhoto() {
  photoFile.value = null;
  photoPreview.value = null;
}

const barcodes = ref([]);
const selectedIds = ref([]);

const search = ref("");

const photoModalOpen = ref(false);
const photoModalSrc = ref("");

function openPhoto(src) {
  photoModalSrc.value = src;
  photoModalOpen.value = true;
}
function closePhoto() {
  photoModalOpen.value = false;
}

const cameraOpen = ref(false);
const video = ref(null);
let stream = null;

function openCameraModal() {
  cameraOpen.value = true;
  startCamera();
}

async function startCamera() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.value.srcObject = stream;
  } catch {
    showMessage("Нет доступа к камере", "error");
  }
}

function closeCameraModal() {
  cameraOpen.value = false;
  if (stream) stream.getTracks().forEach((t) => t.stop());
}

function takePhoto() {
  const canvas = document.createElement("canvas");
  canvas.width = video.value.videoWidth;
  canvas.height = video.value.videoHeight;

  const ctx = canvas.getContext("2d");
  ctx.drawImage(video.value, 0, 0);

  canvas.toBlob(
    (blob) => {
      const file = new File([blob], "photo.jpg", { type: "image/jpeg" });
      photoFile.value = file;
      photoPreview.value = URL.createObjectURL(file);
      showMessage("Фото сделано!", "success");
      closeCameraModal();
    },
    "image/jpeg",
    0.9
  );
}

async function loadBarcodes() {
  const r = await fetch(
    "/api/get_barcodes.php?search=" + encodeURIComponent(search.value)
  );
  barcodes.value = (await r.json()).map((b) => ({
    ...b,
    _size: "30x20", // ← новый размер по умолчанию
    _withName: false,
    _withPrice: false,
  }));

  renderGrid();
}

let timer = null;
function searchChanged() {
  clearTimeout(timer);
  timer = setTimeout(loadBarcodes, 300);
}

async function createBarcode() {
  const nameLen = name.value.trim().length;
  const skuLen = article.value.trim().length;
  const contrLen = contractor.value.trim().length;

  if (nameLen < 2 && skuLen < 2 && contrLen < 2) {
    showMessage(
      "Введите название или артикул или контрагента (минимум 2 символа)",
      "error"
    );
    return;
  }

  let code = "";

  if (manualMode.value) {
    if (!manualBarcode.value.trim()) {
      showMessage("Введите штрихкод!", "error");
      return;
    }
    code = manualBarcode.value.trim();
  } else {
    code = await generateBarcode();
  }

  const form = new FormData();
  form.append("barcode", code);
  form.append("product_name", name.value);
  form.append("sku", article.value);
  form.append("stock", stock.value);
  form.append("contractor", contractor.value);
  form.append("price", price.value);

  if (photoFile.value) form.append("photo", photoFile.value);

  const res = await fetch("/api/create_barcode.php", {
    method: "POST",
    body: form,
  });

  const data = await res.json();

  if (data.status === "error") {
    showMessage(data.message || "Ошибка при создании", "error");
    return;
  }

  showMessage("Создано!", "success");
  resetForm();
  await loadBarcodes();
}

function cancelManualMode() {
  manualMode.value = false;
  manualBarcode.value = "";
}

function randDigit() {
  return Math.floor(Math.random() * 10);
}
function genNumber9() {
  return Array.from({ length: 9 }, () => randDigit()).join("");
}

async function checkExists(code) {
  const r = await fetch("/api/check_barcode.php?barcode=" + code);
  return (await r.json()).exists;
}

async function generateBarcode() {
  while (true) {
    const num = genNumber9(); // генерируем 9 цифр
    const code = "99" + num; // итоговый код без пробелов и знаков

    if (!(await checkExists(code))) {
      // проверка уникальности
      return code;
    }
  }
}

function startEdit(item) {
  editMode.value = true;
  editId.value = item.id;

  name.value = item.product_name || "";
  article.value = item.sku || "";
  stock.value = item.stock || "";
  contractor.value = item.contractor || "";
  price.value = item.price || "";

  photoPreview.value = item.photo || null;
  photoFile.value = null;

  manualMode.value = true;
  manualBarcode.value = item.barcode;

  window.scrollTo({ top: 0, behavior: "smooth" });
}

function cancelEdit() {
  resetForm();
}

async function saveEdit() {
  const form = new FormData();

  form.append("id", editId.value);
  form.append("product_name", name.value);
  form.append("sku", article.value);
  form.append("stock", stock.value);
  form.append("contractor", contractor.value);
  form.append("price", price.value);

  form.append("barcode", manualBarcode.value.trim());

  if (photoFile.value) form.append("photo", photoFile.value);
  if (!photoPreview.value) form.append("remove_photo", "1");

  const r = await fetch("/api/update_barcode.php", {
    method: "POST",
    body: form,
  });

  const d = await r.json();

  if (d.status === "success") {
    showMessage("Сохранено!", "success");
    resetForm();
    await loadBarcodes();
  }
}

function resetForm() {
  editMode.value = false;
  editId.value = null;
  name.value = "";
  article.value = "";
  stock.value = "";
  contractor.value = "";
  price.value = "";
  removePhoto();

  manualMode.value = false;
  manualBarcode.value = "";
}

async function deleteItem(id) {
  if (!confirm("Удалить штрихкод?")) return;

  const r = await fetch("/api/delete_barcode.php?id=" + id);
  const d = await r.json();

  if (d.status === "success") {
    showMessage("Удалено", "success");
    selectedIds.value = selectedIds.value.filter((x) => x != id);
    await loadBarcodes();
  }
}


async function deleteSelected() {
  if (!confirm("Удалить все выбранные штрихкоды?")) return;

  for (const id of selectedIds.value) {
    await fetch("/api/delete_barcode.php?id=" + id);
  }

  showMessage("Удалено: " + selectedIds.value.length, "success");

  selectedIds.value = [];
  await loadBarcodes();
}

async function exportSelected() {
  if (selectedIds.value.length === 0) {
    showMessage("Нет выбранных штрихкодов", "error");
    return;
  }

  const payload = JSON.stringify(selectedIds.value);

  // Скачать Excel
  window.open("/api/export_excel.php?ids=" + encodeURIComponent(payload), "_blank");
}

function openPrint(item) {
  const withName = item._withName ? 1 : 0;
  const withPrice = item._withPrice ? 1 : 0;

  window.open(
    `/api/print.php?id=${item.id}&size=${item._size}&withName=${withName}&withPrice=${withPrice}`,
    "_blank"
  );
}

function printSelected() {
  const payload = {};

  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) {
      payload[i.id] = {
        size: bulkSize.value,
        withName: i._withName,
        withPrice: i._withPrice,
      };
    }
  });

  const encoded = encodeURIComponent(JSON.stringify(payload));
  window.open(`/api/print_bulk.php?data=${encoded}`, "_blank");
}

function applyBulkSize() {
  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) {
      i._size = bulkSize.value;
    }
  });
}

function clearSelected() {
  selectedIds.value = [];
}

function renderGrid() {
  nextTick(() => {
    barcodes.value.forEach((item) => {
      const el = document.getElementById("g-" + item.id);
      if (el) {
        JsBarcode(el, item.barcode.replace("-", ""), {
          format: "code128",
          height: 50,
          displayValue: true,
          text: item.barcode,
        });
      }
    });
  });
}

function isMatch(text) {
  if (!text) return false;
  const s = search.value.trim().toLowerCase();
  if (!s) return false;
  return text.toString().toLowerCase().includes(s);
}

onMounted(loadBarcodes);
</script>

<style>


.highlight-row {
  background: var(--delete-color) !important;
  color: #ffffff !important;
  padding: 2px 4px;
  border-radius: 6px;
}

.card-left {
  width: 260px;
  /* фиксированная ширина */
  flex-shrink: 0;
  /* не сжимается */
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* =========================
   ПРАВАЯ ЧАСТЬ
========================= */
.card-right {
  flex: 1;
  /* занимает всё оставшееся место */
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.card-information {
  width: 370px; /* фиксированная ширина */
  display: flex;
  gap: 35px;
}

.information-title {
  min-width: 95px; /* фикс - выравнивает левую часть */
  flex-shrink: 0; /* НЕ сжимается */
}

.information-text {
  word-break: break-word; /* переносит длинные слова */
  white-space: normal;
  overflow-wrap: break-word;
}

.copy-icon {
  margin-left: 8px;
  font-size: 15px;
  color: var(--accent-color);
  cursor: pointer;
  transition: 0.15s;
}

.copy-icon:hover {
  color: var(--accent-color);
  transform: scale(1.15);
}

.param-row-container {
display: flex;
    flex-direction: row;
    gap: 20px;
    justify-content: center;
}
.grid {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

/* =========================
   КАРТОЧКА
========================= */

.card {
  position: relative;
  background: var(--background-container);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 10px;
  display: flex;
  flex-direction: row;
  gap: 25px;
  min-height: 260px;
  box-sizing: border-box;
  transition: 0.25s;
}

.card:hover {
  box-shadow: 0 0 3px 1px #ffffff63;
}

.card-checkbox {
  position: absolute;
  bottom: 20px;
  right: 20px;
}

.param-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-checkbox input[type="checkbox"],
.param-row input[type="checkbox"] {
  width: 22px;
  height: 22px;
  accent-color: var(--accent-color);
  cursor: pointer;
}

.card-tools {
  position: absolute;
  top: 55px;
  right: 15px;

  display: flex;
  gap: 10px;
}

.card-tool-btn {
  width: 36px;
  height: 36px;
  background: var(--background-input);
  border: 1px solid var(--border-color);
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  cursor: pointer;
  transition: 0.2s;
}

.card-tool-edit {
  color: var(--accent-color);
}

.card-tool-delete {
  color: var(--delete-color);
}

.card-tool-btn:hover {
  background: var(--border-color);
  transform: translateY(-2px);
}

/* =========================
   ЛЕВАЯ ЧАСТЬ (баркод + код + фото)
========================= */

svg {
  width: 100%;
  max-width: 240px;
}

.code {
  margin-top: 8px;
  color: var(--accent-color);
  font-weight: bold;
  font-size: 16px;
}

/* фото внутри карточки */
.card-photo-box {
  width: 100%;
}

.card-photo {
width: 200px;
    height: 130px;
    object-fit: cover;
    border-radius: 12px;
    cursor: pointer;
}

.no-photo-text {
  margin-top: 20px;
  color: var(--unactive);
  font-size: 14px;
  margin-left: 20px;
}

/* =========================
   ЧЕКБОКС "с названием"
========================= */

.print-options {
  margin: 10px 0 15px 0;
  font-size: 14px;
  color: var(--accent-color);
}

.print-options label {
  display: flex;
  gap: 7px;
  align-items: center;
  cursor: pointer;
}

.print-options input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--accent-color);
  cursor: pointer;
}

/* =========================
   НИЖНИЙ БЛОК — ВЫБОР РАЗМЕРА
========================= */

.label-size-box {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid var(--border-color);

  display: flex;
  align-items: center;
  gap: 15px;
}

/* селект */
.select-wrap {
  position: relative;
  width: 120px;
}

.label-size-select,
.bulk-size-select {
    width: 100%;
    padding: 10px;
  background: var(--background-container);
  color: var(--accent-color);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  cursor: pointer;
  appearance: none;
  font-size: 14px;
}

.label-size-select:hover,
.bulk-size-select:hover {
  border-color: var(--cancel-color);
}

/* стрелка */
.select-wrap .select-arrow {
  position: absolute;
  left: 95px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  color: var(--accent-color);
  pointer-events: none;
}

.bulk-block .select-arrow {
  position: absolute;
  right: 30px;
  top: 80px;
  color: var(--accent-color);
}

.manual-field input {
  margin-top: 10px;
}

.selected-controls {
position: fixed;
    left: 40px;
    bottom: 10px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    z-index: 9999;
    background: var(--background-container);
    padding: 20px;
    border-radius: 20px;
}

.bulk-size {
  grid-column: 1 / 3;
}


.cancel-edit-btn {
    background: var(--cancel-color);
    padding: 12px;
    border-radius: 12px;
    grid-column: 2 / 3;
    color: #fff;
    border: none;
    font-weight: 700;
    cursor: pointer;
}

.delete-selected-btn {
background: var(--delete-color);
    grid-column: 1 / 2;
    padding: 12px;
    border-radius: 12px;
    color: #fff;
    border: none;
    font-weight: 700;
    cursor: pointer;
    font-size: 15px;
    box-shadow: 0 0 10px var(--background-container);
    transition: .3;
}



.delete-selected-btn:hover {
  background: #ff6666;
}



.delete-selected-btn:hover, .floating-print:hover, .cancel-edit-btn:hover, .button-export:hover {
  transform: translateY(-3px);
  transition: .3s;
}

.button-export {
  grid-column: 2 / 3;

}

/* === ПЛАВАЮЩАЯ КНОПКА ПЕЧАТИ === */
.floating-print {
    background: var(--accent-color);
    grid-column: 1 / 2;
    color: var(--background-container);
    padding: 14px 22px;
    border-radius: 14px;
    border: none;
    font-weight: 700;
    cursor: pointer;
    z-index: 9999;
    font-size: 16px;
    box-shadow: 0 0 15px var(--background-container);
    transition: .2s;
}

.floating-print:hover {
  background: var(--accent-color);
  transform: translateY(-3px);
}

/* ——— УВЕДОМЛЕНИЯ ——— */
.msg-absolute {
  position: fixed;
  top: 20px;
  left: 40px;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: bold;
  z-index: 9999;
  animation: fadeInOut 3s ease-out;
}

.msg-absolute.success {
  background: #1c4821;
  color: #baffc4;
  border: 1px solid #25a134;
}

.msg-absolute.error {
  background: #5b1a1a;
  color: #ffd5d5;
  border: 1px solid var(--delete-color);
}

@keyframes fadeInOut {
  0% {
    opacity: 0;
    transform: translateY(-8px);
  }

  15% {
    opacity: 1;
    transform: translateY(0);
  }

  80% {
    opacity: 1;
  }

  100% {
    opacity: 0;
  }
}



.page {
  margin: 0 auto;
  padding: 20px;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
}

.top-row {
width: 40%;
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
    flex-direction: column;
    align-items: flex-start;
}

.list-section {
  width: 50%;
}

.create-box {
  width: 100%;
  background: var(--background-container);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 20px;
  min-height: 210px;
  box-shadow: 0 0 12px var(--background-container)5;
}

.search-container {
  width: 80%;
}

.block-title {
  margin-bottom: 14px;
  color: var(--accent-color);
  font-size: 20px;
}

/* ——— ПОИСК ——— */
.search-input {
  width: 100%;
  padding: 15px;
  border-radius: 12px;
  background: var(--background-container);
  border: 1px solid var(--border-color);
  color: white;
  font-size: 16px;
}

/* ——— СОЗДАНИЕ ——— */
.create-row {
  display: flex;
flex-direction: column;
  gap: 10px;
}

.create-row input,
.manual-field input {
  padding: 12px;
  background: var(--background-input);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  color: white;
}

input:not(:placeholder-shown) {
  border-color: #4caf50;
}

.button-main {
    padding: 12px 20px;
    background: var(--accent-color);
    border-radius: 10px;
    border: none;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
    color: var(--background-container);
}

.button-main:hover {
  background: white;
}

/* ——— ПОСЛЕДНИЙ ——— */
.latest {
  border-top: 1px solid var(--border-color);
  margin-top: 14px;
  padding: 16px 0 26px;
  text-align: center;
}

.latest-svg {
  width: 160px !important;
  margin: auto;
}

.latest-code {
  color: var(--accent-color);
  font-size: 17px;
  margin-top: 6px;
}

/* ——— МОДАЛ ФОТО ——— */
.photo-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(3px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 99999;
  animation: modalFade 0.25s ease-out;
}

@keyframes modalFade {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

.photo-modal-content {
  position: relative;
  max-width: 75%;
  max-height: 75%;
  padding-bottom: 100px;
  animation: photoPop 0.25s cubic-bezier(0.17, 0.67, 0.43, 1.01);
}

@keyframes photoPop {
  0% {
    transform: scale(0.85);
    opacity: 0;
  }

  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.photo-modal-img {
  max-width: 100%;
  max-height: 100%;
  border-radius: 12px;
  box-shadow: 0 0 20px var(--background-container);
  object-fit: contain;
}

/* CLOSE */
.photo-modal-close {
  position: absolute;
  top: -10px;
  right: -10px;
  background: var(--accent-color);
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: black;
  box-shadow: 0 0 10px var(--background-container)8;
  transition: 0.2s;
}

.photo-modal-close:hover {
  background: var(--accent-color);
}

/* ——— МОДАЛ КАМЕРЫ ——— */
.camera-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.camera-window {
  background: #111;
  padding: 20px;
  border-radius: 20px;
  max-width: 420px;
  width: 90%;
  text-align: center;
}

.cam-video {
  width: 100%;
  border-radius: 14px;
  background: black;
}

.btn-capture {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: none;
  background: var(--accent-color);
  font-size: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin: 15px auto 0;
  transition: 0.2s;
}

.btn-capture:hover {
  transform: scale(1.1);
}

.btn-close {
  margin-top: 14px;
  width: 100%;
  background: var(--border-color);
  border: none;
  color: var(--accent-color);
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
}

.btn-close:hover {
  background: var(--border-color);
}

/* миниатюра в форме */
.photo-thumb {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 12px;
}

.photo-controls {
  padding-top: 15px;
}

/* кнопки под фото */
.photo-buttons-row {
  margin-top: 10px;
  display: flex;
  gap: 12px;
  align-items: center;
}

.photo-btn {
  background: var(--background-input);
  border: none;
  padding: 10px 5px;
  border-radius: 10px;
  color: var(--accent-color);
  margin-top: 20px;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
}

.photo-btn:hover {
  background: var(--border-color);
  transform: translateY(-2px);
}

.photo-delete {
  background: var(--delete-color);
  border: none;
  margin-top: 20px;
  width: 35px;
  height: 35px;
  border-radius: 12px;
  color: #ffb7b7;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  cursor: pointer;
  transition: 0.2s;
}

.photo-delete:hover {
  background: var(--delete-color);
  transform: scale(1.05);
}

.hidden-checkbox {
  display: none;
}

.button-main.active {
  background-color: #1c4821;
  color: #fff;
}

.select-button {
  gap: 10px;
  display: flex;
  align-items: center;
}

.select-icon {
  margin-right: 6px;
  font-size: 14px;
}


@media (max-width: 1300px) {
  .page {
    flex-direction: column;
    padding: 14px;
    gap: 20px;
  }

  .top-row {
    width: 100%;
    margin: 0;
    gap: 18px;
    align-items: stretch;
  }

  .create-row {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .create-box {
    width: 90%;
    margin: 0 auto;
  }

  .search-container {
    width: 100%;
  }

  .photo-thumb {
    width: 90px;
    height: 90px;
  }

  .grid {
    gap: 20px;
  }

  .list-section {
    width: 100%;
  }
  .card-tools {
    top: 15px;
  }
  .card {
    width: 100%;
    padding: 18px;
    gap: 16px;
    flex-direction: column;
    min-height: auto;
  }

  .card-left {
    width: 100%;
    align-items: center;
  }

  svg {
    max-width: 260px;
    width: 100%;
  }

  .card-checkbox {
    width: 120px;
    position: relative;
    right: 0;
    bottom: 0;
  }

  .card-right {
    width: 100%;
  }

  .card-information {
    width: 100%;
    flex-direction: column;
    gap: 12px;
  }

  .information-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .information-title {
    min-width: auto;
  }

  .card-photo {
    width: 100%;
    max-height: 280px;
    object-fit: cover;
  }

  .label-size-box {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    width: 100%;
  }

  .select-wrap {
    width: 100%;
  }

  .button-main {
    text-align: center;
  }

  .select-button {
    width: 80px;
  }

  .manual-field {
    display: flex;
    flex-direction: column;
  }

  .print-params {
    width: 100%;
  }

  .selected-controls {
    position: fixed;
    left: 10px;
    right: 10px;
    bottom: 10px;
    padding: 14px;
    border-radius: 16px;
    gap: 10px;
  }

  .floating-print {
    width: 100%;
    left: 0;
    right: 0;
    bottom: 80px;
    margin: 0 auto;
    text-align: center;
  }

  .msg-absolute {
    left: 10px;
    right: 10px;
    text-align: center;
  }
  .selected-controls {
    position: fixed;
    left: 12px;
    right: 12px;
    bottom: 12px;

    background: #111;
    border: 1px solid var(--border-color);
    border-radius: 16px;

    padding: 14px;
    box-shadow: 0 0 15px var(--background-container);
    z-index: 9999;

    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  /* ======= СЕЛЕКТ РАЗМЕРА (полная ширина) ======= */
  .selected-controls .bulk-block {
    grid-column: 1 / 3; /* на всю ширину */
  }

  .selected-controls .bulk-size-select {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    font-size: 15px;
  }

  .selected-controls .select-arrow {
    top: 30px;
    transform: translateY(-50%);
  }

  /* ======= ЧЕКБОКСЫ РЯДОМ ======= */

  .selected-controls .param-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 15px;
  }

  .selected-controls input[type="checkbox"] {
    width: 18px;
    height: 18px;
  }

  /* Убираем заголовки "Название" и "Цена" */
  .selected-controls .bulk-title {
    display: none !important;
  }

  /* ======= КНОПКИ РЯДОМ ======= */

  .selected-controls .floating-print,
  .selected-controls .cancel-edit-btn {
    padding: 12px;
    font-size: 15px;
    border-radius: 12px;
    width: 100%;
  }


  


  

  /* ======= УДАЛИТЬ ВНИЗУ на всю ширину ======= */
  .selected-controls .delete-selected-btn {
    width: 100%;
    padding: 12px;
    font-size: 15px;
    border-radius: 12px;
  }
}
</style>
