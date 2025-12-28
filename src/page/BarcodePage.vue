<template>
  <div class="page" @mousedown="dragStart">
    <!-- ===== Bottom panel для выбранных ===== -->
    <transition name="slide-up">
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
              <Fa aria-hidden="true" class="select-arrow" :icon="['fas','chevron-down']" />
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
            <Fa :icon="['fas','print']" />
            Печать ({{ selectedIds.length }})
          </button>

          <button class="btn ghost" @click="exportSelected">
            <Fa :icon="['fas','file-excel']" />
            Экспорт
          </button>

          <button class="btn danger" @click="deleteSelected">
            <Fa :icon="['fas','trash']" />
            Удалить
          </button>

          <button class="btn soft" @click="clearSelected">
            <Fa :icon="['fas','xmark']" />
            Снять выделение
          </button>
        </div>
      </div>
    </transition>

    <!-- ===== Toast ===== -->
    <transition name="toast">
      <div v-if="message" :class="['toast', messageType]">
        <div class="toast-dot" aria-hidden="true"></div>
        <div class="toast-text">{{ message }}</div>
      </div>
    </transition>

    <!-- ===== LEFT (create + search) ===== -->
    <section class="left">
      <div class="card-box">
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
              <Fa :icon="['fas','check']" />
              {{ editMode ? "Сохранить" : "Создать" }}
            </button>

            <button v-if="!manualMode && !editMode" class="btn ghost" @click="manualMode = true">
              <Fa :icon="['fas','pen-to-square']" />
              Вручную
            </button>

            <button v-if="manualMode && !editMode" class="btn soft" @click="cancelManualMode">
              <Fa :icon="['fas','xmark']" />
              Отменить
            </button>

            <button v-if="editMode" class="btn soft" @click="cancelEdit">
              <Fa :icon="['fas','xmark']" />
              Отменить
            </button>
          </div>

          <transition name="fade">
            <div v-if="manualMode" class="manual">
              <label class="field">
                <span class="label">Штрихкод вручную</span>
                <input v-model="manualBarcode" placeholder="Введите штрихкод вручную" />
              </label>
            </div>
          </transition>

          <!-- Фото -->
<!-- Фото -->
<div class="photo">
  <transition name="fade" mode="out-in">
    <div
      v-if="!photoPreview"
      key="no-photo"
      class="photo-btn"
      @click="openCameraModal"
    >
      <Fa :icon="['fas','camera']" />
      Сделать фото
    </div>

    <div v-else key="has-photo" class="photo-preview">
      <img :src="photoPreview" class="thumb" />

      <div class="photo-actions">
        <div class="photo-btn" @click="openCameraModal">
          <Fa :icon="['fas','camera-rotate']" />
          Переснять
        </div>

        <div class="photo-del" @click="removePhoto" title="Удалить фото">
          <Fa :icon="['fas','trash']" />
        </div>
      </div>
    </div>
  </transition>
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

      <TransitionGroup name="cards" tag="div" class="grid">
        <div
          class="card"
          v-for="(item, idx) in barcodes"
          :key="item.id"
          :data-id="item.id"
          :class="{ selected: selectedIds.includes(item.id) }"
          :style="{ animationDelay: (idx * 18) + 'ms' }"
          @click="cardClick($event, item.id)"
        >
          <!-- tools -->
          <div class="tools">
            <button class="tool edit" @click.stop="startEdit(item)" title="Редактировать">
              <Fa :icon="['fas','pen']" />
            </button>
            <button class="tool del" @click.stop="deleteItem(item.id)" title="Удалить">
              <Fa :icon="['fas','trash']" />
            </button>
          </div>

          <!-- LEFT barcode -->
          <div class="col leftcol">
<svg :id="'g-' + item.id" class="barcode-svg"></svg>

            <div class="code-row">
              <div class="code" v-html="highlight(item.barcode, search)"></div>
              <button class="copy" @click.stop="copy(item.barcode)" title="Копировать штрихкод">
                <Fa :icon="['fas','copy']" />
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
                  <Fa class="select-arrow" :icon="['fas','chevron-down']" />
                </div>

                <button class="btn primary mini" @click.stop="openPrint(item)">
                  <Fa :icon="['fas','print']" />
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
                    <Fa :icon="['fas','copy']" />
                  </button>
                </div>
                <div class="v" v-html="highlight(item.product_name, search)"></div>
              </div>

              <div class="row">
                <div class="k">
                  Артикул
                  <button class="copy mini" @click.stop="copy(item.sku)" title="Копировать">
                    <Fa :icon="['fas','copy']" />
                  </button>
                </div>
                <div class="v" v-html="highlight(item.sku, search)"></div>
              </div>

              <div class="row two">
                <div class="kv">
                  <div class="k">Остаток</div>
                  <div class="v">{{ item.stock }}</div>
                </div>
                <div class="kv">
                  <div class="k">Цена</div>
                  <div class="v" v-html="highlight(item.price, search)"></div>
                </div>
              </div>

              <div class="row">
                <div class="k">Контрагент</div>
                <div class="v" v-html="highlight(item.contractor, search)"></div>
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

            <input type="checkbox" :value="item.id" v-model="selectedIds" class="hidden-checkbox" />
          </div>
        </div>
      </TransitionGroup>
    </section>

    <!-- ===== Camera modal ===== -->
    <transition name="modal">
      <div v-if="cameraOpen" class="camera-overlay">
        <div class="camera-window">
          <div class="modal-top">
            <div class="modal-title">Камера</div>
            <button class="icon-x" @click="closeCameraModal" aria-label="Закрыть">
              <Fa :icon="['fas','xmark']" />
            </button>
          </div>

          <video ref="video" autoplay playsinline class="cam-video"></video>

          <button class="btn-capture" @click="takePhoto">
            <Fa :icon="['fas','camera']" />
          </button>

          <button class="btn-close" @click="closeCameraModal">Закрыть</button>
        </div>
      </div>
    </transition>

    <!-- ===== Photo modal ===== -->
    <transition name="modal">
      <div v-if="photoModalOpen" class="photo-modal-overlay" @click="closePhoto">
        <div class="photo-modal-content" @click.stop>
          <img :src="photoModalSrc" class="photo-modal-img" />
          <button class="photo-modal-close" @click="closePhoto" aria-label="Закрыть фото">
            <Fa :icon="['fas','xmark']" />
          </button>
        </div>
      </div>
    </transition>

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
  } catch (e) {}

  if (!labelSizes.value?.length) {
    labelSizes.value = [
      { value: "42x25", text: "42 × 25 мм" },
      { value: "30x20", text: "30 × 20 мм" },
    ];
  }

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
  if (window.getSelection?.().toString().length > 0) return;

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
    const r = await fetch("/api/barcode/get_barcodes.php?search=" + encodeURIComponent(search.value));
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

  const res = await fetch("/api/barcode/create_barcode.php", { method: "POST", body: form });
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

  const r = await fetch("/api/barcode/update_barcode.php", { method: "POST", body: form });
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
        const r = await fetch("/api/barcode/delete_barcode.php?id=" + id);
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
   BARCODE PAGE — PREMIUM LIGHT UI (под твой :root)
   ========================================================= */

.page{
  min-height: 100dvh;
  margin: 0 auto;
  padding: clamp(12px, 2vw, 18px);
  display: grid;
  grid-template-columns: minmax(320px, 460px) 1fr;
  gap: clamp(12px, 2vw, 18px);
  align-items: start;

  background:
    radial-gradient(1200px 700px at 10% -10%, color-mix(in srgb, var(--accent) 9%, transparent), transparent 60%),
    radial-gradient(1000px 600px at 110% 10%, color-mix(in srgb, var(--secondary-accent) 12%, transparent), transparent 55%),
    var(--bg-main);
  color: var(--text-main);
}

/* ---------- columns ---------- */
.left{
  display: grid;
  gap: 14px;
  position: sticky;
  top: calc(var(--site-header-h) + 14px);
  align-self: start;
}
.right{ min-width: 0; }

/* ---------- card ---------- */
.card-box{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 16px;
  box-shadow: var(--shadow-sm);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.card-box:hover{
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--text-light) 18%, var(--border-soft));
}

/* ---------- headings ---------- */
.card-head{ margin-bottom: 12px; }
.title{
  margin: 0;
  font-size: 18px;
  line-height: 1.2;
  font-weight: 900;
  letter-spacing: .2px;
}
.hint{
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-muted);
}
.list-head{ padding: 6px 4px 14px; }
.list-title{
  font-weight: 900;
  font-size: 18px;
  letter-spacing: .2px;
}
.list-sub{
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 13px;
}
.count{
  color: var(--text-light);
  font-weight: 800;
  margin-left: 6px;
}

/* ---------- toast ---------- */
.toast{
  position: fixed;
  top: calc(var(--site-header-h) + 12px);
  left: 12px;
  right: 12px;
  max-width: 560px;
  margin: 0 auto;
  z-index: 99999;

  display: grid;
  grid-template-columns: 10px 1fr;
  gap: 10px;

  padding: 12px 14px;
  border-radius: 18px;
  font-weight: 900;

  background: var(--bg-panel);
  color: var(--text-main);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-lg);
}
.toast-dot{
  width: 10px;
  height: 10px;
  border-radius: 99px;
  margin-top: 3px;
  background: color-mix(in srgb, var(--accent) 80%, #fff);
}
.toast.success{
  border-color: color-mix(in srgb, var(--accent-2) 45%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-panel));
}
.toast.success .toast-dot{ background: var(--accent-2); }
.toast.error{
  border-color: color-mix(in srgb, var(--accent-danger) 50%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
}
.toast.error .toast-dot{ background: var(--accent-danger); }

/* toast transitions */
.toast-enter-active, .toast-leave-active{ transition: opacity .18s ease, transform .18s ease; }
.toast-enter-from, .toast-leave-to{ opacity: 0; transform: translateY(-6px) scale(.98); }

/* ---------- form ---------- */
.form{ display: grid; gap: 10px; }
.field{ display: grid; gap: 6px; }
.label{
  font-size: 13px;
  font-weight: 800;
  color: var(--text-muted);
}
.field input,
.search,
.label-size-select{
  width: 100%;
  padding: 12px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease, transform .15s ease;
}
.field input::placeholder,
.search::placeholder{ color: var(--text-light); }
.field input:focus,
.search:focus,
.label-size-select:focus{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
  background: var(--bg-panel);
}

/* buttons row */
.buttons{
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 2px;
}

/* ---------- buttons ---------- */
.btn{
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: 14px;
  padding: 11px 14px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform .14s ease, box-shadow .14s ease, background .14s ease, border-color .14s ease, filter .14s ease;
  user-select: none;
  box-shadow: var(--shadow-sm);
}
.btn:hover{
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--text-light) 25%, var(--border-soft));
}
.btn:active{ transform: translateY(0px); }

.btn.primary{
  background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 92%, #fff), var(--accent));
  border-color: color-mix(in srgb, var(--accent) 65%, var(--border-soft));
  color: #fff;
}
.btn.primary:hover{ filter: brightness(1.04); }

.btn.ghost{ background: transparent; }
.btn.soft{ background: var(--bg-soft); }
.btn.danger{
  background: color-mix(in srgb, var(--accent-danger) 12%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-danger) 45%, var(--border-soft));
  color: var(--accent-danger);
}
.btn.mini{
  padding: 10px 12px;
  border-radius: 14px;
  white-space: nowrap;
}

/* ---------- select ---------- */
.select-wrap{ position: relative; width: 100%; }
.select-wrap.grow{ flex: 1; }
.label-size-select{
  appearance: none;
  cursor: pointer;
  padding-right: 40px;
}
.select-arrow{
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  pointer-events: none;
}

/* ---------- photo (left) ---------- */
.photo{ margin-top: 6px; }
.photo-btn{
  border: 1px dashed var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
  padding: 12px;
  border-radius: 18px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 900;
  transition: transform .14s ease, box-shadow .14s ease, background .14s ease, border-color .14s ease;
}
.photo-btn:hover{
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 30%, var(--border-soft));
}
.photo-preview .thumb{
  width: 112px;
  height: 112px;
  border-radius: 18px;
  object-fit: cover;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-md);
}
.photo-actions{
  margin-top: 10px;
  display: flex;
  gap: 10px;
  align-items: center;
}
.photo-del{
  width: 46px;
  height: 46px;
  border-radius: 18px;
  border: 1px solid color-mix(in srgb, var(--accent-danger) 40%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent-danger);
  cursor: pointer;
  transition: transform .14s ease, box-shadow .14s ease, filter .14s ease;
  box-shadow: var(--shadow-sm);
}
.photo-del:hover{ transform: translateY(-1px); box-shadow: var(--shadow-md); }

/* ---------- grid/cards ---------- */
.grid{ display: grid; gap: 14px; }

/* TransitionGroup */
.cards-enter-active, .cards-leave-active{ transition: opacity .18s ease, transform .18s ease; }
.cards-enter-from, .cards-leave-to{ opacity: 0; transform: translateY(10px) scale(.985); }
.cards-move{ transition: transform .18s ease; }

.card{
  position: relative;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 22px;
  padding: 14px;
  display: grid;
  grid-template-columns: 310px 1fr;
  gap: 14px;
  box-shadow: var(--shadow-sm);
  transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  overflow: visible;

  /* небольшая “премиум” анимация появления */
  animation: cardPop .26s ease both;
}
@keyframes cardPop{
  from{ opacity: 0; transform: translateY(10px) scale(.985); }
  to{ opacity: 1; transform: translateY(0) scale(1); }
}
.card:hover{
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--text-light) 22%, var(--border-soft));
}
.card.selected{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow:
    0 0 0 2px color-mix(in srgb, var(--accent) 18%, transparent),
    var(--shadow-md);
}

.col{ min-width: 0; }
.leftcol{
  display: grid;
  align-content: start;
  justify-items: center;
  gap: 10px;
}
.rightcol{
  display: grid;
  grid-template-columns: 1fr 220px;
  gap: 12px;
  align-items: start;
}

/* barcode svg */
.barcode-svg{
  width: 100%;
  max-width: 290px;
  background: #fff; /* для читаемости штрихкода */
  border-radius: var(--radius-md);
  padding: 8px;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
}


/* code row */
.code-row{
  display: flex;
  width: 100%;
  align-items: center;
  gap: 8px;
}
.code{
  flex: 1;
  font-weight: 900;
  letter-spacing: .2px;
  word-break: break-word;
}

/* copy buttons */
.copy{
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
  border-radius: 14px;
  padding: 10px 12px;
  cursor: pointer;
  transition: transform .14s ease, box-shadow .14s ease, background .14s ease, border-color .14s ease;
  box-shadow: var(--shadow-sm);
}
.copy:hover{
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 25%, var(--border-soft));
}
.copy.mini{ padding: 6px 8px; border-radius: 12px; }

/* print params */
.print-params{
  width: 100%;
  border-top: 1px solid var(--border-soft);
  padding-top: 12px;
  display: grid;
  gap: 10px;
}
.flags{
  display: flex;
  justify-content: center;
  gap: 14px;
  flex-wrap: wrap;
}
.param-row{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
}
.param-row input[type="checkbox"]{
  width: 18px;
  height: 18px;
  accent-color: var(--accent);
  cursor: pointer;
}
.print-row{
  display: flex;
  gap: 10px;
  align-items: center;
}

/* info rows */
.info{ display: grid; gap: 10px; }
.info .row{
  display: grid;
  gap: 6px;
  padding: 10px 10px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}
.info .row.two{
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.k{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-weight: 900;
}
.v{
  font-weight: 800;
  word-break: break-word;
}
.kv{ display: grid; gap: 6px; }

/* photo on card */
.photo-box{ width: 100%; }
.card-photo{
  width: 100%;
  height: 168px;
  object-fit: cover;
  border-radius: 18px;
  border: 1px solid var(--border-soft);
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: transform .16s ease, box-shadow .16s ease;
}
.card-photo:hover{ transform: translateY(-1px); box-shadow: var(--shadow-md); }
.no-photo{
  width: 100%;
  height: 168px;
  border-radius: 18px;
  border: 1px dashed var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}

/* tools */
.tools{
  position: absolute;
  top: 10px;
  right: 10px;
  display: flex;
  gap: 8px;
}
.tool{
  width: 40px;
  height: 40px;
  border-radius: 16px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
  color: var(--text-main);
  box-shadow: var(--shadow-sm);
}
.tool:hover{ transform: translateY(-1px); box-shadow: var(--shadow-md); background: var(--bg-soft); }
.tool.edit{ color: var(--accent); }
.tool.del{ color: var(--accent-danger); }

/* select */
.selectbox{
  position: absolute;
  bottom: 12px;
  right: 12px;
  width: 150px;
}
.btn.select{
  width: 100%;
  justify-content: center;
  background: var(--bg-soft);
}
.btn.select.active{
  background: color-mix(in srgb, var(--accent-2) 14%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-2) 45%, var(--border-soft));
}
.hidden-checkbox{ display: none; }

/* chips */
.sizes-hint{
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.muted{
  color: var(--text-muted);
  font-weight: 900;
  font-size: 13px;
}
.chip{
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
}

/* highlight */
.highlight-row{
  font-weight: 900;
  background: color-mix(in srgb, var(--accent) 16%, transparent);
  border: 1px solid color-mix(in srgb, var(--accent) 35%, var(--border-soft));
  padding: 0 6px;
  border-radius: 10px;
}

/* bottom bulk */
.selected-controls{
  position: fixed;
  left: 12px;
  right: 12px;
  bottom: calc(12px + env(safe-area-inset-bottom));
  z-index: 99999;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 22px;
  padding: 14px;

  display: grid;
  gap: 12px;

  box-shadow: var(--shadow-lg);
}
.bulk-row{ display: grid; gap: 10px; }
.bulk-head{ display: grid; gap: 2px; }
.bulk-title{ font-weight: 950; }
.bulk-sub{ color: var(--text-muted); font-size: 12px; font-weight: 800; }
.bulk-flags-row{ display: flex; gap: 14px; flex-wrap: wrap; }
.bulk-actions-row{ display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

/* slide-up transition */
.slide-up-enter-active, .slide-up-leave-active{ transition: opacity .18s ease, transform .18s ease; }
.slide-up-enter-from, .slide-up-leave-to{ opacity: 0; transform: translateY(10px); }

/* drag rect */
.drag-rect{
  position: absolute;
  border: 2px dashed var(--accent);
  background: color-mix(in srgb, var(--accent) 10%, transparent);
  pointer-events: none;
  z-index: 999999;
  border-radius: 14px;
}

/* modals */
.photo-modal-overlay,
.camera-overlay{
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.70);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999999;
}
.photo-modal-content{
  position: relative;
  max-width: 86%;
  max-height: 86%;
}
.photo-modal-img{
  max-width: 100%;
  max-height: 100%;
  border-radius: 18px;
  object-fit: contain;
  box-shadow: var(--shadow-lg);
  border: 1px solid rgba(255,255,255,0.18);
}
.photo-modal-close{
  position: absolute;
  top: -12px;
  right: -12px;
  background: var(--accent);
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: var(--shadow-md);
}

.camera-window{
  background: var(--bg-panel);
  padding: 18px;
  border-radius: 22px;
  max-width: 560px;
  width: min(94%, 560px);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-lg);
}
.modal-top{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}
.modal-title{
  font-weight: 950;
  letter-spacing: .2px;
}
.icon-x{
  width: 40px;
  height: 40px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform .14s ease, box-shadow .14s ease;
}
.icon-x:hover{ transform: translateY(-1px); box-shadow: var(--shadow-sm); }

.cam-video{
  width: 100%;
  border-radius: 18px;
  background: #000;
  border: 1px solid var(--border-soft);
}
.btn-capture{
  width: 78px;
  height: 78px;
  border-radius: 50%;
  border: none;
  background: var(--accent);
  font-size: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin: 14px auto 0;
  color: #fff;
  box-shadow: var(--shadow-md);
  transition: transform .14s ease, filter .14s ease;
}
.btn-capture:hover{ transform: translateY(-1px); filter: brightness(1.05); }

.btn-close{
  margin-top: 12px;
  width: 100%;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  color: var(--text-main);
  padding: 12px;
  border-radius: 14px;
  cursor: pointer;
  transition: background .14s ease;
}
.btn-close:hover{ background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft)); }

/* modal transitions */
.modal-enter-active, .modal-leave-active{ transition: opacity .18s ease; }
.modal-enter-from, .modal-leave-to{ opacity: 0; }

.fade-enter-active, .fade-leave-active{ transition: opacity .16s ease, transform .16s ease; }
.fade-enter-from, .fade-leave-to{ opacity: 0; transform: translateY(6px); }

/* ---------- responsive ---------- */
@media (max-width: 1100px){
  .page{ grid-template-columns: 1fr; }
  .left{ position: static; }
  .card{ grid-template-columns: 1fr; }
  .rightcol{ grid-template-columns: 1fr; }
  .selectbox{
    position: static;
    width: 100%;
    margin-top: 10px;
  }
  .bulk-actions-row{ grid-template-columns: 1fr; }
}
@media (max-width: 520px){
  .card-box{ padding: 14px; }
  .btn{ width: 100%; justify-content: center; }
  .buttons .btn{ width: 100%; }
  .flags{ justify-content: flex-start; }
}

/* reduce motion */
@media (prefers-reduced-motion: reduce){
  *{ animation: none !important; transition: none !important; scroll-behavior: auto !important; }
}
</style>
