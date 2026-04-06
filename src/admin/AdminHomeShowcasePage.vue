<template>
  <div class="page">
    <div class="head card">
      <div>
        <h1 class="title">Карточки витрины</h1>
        <div class="sub">
          Один блок на главной странице под текстом поверх слайдера. Заголовок редактируется отдельно, карточки хранятся в своей таблице и не зависят от синхронизации товаров.
        </div>
      </div>

      <button class="btn ghost" :disabled="loading" @click="fetchData">
        <Fa :icon="['fas','rotate-right']" />
        Обновить
      </button>
    </div>

    <div v-if="error" class="notice error">{{ error }}</div>
    <div v-if="loading" class="notice">Загрузка...</div>

    <section class="card section">
      <div class="section-head">
        <h2>Заголовок блока</h2>
      </div>

      <div class="form-grid single">
        <label class="field">
          <span>Текст заголовка</span>
          <input v-model.trim="blockTitle" type="text" maxlength="255" placeholder="Например: Новинки и предложения" />
        </label>
      </div>

      <div class="actions-row">
        <button class="btn primary" :disabled="savingTitle || !blockTitle" @click="saveTitle">
          <Fa :icon="['fas','floppy-disk']" />
          {{ savingTitle ? 'Сохранение...' : 'Сохранить заголовок' }}
        </button>
      </div>
    </section>

    <section class="card section">
      <div class="section-head">
        <h2>Добавить карточку</h2>
      </div>

      <div class="form-grid">
        <label class="field field-span-2">
          <span>Название</span>
          <input v-model.trim="newItem.title" type="text" maxlength="255" placeholder="Название карточки" />
        </label>

        <label class="field">
          <span>Цена - необязательно</span>
          <input v-model.trim="newItem.price" type="text" maxlength="120" placeholder="Например: 990 ₽" />
        </label>

        <label class="field">
          <span>Фото</span>
          <input type="file" accept="image/jpeg,image/png,image/webp" @change="onNewImageChange" />
        </label>

        <label class="field field-span-2">
          <span>Описание - необязательно</span>
          <textarea v-model.trim="newItem.description" rows="4" placeholder="Короткое описание"></textarea>
        </label>

        <label class="field">
          <span>Текст кнопки</span>
          <input v-model.trim="newItem.button_text" type="text" maxlength="120" placeholder="Например: Уточнить наличие" />
        </label>

        <label class="field">
          <span>Ссылка кнопки</span>
          <input v-model.trim="newItem.button_url" type="text" maxlength="500" placeholder="/catalog или https://..." />
        </label>
      </div>

      <div class="actions-row">
        <button class="btn primary" :disabled="creating" @click="createItem">
          <Fa :icon="['fas','plus']" />
          {{ creating ? 'Добавление...' : 'Добавить карточку' }}
        </button>
      </div>
    </section>

    <section class="card section">
      <div class="section-head section-head-between">
        <h2>Текущие карточки</h2>
        <div class="count">{{ items.length }}</div>
      </div>

      <div v-if="!items.length" class="empty">
        Карточек пока нет.
      </div>

      <div v-else class="items-grid">
        <article v-for="item in items" :key="item.id" class="item-card" :class="{ hiddenCard: !item.is_active }">
          <div class="card-top">
            <div class="status-pill" :class="item.is_active ? 'isVisible' : 'isHidden'">
              {{ item.is_active ? 'Показывается' : 'Скрыта' }}
            </div>
          </div>

          <div class="preview-box">
            <img v-if="item.image_url" :src="item.image_url" :alt="item.title" class="preview-image" />
          </div>

          <div class="form-grid">
            <label class="field field-span-2">
              <span>Название</span>
              <input v-model.trim="item.title" type="text" maxlength="255" />
            </label>

            <label class="field">
              <span>Цена - необязательно</span>
              <input v-model.trim="item.price" type="text" maxlength="120" />
            </label>

            <label class="field">
              <span>Заменить фото</span>
              <input type="file" accept="image/jpeg,image/png,image/webp" @change="onExistingImageChange(item, $event)" />
            </label>

            <label class="field field-span-2">
              <span>Описание - необязательно</span>
              <textarea v-model.trim="item.description" rows="4"></textarea>
            </label>

            <label class="field">
              <span>Текст кнопки</span>
              <input v-model.trim="item.button_text" type="text" maxlength="120" />
            </label>

            <label class="field">
              <span>Ссылка кнопки</span>
              <input v-model.trim="item.button_url" type="text" maxlength="500" />
            </label>
          </div>

          <div class="actions-row actions-row-end">
            <button class="btn secondary" :disabled="!!busyById[item.id]" @click="toggleVisibility(item)">
              <Fa :icon="['fas', item.is_active ? 'eye-slash' : 'eye']" />
              {{ busyById[item.id] === 'toggle' ? 'Сохранение...' : (item.is_active ? 'Скрыть' : 'Открыть') }}
            </button>

            <button class="btn primary" :disabled="!!busyById[item.id]" @click="updateItem(item)">
              <Fa :icon="['fas','floppy-disk']" />
              {{ busyById[item.id] === 'save' ? 'Сохранение...' : 'Сохранить' }}
            </button>

            <button class="btn danger" :disabled="!!busyById[item.id]" @click="deleteItem(item)">
              <Fa :icon="['fas','trash']" />
              {{ busyById[item.id] === 'delete' ? 'Удаление...' : 'Удалить' }}
            </button>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import Swal from "sweetalert2";

const API_GET = "/api/admin/home_showcase/get_showcase.php";
const API_SAVE_SETTINGS = "/api/admin/home_showcase/save_settings.php";
const API_CREATE = "/api/admin/home_showcase/create_item.php";
const API_UPDATE = "/api/admin/home_showcase/update_item.php";
const API_DELETE = "/api/admin/home_showcase/delete_item.php";
const API_TOGGLE = "/api/admin/home_showcase/toggle_visibility.php";

const loading = ref(false);
const error = ref("");
const savingTitle = ref(false);
const creating = ref(false);
const blockTitle = ref("");
const items = ref([]);
const busyById = ref({});
const newImageFile = ref(null);

const newItem = ref(getEmptyItem());

function getEmptyItem() {
  return {
    title: "",
    description: "",
    price: "",
    button_text: "",
    button_url: "",
  };
}

function normalizeItem(item) {
  return {
    id: Number(item?.id || 0),
    title: String(item?.title || ""),
    description: String(item?.description || ""),
    price: String(item?.price || ""),
    image_url: String(item?.image_url || ""),
    button_text: String(item?.button_text || ""),
    button_url: String(item?.button_url || ""),
    is_active: Number(item?.is_active ?? 1) === 1,
    sort_order: Number(item?.sort_order || 0),
    _newImageFile: null,
  };
}

function validateFile(file) {
  if (!file) return "Выберите фото";
  const allowed = new Set(["image/jpeg", "image/png", "image/webp"]);
  if (!allowed.has(file.type)) return "Формат только JPG / PNG / WEBP";
  if (file.size > 8 * 1024 * 1024) return "Файл больше 8 МБ";
  return "";
}

function validateItemPayload(item, requireImage = false, imageFile = null) {
  if (!String(item?.title || "").trim()) return "Заполните название";
  if (!String(item?.button_text || "").trim()) return "Заполните текст кнопки";
  if (!String(item?.button_url || "").trim()) return "Заполните ссылку кнопки";
  if (requireImage) {
    const fileError = validateFile(imageFile);
    if (fileError) return fileError;
  }
  if (imageFile) {
    const fileError = validateFile(imageFile);
    if (fileError) return fileError;
  }
  return "";
}

async function fetchData() {
  loading.value = true;
  error.value = "";

  try {
    const res = await fetch(API_GET, {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || "Не удалось загрузить данные");

    blockTitle.value = String(data?.settings?.block_title || "");
    items.value = Array.isArray(data?.items) ? data.items.map(normalizeItem) : [];
  } catch (e) {
    error.value = e?.message || "Не удалось загрузить данные";
  } finally {
    loading.value = false;
  }
}

function onNewImageChange(event) {
  newImageFile.value = event.target.files?.[0] || null;
}

function onExistingImageChange(item, event) {
  item._newImageFile = event.target.files?.[0] || null;
}

async function saveTitle() {
  if (!blockTitle.value.trim()) {
    error.value = "Заполните заголовок блока";
    return;
  }

  savingTitle.value = true;
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("block_title", blockTitle.value.trim());

    const res = await fetch(API_SAVE_SETTINGS, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || "Не удалось сохранить заголовок");

    blockTitle.value = String(data?.settings?.block_title || blockTitle.value);

    await Swal.fire({
      icon: "success",
      title: "Готово",
      text: "Заголовок сохранен",
      timer: 1200,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Не удалось сохранить заголовок";
  } finally {
    savingTitle.value = false;
  }
}

async function createItem() {
  const validationError = validateItemPayload(newItem.value, true, newImageFile.value);
  if (validationError) {
    error.value = validationError;
    return;
  }

  creating.value = true;
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("title", newItem.value.title.trim());
    fd.append("description", newItem.value.description.trim());
    fd.append("price", newItem.value.price.trim());
    fd.append("button_text", newItem.value.button_text.trim());
    fd.append("button_url", newItem.value.button_url.trim());
    fd.append("image", newImageFile.value);

    const res = await fetch(API_CREATE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || "Не удалось добавить карточку");

    if (data?.item) items.value.push(normalizeItem(data.item));
    newItem.value = getEmptyItem();
    newImageFile.value = null;

    await Swal.fire({
      icon: "success",
      title: "Готово",
      text: "Карточка добавлена",
      timer: 1200,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Не удалось добавить карточку";
  } finally {
    creating.value = false;
  }
}

async function updateItem(item) {
  const validationError = validateItemPayload(item, false, item._newImageFile || null);
  if (validationError) {
    error.value = validationError;
    return;
  }

  busyById.value = { ...busyById.value, [item.id]: "save" };
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("id", String(item.id));
    fd.append("title", item.title.trim());
    fd.append("description", item.description.trim());
    fd.append("price", item.price.trim());
    fd.append("button_text", item.button_text.trim());
    fd.append("button_url", item.button_url.trim());
    if (item._newImageFile) fd.append("image", item._newImageFile);

    const res = await fetch(API_UPDATE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || "Не удалось сохранить карточку");

    items.value = items.value.map((row) => row.id === item.id ? normalizeItem(data.item) : row);

    await Swal.fire({
      icon: "success",
      title: "Готово",
      text: "Карточка сохранена",
      timer: 1200,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Не удалось сохранить карточку";
  } finally {
    busyById.value = { ...busyById.value, [item.id]: "" };
  }
}

async function toggleVisibility(item) {
  busyById.value = { ...busyById.value, [item.id]: "toggle" };
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("id", String(item.id));
    fd.append("is_active", item.is_active ? "0" : "1");

    const res = await fetch(API_TOGGLE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || "Не удалось изменить видимость карточки");

    items.value = items.value.map((row) => row.id === item.id ? normalizeItem(data.item) : row);
  } catch (e) {
    error.value = e?.message || "Не удалось изменить видимость карточки";
  } finally {
    busyById.value = { ...busyById.value, [item.id]: "" };
  }
}

async function deleteItem(item) {
  const result = await Swal.fire({
    icon: "warning",
    title: "Удалить карточку?",
    text: item.title,
    showCancelButton: true,
    confirmButtonText: "Удалить",
    cancelButtonText: "Отмена",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;

  busyById.value = { ...busyById.value, [item.id]: "delete" };
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("id", String(item.id));

    const res = await fetch(API_DELETE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || "Не удалось удалить карточку");

    items.value = items.value.filter((row) => row.id !== item.id);

    await Swal.fire({
      icon: "success",
      title: "Удалено",
      text: "Карточка удалена",
      timer: 1200,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Не удалось удалить карточку";
  } finally {
    busyById.value = { ...busyById.value, [item.id]: "" };
  }
}

onMounted(fetchData);
</script>

<style scoped>
.page {
  min-height: 100dvh;
  padding: clamp(14px, 3vw, 28px);
  background: var(--bg-main);
  color: var(--text-main);
}

.card {
  max-width: 1200px;
  margin: 0 auto 14px;
  padding: 16px;
  border-radius: 18px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.06);
}

.head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.title {
  margin: 0;
  font-size: clamp(22px, 2.2vw, 30px);
}

.sub {
  margin-top: 6px;
  color: var(--text-muted);
  line-height: 1.45;
  max-width: 880px;
}

.section {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.section-head {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-head h2 {
  margin: 0;
  font-size: 18px;
}

.section-head-between {
  justify-content: space-between;
}

.count {
  min-width: 34px;
  height: 34px;
  padding: 0 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: var(--bg-main);
  border: 1px solid var(--border-soft);
  font-weight: 900;
}

.notice {
  max-width: 1200px;
  margin: 0 auto 14px;
  padding: 12px 14px;
  border-radius: 14px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
}

.notice.error {
  color: #991b1b;
  background: #fef2f2;
  border-color: #fecaca;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.form-grid.single {
  grid-template-columns: minmax(0, 1fr);
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field span {
  font-size: 13px;
  font-weight: 800;
  color: var(--text-muted);
}

.field input,
.field textarea {
  width: 100%;
  border: 1px solid var(--border-soft);
  background: var(--bg-main);
  color: var(--text-main);
  border-radius: 14px;
  padding: 11px 12px;
  outline: none;
}

.field input:focus,
.field textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.field textarea {
  resize: vertical;
  min-height: 96px;
}

.field-span-2 {
  grid-column: span 2;
}

.actions-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.actions-row-end {
  justify-content: flex-end;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 42px;
  padding: 0 14px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  color: #111827;
  font-weight: 800;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.65;
  cursor: default;
}

.btn.primary {
  background: #111827;
  color: #fff;
  border-color: #111827;
}

.btn.secondary {
  background: #eff6ff;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.btn.ghost {
  background: var(--bg-main);
}

.btn.danger {
  background: #fff1f2;
  color: #b91c1c;
  border-color: #fecdd3;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 14px;
}

.item-card {
  border: 1px solid var(--border-soft);
  border-radius: 18px;
  background: var(--bg-main);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.item-card.hiddenCard {
  opacity: 0.82;
  border-color: #cbd5e1;
}

.card-top {
  display: flex;
  justify-content: flex-end;
}

.status-pill {
  min-height: 30px;
  padding: 0 12px;
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
}

.status-pill.isVisible {
  background: #dcfce7;
  color: #166534;
}

.status-pill.isHidden {
  background: #f1f5f9;
  color: #475569;
}

.preview-box {
  width: 100%;
  aspect-ratio: 1.4 / 1;
  border-radius: 16px;
  overflow: hidden;
  background: #e5e7eb;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.empty {
  padding: 14px;
  border-radius: 14px;
  background: var(--bg-main);
  border: 1px dashed var(--border-soft);
  color: var(--text-muted);
  font-weight: 800;
}

@media (max-width: 860px) {
  .head {
    flex-direction: column;
  }

  .form-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .field-span-2 {
    grid-column: span 1;
  }

  .actions-row-end {
    justify-content: stretch;
  }

  .actions-row-end .btn {
    flex: 1 1 180px;
    justify-content: center;
  }
}
</style>
