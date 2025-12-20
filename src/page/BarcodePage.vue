<template>
  <div class="page" @mousedown="dragStart">
    <!-- ===== Bottom panel для выбранных ===== -->
    <div v-if="selectedIds.length >= 2" class="selected-controls">
      <div class="bulk-row bulk-size-row">
        <div class="bulk-block">
          <div class="bulk-head">
            <div class="bulk-title">Размер этикетки</div>
            <div class="bulk-sub">Применится ко всем выбранным</div>
          </div>

          <div class="select-wrap">
            <select v-model="bulkSize" @change="applyBulkSize" class="label-size-select">
              <option v-for="s in labelSizes" :key="s.value" :value="s.value">
                {{ s.text }}
              </option>
            </select>
            <i class="fa-solid fa-chevron-down select-arrow" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="bulk-row bulk-flags-row">
        <label class="param-row bulk-flag">
          <input type="checkbox" v-model="bulkWithName" @change="applyBulkName" />
          Печатать название
        </label>

        <label class="param-row bulk-flag">
          <input type="checkbox" v-model="bulkWithPrice" @change="applyBulkPrice" />
          Печатать цену
        </label>
      </div>

      <div class="bulk-row bulk-actions-row">
        <button class="btn primary" @click="printSelected">
          <i class="fa-solid fa-print"></i>
          Печать ({{ selectedIds.length }})
        </button>

        <button class="btn ghost" @click="exportSelected">
          <i class="fa-solid fa-file-excel"></i>
          Экспорт
        </button>

        <button class="btn danger" @click="deleteSelected">
          <i class="fa-solid fa-trash"></i>
          Удалить
        </button>

        <button class="btn soft" @click="clearSelected">
          <i class="fa-solid fa-xmark"></i>
          Снять выделение
        </button>
      </div>
    </div>

    <!-- ===== LEFT (create + search) ===== -->
    <section class="left">
      <div class="card-box">
        <div v-if="message" :class="['toast', messageType]">
          {{ message }}
        </div>

        <div class="card-head">
          <h2 class="title">
            {{ editMode ? "Редактировать штрихкод" : "Создать штрихкод" }}
          </h2>
          <div class="hint">
            Фото сохраняется в <b>/photo_product_barcode/</b>
          </div>
        </div>

        <div class="form">
          <label class="field">
            <span class="label">Название</span>
            <input v-model="name" placeholder="Название" />
          </label>

          <label class="field">
            <span class="label">Артикул</span>
            <input v-model="article" placeholder="Артикул" />
          </label>

          <label class="field">
            <span class="label">Текущий остаток</span>
            <input v-model="stock" placeholder="Текущий остаток" />
          </label>

          <label class="field">
            <span class="label">Контрагент</span>
            <input v-model="contractor" placeholder="Контрагент" />
          </label>

          <label class="field">
            <span class="label">Цена (необязательно)</span>
            <input v-model="price" placeholder="Цена (необязательно)" />
          </label>

          <div class="row buttons">
            <button class="btn primary" @click="editMode ? saveEdit() : createBarcode()">
              <i class="fa-solid fa-check"></i>
              {{ editMode ? "Сохранить" : "Создать" }}
            </button>

            <button v-if="!manualMode && !editMode" class="btn ghost" @click="manualMode = true">
              <i class="fa-solid fa-pen-to-square"></i>
              Вручную
            </button>

            <button v-if="manualMode && !editMode" class="btn soft" @click="cancelManualMode">
              <i class="fa-solid fa-xmark"></i>
              Отменить
            </button>

            <button v-if="editMode" class="btn soft" @click="cancelEdit">
              <i class="fa-solid fa-xmark"></i>
              Отменить
            </button>
          </div>

          <div v-if="manualMode" class="manual">
            <label class="field">
              <span class="label">Штрихкод вручную</span>
              <input v-model="manualBarcode" placeholder="Введите штрихкод вручную" />
            </label>
          </div>

          <!-- Фото -->
          <div class="photo">
            <div v-if="!photoPreview" class="photo-btn" @click="openCameraModal">
              <i class="fa-solid fa-camera"></i>
              Сделать фото
            </div>

            <div v-else class="photo-preview">
              <img :src="photoPreview" class="thumb" />

              <div class="photo-actions">
                <div class="photo-btn" @click="openCameraModal">
                  <i class="fa-solid fa-camera-rotate"></i>
                  Переснять
                </div>

                <div class="photo-del" @click="removePhoto" title="Удалить фото">
                  <i class="fa-solid fa-trash"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="card-box">
        <div class="card-head">
          <h2 class="title">Поиск</h2>
          <div class="hint">По штрихкоду / названию / артикулу / контрагенту</div>
        </div>

        <input
          v-model="search"
          class="search"
          placeholder="Например: 4600... или “эмаль”"
          @input="searchChanged"
        />

        <div class="sizes-hint">
          <span class="muted">Размеры печати:</span>
          <span class="chip" v-for="s in labelSizes" :key="s.value">{{ s.text }}</span>
        </div>
      </div>
    </section>

    <!-- ===== RIGHT (list) ===== -->
    <section class="right">
      <div class="list-head">
        <div class="list-title">
          Список штрихкодов
          <span class="count" v-if="barcodes.length">({{ barcodes.length }})</span>
        </div>

        <div class="list-sub">
          Выделение: клик по карточке или рамкой мышкой (как Windows).
        </div>
      </div>

      <div class="grid">
        <div
          class="card"
          v-for="item in barcodes"
          :key="item.id"
          :data-id="item.id"
          :class="{ selected: selectedIds.includes(item.id) }"
          @click="cardClick($event, item.id)"
        >
          <!-- tools -->
          <div class="tools">
            <button class="tool edit" @click.stop="startEdit(item)" title="Редактировать">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button class="tool del" @click.stop="deleteItem(item.id)" title="Удалить">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>

          <!-- LEFT barcode -->
          <div class="col leftcol">
            <svg :id="'g-' + item.id"></svg>

            <div class="code-row">
              <div class="code" v-html="highlight(item.barcode, search)"></div>
              <button class="copy" @click.stop="copy(item.barcode)" title="Копировать штрихкод">
                <i class="fa-solid fa-copy"></i>
              </button>
            </div>

            <div class="print-params">
              <div class="flags">
                <label class="param-row">
                  <input type="checkbox" v-model="item._withName" />
                  Название
                </label>

                <label class="param-row">
                  <input type="checkbox" v-model="item._withPrice" />
                  Цена
                </label>
              </div>

              <div class="print-row">
                <div class="select-wrap grow">
                  <select v-model="item._size" class="label-size-select">
                    <option v-for="s in labelSizes" :key="s.value" :value="s.value">
                      {{ s.text }}
                    </option>
                  </select>
                  <i class="fa-solid fa-chevron-down select-arrow"></i>
                </div>

                <button class="btn primary mini" @click.stop="openPrint(item)">
                  <i class="fa-solid fa-print"></i>
                  Печать
                </button>
              </div>
            </div>
          </div>

          <!-- RIGHT info -->
          <div class="col rightcol">
            <div class="info">
              <div class="row">
                <div class="k">
                  Товар
                  <button class="copy mini" @click.stop="copy(item.product_name)" title="Копировать">
                    <i class="fa-solid fa-copy"></i>
                  </button>
                </div>
                <div class="v" v-html="highlight(item.product_name, search)"></div>
              </div>

              <div class="row">
                <div class="k">
                  Артикул
                  <button class="copy mini" @click.stop="copy(item.sku)" title="Копировать">
                    <i class="fa-solid fa-copy"></i>
                  </button>
                </div>
                <div class="v" v-html="highlight(item.sku, search)"></div>
              </div>

              <div class="row">
                <div class="k">Остаток</div>
                <div class="v">{{ item.stock }}</div>
              </div>

              <div class="row">
                <div class="k">Контрагент</div>
                <div class="v" v-html="highlight(item.contractor, search)"></div>
              </div>

              <div class="row">
                <div class="k">Цена</div>
                <div class="v" v-html="highlight(item.price, search)"></div>
              </div>
            </div>

            <div class="photo-box">
              <img
                v-if="item.photo"
                :src="item.photo"
                class="card-photo"
                @click.stop="openPhoto(item.photo)"
              />
              <div v-else class="no-photo">Без фото</div>
            </div>
          </div>

          <!-- SELECT button -->
          <div class="selectbox">
            <button
              class="btn select"
              :class="{ active: selectedIds.includes(item.id) }"
              @click.stop="toggleSelect(item.id)"
            >
              <i class="fa-solid" :class="selectedIds.includes(item.id) ? 'fa-check' : 'fa-plus'"></i>
              {{ selectedIds.includes(item.id) ? "Выбрано" : "Выбрать" }}
            </button>

            <input
              type="checkbox"
              :value="item.id"
              v-model="selectedIds"
              class="hidden-checkbox"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- ===== Camera modal ===== -->
    <div v-if="cameraOpen" class="camera-overlay">
      <div class="camera-window">
        <video ref="video" autoplay playsinline class="cam-video"></video>

        <button class="btn-capture" @click="takePhoto">
          <i class="fa-solid fa-camera"></i>
        </button>

        <button class="btn-close" @click="closeCameraModal">Закрыть</button>
      </div>
    </div>

    <!-- ===== Photo modal ===== -->
    <div v-if="photoModalOpen" class="photo-modal-overlay" @click="closePhoto">
      <div class="photo-modal-content" @click.stop>
        <img :src="photoModalSrc" class="photo-modal-img" />
        <button class="photo-modal-close" @click="closePhoto">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>

    <!-- drag rect -->
    <div v-if="drag.active" class="drag-rect" :style="dragStyle"></div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount, computed } from "vue";
import JsBarcode from "jsbarcode";

/** ====== Label sizes (из API) ====== */
const labelSizes = ref([
  { value: "42x25", text: "42 × 25 мм" },
  { value: "30x20", text: "30 × 20 мм" },
]);

const bulkSize = ref("42x25");

async function loadLabelSizes() {
  try {
    const r = await fetch("/api/barcode/label_sizes_get.php");
    const d = await r.json();

    if (d?.status === "success" && Array.isArray(d.items) && d.items.length) {
      labelSizes.value = d.items.map((x) => ({
        value: String(x.value),
        text: String(x.text),
      }));
    }
  } catch (e) {
    // тихо, есть дефолты
  }

  // гарантируем, что есть хотя бы 2 дефолта
  if (!labelSizes.value?.length) {
    labelSizes.value = [
      { value: "42x25", text: "42 × 25 мм" },
      { value: "30x20", text: "30 × 20 мм" },
    ];
  }

  // bulkSize всегда валидный
  if (!labelSizes.value.some((s) => s.value === bulkSize.value)) {
    bulkSize.value = labelSizes.value[0].value;
  }
}

/** ====== Bulk flags ====== */
const bulkWithName = ref(false);
const bulkWithPrice = ref(false);

/** ====== Manual ====== */
const manualMode = ref(false);
const manualBarcode = ref("");

/** ====== Drag select ====== */
const drag = ref({
  active: false,
  startX: 0,
  startY: 0,
  x: 0,
  y: 0,
  w: 0,
  h: 0,
});

function dragStart(e) {
  // не начинаем, если выделяют текст
  if (window.getSelection?.().toString().length > 0) return;

  // не начинаем по интерактивным элементам
  const ignore = ["BUTTON", "INPUT", "LABEL", "I", "SVG", "SELECT", "OPTION"];
  if (ignore.includes(e.target.tagName)) return;

  drag.value.active = true;
  drag.value.startX = e.pageX;
  drag.value.startY = e.pageY;

  selectedIds.value = [];

  window.addEventListener("mousemove", dragMove);
  window.addEventListener("mouseup", dragEnd);
}

function dragMove(e) {
  if (!drag.value.active) return;

  const dx = e.pageX - drag.value.startX;
  const dy = e.pageY - drag.value.startY;

  drag.value.x = dx < 0 ? e.pageX : drag.value.startX;
  drag.value.y = dy < 0 ? e.pageY : drag.value.startY;
  drag.value.w = Math.abs(dx);
  drag.value.h = Math.abs(dy);

  detectSelection();
}

function dragEnd() {
  drag.value.active = false;
  window.removeEventListener("mousemove", dragMove);
  window.removeEventListener("mouseup", dragEnd);
}

function detectSelection() {
  const rect = {
    left: drag.value.x,
    top: drag.value.y,
    right: drag.value.x + drag.value.w,
    bottom: drag.value.y + drag.value.h,
  };

  document.querySelectorAll(".card").forEach((el) => {
    const box = el.getBoundingClientRect();

    const elRect = {
      left: box.left + window.scrollX,
      top: box.top + window.scrollY,
      right: box.right + window.scrollX,
      bottom: box.bottom + window.scrollY,
    };

    const overlap =
      rect.left < elRect.right &&
      rect.right > elRect.left &&
      rect.top < elRect.bottom &&
      rect.bottom > elRect.top;

    const idAttr = el.getAttribute("data-id");
    if (!idAttr) return;
    const itemId = Number(idAttr);

    if (overlap) {
      if (!selectedIds.value.includes(itemId)) selectedIds.value.push(itemId);
    } else {
      selectedIds.value = selectedIds.value.filter((x) => x !== itemId);
    }
  });
}

const dragStyle = computed(() => ({
  left: drag.value.x + "px",
  top: drag.value.y + "px",
  width: drag.value.w + "px",
  height: drag.value.h + "px",
}));

/** ====== UI helpers ====== */
const message = ref("");
const messageType = ref("info");

function showMessage(t, type = "info") {
  message.value = t;
  messageType.value = type;
  setTimeout(() => (message.value = ""), 2500);
}

function highlight(text, search) {
  if (!text) return "";
  if (!search) return text;

  const escaped = search.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const regex = new RegExp(escaped, "gi");

  return text.toString().replace(regex, (match) => {
    return `<span class="highlight-row">${match}</span>`;
  });
}

function copy(text) {
  if (!text) return;
  navigator.clipboard.writeText(text.toString());
  showMessage("Скопировано", "success");
}

/** ====== Data ====== */
const barcodes = ref([]);
const selectedIds = ref([]);

const search = ref("");

let timer = null;
function searchChanged() {
  clearTimeout(timer);
  timer = setTimeout(loadBarcodes, 250);
}

/** ====== Card click ====== */
function cardClick(e, id) {
  if (window.getSelection?.().toString().length > 0) return;

  const ignore = ["BUTTON", "INPUT", "LABEL", "SELECT", "I", "SVG", "OPTION"];
  if (ignore.includes(e.target.tagName)) return;

  toggleSelect(id);
}

function toggleSelect(id) {
  const arr = selectedIds.value;
  const idx = arr.indexOf(id);

  if (idx === -1) arr.push(id);
  else arr.splice(idx, 1);
}

/** ====== Bulk apply ====== */
function applyBulkSize() {
  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) i._size = bulkSize.value;
  });
}

function applyBulkName() {
  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) i._withName = bulkWithName.value;
  });
}

function applyBulkPrice() {
  barcodes.value.forEach((i) => {
    if (selectedIds.value.includes(i.id)) i._withPrice = bulkWithPrice.value;
  });
}

function clearSelected() {
  selectedIds.value = [];
}

/** ====== Form (create/edit) ====== */
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

function cancelEdit() {
  resetForm();
}

/** ====== Camera ====== */
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
  stream = null;
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
      showMessage("Фото сделано", "success");
      closeCameraModal();
    },
    "image/jpeg",
    0.9
  );
}

onBeforeUnmount(() => {
  if (stream) stream.getTracks().forEach((t) => t.stop());
});

/** ====== Photo modal ====== */
const photoModalOpen = ref(false);
const photoModalSrc = ref("");

function openPhoto(src) {
  photoModalSrc.value = src;
  photoModalOpen.value = true;
}
function closePhoto() {
  photoModalOpen.value = false;
}

/** ====== API ====== */
async function loadBarcodes() {
  try {
    const r = await fetch(
      "/api/barcode/get_barcodes.php?search=" + encodeURIComponent(search.value)
    );
    const raw = await r.json();
    const defSize = labelSizes.value?.[0]?.value || "42x25";

    barcodes.value = (Array.isArray(raw) ? raw : []).map((b) => {
      const id = Number(b.id);
      return {
        ...b,
        id,
        _size: defSize,
        _withName: false,
        _withPrice: false,
      };
    });

    renderGrid();
  } catch (e) {
    showMessage("Ошибка загрузки списка", "error");
    barcodes.value = [];
  }
}

function randDigit() {
  return Math.floor(Math.random() * 10);
}
function genNumber9() {
  return Array.from({ length: 9 }, () => randDigit()).join("");
}

async function checkExists(code) {
  const r = await fetch("/api/barcode/check_barcode.php?barcode=" + encodeURIComponent(code));
  return (await r.json()).exists;
}

async function generateBarcode() {
  while (true) {
    const num = genNumber9();
    const code = "99" + num;
    if (!(await checkExists(code))) return code;
  }
}

async function createBarcode() {
  const nameLen = name.value.trim().length;
  const skuLen = article.value.trim().length;
  const contrLen = contractor.value.trim().length;

  if (nameLen < 2 && skuLen < 2 && contrLen < 2) {
    showMessage("Введите название или артикул или контрагента (минимум 2 символа)", "error");
    return;
  }

  let code = "";
  if (manualMode.value) {
    if (!manualBarcode.value.trim()) {
      showMessage("Введите штрихкод", "error");
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

  const res = await fetch("/api/barcode/create_barcode.php", {
    method: "POST",
    body: form,
  });

  const data = await res.json();

  if (data.status === "error") {
    showMessage(data.message || "Ошибка при создании", "error");
    return;
  }

  showMessage("Создано", "success");
  resetForm();
  await loadBarcodes();
}

function cancelManualMode() {
  manualMode.value = false;
  manualBarcode.value = "";
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

  const r = await fetch("/api/barcode/update_barcode.php", {
    method: "POST",
    body: form,
  });

  const d = await r.json();

  if (d.status === "success") {
    showMessage("Сохранено", "success");
    resetForm();
    await loadBarcodes();
    return;
  }

  showMessage(d.msg || "Ошибка сохранения", "error");
}

async function deleteItem(id) {
  if (!confirm("Удалить штрихкод?")) return;

  const r = await fetch("/api/barcode/delete_barcode.php?id=" + id);
  const d = await r.json().catch(() => null);

  if (d?.status === "success") {
    showMessage("Удалено", "success");
    selectedIds.value = selectedIds.value.filter((x) => x !== id);
    await loadBarcodes();
  } else {
    showMessage("Ошибка удаления", "error");
  }
}

async function deleteSelected() {
  if (!confirm("Удалить все выбранные штрихкоды?")) return;
  const ids = [...selectedIds.value];

  const results = await Promise.all(
    ids.map(async (id) => {
      try {
        const r = await fetch("/api/barcode/delete_barcode.php?id=" + id); // ✅ правильный путь
        const d = await r.json().catch(() => null);
        return { id, ok: d?.status === "success" };
      } catch {
        return { id, ok: false };
      }
    })
  );

  const okCount = results.filter((x) => x.ok).length;
  if (okCount > 0) showMessage("Удалено: " + okCount, "success");
  else showMessage("Не удалось удалить выбранные", "error");

  selectedIds.value = [];
  await loadBarcodes();
}

async function exportSelected() {
  if (selectedIds.value.length === 0) {
    showMessage("Нет выбранных штрихкодов", "error");
    return;
  }

  const payload = JSON.stringify(selectedIds.value);
  window.open("/api/barcode/export_excel.php?ids=" + encodeURIComponent(payload), "_blank");
}

function openPrint(item) {
  const withName = item._withName ? 1 : 0;
  const withPrice = item._withPrice ? 1 : 0;

  window.open(
    `/api/barcode/print.php?id=${item.id}&size=${encodeURIComponent(item._size)}&withName=${withName}&withPrice=${withPrice}`,
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
  window.open(`/api/barcode/print_bulk.php?data=${encoded}`, "_blank");
}

/** ====== Barcode render ====== */
function renderGrid() {
  nextTick(() => {
    barcodes.value.forEach((item) => {
      const el = document.getElementById("g-" + item.id);
      if (!el) return;

      const txt = (item.barcode || "").toString().replace(/\s+/g, "");
      JsBarcode(el, txt, {
        format: "code128",
        height: 50,
        displayValue: true,
        text: txt,
      });
    });
  });
}

/** ====== init ====== */
onMounted(async () => {
  await loadLabelSizes();
  await loadBarcodes();
});
</script>

<style scoped>
/* =========================================================
   BARCODE ADMIN PAGE — CONTRAST DARK UI (mobile friendly)
   ========================================================= */

/* ---------- Design tokens (fallback) ---------- */
.page {
  --bg: #0b1220;            /* общий фон */
  --panel: #0f1a2b;         /* карточки */
  --panel-2: #0c1525;       /* внутренние блоки */
  --stroke: rgba(148, 163, 184, 0.18);
  --stroke-2: rgba(148, 163, 184, 0.28);

  --txt: rgba(255,255,255,0.92);
  --muted: rgba(255,255,255,0.62);
  --muted2: rgba(255,255,255,0.48);

  --brand: #4f7cff;         /* акцент */
  --brand-2: #77a1ff;

  --ok: #10b981;
  --warn: #f59e0b;
  --bad: #ef4444;

  --shadow: 0 14px 40px rgba(0,0,0,0.38);
  --shadow-soft: 0 10px 28px rgba(0,0,0,0.28);

  --r12: 12px;
  --r16: 16px;
  --r18: 18px;

  color: var(--txt);
}

/* ---------- Page layout ---------- */
.page {
  max-width: 1440px;
  margin: 0 auto;
  padding: 18px;
  display: grid;
  grid-template-columns: minmax(330px, 440px) 1fr;
  gap: 18px;
  align-items: start;

  /* контрастный общий фон */
  background:
    radial-gradient(1200px 800px at 15% -10%, rgba(79,124,255,0.18), transparent 55%),
    radial-gradient(1200px 800px at 110% 20%, rgba(16,185,129,0.12), transparent 55%),
    linear-gradient(180deg, rgba(255,255,255,0.02), transparent 50%),
    var(--bg);
  box-shadow: var(--shadow-soft);
}

/* ---------- Sticky left column ---------- */
.left {
  display: grid;
  gap: 14px;
  position: sticky;
  top: 14px;
}
.right {
  min-width: 0;
}

/* ---------- Card boxes ---------- */
.card-box {
  background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent 45%) , var(--panel);
  border: 1px solid var(--stroke);
  border-radius: var(--r18);
  padding: 16px;
  box-shadow: var(--shadow-soft);
}

/* ---------- Headers / texts ---------- */
.card-head { margin-bottom: 12px; }

.title {
  margin: 0;
  font-size: 20px;
  line-height: 1.15;
  font-weight: 900;
  letter-spacing: 0.2px;
  color: var(--txt);
}
.hint {
  margin-top: 6px;
  font-size: 13px;
  color: var(--muted);
}
.list-head { padding: 6px 4px 14px; }
.list-title {
  color: var(--txt);
  font-weight: 950;
  font-size: 18px;
  letter-spacing: 0.15px;
}
.list-sub {
  margin-top: 6px;
  color: var(--muted);
  font-size: 13px;
}
.count {
  color: var(--muted2);
  font-weight: 800;
  margin-left: 6px;
}

/* ---------- Inputs / Selects ---------- */
.form { display: grid; gap: 10px; }
.field { display: grid; gap: 6px; }

.label {
  font-size: 13px;
  font-weight: 800;
  color: var(--muted);
}

.field input,
.search,
.label-size-select {
  width: 100%;
  padding: 12px 12px;
  border-radius: 14px;
  border: 1px solid var(--stroke);
  background: linear-gradient(180deg, rgba(255,255,255,0.04), transparent 35%), rgba(0,0,0,0.22);
  color: var(--txt);
  outline: none;
  transition: 0.15s ease;
}

.field input::placeholder,
.search::placeholder {
  color: rgba(255,255,255,0.35);
}

.field input:focus,
.search:focus,
.label-size-select:focus {
  border-color: rgba(79,124,255,0.65);
  box-shadow: 0 0 0 4px rgba(79,124,255,0.18);
  background: rgba(0,0,0,0.28);
}

/* select */
.select-wrap { position: relative; width: 100%; }
.select-wrap.grow { flex: 1; }

.label-size-select {
  appearance: none;
  cursor: pointer;
  padding-right: 40px;
}
.select-arrow {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.62);
  pointer-events: none;
}

/* ---------- Buttons ---------- */
.buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 2px;
}

.btn {
  border: 1px solid var(--stroke);
  background: rgba(255,255,255,0.04);
  color: var(--txt);
  border-radius: 14px;
  padding: 11px 14px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: 0.15s ease;
  user-select: none;
  box-shadow: 0 8px 18px rgba(0,0,0,0.18);
}

.btn:hover {
  transform: translateY(-1px);
  border-color: rgba(255,255,255,0.22);
  background: rgba(255,255,255,0.06);
}

.btn:active { transform: translateY(0px); }

.btn.primary {
  background: linear-gradient(180deg, rgba(255,255,255,0.10), transparent 40%), var(--brand);
  border-color: rgba(79,124,255,0.75);
  color: #0b1220;
  box-shadow: 0 14px 28px rgba(79,124,255,0.28);
}

.btn.primary:hover {
  filter: brightness(1.06);
  border-color: rgba(119,161,255,0.9);
}

.btn.ghost { background: transparent; }

.btn.soft {
  background: rgba(148,163,184,0.10);
}

.btn.danger {
  background: rgba(239,68,68,0.16);
  border-color: rgba(239,68,68,0.36);
}

.btn.mini {
  padding: 10px 12px;
  border-radius: 14px;
  white-space: nowrap;
}

/* ---------- Photo UI ---------- */
.photo { margin-top: 6px; }

.photo-btn {
  border: 1px dashed rgba(255,255,255,0.22);
  background: rgba(255,255,255,0.04);
  color: var(--txt);
  padding: 12px;
  border-radius: 16px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 900;
  transition: 0.15s ease;
}

.photo-btn:hover {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.32);
}

.photo-preview .thumb {
  width: 112px;
  height: 112px;
  border-radius: 16px;
  object-fit: cover;
  border: 1px solid var(--stroke);
  box-shadow: 0 10px 22px rgba(0,0,0,0.25);
}

.photo-actions {
  margin-top: 10px;
  display: flex;
  gap: 10px;
  align-items: center;
}

.photo-del {
  width: 44px;
  height: 44px;
  border-radius: 16px;
  border: 1px solid rgba(239,68,68,0.35);
  background: rgba(239,68,68,0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.86);
  cursor: pointer;
  transition: 0.15s ease;
}
.photo-del:hover { filter: brightness(1.08); }

/* ---------- Right grid list ---------- */
.grid { display: grid; gap: 14px; }

/* ---------- Item card ---------- */
.card {
  position: relative;
  background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent 50%) , var(--panel);
  border: 1px solid var(--stroke);
  border-radius: 20px;
  padding: 14px;
  display: grid;
  grid-template-columns: 310px 1fr;
  gap: 14px;
  transition: 0.15s ease;
  box-shadow: var(--shadow-soft);
    overflow: visible; /* чтобы кнопки точно не обрезались */

}

.card:hover {
  border-color: rgba(255,255,255,0.20);
  box-shadow: var(--shadow);
}

.card.selected {
  border-color: rgba(79,124,255,0.75);
  box-shadow: 0 0 0 2px rgba(79,124,255,0.24), var(--shadow);
}

.col { min-width: 0; }
.leftcol {
  display: grid;
  align-content: start;
  justify-items: center;
  gap: 10px;
}

.rightcol {
  display: grid;
  grid-template-columns: 1fr 220px;
  gap: 12px;
  align-items: start;
}

/* ---------- Barcode svg area ---------- */
svg {
  width: 100%;
  max-width: 290px;
  background: #fff;
  border-radius: 14px;
  padding: 8px;
  border: 1px solid rgba(0,0,0,0.08);
  box-shadow: 0 10px 24px rgba(0,0,0,0.22);
}

/* ---------- Code row ---------- */
.code-row {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 8px;
}
.code {
  flex: 1;
  color: rgba(255,255,255,0.92);
  font-weight: 950;
  letter-spacing: 0.25px;
  word-break: break-word;
}

/* copy button */
.copy {
  border: 1px solid var(--stroke);
  background: rgba(255,255,255,0.05);
  color: rgba(255,255,255,0.88);
  border-radius: 12px;
  padding: 10px 12px;
  cursor: pointer;
  transition: 0.15s ease;
}
.copy:hover {
  background: rgba(255,255,255,0.07);
  border-color: rgba(255,255,255,0.22);
}
.copy.mini {
  padding: 6px 8px;
  border-radius: 12px;
}

/* ---------- Print params area ---------- */
.print-params {
  width: 100%;
  border-top: 1px solid var(--stroke);
  padding-top: 12px;
  display: grid;
  gap: 10px;
}

.flags {
  display: flex;
  justify-content: center;
  gap: 14px;
}

.param-row {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,0.88);
  font-weight: 850;
}

.param-row input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--brand);
  cursor: pointer;
}

.print-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

/* ---------- Info block ---------- */
.info {
  display: grid;
  gap: 10px;
}

.info .row {
  display: grid;
  gap: 6px;
  padding: 10px 10px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.06);
  background: rgba(0,0,0,0.18);
}

.k {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-weight: 900;
}

.v {
  color: rgba(255,255,255,0.92);
  font-weight: 800;
  word-break: break-word;
}

/* ---------- Photo preview on card ---------- */
.photo-box { width: 100%; }

.card-photo {
  width: 100%;
  height: 168px;
  object-fit: cover;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.10);
  cursor: pointer;
  box-shadow: 0 12px 26px rgba(0,0,0,0.28);
  transition: 0.15s ease;
}
.card-photo:hover { transform: translateY(-1px); }

.no-photo {
  width: 100%;
  height: 168px;
  border-radius: 16px;
  border: 1px dashed rgba(255,255,255,0.22);
  background: rgba(0,0,0,0.18);
  color: var(--muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}

/* ---------- Tools on card (edit/delete) ---------- */
.tools {
  position: absolute;
  top: 10px;
  right: 10px;
  display: flex;
  gap: 8px;
}

.tool {
  width: 42px;
  height: 42px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(0,0,0,0.20);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: 0.15s ease;
  color: rgba(255,255,255,0.88);
  box-shadow: 0 10px 22px rgba(0,0,0,0.20);
}
.tool:hover {
  border-color: rgba(255,255,255,0.22);
  background: rgba(255,255,255,0.06);
}
.tool.edit { color: rgba(119,161,255,0.95); }
.tool.del  { color: rgba(255,120,120,0.92); }

/* ---------- Select button (bottom-right) ---------- */
.selectbox {
  position: absolute;
  bottom: 12px;
  right: 12px;
  width: 150px;
}

.btn.select {
  width: 100%;
  justify-content: center;
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.16);
}
.btn.select.active {
  background: rgba(16,185,129,0.16);
  border-color: rgba(16,185,129,0.42);
}

/* checkbox hidden */
.hidden-checkbox { display: none; }

/* ---------- Search hint chips ---------- */
.sizes-hint {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.muted {
  color: var(--muted);
  font-weight: 900;
  font-size: 13px;
}
.chip {
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(255,255,255,0.06);
  padding: 6px 10px;
  border-radius: 999px;
  color: rgba(255,255,255,0.88);
  font-weight: 900;
  font-size: 12px;
}

/* ---------- Highlight in search ---------- */
.highlight-row {
  color: rgba(255,255,255,0.95);
  font-weight: 950;
  background: rgba(245,158,11,0.22);
  border: 1px solid rgba(245,158,11,0.32);
  padding: 0 6px;
  border-radius: 8px;
}

/* ---------- Selected bulk controls (bottom fixed) ---------- */
.selected-controls {
  position: fixed;
  left: 12px;
  right: 12px;
  bottom: 12px;
  z-index: 99999;

  background: linear-gradient(180deg, rgba(255,255,255,0.06), transparent 60%), rgba(10,14,22,0.92);
  border: 1px solid rgba(255,255,255,0.14);
  border-radius: 20px;
  padding: 14px;

  display: grid;
  gap: 12px;

  box-shadow: 0 24px 60px rgba(0,0,0,0.45);
  backdrop-filter: blur(10px);
}

.bulk-row { display: grid; gap: 10px; }

.bulk-head { display: grid; gap: 2px; }
.bulk-title { color: rgba(255,255,255,0.92); font-weight: 950; }
.bulk-sub { color: var(--muted); font-size: 12px; font-weight: 800; }

.bulk-flags-row {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}
.bulk-actions-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* ---------- Drag selection rect ---------- */
.drag-rect {
  position: absolute;
  border: 2px dashed rgba(79,124,255,0.9);
  background: rgba(79,124,255,0.10);
  pointer-events: none;
  z-index: 999999;
  border-radius: 14px;
}

/* ---------- Toast ---------- */
.toast {
  position: fixed;
  top: 16px;
  left: 16px;
  right: 16px;
  max-width: 560px;
  margin: 0 auto;
  z-index: 99999;
  padding: 12px 14px;
  border-radius: 16px;
  font-weight: 950;
  border: 1px solid rgba(255,255,255,0.16);
  background: rgba(10,14,22,0.92);
  color: rgba(255,255,255,0.92);
  box-shadow: 0 18px 44px rgba(0,0,0,0.44);
  backdrop-filter: blur(10px);
}
.toast.success {
  border-color: rgba(16,185,129,0.42);
  background: rgba(16,185,129,0.16);
}
.toast.error {
  border-color: rgba(239,68,68,0.42);
  background: rgba(239,68,68,0.16);
}

/* ---------- Modals (camera / photo) ---------- */
.photo-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.86);
  backdrop-filter: blur(5px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999999;
}
.photo-modal-content {
  position: relative;
  max-width: 86%;
  max-height: 86%;
}
.photo-modal-img {
  max-width: 100%;
  max-height: 100%;
  border-radius: 18px;
  object-fit: contain;
  box-shadow: 0 26px 70px rgba(0,0,0,0.55);
  border: 1px solid rgba(255,255,255,0.10);
}
.photo-modal-close {
  position: absolute;
  top: -12px;
  right: -12px;
  background: linear-gradient(180deg, rgba(255,255,255,0.16), transparent 60%), var(--brand);
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #0b1220;
  box-shadow: 0 16px 36px rgba(79,124,255,0.32);
}

.camera-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.86);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999999;
}
.camera-window {
  background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent 60%), #0b1220;
  padding: 18px;
  border-radius: 22px;
  max-width: 520px;
  width: 94%;
  text-align: center;
  border: 1px solid rgba(255,255,255,0.12);
  box-shadow: 0 26px 70px rgba(0,0,0,0.58);
}
.cam-video {
  width: 100%;
  border-radius: 18px;
  background: black;
  border: 1px solid rgba(255,255,255,0.10);
}
.btn-capture {
  width: 78px;
  height: 78px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(180deg, rgba(255,255,255,0.18), transparent 60%), var(--brand);
  font-size: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin: 14px auto 0;
  color: #0b1220;
  box-shadow: 0 18px 42px rgba(79,124,255,0.34);
}
.btn-close {
  margin-top: 12px;
  width: 100%;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.14);
  color: rgba(255,255,255,0.88);
  padding: 12px;
  border-radius: 14px;
  cursor: pointer;
}
.btn-close:hover { background: rgba(255,255,255,0.08); }
.card-tools-box {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 9999;           /* главное — очень высокий */
  display: flex;
  gap: 8px;
}

.card-tool-btn {
  position: relative;
  z-index: 10000;
}
@media (min-width: 1101px) {
  .photo-box,
  .no-photo-text {
    margin-top: 56px;
  }
}
/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
  .page {
    grid-template-columns: 1fr;
    padding: 14px;
  }
    .card-left svg {
    margin-top: 56px;
  }
  .left { position: static; }
  .card { grid-template-columns: 1fr; }
  .rightcol { grid-template-columns: 1fr; }
  .selectbox {
    position: static;
    width: 100%;
    margin-top: 10px;
  }
  .bulk-actions-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .page { padding: 12px; border-radius: 18px; }
  .card-box { padding: 14px; }
  .btn { width: 100%; justify-content: center; }
  .buttons .btn { width: 100%; }
  .flags { justify-content: flex-start; }
  .tools { top: 10px; right: 10px; }
}
</style>

