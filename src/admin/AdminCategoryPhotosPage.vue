<template>
  <div class="page">
    <div class="head card">
      <div>
        <h1 class="title">Фото категорий</h1>
        <div class="sub">
          Только категории первого уровня. Фото сохраняются в /photo_categories_vitrina/ и сразу используются на главной странице.
        </div>
      </div>

      <div class="head-actions">
        <input
          ref="fileInput"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          class="hidden-input"
          @change="onPickFile"
        />

        <button class="btn ghost" :disabled="loading" @click="fetchCategories">
          <Fa :icon="['fas','rotate-right']" />
          Обновить
        </button>
      </div>
    </div>

    <div class="stats card">
      <div class="stat">
        <span class="stat-label">Категорий</span>
        <b>{{ items.length }}</b>
      </div>
      <div class="stat">
        <span class="stat-label">С фото</span>
        <b>{{ withPhotoCount }}</b>
      </div>
      <div class="stat">
        <span class="stat-label">Без фото</span>
        <b>{{ withoutPhotoCount }}</b>
      </div>
    </div>

    <div v-if="error" class="notice error">{{ error }}</div>
    <div v-if="loading" class="notice">Загрузка категорий...</div>

    <div v-else class="grid">
      <div v-if="items.length === 0" class="empty card">
        Категории первого уровня не найдены.
      </div>

      <article v-for="item in items" :key="item.id" class="cat card">
        <div class="photo-box">
          <img
            v-if="item.photo_url && !imgError[item.id]"
            :src="item.photo_url"
            :alt="item.name"
            loading="lazy"
            decoding="async"
            @error="setImgError(item.id, true)"
          />
          <div v-else class="photo-placeholder" aria-hidden="true">
            <Fa :icon="['far','image']" />
          </div>
        </div>

        <div class="body">
          <div class="name">{{ item.name }}</div>
          <div class="meta">
            <span>#{{ item.id }}</span>
            <span>{{ item.code }}</span>
            <span v-if="item.slug">/{{ item.slug }}</span>
          </div>
        </div>

        <div class="actions">
          <button
            class="btn"
            :disabled="isBusy(item.id)"
            @click="openPicker(item.id)"
          >
            <Fa :icon="['fas', item.photo_url ? 'arrows-rotate' : 'plus']" />
            {{ uploadState[item.id] ? 'Загрузка...' : item.photo_url ? 'Заменить фото' : 'Добавить фото' }}
          </button>

          <button
            class="btn danger"
            :disabled="isBusy(item.id) || !item.photo_url"
            @click="removePhoto(item)"
          >
            <Fa :icon="['fas','trash']" />
            {{ removeState[item.id] ? 'Удаление...' : 'Удалить фото' }}
          </button>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import Swal from "sweetalert2";

const API_GET = "/api/admin/categories/get_root_category_photos.php";
const API_UPDATE = "/api/admin/categories/update_root_category_photo.php";
const API_DELETE = "/api/admin/categories/delete_root_category_photo.php";

const items = ref([]);
const loading = ref(false);
const error = ref("");
const fileInput = ref(null);
const activeCategoryId = ref(null);
const uploadState = ref({});
const removeState = ref({});
const imgError = ref({});

const withPhotoCount = computed(() => items.value.filter((x) => !!x.photo_url).length);
const withoutPhotoCount = computed(() => items.value.length - withPhotoCount.value);

function isBusy(id) {
  return loading.value || !!uploadState.value[id] || !!removeState.value[id];
}

function setImgError(id, value) {
  imgError.value = { ...imgError.value, [id]: !!value };
}

function normalizeItems(list) {
  items.value = (Array.isArray(list) ? list : []).map((item) => ({
    ...item,
    photo_url: item?.photo_url || null,
  }));

  const next = {};
  for (const item of items.value) next[item.id] = false;
  imgError.value = next;
}

async function fetchCategories() {
  loading.value = true;
  error.value = "";

  try {
    const res = await fetch(API_GET, {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || "Не удалось загрузить категории");
    }
    normalizeItems(data.items);
  } catch (e) {
    error.value = e?.message || "Не удалось загрузить категории";
  } finally {
    loading.value = false;
  }
}

function openPicker(categoryId) {
  activeCategoryId.value = Number(categoryId);
  fileInput.value.value = "";
  fileInput.value?.click();
}

async function onPickFile(event) {
  const categoryId = Number(activeCategoryId.value || 0);
  const file = event.target.files?.[0] || null;
  event.target.value = "";
  activeCategoryId.value = null;

  if (!categoryId || !file) return;

  const allowed = new Set(["image/jpeg", "image/png", "image/webp"]);
  if (!allowed.has(file.type)) {
    error.value = "Формат только JPG / PNG / WEBP";
    return;
  }
  if (file.size > 8 * 1024 * 1024) {
    error.value = "Файл больше 8 МБ";
    return;
  }

  uploadState.value = { ...uploadState.value, [categoryId]: true };
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("id", String(categoryId));
    fd.append("photo", file);

    const res = await fetch(API_UPDATE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || "Не удалось сохранить фото");
    }

    items.value = items.value.map((item) =>
      Number(item.id) === categoryId ? { ...item, ...data.item } : item
    );
    setImgError(categoryId, false);

    await Swal.fire({
      icon: "success",
      title: "Готово",
      text: "Фото сохранено",
      timer: 1300,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Не удалось сохранить фото";
  } finally {
    uploadState.value = { ...uploadState.value, [categoryId]: false };
  }
}

async function removePhoto(item) {
  const categoryId = Number(item?.id || 0);
  if (!categoryId || !item?.photo_url) return;

  const result = await Swal.fire({
    icon: "warning",
    title: "Удалить фото?",
    text: item.name,
    showCancelButton: true,
    confirmButtonText: "Удалить",
    cancelButtonText: "Отмена",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;

  removeState.value = { ...removeState.value, [categoryId]: true };
  error.value = "";

  try {
    const fd = new FormData();
    fd.append("id", String(categoryId));

    const res = await fetch(API_DELETE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || "Не удалось удалить фото");
    }

    items.value = items.value.map((row) =>
      Number(row.id) === categoryId ? { ...row, ...data.item } : row
    );
    setImgError(categoryId, false);

    await Swal.fire({
      icon: "success",
      title: "Удалено",
      text: "Фото удалено",
      timer: 1200,
      showConfirmButton: false,
    });
  } catch (e) {
    error.value = e?.message || "Не удалось удалить фото";
  } finally {
    removeState.value = { ...removeState.value, [categoryId]: false };
  }
}

onMounted(fetchCategories);
</script>

<style scoped>
.page {
  min-height: 100dvh;
  padding: 16px;
  background: var(--bg-main);
  color: var(--text-main);
}

.card {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.head {
  padding: 14px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
}

.title {
  margin: 0;
  font-size: 22px;
}

.sub {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.45;
  max-width: 720px;
}

.head-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.hidden-input {
  display: none;
}

.stats {
  margin-top: 12px;
  padding: 12px 14px;
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 700;
}

.notice {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
}

.notice.error {
  color: #991b1b;
  border-color: rgba(220, 38, 38, 0.25);
  background: rgba(220, 38, 38, 0.08);
}

.grid {
  margin-top: 14px;
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

.empty {
  padding: 16px;
  color: var(--text-muted);
}

.cat {
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.photo-box {
  width: 100%;
  aspect-ratio: 1 / 1;
  background: linear-gradient(180deg, #ffffff, #f7f9ff);
  border-bottom: 1px solid var(--border-soft);
  overflow: hidden;
}

.photo-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.photo-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 28px;
}

.body {
  padding: 12px 12px 10px;
}

.name {
  font-weight: 800;
  line-height: 1.3;
}

.meta {
  margin-top: 6px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  color: var(--text-muted);
  font-size: 12px;
}

.meta span {
  padding: 4px 7px;
  border-radius: 999px;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
}

.actions {
  margin-top: auto;
  padding: 0 12px 12px;
  display: grid;
  gap: 8px;
  grid-template-columns: 1fr;
}

.btn {
  height: 40px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: var(--accent);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn.ghost {
  background: #fff;
  color: var(--text-main);
  border-color: var(--border-soft);
}

.btn.danger {
  background: var(--accent-danger);
  color: #fff;
}

@media (max-width: 760px) {
  .page {
    padding: 12px;
  }

  .stats {
    grid-template-columns: 1fr;
  }
}
</style>
