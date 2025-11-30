<template>
  <div class="page">
    <!-- === ВЕРХНЯЯ ПАНЕЛЬ === -->
    <div class="top-row">
      <!-- === ПОИСК === -->
      <div class="search-box">
        <h2 class="block-title">Поиск</h2>
        <input
          v-model="search"
          class="search-input"
          placeholder="Поиск"
          @input="searchChanged"
        />
      </div>

      <!-- === СОЗДАНИЕ === -->
      <div class="create-box">
        <div v-if="message" :class="['msg-absolute', messageType]">
          {{ message }}
        </div>

        <h2 class="block-title">Создать штрихкод</h2>

        <div class="create-row">
          <input v-model="name" placeholder="Название" />
          <input v-model="article" placeholder="Артикул" />
          <input v-model="contractor" placeholder="Контрагент" />
          <button @click="createBarcode">Создать</button>
        </div>

        <!-- === ФОТО === -->
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

        <!-- ПОСЛЕДНИЙ -->
        <div v-if="showLatest && lastCreated" class="latest">
          <svg ref="latestSvg" class="latest-svg"></svg>
          <p class="latest-code">{{ lastCreated.barcode }}</p>

          <p v-if="lastCreated.photo">
            <a :href="lastCreated.photo" target="_blank" class="photo-link"
              >Фото товара</a
            >
          </p>
          <p v-else class="no-photo-text">Без фото</p>
        </div>
      </div>
    </div>

    <!-- === СПИСОК === -->
    <div class="list-section">
      <h2 class="subtitle">Список штрихкодов</h2>

      <div class="grid">
        <div class="card" v-for="item in barcodes" :key="item.id">
          <svg :id="'g-' + item.id" class="card-svg"></svg>

          <p class="code">{{ item.barcode }}</p>
          <p v-if="item.product_name"><b>Товар:</b> {{ item.product_name }}</p>
          <p v-if="item.sku"><b>Артикул:</b> {{ item.sku }}</p>
          <p v-if="item.contractor"><b>Контрагент:</b> {{ item.contractor }}</p>

          <!-- Фото миниатюра -->
          <div v-if="item.photo" class="card-photo-box">
            <img
              :src="item.photo"
              class="card-photo"
              @click.stop="openPhoto(item.photo)"
            />
          </div>
          <p v-else class="no-photo-text">Без фото</p>

          <!-- === ВЫБОР РАЗМЕРА + ПЕЧАТЬ === -->
          <div class="label-size-box">
            <div class="select-wrap">
              <select v-model="item._size" class="label-size-select">
                <option value="40x30">40 × 30 мм</option>
                <option value="58x40">58 × 40 мм</option>
              </select>
              <i class="fa-solid fa-chevron-down select-arrow"></i>
            </div>

            <button class="print-btn" @click.stop="openPrint(item)">
              <i class="fa-solid fa-print"></i> Печать
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==== МОДАЛ КАМЕРЫ ==== -->
    <div v-if="cameraOpen" class="camera-overlay">
      <div class="camera-window">
        <video ref="video" autoplay playsinline class="cam-video"></video>

        <button class="btn-capture" @click="takePhoto">
          <i class="fa-solid fa-camera"></i>
        </button>

        <button class="btn-close" @click="closeCameraModal">Закрыть</button>
      </div>
    </div>

    <!-- ==== МОДАЛ ФОТО ==== -->
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
import { ref, computed, nextTick, onMounted } from "vue";
import JsBarcode from "jsbarcode";

/* Уведомления */
const message = ref("");
const messageType = ref("");
const showMessage = (t, type = "info") => {
  message.value = t;
  messageType.value = type;
  setTimeout(() => (message.value = ""), 3000);
};

/* Модал фото */
const photoModalOpen = ref(false);
const photoModalSrc = ref(null);

function openPhoto(src) {
  photoModalSrc.value = src;
  photoModalOpen.value = true;
}

function closePhoto() {
  photoModalOpen.value = false;
  photoModalSrc.value = null;
}

/* Транслит */
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
  х: "kh",
  ц: "c",
  ч: "ch",
  ш: "sh",
  щ: "shh",
  ы: "y",
  э: "e",
  ю: "yu",
  я: "ya",
};

const translit = (s) =>
  s
    .toLowerCase()
    .split("")
    .map((c) => map[c] ?? c)
    .join("");
const normalize = (t) => translit(t).replace(/[^a-z]/gi, "");

/* Генерация */
const randDigit = () => Math.floor(Math.random() * 10);
const genNumber9 = () => Array.from({ length: 9 }, () => randDigit()).join("");

function randomPrefix() {
  const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  return (
    letters[Math.floor(Math.random() * letters.length)] +
    letters[Math.floor(Math.random() * letters.length)]
  );
}

function prefixFromName(t) {
  const w = t.trim().split(/\s+/).map(normalize);
  if (!w[0] || w[0].length === 0) return randomPrefix();
  if (w.length >= 2) return (w[0][0] + w[1][0]).toUpperCase();
  if (w.length === 1 && w[0].length >= 2)
    return (w[0][0] + w[0][1]).toUpperCase();
  return randomPrefix();
}

function prefixFromArticle(t) {
  const letters = normalize(t);
  if (letters.length >= 2) return (letters[0] + letters[1]).toUpperCase();
  if (letters.length === 1) return (letters[0] + letters[0]).toUpperCase();
  return randomPrefix();
}

async function checkExists(code) {
  const r = await fetch("/api/check_barcode.php?barcode=" + code);
  return (await r.json()).exists;
}

async function generateUniqueCode(name, article, contractor) {
  let prefix = null;
  if (name.trim()) prefix = prefixFromName(name);
  else if (article.trim()) prefix = prefixFromArticle(article);
  else if (contractor.trim()) prefix = prefixFromName(contractor);
  else throw new Error("Заполните хотя бы одно поле");

  while (true) {
    const num = genNumber9();
    const code = prefix + "-" + num;
    if (!(await checkExists(code))) return code;
  }
}

/* Reactive */
const name = ref("");
const article = ref("");
const contractor = ref("");
const search = ref("");

const barcodes = ref([]);
const latestSvg = ref(null);

const photoFile = ref(null);
const photoPreview = ref(null);

function removePhoto() {
  photoFile.value = null;
  photoPreview.value = null;
}

/* Камера */
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
      nextTick(() => {
        photoPreview.value = URL.createObjectURL(file);
      });
      showMessage("Фото сделано!", "success");
      closeCameraModal();
    },
    "image/jpeg",
    0.9
  );
}

/* Список */
async function loadBarcodes() {
  const r = await fetch(
    "/api/get_barcodes.php?search=" + encodeURIComponent(search.value)
  );
  barcodes.value = (await r.json()).map((b) => ({
    ...b,
    _size: "40x30",
  }));
  renderGrid();
}

let timer = null;
function searchChanged() {
  clearTimeout(timer);
  timer = setTimeout(loadBarcodes, 300);
}

/* Создание */
async function createBarcode() {
  let code;
  try {
    code = await generateUniqueCode(
      name.value,
      article.value,
      contractor.value
    );
  } catch (e) {
    showMessage(e.message, "error");
    return;
  }

  const form = new FormData();
  form.append("barcode", code);
  form.append("product_name", name.value);
  form.append("sku", article.value);
  form.append("contractor", contractor.value);
  if (photoFile.value) form.append("photo", photoFile.value);

  const res = await fetch("/api/create_barcode.php", {
    method: "POST",
    body: form,
  });

  const data = await res.json();

  if (data.status === "success") {
    showMessage("Штрихкод создан!", "success");
    name.value = "";
    article.value = "";
    contractor.value = "";
    removePhoto();
    await loadBarcodes();
    renderLatest();
  } else {
    showMessage("Ошибка", "error");
  }
}

/* Печать */
function openPrint(item) {
  window.open(`/api/print.php?id=${item.id}&size=${item._size}`, "_blank");
}

/* Рендер */
function renderLatest() {
  nextTick(() => {
    if (latestSvg.value && barcodes.value.length > 0) {
      const b = barcodes.value[0];
      JsBarcode(latestSvg.value, b.barcode.replace("-", ""), {
        format: "code128",
        height: 60,
        displayValue: true,
        text: b.barcode,
      });
    }
  });
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

onMounted(loadBarcodes);
</script>

<style>
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

.create-row button {
  padding: 12px 20px;
  background: #ffb400;
  border-radius: 10px;
  border: none;
  font-weight: bold;
  cursor: pointer;
  transition: 0.2s;
}
.create-row button:hover {
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

/* ——— СЕТКА ——— */
.grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
}

/* ——— КАРТОЧКА ——— */
.card {
  background: #1a1a1a;
  padding: 16px;
  border-radius: 14px;
  border: 1px solid #2a2a2a;
  transition: 0.25s cubic-bezier(0.17, 0.67, 0.43, 1.01);

  /* 🔥 ВАЖНО – делаем карточку flex-контейнером */
  display: flex;
  flex-direction: column;

  /* 🔥 фиксируем высоту (можешь изменить цифру) */
  min-height: 370px;

  /* чтобы hover не конфликтовал */
  transform: translateY(0);
}

.card:hover {
  box-shadow: 0 0 14px rgba(255, 255, 255, 0.533);
}

.card-svg {
  width: 100%;
}

.code {
  margin-top: 6px;
  color: #ffde59;
  font-weight: bold;
}

/* ——— МИНИ-ФОТО ——— */
.card-photo-box {
  margin-top: 10px;
  display: flex;
  justify-content: center;
}

.card-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 12px;
  transition: 0.25s cubic-bezier(0.17, 0.67, 0.43, 1.01);
  box-shadow: 0 0 0 #ffffff00;
  cursor: pointer;
}

/* Эффект при наведении */
.card-photo:hover {
  transform: scale(1.03) translateY(-3px);
}

/* ——— ВЫБОР РАЗМЕРА ——— */
.label-size-box {
  margin-top: auto; /* 🔥 отправляет блок вниз */
  padding-top: 12px;
  border-top: 1px solid #333;
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Dropdown wrap */
.select-wrap {
  position: relative;
  width: 100%;
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
  transition: 0.2s;
  font-size: 14px;
}
.label-size-select:hover {
  border-color: #555;
}
.label-size-select:focus {
  border-color: #ffde59;
}

/* стрелка */
.select-arrow {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #ffde59;
  font-size: 14px;
  pointer-events: none;
  transition: 0.2s;
}

.select-wrap:focus-within .select-arrow {
  transform: translateY(-50%) rotate(180deg);
}

/* ——— КНОПКА ПЕЧАТЬ ——— */
.print-btn {
  background: #ffb400;
  color: black;
  font-weight: bold;
  border: none;
  border-radius: 10px;
  padding: 10px 16px;
  cursor: pointer;
  transition: 0.2s;
}
.print-btn:hover {
  background: #ffca4d;
  transform: translateY(-2px);
}

.no-photo-text {
  color: grey;
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
}
</style>
