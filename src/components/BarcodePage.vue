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

        <!-- Сообщение -->
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

        <!-- === БЛОК ФОТО === -->
        <div class="photo-section">

          <!-- Кнопки фото -->
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

        <!-- ПОСЛЕДНИЙ СОЗДАННЫЙ -->
        <div v-if="showLatest && lastCreated" class="latest">
          <svg ref="latestSvg" class="latest-svg"></svg>
          <p class="latest-code">{{ lastCreated.barcode }}</p>

          <p v-if="lastCreated.photo">
            <a :href="lastCreated.photo" target="_blank" style="color:#ffde59">
              Фото товара
            </a>
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

          <p v-if="item.photo">
            <a :href="item.photo" target="_blank" style="color:#ffde59">
              Фото товара
            </a>
          </p>

          <p v-else class="no-photo-text">Без фото</p>

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

        <button class="btn-close" @click="closeCameraModal">
          Закрыть
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from "vue";
import JsBarcode from "jsbarcode";

/* ========= Сообщения ========= */
const message = ref("");
const messageType = ref("");

function showMessage(text, type = "info") {
  message.value = text;
  messageType.value = type;
  setTimeout(() => {
    message.value = "";
  }, 3000);
}

/* ========= Транслит ========= */
const map = {
  а:"a", б:"b", в:"v", г:"g", д:"d", е:"e", ё:"e", ж:"zh",
  з:"z", и:"i", й:"y", к:"k", л:"l", м:"m", н:"n", о:"o",
  п:"p", р:"r", с:"s", т:"t", у:"u", ф:"f", х:"kh", ц:"c",
  ч:"ch", ш:"sh", щ:"shh", ы:"y", э:"e", ю:"yu", я:"ya"
};

const translit = s => s.toLowerCase().split("").map(c => map[c] ?? c).join("");
const normalize = t => translit(t).replace(/[^a-z]/gi, "");

/* ========= Генерация ========= */
const randDigit = () => Math.floor(Math.random() * 10);
const genNumber9 = () => Array.from({length:9}, () => randDigit()).join("");

function prefixFromName(t) {
  const w = t.trim().split(/\s+/).map(normalize);
  if (w.length >= 2) return (w[0][0] + w[1][0]).toUpperCase();
  if (w.length === 1 && w[0].length >= 2)
    return (w[0][0] + w[0][1]).toUpperCase();
  return null;
}

function prefixFromArticle(t) {
  const letters = normalize(t);
  if (letters.length >= 2) return (letters[0] + letters[1]).toUpperCase();
  if (letters.length === 1) return (letters[0] + letters[0]).toUpperCase();
  return null;
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

/* ========= РЕАКТИВНЫЕ ========= */
const name = ref("");
const article = ref("");
const contractor = ref("");
const search = ref("");

const barcodes = ref([]);
const latestSvg = ref(null);

/* === Фото === */
const photoFile = ref(null);      // File
const photoPreview = ref(null);   // base64 миниатюра

function removePhoto() {
  photoFile.value = null;
  photoPreview.value = null;
}

/* === latest === */
const showLatest = ref(false);
const lastCreated = computed(() => barcodes.value[0] ?? null);

/* ========= Камера ========= */
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
  } catch (e) {
    showMessage("Нет доступа к камере", "error");
  }
}

function closeCameraModal() {
  cameraOpen.value = false;
  if (stream) stream.getTracks().forEach(t => t.stop());
}

function takePhoto() {
  const canvas = document.createElement("canvas");
  canvas.width = video.value.videoWidth;
  canvas.height = video.value.videoHeight;

  const ctx = canvas.getContext("2d");
  ctx.drawImage(video.value, 0, 0);

  canvas.toBlob(blob => {
    const file = new File([blob], "photo.jpg", { type: "image/jpeg" });
    photoFile.value = file;
    if (photoFile.value && !photoPreview.value) {
  photoPreview.value = URL.createObjectURL(photoFile.value);
}
    // превью
    photoPreview.value = "";
nextTick(() => {
  photoPreview.value = URL.createObjectURL(file);
});

    showMessage("Фото сделано!", "success");
    closeCameraModal();
  }, "image/jpeg", 0.9);
}

/* ========= Загрузка списка ========= */
async function loadBarcodes() {
  const r = await fetch(
    "/api/get_barcodes.php?search=" + encodeURIComponent(search.value)
  );
  barcodes.value = await r.json();
  renderGrid();
}

let timer = null;
function searchChanged() {
  clearTimeout(timer);
  timer = setTimeout(loadBarcodes, 300);
}

/* ========= Создание ========= */
async function createBarcode() {
  let code;

  try {
    code = await generateUniqueCode(name.value, article.value, contractor.value);
  } catch (e) {
    showMessage(e.message, "error");
    return;
  }

  const form = new FormData();
  form.append("barcode", code);
  form.append("product_name", name.value);
  form.append("sku", article.value);
  form.append("contractor", contractor.value);

  if (photoFile.value) {
    form.append("photo", photoFile.value);
  }

  const res = await fetch("/api/create_barcode.php", {
    method: "POST",
    body: form
  });

  const data = await res.json();

  if (data.status === "success") {
    showMessage("Штрихкод создан!", "success");

    showLatest.value = true;
    setTimeout(() => (showLatest.value = false), 20000);

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

/* ========= Рендер ========= */
function renderLatest() {
  nextTick(() => {
    if (showLatest.value && lastCreated.value && latestSvg.value) {
      JsBarcode(latestSvg.value, lastCreated.value.barcode.replace("-", ""), {
        format: "code128",
        height: 60,
        displayValue: true,
        text: lastCreated.value.barcode
      });
    }
  });
}

function renderGrid() {
  nextTick(() => {
    barcodes.value.forEach(item => {
      const el = document.getElementById("g-" + item.id);
      if (el) {
        JsBarcode(el, item.barcode.replace("-", ""), {
          format: "code128",
          height: 50,
          displayValue: true,
          text: item.barcode
        });
      }
    });
  });
}

onMounted(loadBarcodes);
</script>

<style>

.msg-absolute {
  position: fixed;
  top: 40px;
  right: 40px;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: bold;
  z-index: 9999;
  animation: fadeInOut 3s linear forwards;
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


/* Анимация */
@keyframes fadeInOut {
  0% { opacity: 0; transform: translateY(-5px); }
  15% { opacity: 1; transform: translateY(0); }
  85% { opacity: 1; }
  100% { opacity: 0; transform: translateY(-5px); }
}



body {
  background: #0d0d0d;
  color: white;
}

/* === СТРАНИЦА === */
.page {
  max-width: 1400px;
  margin: auto;
  padding: 20px;
}

/* === ДВА БЛОКА ВВЕРХУ (ПО 50%) === */
.top-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

/* === РАВНЫЕ БЛОКИ === */
.search-box,
.create-box {
  background: #161616;
  border: 1px solid #2a2a2a;
  border-radius: 14px;
  padding: 20px;
  min-height: 210px;
  box-shadow: 0 0 12px #0005;
  display: flex;
  flex-direction: column;
}

.block-title {
  margin-bottom: 14px;
  color: #ffde59;
  font-size: 20px;
}

/* === ПОИСК === */
.search-input {
  padding: 15px;
  border-radius: 12px;
  background: #1e1e1e;
  border: 1px solid #333;
  color: white;
  font-size: 16px;
}

/* === СОЗДАНИЕ === */
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
}

/* === ПОСЛЕДНИЙ КОД === */
.latest {
  margin-top: 14px;
  padding-top: 12px;
  padding-bottom: 30px;
  border-top: 1px solid #333;
  text-align: center;
}

.latest {
  animation: fadeLatest 0.3s ease-out;
}

@keyframes fadeLatest {
  from { opacity: 0; transform: translateY(5px); }
  to   { opacity: 1; transform: translateY(0); }
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

/* === СЕТКА 5 В РЯДУ === */
.grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 18px;
}

.card {
  background: #1a1a1a;
  padding: 16px;
  border-radius: 14px;
  border: 1px solid #2a2a2a;
  box-shadow: 0 0 10px #0007;
  cursor: pointer;
  transition: 0.15s;
}

.card:hover {
  transform: scale(1.03);
}

.card-svg {
  width: 100%;
}

.code {
  margin-top: 6px;
  color: #ffde59;
  font-weight: bold;
}

.empty {
  grid-column: 1 / -1;
  text-align: center;
  opacity: 0.6;
}

/* === АДАПТИВ === */
@media (max-width: 900px) {
  .top-row {
    grid-template-columns: 1fr;
  }

  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}


.no-photo-text {
  color: #777;
  font-style: italic;
  margin-top: 4px;
}

/* Камера центр */
.camera-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(5px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.camera-window {
  background: #111;
  width: 90%;
  max-width: 420px;
  padding: 20px;
  border-radius: 20px;
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
  margin-top: 15px;
  border-radius: 50%;
  border: 4px solid #fff;
  background: #ffde59;
  font-size: 32px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-left: auto;
  margin-right: auto;
  cursor: pointer;
  transition: 0.2s;
}

.btn-capture:hover {
  transform: scale(1.1);
}

.btn-close {
  margin-top: 15px;
  background: #333;
  color: #ffde59;
  padding: 12px 20px;
  border-radius: 12px;
  border: none;
  width: 100%;
  cursor: pointer;
}

.btn-close:hover {
  background: #444;
}

/* миниатюра фото */
.photo-thumb {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 12px;
  border: 2px solid #555;
}

/* блок кнопок под фото */
.photo-controls {
  margin-top: 12px;
}

.photo-buttons-row {
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
}

/* кнопка "Переснять" */
.photo-btn {
  background: #222;
  margin-top: 20px;
  border: 1px solid #444;
  padding: 12px;
  color: #ffde59;
  border-radius: 10px;
  text-align: center;
  cursor: pointer;
  user-select: none;
  transition: 0.2s;
}

/* удалить фото */
.photo-delete {
  width: 50px;
  height: 50px;
  margin-top: 20px;
  background: #600;
  border-radius: 12px;
  color: #ffb7b7;
  display: flex;
  justify-content: center;
  align-items:
  center;
  cursor: pointer;
  font-size: 22px;
  transition: 0.2s;
}

.photo-delete:hover {
  background: #800;
  transform: scale(1.05);
}

.photo-btn:hover {
  background: #333;
  transform: scale(1.03);
}
.photo-btn .fa-camera {
  margin-right: 6px;
}
</style>