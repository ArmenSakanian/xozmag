<template>
  <div class="admin-page">
    <!-- ===== HEADER ===== -->
    <div class="page-head">
      <div class="page-head-left">
        <h1 class="page-title">Категории</h1>
        <div class="page-subtitle">
          Одинаковые названия разрешены только в разных ветках - у разных родителей.
        </div>
      </div>

      <div class="page-head-right">
        <button class="btn soft" @click="loadCategories" :disabled="loading">
          <Fa :icon="['fas','rotate-right']" />
          Обновить
        </button>
      </div>
    </div>

    <div class="grid">
      <!-- ===== CREATE ===== -->
      <div class="card">
        <div class="card-head">
          <h2 class="card-title">Создать категорию</h2>
          <span class="pill" v-if="categories.length">{{ categories.length }}</span>
        </div>

        <div class="form">
          <label class="label">Название</label>
          <input
            v-model="newName"
            class="input"
            placeholder="Например: Эмаль"
            @keyup.enter="createCategory"
          />

          <label class="label">Родитель</label>
          <select v-model="newParent" class="select">
            <option :value="null">Без родителя (корень)</option>
            <option v-for="c in treeOrdered" :key="c.id" :value="c.id">
              {{ c.code }} - {{ c.name }}
            </option>
          </select>

          <div v-if="isRootCreation" class="photo-field">
            <div class="photo-field-head">
              <label class="label" for="root-category-photo">Фото для плитки</label>
              <span class="photo-hint">Только для категории первого уровня</span>
            </div>

            <input
              id="root-category-photo"
              ref="rootPhotoInput"
              type="file"
              class="hidden-file-input"
              accept="image/jpeg,image/png,image/webp"
              @change="onRootPhotoPick"
            />

            <div class="photo-picker" :class="{ filled: !!rootPhotoPreviewUrl }">
              <div v-if="rootPhotoPreviewUrl" class="photo-preview-wrap">
                <img :src="rootPhotoPreviewUrl" alt="Предпросмотр фото категории" class="photo-preview" />
              </div>
              <div v-else class="photo-placeholder" aria-hidden="true">
                <Fa :icon="['far','image']" />
              </div>

              <div class="photo-meta">
                <div class="photo-title">
                  {{ rootPhotoFile?.name || 'Фотография не выбрана' }}
                </div>
                <div class="photo-subtitle">
                  JPG, PNG, WEBP - до 8 МБ. Фото будет использовано в плитках на главной и в каталоге.
                </div>
              </div>

              <div class="photo-actions">
                <button type="button" class="btn soft" @click="pickRootPhoto" :disabled="creating">
                  <Fa :icon="['fas', rootPhotoFile ? 'arrows-rotate' : 'upload']" />
                  {{ rootPhotoFile ? 'Заменить фото' : 'Выбрать фото' }}
                </button>

                <button
                  v-if="rootPhotoFile"
                  type="button"
                  class="btn ghost"
                  @click="clearRootPhoto"
                  :disabled="creating"
                >
                  <Fa :icon="['fas','xmark']" />
                  Убрать
                </button>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button
              class="btn primary"
              @click="createCategory"
              :disabled="creating || !newName.trim()"
            >
              <Fa :icon="['fas','plus']" />
              {{ creating ? 'Создание...' : 'Создать' }}
            </button>

            <button class="btn ghost" @click="resetForm" :disabled="creating">
              Сброс
            </button>
          </div>

          <div v-if="formError" class="notice error">{{ formError }}</div>
          <div v-if="formOk" class="notice ok">{{ formOk }}</div>
        </div>
      </div>

      <!-- ===== LIST ===== -->
      <div class="card list-card">
        <div class="card-head list-head">
          <div class="list-head-left">
            <h2 class="card-title">Существующие категории</h2>
            <div class="muted" v-if="!loading && treeOrdered.length">
              {{ treeOrdered.length }} строк - у корневых категорий можно сразу добавить или заменить фото
            </div>
          </div>

          <div class="list-head-right">
            <input v-model="q" class="input small" placeholder="Поиск..." />

            <button class="btn soft" @click="collapseAll" :disabled="loading">
              Свернуть
            </button>
            <button class="btn soft" @click="expandAll" :disabled="loading">
              Развернуть
            </button>
          </div>
        </div>

        <input
          ref="rowRootPhotoInput"
          type="file"
          class="hidden-file-input"
          accept="image/jpeg,image/png,image/webp"
          @change="onRowRootPhotoPick"
        />

        <div v-if="loading" class="empty">Загрузка...</div>
        <div v-else-if="filtered.length === 0" class="empty">
          Ничего не найдено
        </div>

        <div v-else class="tree">
          <div
            v-for="c in filtered"
            :key="c.id"
            class="row"
            :class="{ root: c.level === 1 }"
          >
            <div
              class="row-main"
              :class="{ 'row-main-root': c.level === 1 }"
              :style="{ paddingLeft: (c.level - 1) * 22 + 'px' }"
            >
              <button
                v-if="c.hasChildren"
                class="twisty"
                @click.stop="toggle(c.id)"
                :title="opened[c.id] === false ? 'Развернуть' : 'Свернуть'"
              >
                <Fa
                  :class="{ open: opened[c.id] !== false }"
                  :icon="['fas','chevron-right']"
                />
              </button>
              <span v-else class="twisty placeholder" aria-hidden="true"></span>

              <div
                v-if="c.level === 1"
                class="root-photo-panel"
                :class="{ missing: !hasRootPhoto(c) }"
              >
                <div
                  class="root-photo-box compact"
                  :title="hasRootPhoto(c) ? 'Фото добавлено' : 'Фото отсутствует'"
                >
                  <img
                    v-if="hasRootPhoto(c)"
                    :src="c.photo_url"
                    :alt="c.name"
                    class="root-photo"
                    loading="lazy"
                    decoding="async"
                    @error="markRootPhotoBroken(c.id)"
                  />
                  <div v-else class="root-photo-placeholder" aria-hidden="true">
                    <Fa :icon="['far','image']" />
                  </div>
                </div>

                <div class="root-photo-panel-meta">
                  <span
                    class="photo-note"
                    :class="hasRootPhoto(c) ? 'ok' : 'missing'"
                  >
                    {{ hasRootPhoto(c) ? 'Фото добавлено' : 'Фото отсутствует' }}
                  </span>

                  <button
                    type="button"
                    class="btn soft btn-inline"
                    @click="openRowRootPhotoPicker(c.id)"
                    :disabled="!!rootPhotoUpdating[c.id]"
                  >
                    <Fa :icon="['fas', hasRootPhoto(c) ? 'arrows-rotate' : 'upload']" />
                    {{ rootPhotoUpdating[c.id] ? 'Сохранение...' : hasRootPhoto(c) ? 'Заменить фото' : 'Добавить фото' }}
                  </button>
                </div>
              </div>

              <div class="row-text">
                <div class="row-topline compact">
                  <span class="code">{{ c.code }}</span>
                  <span class="name" :title="c.name">{{ c.name }}</span>
                </div>
              </div>
            </div>

            <div class="row-actions">
              <select v-model="moveTo[c.id]" class="select small">
                <option :value="null">Корень</option>
                <option
                  v-for="p in treeOrdered"
                  :key="p.id"
                  :value="p.id"
                  :disabled="p.id === c.id"
                >
                  {{ p.code }} - {{ p.name }}
                </option>
              </select>

              <button
                class="btn ghost"
                @click="changeParent(c.id)"
                :disabled="!!moving[c.id]"
                title="Переместить категорию"
              >
                <Fa :icon="['fas','arrow-right-arrow-left']" />
                Перенести
              </button>

              <button
                class="btn danger"
                @click="deleteCategory(c.id, c.name)"
                :disabled="!!deleting[c.id]"
                title="Удалить категорию"
              >
                <Fa :icon="['fas','trash']" />
                Удалить
              </button>
            </div>
          </div>
        </div>

        <div v-if="listError" class="notice error" style="margin-top: 12px">
          {{ listError }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, onBeforeUnmount } from "vue";

const categories = ref([]);
const treeOrdered = ref([]);
const opened = ref({});
const moveTo = ref({});

const newName = ref("");
const newParent = ref(null);
const rootPhotoInput = ref(null);
const rootPhotoFile = ref(null);
const rootPhotoPreviewUrl = ref("");
const rowRootPhotoInput = ref(null);
const activeRowRootPhotoCategoryId = ref(null);
const rootPhotoBroken = ref({});
const rootPhotoUpdating = ref({});

const q = ref("");

const loading = ref(false);
const creating = ref(false);
const moving = ref({});
const deleting = ref({});

const formError = ref("");
const formOk = ref("");
const listError = ref("");

const MAX_PHOTO_SIZE = 8 * 1024 * 1024;
const ALLOWED_PHOTO_TYPES = new Set(["image/jpeg", "image/png", "image/webp"]);
const API_UPLOAD_ROOT_PHOTO = "/api/admin/categories/update_root_category_photo.php";
const API_GET_ROOT_PHOTOS = "/api/admin/categories/get_root_category_photos.php";

const isRootCreation = computed(() => normalizeParent(newParent.value) === null);

const byId = computed(() => {
  const m = {};
  categories.value.forEach((c) => (m[c.id] = c));
  return m;
});

const filtered = computed(() => {
  const query = q.value.trim().toLowerCase();
  if (!query) return treeOrdered.value;

  return treeOrdered.value.filter((c) => {
    const name = String(c.name || "").toLowerCase();
    const code = String(c.code || "").toLowerCase();
    return name.includes(query) || code.includes(query);
  });
});

function normalizeParent(v) {
  return v === "" || v === undefined ? null : v;
}

function mergeRootPhotoData(flat, items) {
  const photoById = {};
  (Array.isArray(items) ? items : []).forEach((item) => {
    photoById[Number(item.id)] = item;
  });

  return (Array.isArray(flat) ? flat : []).map((item) => {
    const photoItem = photoById[Number(item.id)] || null;
    return {
      ...item,
      photo_categories: photoItem?.photo_categories || null,
      photo_url: photoItem?.photo_url || null,
    };
  });
}

function hasRootPhoto(category) {
  return Number(category?.level) === 1 && !!category?.photo_url && !rootPhotoBroken.value[category.id];
}

function markRootPhotoBroken(id) {
  rootPhotoBroken.value = {
    ...rootPhotoBroken.value,
    [id]: true,
  };
}

function getRootPhotoValidationError(file) {
  if (!file) return "";
  if (!ALLOWED_PHOTO_TYPES.has(file.type)) {
    return "Формат фото только JPG / PNG / WEBP";
  }
  if (file.size > MAX_PHOTO_SIZE) {
    return "Фото больше 8 МБ";
  }
  return "";
}

function revokeRootPhotoPreview() {
  if (rootPhotoPreviewUrl.value) {
    URL.revokeObjectURL(rootPhotoPreviewUrl.value);
    rootPhotoPreviewUrl.value = "";
  }
}

function clearRootPhoto() {
  rootPhotoFile.value = null;
  revokeRootPhotoPreview();
  if (rootPhotoInput.value) rootPhotoInput.value.value = "";
}

function pickRootPhoto() {
  rootPhotoInput.value?.click();
}

function onRootPhotoPick(event) {
  const file = event?.target?.files?.[0] || null;
  if (event?.target) event.target.value = "";
  if (!file) return;

  const error = getRootPhotoValidationError(file);
  if (error) {
    formError.value = error;
    return;
  }

  formError.value = "";
  rootPhotoFile.value = file;
  revokeRootPhotoPreview();
  rootPhotoPreviewUrl.value = URL.createObjectURL(file);
}

function openRowRootPhotoPicker(categoryId) {
  activeRowRootPhotoCategoryId.value = Number(categoryId);
  listError.value = "";
  if (rowRootPhotoInput.value) {
    rowRootPhotoInput.value.value = "";
  }
  rowRootPhotoInput.value?.click();
}

async function onRowRootPhotoPick(event) {
  const categoryId = Number(activeRowRootPhotoCategoryId.value || 0);
  const file = event?.target?.files?.[0] || null;
  if (event?.target) event.target.value = "";
  activeRowRootPhotoCategoryId.value = null;

  if (!categoryId || !file) return;

  const error = getRootPhotoValidationError(file);
  if (error) {
    listError.value = error;
    return;
  }

  rootPhotoUpdating.value = {
    ...rootPhotoUpdating.value,
    [categoryId]: true,
  };
  listError.value = "";

  try {
    const data = await uploadRootCategoryPhoto(categoryId, file);
    const item = data?.item || null;

    if (!item) {
      await loadCategories();
      return;
    }

    categories.value = categories.value.map((category) =>
      Number(category.id) === categoryId
        ? {
            ...category,
            ...item,
          }
        : category
    );

    rootPhotoBroken.value = {
      ...rootPhotoBroken.value,
      [categoryId]: false,
    };
    treeOrdered.value = buildTreeOrder(categories.value);
  } catch (e) {
    listError.value = e?.message || "Не удалось сохранить фото";
  } finally {
    rootPhotoUpdating.value = {
      ...rootPhotoUpdating.value,
      [categoryId]: false,
    };
  }
}

async function uploadRootCategoryPhoto(categoryId, file) {
  const fd = new FormData();
  fd.append("id", String(categoryId));
  fd.append("photo", file);

  const r = await fetch(API_UPLOAD_ROOT_PHOTO, {
    method: "POST",
    body: fd,
    credentials: "include",
  });
  const data = await r.json().catch(() => ({}));
  if (!r.ok || !data?.ok) {
    throw new Error(data?.error || "Категория создана, но фото не удалось сохранить");
  }
  return data;
}

async function apiGet(url) {
  const r = await fetch(url);
  const data = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error("Ошибка загрузки");
  if (data?.error) throw new Error(data.error);
  return data;
}

async function apiPost(url, body) {
  const r = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  const data = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error("Ошибка запроса");
  if (data?.error) throw new Error(data.error);
  return data;
}

async function loadCategories() {
  loading.value = true;
  listError.value = "";
  formOk.value = "";
  try {
    const flat = await apiGet("/api/admin/categories/get_categories.php");

    let rootPhotoItems = [];
    try {
      const rootPhotos = await apiGet(API_GET_ROOT_PHOTOS);
      rootPhotoItems = Array.isArray(rootPhotos?.items) ? rootPhotos.items : [];
    } catch {
      rootPhotoItems = [];
    }

    const merged = mergeRootPhotoData(flat, rootPhotoItems);
    categories.value = merged;
    rootPhotoBroken.value = {};

    const nextMove = {};
    merged.forEach((c) => (nextMove[c.id] = c.parent_id ?? null));
    moveTo.value = nextMove;

    treeOrdered.value = buildTreeOrder(merged);
  } catch (e) {
    listError.value = e?.message || "Ошибка";
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  newName.value = "";
  newParent.value = null;
  clearRootPhoto();
  formError.value = "";
  formOk.value = "";
}

async function createCategory() {
  const name = newName.value.trim();
  if (!name) return;

  creating.value = true;
  formError.value = "";
  formOk.value = "";

  try {
    const isRoot = normalizeParent(newParent.value) === null;
    const pickedPhoto = isRoot ? rootPhotoFile.value : null;

    const created = await apiPost("/api/admin/categories/create_category.php", {
      name,
      parent_id: normalizeParent(newParent.value),
    });

    if (isRoot && pickedPhoto) {
      await uploadRootCategoryPhoto(created?.id, pickedPhoto);
      formOk.value = "Категория создана и фото сохранено";
    } else {
      formOk.value = "Категория создана";
    }

    newName.value = "";
    newParent.value = null;
    clearRootPhoto();
    await loadCategories();
  } catch (e) {
    formError.value = e?.message || "Ошибка";
  } finally {
    creating.value = false;
  }
}

async function changeParent(id) {
  listError.value = "";
  const current = byId.value[id]?.parent_id ?? null;
  const target = normalizeParent(moveTo.value[id]);

  if (target === current) return;

  moving.value[id] = true;
  try {
    await apiPost("/api/admin/categories/change_parent.php", {
      id,
      parent_id: target,
    });
    await loadCategories();
  } catch (e) {
    listError.value = e?.message || "Ошибка";
  } finally {
    moving.value[id] = false;
  }
}

async function deleteCategory(id, name) {
  listError.value = "";
  if (!confirm(`Удалить категорию «${name}»?`)) return;

  deleting.value[id] = true;
  try {
    await apiPost("/api/admin/categories/delete_category.php", { id });
    await loadCategories();
  } catch (e) {
    listError.value = e?.message || "Ошибка";
  } finally {
    deleting.value[id] = false;
  }
}

function buildTreeOrder(list) {
  const map = {};
  const roots = [];

  list.forEach((c) => {
    map[c.id] = { ...c, children: [], hasChildren: false };
  });

  list.forEach((c) => {
    if (c.parent_id !== null && c.parent_id !== undefined) {
      if (map[c.parent_id]) {
        map[c.parent_id].children.push(map[c.id]);
        map[c.parent_id].hasChildren = true;
      } else {
        roots.push(map[c.id]);
      }
    } else {
      roots.push(map[c.id]);
    }
  });

  const result = [];

  function walk(node) {
    result.push(node);
    if (opened.value[node.id] === false) return;

    node.children.sort((a, b) => a.sort - b.sort).forEach(walk);
  }

  roots.sort((a, b) => a.sort - b.sort).forEach(walk);

  return result;
}

function toggle(id) {
  opened.value[id] = opened.value[id] === false;
  treeOrdered.value = buildTreeOrder(categories.value);
}

function collapseAll() {
  const o = {};
  categories.value.forEach((c) => (o[c.id] = false));
  opened.value = o;
  treeOrdered.value = buildTreeOrder(categories.value);
}

function expandAll() {
  opened.value = {};
  treeOrdered.value = buildTreeOrder(categories.value);
}

watch(newParent, (value) => {
  if (normalizeParent(value) !== null) {
    clearRootPhoto();
  }
});

onMounted(loadCategories);
onBeforeUnmount(revokeRootPhotoPreview);
</script>

<style scoped>
.admin-page{
  max-width: 1440px;
  margin: 0 auto;
  padding: clamp(14px, 2.4vw, 24px);
  color: var(--text-main);
}

.page-head{
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 14px;
}

.page-title{
  margin: 0;
  font-size: clamp(20px, 2.2vw, 28px);
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

.page-subtitle{
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.35;
  max-width: 760px;
}

.grid{
  display: grid;
  grid-template-columns: minmax(340px, 380px) minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}
@media (max-width: 1120px){
  .grid{ grid-template-columns: 1fr; }
}

.card{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 14px;
}

.list-card{
  min-width: 0;
}

.card-head{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.card-title{
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  color: var(--text-main);
}

.pill{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 24px;
  padding: 0 10px;
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--accent) 12%, var(--bg-panel));
  color: var(--accent);
  border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border-soft));
  font-weight: 900;
  font-size: 12px;
}

.list-head{
  align-items: flex-start;
  gap: 12px;
}

.list-head-left{
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.muted{
  font-size: 12px;
  color: var(--text-light);
  line-height: 1.45;
}

.list-head-right{
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.form{
  display: grid;
  gap: 10px;
}

.label{
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 900;
}

.input,
.select{
  width: 100%;
  padding: 10px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.input::placeholder{
  color: var(--text-light);
}

.input.small{
  padding: 9px 10px;
  border-radius: var(--radius-md);
  width: 240px;
  background: var(--bg-soft);
}

.select{
  background: var(--bg-panel);
}

.select.small{
  padding: 8px 10px;
  border-radius: var(--radius-md);
  min-width: 240px;
  background: var(--bg-soft);
}

.input:focus,
.select:focus{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
  background: var(--bg-panel);
}

.photo-field{
  display: grid;
  gap: 8px;
}

.photo-field-head{
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
}

.photo-hint{
  font-size: 11px;
  line-height: 1.3;
  color: var(--text-light);
  text-align: right;
}

.hidden-file-input{
  display: none;
}

.photo-picker{
  display: grid;
  grid-template-columns: 92px 1fr;
  gap: 12px;
  padding: 12px;
  border-radius: var(--radius-md);
  border: 1px dashed color-mix(in srgb, var(--accent) 22%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 4%, var(--bg-panel));
}

.photo-picker.filled{
  border-style: solid;
}

.photo-preview-wrap,
.photo-placeholder{
  width: 92px;
  aspect-ratio: 1;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo-placeholder{
  color: var(--text-light);
  font-size: 24px;
}

.photo-preview{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.photo-meta{
  min-width: 0;
  display: grid;
  gap: 6px;
  align-content: start;
}

.photo-title{
  font-weight: 900;
  color: var(--text-main);
  word-break: break-word;
}

.photo-subtitle{
  font-size: 12px;
  line-height: 1.45;
  color: var(--text-light);
}

.photo-actions{
  grid-column: 1 / -1;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.form-actions{
  display: flex;
  gap: 10px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.btn{
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
  user-select: none;
  white-space: nowrap;
}

.btn-inline{
  min-height: 36px;
  padding: 8px 10px;
  font-size: 12px;
}

.btn:disabled{
  opacity: 0.55;
  cursor: not-allowed;
}

.btn.primary{
  background: var(--accent);
  color: #fff;
  box-shadow: var(--shadow-sm);
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
}
.btn.primary:hover:not(:disabled){
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  filter: brightness(1.03);
}

.btn.soft{
  background: var(--bg-soft);
  color: var(--text-main);
  border-color: var(--border-soft);
}
.btn.soft:hover:not(:disabled),
.btn.ghost:hover:not(:disabled){
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
}

.btn.ghost{
  background: transparent;
  color: var(--text-main);
  border: 1px solid var(--border-soft);
}

.btn.danger{
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
  color: var(--accent-danger);
  border: 1px solid color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
}
.btn.danger:hover:not(:disabled){
  background: color-mix(in srgb, var(--accent-danger) 14%, var(--bg-panel));
}

.btn:focus-visible{
  outline: none;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
}

.notice{
  margin-top: 8px;
  border-radius: var(--radius-md);
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  border: 1px solid var(--border-soft);
}

.notice.error{
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-danger) 25%, var(--border-soft));
  color: var(--accent-danger);
}

.notice.ok{
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-2) 22%, var(--border-soft));
  color: var(--accent-2);
}

.empty{
  padding: 16px;
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
  color: var(--text-muted);
  font-weight: 900;
  border: 1px solid var(--border-soft);
}

.tree{
  display: grid;
  gap: 10px;
}

.row{
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 12px;
  padding: 14px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease, background .15s ease;
}

.row:hover{
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
  box-shadow: var(--shadow-sm);
}

.row.root{
  background: linear-gradient(
    90deg,
    color-mix(in srgb, var(--accent) 6%, var(--bg-panel)),
    var(--bg-panel)
  );
}

.row-main{
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-width: 0;
  flex: 1 1 auto;
}

.row-main-root{
  align-items: center;
}

.row-text{
  min-width: 0;
  display: grid;
  gap: 4px;
  flex: 1 1 auto;
  align-content: center;
}

.row-topline{
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex-wrap: wrap;
}

.row-topline.compact{
  flex-wrap: wrap;
}

.root-photo-panel{
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: 12px;
  border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 4%, var(--bg-panel));
  flex: 0 0 auto;
}

.root-photo-panel.missing{
  border-color: color-mix(in srgb, var(--accent-danger) 34%, var(--border-soft));
  background: color-mix(in srgb, var(--accent-danger) 7%, var(--bg-panel));
}

.root-photo-panel-meta{
  display: grid;
  gap: 8px;
  min-width: 0;
}

.root-photo-box{
  width: 112px;
  height: 72px;
  border-radius: 14px;
  overflow: hidden;
  background: var(--bg-soft);
  border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border-soft));
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-sm);
}

.root-photo-box.compact{
  width: 84px;
  height: 56px;
  border-radius: 10px;
  box-shadow: none;
}

.root-photo,
.root-photo-placeholder{
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.root-photo{
  object-fit: cover;
}

.root-photo-placeholder{
  color: color-mix(in srgb, var(--accent-danger) 72%, var(--text-light));
  font-size: 24px;
}

.photo-note{
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  font-size: 12px;
  font-weight: 800;
  line-height: 1.35;
}

.photo-note.ok{
  color: var(--accent-2);
}

.photo-note.missing{
  color: var(--accent-danger);
}

.twisty{
  width: 30px;
  height: 30px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex: 0 0 auto;
  transition: background .15s ease, border-color .15s ease;
}

.twisty:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
}

.twisty.placeholder{
  border-color: transparent;
  background: transparent;
}

.twisty svg {
  transition: transform .15s ease;
  color: var(--text-main);
  font-size: 13px;
}
.twisty svg.open{
  transform: rotate(90deg);
}

.code{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
  font-weight: 900;
  color: var(--text-main);
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  padding: 4px 8px;
  border-radius: var(--radius-lg);
  white-space: nowrap;
}

.name{
  font-weight: 900;
  color: var(--text-main);
  overflow: visible;
  text-overflow: unset;
  white-space: normal;
  line-height: 1.35;
  min-width: 0;
  flex: 1 1 auto;
}

.row-actions{
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
  flex-wrap: wrap;
  justify-content: flex-end;
  max-width: 100%;
}

@media (max-width: 1120px){
  .page-head-right{ width: 100%; }
}

@media (max-width: 920px){
  .page-head{
    flex-direction: column;
    align-items: flex-start;
  }

  .page-subtitle{ max-width: 100%; }

  .list-head-right{
    width: 100%;
    justify-content: flex-start;
  }

  .input.small{ width: 100%; }

  .row{
    grid-template-columns: 1fr;
    align-items: stretch;
    padding: 12px;
  }

  .row-main{ width: 100%; }

  .row-main-root{
    align-items: flex-start;
  }

  .row-actions{
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 10px;
  }

  .row-actions .select.small,
  .row-actions .btn{
    flex: 1 1 220px;
    min-width: 0;
  }

  .select.small{
    min-width: 0;
    width: 100%;
  }

  .root-photo-panel{
    flex-wrap: wrap;
  }
}

@media (max-width: 600px){
  .admin-page{ padding: 12px; }

  .card{
    padding: 12px;
    border-radius: var(--radius-lg);
  }

  .page-head-right .btn,
  .form-actions .btn,
  .photo-actions .btn{
    width: 100%;
    justify-content: center;
  }

  .photo-picker{
    grid-template-columns: 1fr;
  }

  .photo-preview-wrap,
  .photo-placeholder{
    width: 100%;
    aspect-ratio: 16 / 9;
  }

  .row-actions{ gap: 8px; }

  .row-main-root{
    flex-wrap: wrap;
  }

  .root-photo-panel{
    width: 100%;
  }

  .root-photo-box.compact{
    width: 76px;
    height: 52px;
    aspect-ratio: auto;
  }

  .row-actions .select.small,
  .row-actions .btn,
  .root-photo-panel-meta .btn-inline{
    flex: 1 1 100%;
    width: 100%;
  }

  .input,
  .select,
  .btn{
    min-height: 44px;
  }

  .twisty{
    width: 28px;
    height: 28px;
  }
}

@media (prefers-reduced-motion: reduce){
  .row,
  .btn,
  .twisty,
  .input,
  .select{
    transition: none !important;
  }
}
</style>
