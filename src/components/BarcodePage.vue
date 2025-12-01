<template>
  <div class="page">

    <!-- === ПАНЕЛЬ ДЛЯ ВЫБРАННЫХ === -->
    <div v-if="selectedIds.length >= 2" class="selected-controls">

      <!-- БЛОК 1 — РАЗМЕР -->
      <div class="bulk-block">
        <p class="bulk-title">Размер этикетки:</p>

        <select v-model="bulkSize" @change="applyBulkSize" class="bulk-size-select">
          <option v-for="s in labelSizes" :key="s.value" :value="s.value">
            {{ s.text }}
          </option>
        </select>
      </div>

      <!-- БЛОК 2 — НАЗВАНИЕ -->
      <div class="bulk-block">
        <p class="bulk-title">Название:</p>

        <label class="param-row">
          <input type="checkbox" v-model="bulkWithName" @change="applyBulkName" />
          Печатать название
        </label>
      </div>

      <!-- БЛОК 3 — ЦЕНА -->
      <div class="bulk-block">
        <p class="bulk-title">Цена:</p>

        <label class="param-row">
          <input type="checkbox" v-model="bulkWithPrice" @change="applyBulkPrice" />
          Печатать цену
        </label>
      </div>

      <!-- КНОПКА ПЕЧАТЬ -->
      <button class="floating-print" @click="printSelected">
        <i class="fa-solid fa-print"></i>
        Печать выбранных ({{ selectedIds.length }})
      </button>

      <!-- СБРОС -->
      <button class="floating-cancel" @click="clearSelected">
        <i class="fa-solid fa-xmark"></i>
        Снять выделение
      </button>
    </div>

    <!-- === ВЕРХ === -->
    <div class="top-row">

      <!-- ПОИСК -->
      <div class="search-box">
        <h2 class="block-title">Поиск</h2>
        <input v-model="search" class="search-input" placeholder="Поиск" @input="searchChanged" />
      </div>

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
          <input v-model="contractor" placeholder="Контрагент" />
          <input v-model="price" placeholder="Цена (необязательно)" />

          <button class="button-main" @click="editMode ? saveEdit() : createBarcode()">
            {{ editMode ? "Сохранить" : "Создать" }}
          </button>

          <button v-if="!manualMode && !editMode" class="button-main" @click="manualMode = true">
            Создать вручную
          </button>

          <button v-if="manualMode && !editMode" class="cancel-edit-btn" @click="cancelManualMode">
            Отменить
          </button>

          <button v-if="editMode" class="cancel-edit-btn" @click="cancelEdit">
            Отменить
          </button>
        </div>

        <div v-if="manualMode" class="manual-field">
          <input v-model="manualBarcode" placeholder="Введите штрихкод вручную" />
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
    </div>

    <!-- === СПИСОК === -->
    <div class="list-section">
      <h2 class="subtitle">Список штрихкодов</h2>

      <div class="grid">
        <div class="card" v-for="item in barcodes" :key="item.id">

          <div class="card-tools">
            <div class="card-tool-btn card-tool-edit" @click.stop="startEdit(item)">
              <i class="fa-solid fa-pen"></i>
            </div>
            <div class="card-tool-btn card-tool-delete" @click.stop="deleteItem(item.id)">
              <i class="fa-solid fa-trash"></i>
            </div>
          </div>

          <div class="card-checkbox">
            <input type="checkbox" :value="item.id" v-model="selectedIds" />
          </div>

          <div class="card-left">
            <svg :id="'g-' + item.id"></svg>

            <p class="code" :class="isMatch(item.barcode) ? 'highlight-row' : ''">
              {{ item.barcode }}
            </p>

            <div class="card-photo-box" v-if="item.photo">
              <img :src="item.photo" class="card-photo" @click.stop="openPhoto(item.photo)" />
            </div>
            <p v-else class="no-photo-text">Без фото</p>
          </div>

          <div class="card-right">

            <div class="card-information">
              <p :class="isMatch(item.product_name) ? 'highlight-row' : ''">
              <div class="information-title"><b>Товар:</b> </div>
              <div class="information-text">{{ item.product_name }}</div>
              </p>

              <p :class="isMatch(item.sku) ? 'highlight-row' : ''">
              <div class="information-title"><b>Артикул:</b>
              </div>
              <div class="information-text">{{ item.sku }}</div>
              </p>

              <p :class="isMatch(item.contractor) ? 'highlight-row' : ''">
              <div class="information-title"><b>Контрагент:</b>
              </div>
              <div class="information-text">{{ item.contractor }}</div>
              </p>

              <p :class="isMatch(item.price) ? 'highlight-row' : ''">
              <div class="information-title"><b>Цена:</b>
              </div>
              <div class="information-text">{{ item.price }}</div>
              </p>
            </div>

            <!-- Настройки печати одной -->
            <div class="print-params">
              <p class="params-title">Параметры:</p>

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
            </div>

            <div class="label-size-box">
              <div class="select-wrap">
                <select v-model="item._size" class="label-size-select">
                  <option v-for="s in labelSizes" :key="s.value" :value="s.value">
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
  { value: "40x30", text: "40 × 30 мм" },
  { value: "58x40", text: "58 × 40 мм" },
  { value: "42x25", text: "42 × 25 мм" },
];

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
    _size: "40x30",
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
  const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  while (true) {
    const prefix =
      letters[Math.floor(Math.random() * letters.length)] +
      letters[Math.floor(Math.random() * letters.length)];

    const num = genNumber9();
    const code = prefix + "-" + num;

    if (!(await checkExists(code))) return code;
  }
}

function startEdit(item) {
  editMode.value = true;
  editId.value = item.id;

  name.value = item.product_name || "";
  article.value = item.sku || "";
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
/* =========================
   ГЛАВНЫЙ КОНТЕЙНЕР СПИСКА
========================= */

.highlight-row {
  background: #ffde59 !important;
  color: #000 !important;
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
  min-width: 95px;    /* фикс - выравнивает левую часть */
  flex-shrink: 0;     /* НЕ сжимается */
}

.information-text {
  word-break: break-word; /* переносит длинные слова */
  white-space: normal;
  overflow-wrap: break-word;
}



.param-row-container {
  display: flex;
  flex-direction: column;
}
.grid {
  display: flex;
  flex-direction: column;
  gap: 25px;
  margin-top: 30px;
}

/* =========================
   КАРТОЧКА
========================= */

.card {
  position: relative;
  width: 1000px;
  margin: 0 auto;

  background: #1a1a1a;
  border: 1px solid #2a2a2a;
  border-radius: 14px;

  padding: 20px 20px 20px 40px;
  display: flex;
  flex-direction: row;
  gap: 25px;

  min-height: 260px;
  box-sizing: border-box;

  transition: 0.25s;
}

.card:hover {
  box-shadow: 0 0 14px rgba(255, 255, 255, 0.4);
}


.card-checkbox {
  position: absolute;
  top: 21px;
  left: 10px;
}

.card-checkbox input[type="checkbox"], .param-row input[type="checkbox"] {
  width: 22px;
  height: 22px;
  accent-color: #ffde59;
  cursor: pointer;
}


.card-tools {
  position: absolute;
  top: 15px;
  right: 15px;

  display: flex;
  gap: 10px;
}

.card-tool-btn {
  width: 36px;
  height: 36px;
  background: #222;
  border: 1px solid #333;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  cursor: pointer;
  transition: 0.2s;
}

.card-tool-edit {
  color: #ffde59;
}

.card-tool-delete {
  color: #ff6b6b;
}

.card-tool-btn:hover {
  background: #333;
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
  color: #ffde59;
  font-weight: bold;
  font-size: 16px;
}

/* фото внутри карточки */
.card-photo-box {
  margin-top: 15px;
  width: 100%;
}

.card-photo {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 12px;
  cursor: pointer;
}

.no-photo-text {
  margin-top: 20px;
  color: #777;
  font-size: 14px;
}



/* =========================
   ЧЕКБОКС "с названием"
========================= */

.print-options {
  margin: 10px 0 15px 0;
  font-size: 14px;
  color: #ffde59;
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
  accent-color: #ffde59;
  cursor: pointer;
}

/* =========================
   НИЖНИЙ БЛОК — ВЫБОР РАЗМЕРА
========================= */

.label-size-box {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #333;

  display: flex;
  align-items: center;
  gap: 15px;
}

/* селект */
.select-wrap {
  position: relative;
  width: 180px;
}

.label-size-select {
  width: 100%;
  padding: 10px 40px 10px 12px;
  background: #1e1e1e;
  color: #ffde59;
  border: 1px solid #444;
  border-radius: 10px;
  cursor: pointer;
  appearance: none;
  font-size: 14px;
}

.label-size-select:hover {
  border-color: #666;
}

/* стрелка */
.select-wrap .select-arrow {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  color: #ffde59;
  pointer-events: none;
}





.manual-field input {
  margin-top: 10px;
  padding: 12px;
  background: #222;
  border: 1px solid #333;
  border-radius: 10px;
  color: white;
  width: 100%;
}




.selected-controls {
  position: fixed;
  right: 40px;
  bottom: 40px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 9999;
}


.floating-cancel {
  background: #333;
  color: #fff;
  padding: 12px;
  border-radius: 12px;
  border: none;
}

.cancel-edit-btn {
  background: #666;
  padding: 12px;
  border-radius: 12px;
  color: #fff;
  border: none;
  font-weight: bold;
}



/* === ПЛАВАЮЩАЯ КНОПКА ПЕЧАТИ === */
.floating-print {
  position: fixed;
  right: 40px;
  bottom: 40px;
  background: #ffde59;
  color: black;
  padding: 14px 22px;
  border-radius: 14px;
  border: none;
  font-weight: bold;
  cursor: pointer;
  z-index: 9999;
  font-size: 16px;
  box-shadow: 0 0 15px #000;
  transition: 0.2s;
}

.floating-print:hover {
  background: #ffe88b;
  transform: translateY(-3px);
}

/* ——— УВЕДОМЛЕНИЯ ——— */
.msg-absolute {
  position: fixed;
  top: 40px;
  right: 40px;
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
  border: 1px solid #d63c3c;
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
    transform: translateY(-8px);
  }
}

/* ——— ОСНОВА ——— */
body {
  background: #0d0d0d;
  color: white;
}

.page {
  max-width: 1400px;
  margin: auto;
  padding: 20px;
}

.top-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.search-box,
.create-box {
  background: #161616;
  border: 1px solid #2a2a2a;
  border-radius: 14px;
  padding: 20px;
  min-height: 210px;
  box-shadow: 0 0 12px #0005;
}

.block-title {
  margin-bottom: 14px;
  color: #ffde59;
  font-size: 20px;
}

/* ——— ПОИСК ——— */
.search-input {
  padding: 15px;
  border-radius: 12px;
  background: #1e1e1e;
  border: 1px solid #333;
  color: white;
  font-size: 16px;
}

/* ——— СОЗДАНИЕ ——— */
.create-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr auto;
  gap: 10px;
}

.create-row input {
  padding: 12px;
  background: #222;
  border: 1px solid #333;
  border-radius: 10px;
  color: white;
}

.button-main {
  padding: 12px 20px;
  background: #ffb400;
  border-radius: 10px;
  border: none;
  font-weight: bold;
  cursor: pointer;
  transition: 0.2s;
}

.button-main:hover {
  background: #ffcd4d;
}

/* ——— ПОСЛЕДНИЙ ——— */
.latest {
  border-top: 1px solid #333;
  margin-top: 14px;
  padding: 16px 0 26px;
  text-align: center;
}

.latest-svg {
  width: 160px !important;
  margin: auto;
}

.latest-code {
  color: #ffde59;
  font-size: 17px;
  margin-top: 6px;
}



.bulk-size-select {
  padding: 10px 40px 10px 12px;
  background: #1e1e1e;
  color: #ffde59;
  border: 1px solid #444;
  border-radius: 10px;
  cursor: pointer;
  appearance: none;
  transition: 0.2s;
  font-size: 14px;
  min-width: 140px;
  box-shadow: 0 0 10px #0005;
}

.bulk-size-select:hover {
  border-color: #555;
}

.bulk-size-select:focus {
  border-color: #ffde59;
}

.bulk-size-select-wrap {
  position: relative;
}

.bulk-size-select-wrap::after {
  content: "▼";
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  color: #ffde59;
  pointer-events: none;
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
  box-shadow: 0 0 20px #000;
  object-fit: contain;
}

/* CLOSE */
.photo-modal-close {
  position: absolute;
  top: -10px;
  right: -10px;
  background: #ffde59;
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
  box-shadow: 0 0 10px #0008;
  transition: 0.2s;
}

.photo-modal-close:hover {
  background: #ffe88b;
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
  background: #ffde59;
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
  background: #333;
  border: none;
  color: #ffde59;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
}

.btn-close:hover {
  background: #444;
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
  background: #222;
  border: none;
  padding: 10px 5px;
  border-radius: 10px;
  color: #ffde59;
  margin-top: 20px;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  gap: 10px;
}

.photo-btn:hover {
  background: #333;
  transform: translateY(-2px);
}

.photo-delete {
  background: #600;
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
  background: #800;
  transform: scale(1.05);
}</style>
