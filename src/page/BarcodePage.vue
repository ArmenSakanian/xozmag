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

          <div v-if="manualMode" class="manual">
            <label class="field">
              <span class="label">Штрихкод вручную</span>
              <input v-model="manualBarcode" placeholder="Введите штрихкод вручную" />
            </label>
          </div>

          <!-- Фото -->
          <div class="photo">
            <div v-if="!photoPreview" class="photo-btn" @click="openCameraModal">
              <Fa :icon="['fas','camera']" />
              Сделать фото
            </div>

            <div v-else class="photo-preview">
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
              <Fa :icon="['fas','pen']" />
            </button>
            <button class="tool del" @click.stop="deleteItem(item.id)" title="Удалить">
              <Fa :icon="['fas','trash']" />
            </button>
          </div>

          <!-- LEFT barcode -->
          <div class="col leftcol">
            <svg :id="'g-' + item.id"></svg>

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
          <Fa :icon="['fas','camera']" />
        </button>

        <button class="btn-close" @click="closeCameraModal">Закрыть</button>
      </div>
    </div>

    <!-- ===== Photo modal ===== -->
    <div v-if="photoModalOpen" class="photo-modal-overlay" @click="closePhoto">
      <div class="photo-modal-content" @click.stop>
        <img :src="photoModalSrc" class="photo-modal-img" />
        <button class="photo-modal-close" @click="closePhoto">
          <Fa :icon="['fas','xmark']" />
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
   BARCODE ADMIN PAGE — LIGHT UI (под твой :root)
   ========================================================= */

.page{
  margin: 0 auto;
  padding: 18px;
  display: grid;
  grid-template-columns: minmax(330px, 440px) 1fr;
  gap: 18px;
  align-items: start;

  background: var(--bg-main);
  color: var(--text-main);
}

/* ---------- Columns ---------- */
.left{
  display: grid;
  gap: 14px;
  position: sticky;
  top: calc(var(--site-header-h) + 14px);
}
.right{ min-width: 0; }

/* ---------- Card boxes ---------- */
.card-box{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 16px;
  box-shadow: var(--shadow-sm);
}

/* ---------- Headers / texts ---------- */
.card-head{ margin-bottom: 12px; }

.title{
  margin: 0;
  font-size: 18px;
  line-height: 1.2;
  font-weight: 800;
  color: var(--text-main);
}
.hint{
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-muted);
}

.list-head{ padding: 6px 4px 14px; }
.list-title{
  color: var(--text-main);
  font-weight: 800;
  font-size: 18px;
}
.list-sub{
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 13px;
}
.count{
  color: var(--text-light);
  font-weight: 700;
  margin-left: 6px;
}

/* ---------- Toast ---------- */
.toast{
  position: fixed;
  top: calc(var(--site-header-h) + 10px);
  left: 16px;
  right: 16px;
  max-width: 560px;
  margin: 0 auto;
  z-index: 99999;

  padding: 12px 14px;
  border-radius: var(--radius-md);
  font-weight: 800;

  background: var(--bg-panel);
  color: var(--text-main);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-md);
}
.toast.success{
  border-color: color-mix(in srgb, var(--accent-2) 55%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-panel));
}
.toast.error{
  border-color: color-mix(in srgb, var(--accent-danger) 55%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
}

/* ---------- Inputs / Selects ---------- */
.form{ display: grid; gap: 10px; }
.field{ display: grid; gap: 6px; }

.label{
  font-size: 13px;
  font-weight: 700;
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
  transition: 0.15s ease;
}

.field input::placeholder,
.search::placeholder{
  color: var(--text-light);
}

.field input:focus,
.search:focus,
.label-size-select:focus{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
  background: var(--bg-panel);
}

/* select */
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

/* ---------- Buttons ---------- */
.buttons{
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 2px;
}

.btn{
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: var(--radius-md);
  padding: 11px 14px;
  font-weight: 800;

  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: 0.15s ease;
  user-select: none;
  box-shadow: var(--shadow-sm);
}

.btn:hover{
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn:active{ transform: translateY(0px); }

.btn.primary{
  background: var(--accent);
  border-color: color-mix(in srgb, var(--accent) 65%, var(--border-soft));
  color: #fff;
}
.btn.primary:hover{ filter: brightness(1.05); }

.btn.ghost{
  background: transparent;
}

.btn.soft{
  background: var(--bg-soft);
}

.btn.danger{
  background: color-mix(in srgb, var(--accent-danger) 12%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-danger) 45%, var(--border-soft));
  color: var(--accent-danger);
}

.btn.mini{
  padding: 10px 12px;
  border-radius: var(--radius-md);
  white-space: nowrap;
}

/* ---------- Photo UI (left form) ---------- */
.photo{ margin-top: 6px; }

.photo-btn{
  border: 1px dashed var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);

  padding: 12px;
  border-radius: var(--radius-lg);
  cursor: pointer;

  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  transition: 0.15s ease;
}

.photo-btn:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 30%, var(--border-soft));
}

.photo-preview .thumb{
  width: 112px;
  height: 112px;
  border-radius: var(--radius-lg);
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
  width: 44px;
  height: 44px;
  border-radius: var(--radius-lg);

  border: 1px solid color-mix(in srgb, var(--accent-danger) 40%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent-danger);

  cursor: pointer;
  transition: 0.15s ease;
}
.photo-del:hover{ filter: brightness(1.05); }

/* ---------- Right grid list ---------- */
.grid{ display: grid; gap: 14px; }

/* ---------- Item card ---------- */
.card{
  position: relative;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 20px;
  padding: 14px;

  display: grid;
  grid-template-columns: 310px 1fr;
  gap: 14px;

  transition: 0.15s ease;
  box-shadow: var(--shadow-sm);
  overflow: visible;
}

.card:hover{
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--text-light) 25%, var(--border-soft));
}

.card.selected{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 18%, transparent), var(--shadow-md);
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

/* ---------- Barcode svg ---------- */
svg{
  width: 100%;
  max-width: 290px;
  background: #fff; /* для читаемости штрихкода */
  border-radius: var(--radius-md);
  padding: 8px;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
}

/* ---------- Code row ---------- */
.code-row{
  display: flex;
  width: 100%;
  align-items: center;
  gap: 8px;
}

.code{
  flex: 1;
  color: var(--text-main);
  font-weight: 800;
  letter-spacing: 0.2px;
  word-break: break-word;
}

/* copy */
.copy{
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);

  border-radius: var(--radius-md);
  padding: 10px 12px;
  cursor: pointer;
  transition: 0.15s ease;
}
.copy:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 25%, var(--border-soft));
}
.copy.mini{
  padding: 6px 8px;
  border-radius: var(--radius-md);
}

/* ---------- Print params ---------- */
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
}

.param-row{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-main);
  font-weight: 700;
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

/* ---------- Info block ---------- */
.info{ display: grid; gap: 10px; }

.info .row{
  display: grid;
  gap: 6px;
  padding: 10px 10px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}

.k{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-weight: 800;
}

.v{
  color: var(--text-main);
  font-weight: 700;
  word-break: break-word;
}

/* ---------- Photo on card ---------- */
.photo-box{ width: 100%; }

.card-photo{
  width: 100%;
  height: 168px;
  object-fit: cover;

  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  cursor: pointer;

  box-shadow: var(--shadow-sm);
  transition: 0.15s ease;
}
.card-photo:hover{ transform: translateY(-1px); box-shadow: var(--shadow-md); }

.no-photo{
  width: 100%;
  height: 168px;

  border-radius: var(--radius-lg);
  border: 1px dashed var(--border-soft);
  background: var(--bg-soft);

  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
}

/* ---------- Tools (edit/delete) ---------- */
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
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  cursor: pointer;

  display: flex;
  align-items: center;
  justify-content: center;

  transition: 0.15s ease;
  color: var(--text-main);
  box-shadow: var(--shadow-sm);
}
.tool:hover{
  background: var(--bg-soft);
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}
.tool.edit{ color: var(--accent); }
.tool.del{ color: var(--accent-danger); }

/* ---------- Select button ---------- */
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
  color: var(--text-main);
}

/* hidden checkbox */
.hidden-checkbox{ display: none; }

/* ---------- Search hint chips ---------- */
.sizes-hint{
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.muted{
  color: var(--text-muted);
  font-weight: 800;
  font-size: 13px;
}
.chip{
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  padding: 6px 10px;
  border-radius: 999px;
  color: var(--text-main);
  font-weight: 800;
  font-size: 12px;
}

/* ---------- Highlight ---------- */
.highlight-row{
  font-weight: 800;
  background: color-mix(in srgb, var(--accent) 16%, transparent);
  border: 1px solid color-mix(in srgb, var(--accent) 35%, var(--border-soft));
  padding: 0 6px;
  border-radius: 8px;
}

/* ---------- Selected bulk controls (bottom fixed) ---------- */
.selected-controls{
  position: fixed;
  left: 12px;
  right: 12px;
  bottom: 12px;
  z-index: 99999;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 20px;
  padding: 14px;

  display: grid;
  gap: 12px;

  box-shadow: var(--shadow-lg);
}

.bulk-row{ display: grid; gap: 10px; }

.bulk-head{ display: grid; gap: 2px; }
.bulk-title{ color: var(--text-main); font-weight: 900; }
.bulk-sub{ color: var(--text-muted); font-size: 12px; font-weight: 700; }

.bulk-flags-row{
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.bulk-actions-row{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

/* ---------- Drag selection rect ---------- */
.drag-rect{
  position: absolute;
  border: 2px dashed var(--accent);
  background: color-mix(in srgb, var(--accent) 10%, transparent);
  pointer-events: none;
  z-index: 999999;
  border-radius: var(--radius-md);
}

/* ---------- Modals ---------- */
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

  font-size: 18px;
  color: #fff;
  box-shadow: var(--shadow-md);
}

.camera-window{
  background: var(--bg-panel);
  padding: 18px;
  border-radius: 22px;
  max-width: 520px;
  width: 94%;
  text-align: center;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-lg);
}

.cam-video{
  width: 100%;
  border-radius: 18px;
  background: black;
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
}
.btn-capture:hover{ filter: brightness(1.05); }

.btn-close{
  margin-top: 12px;
  width: 100%;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  color: var(--text-main);
  padding: 12px;
  border-radius: var(--radius-md);
  cursor: pointer;
}
.btn-close:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
}

/* ---------- Responsive ---------- */
@media (max-width: 1100px){
  .page{
    grid-template-columns: 1fr;
    padding: 14px;
  }
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
  .page{ padding: 12px; }
  .card-box{ padding: 14px; }
  .btn{ width: 100%; justify-content: center; }
  .buttons .btn{ width: 100%; }
  .flags{ justify-content: flex-start; }
  .tools{ top: 10px; right: 10px; }
}
</style>


