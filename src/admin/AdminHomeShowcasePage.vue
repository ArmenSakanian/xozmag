<template>
  <div class="page">
    <div class="head card">
      <div>
        <h1 class="title">Карточки витрины</h1>
        <div class="sub">Отдельный блок карточек поверх главного слайдера. Фото, название обязательны. Цена, кнопка и ссылка - необязательны.</div>
      </div>

      <div class="head-actions">
        <button class="btn ghost" :disabled="loading" @click="loadAll">
          <Fa :icon="['fas','rotate-right']" />
          Обновить
        </button>
      </div>
    </div>

    <div v-if="error" class="notice error">{{ error }}</div>

    <div class="layout">
      <section class="card settings-card">
        <div class="section-title">Заголовок блока</div>
        <div class="settings-row">
          <input v-model.trim="settingsTitle" class="input" type="text" maxlength="255" placeholder="Например: Новинки недели" />
          <button class="btn" :disabled="settingsSaving" @click="saveSettings">
            {{ settingsSaving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </section>

      <section class="card form-card">
        <div class="section-head">
          <div class="section-title">{{ editingId ? 'Редактирование карточки' : 'Новая карточка' }}</div>
          <button v-if="editingId" class="btn ghost" @click="resetForm">Отменить редактирование</button>
        </div>

        <div class="form-grid">
          <label class="field wide">
            <span>Название</span>
            <input v-model.trim="form.title" class="input" type="text" maxlength="255" placeholder="Название карточки" />
          </label>

          <label class="field">
            <span>Цена</span>
            <input
              v-model="form.price"
              class="input"
              type="text"
              inputmode="numeric"
              maxlength="12"
              placeholder="Только число"
              @input="sanitizePrice"
            />
          </label>

          <label class="field wide">
            <span>Описание</span>
            <textarea v-model.trim="form.description" class="textarea" rows="3" placeholder="Необязательно"></textarea>
          </label>

          <label class="field">
            <span>Текст кнопки</span>
            <input v-model.trim="form.button_text" class="input" type="text" maxlength="255" placeholder="Необязательно" />
          </label>

          <label class="field">
            <span>Ссылка кнопки</span>
            <input v-model.trim="form.button_url" class="input" type="text" maxlength="500" placeholder="Необязательно" />
          </label>

<label class="field wide">
  <span>{{ editingId ? 'Новая фотография' : 'Фотография' }}</span>

  <input
    ref="fileInput"
    class="file-native"
    type="file"
    accept="image/jpeg,image/png,image/webp"
    @change="onFileChange"
  />

  <div class="upload-box" :class="{ active: pickedFile || previewUrl }">
    <div class="upload-icon">
      <Fa :icon="['fas','image']" />
    </div>

    <div class="upload-content">
      <div class="upload-title">
        {{ pickedFile ? 'Файл выбран' : (editingId ? 'Выбери новую фотографию' : 'Выбери фотографию') }}
      </div>

      <div class="upload-sub">
        {{
          pickedFile?.name
            ? pickedFile.name
            : (previewUrl
              ? 'Текущее изображение уже загружено'
              : 'JPG, PNG, WEBP до 8 МБ')
        }}
      </div>
    </div>

    <div class="upload-action">
      {{ pickedFile ? 'Заменить' : 'Выбрать' }}
    </div>
  </div>
</label>
        </div>

        <div v-if="previewUrl" class="preview-box">
          <div class="preview-media">
            <div class="preview-bg" :style="{ backgroundImage: `url(${previewUrl})` }"></div>
            <img :src="previewUrl" alt="preview" />
          </div>
        </div>

        <div class="form-actions">
          <button class="btn" :disabled="saving" @click="submitForm">
            {{ saving ? (editingId ? 'Сохранение...' : 'Создание...') : (editingId ? 'Сохранить изменения' : 'Создать карточку') }}
          </button>
        </div>
      </section>

      <section class="card list-card">
        <div class="section-head">
          <div class="section-title">Существующие карточки</div>
          <div class="section-meta">{{ items.length }} шт.</div>
        </div>

        <div v-if="loading" class="empty">Загрузка...</div>
        <div v-else-if="!items.length" class="empty">Карточек пока нет.</div>

        <div v-else class="rows">
          <article v-for="item in items" :key="item.id" class="row-item" :class="{ off: !item.is_active }">
            <div class="row-thumb">
              <div class="row-thumb-bg" :style="{ backgroundImage: `url(${item.image_url})` }"></div>
              <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" />
            </div>

            <div class="row-main">
              <div class="row-title">{{ item.title }}</div>
              <div class="row-sub">
                <span>{{ item.price ? `${formatPrice(item.price)} ₽` : 'Без цены' }}</span>
                <span>{{ item.is_active ? 'Показывается' : 'Скрыта' }}</span>
              </div>
              <div v-if="item.description" class="row-desc">{{ item.description }}</div>
              <div v-if="item.button_text && item.button_url" class="row-link">{{ item.button_text }} - {{ item.button_url }}</div>
            </div>

            <div class="row-actions">
              <button class="btn mini" @click="startEdit(item)">Редактировать</button>
              <button class="btn mini ghost" @click="toggleVisibility(item)">{{ item.is_active ? 'Скрыть' : 'Открыть' }}</button>
              <button class="btn mini danger" @click="removeItem(item)">Удалить</button>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Swal from 'sweetalert2';

const API_GET = '/api/admin/home_showcase/get_showcase.php';
const API_SAVE_SETTINGS = '/api/admin/home_showcase/save_settings.php';
const API_CREATE = '/api/admin/home_showcase/create_item.php';
const API_UPDATE = '/api/admin/home_showcase/update_item.php';
const API_DELETE = '/api/admin/home_showcase/delete_item.php';
const API_TOGGLE = '/api/admin/home_showcase/toggle_visibility.php';

const loading = ref(false);
const saving = ref(false);
const settingsSaving = ref(false);
const error = ref('');
const items = ref([]);
const settingsTitle = ref('');
const editingId = ref(0);
const fileInput = ref(null);
const pickedFile = ref(null);
const previewUrl = ref('');

const form = ref({
  title: '',
  price: '',
  description: '',
  button_text: '',
  button_url: '',
});

function formatPrice(value) {
  const digits = String(value ?? '').replace(/\D+/g, '');
  return digits ? new Intl.NumberFormat('ru-RU').format(Number(digits)) : '';
}

function sanitizePrice() {
  form.value.price = String(form.value.price ?? '').replace(/\D+/g, '');
}

function resetFileInput() {
  pickedFile.value = null;
  if (previewUrl.value && previewUrl.value.startsWith('blob:')) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = '';
  if (fileInput.value) fileInput.value.value = '';
}

function resetForm() {
  editingId.value = 0;
  form.value = { title: '', price: '', description: '', button_text: '', button_url: '' };
  resetFileInput();
}

function onFileChange(event) {
  const file = event.target.files?.[0] || null;
  pickedFile.value = file;
  if (previewUrl.value && previewUrl.value.startsWith('blob:')) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = file ? URL.createObjectURL(file) : (editingId.value ? (items.value.find((x) => x.id === editingId.value)?.image_url || '') : '');
}

async function loadAll() {
  loading.value = true;
  error.value = '';
  try {
    const res = await fetch(API_GET, { credentials: 'include', headers: { Accept: 'application/json' } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || 'Не удалось загрузить блок');
    items.value = Array.isArray(data?.items) ? data.items : [];
    settingsTitle.value = String(data?.settings?.title || '').trim();
    if (editingId.value) {
      const current = items.value.find((x) => x.id === editingId.value);
      if (current && !pickedFile.value) previewUrl.value = current.image_url || '';
    }
  } catch (e) {
    error.value = e?.message || 'Не удалось загрузить блок';
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  settingsSaving.value = true;
  error.value = '';
  try {
    const fd = new FormData();
    fd.append('title', settingsTitle.value || 'Подборка товаров');
    const res = await fetch(API_SAVE_SETTINGS, { method: 'POST', body: fd, credentials: 'include' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || 'Не удалось сохранить заголовок');
    settingsTitle.value = String(data?.settings?.title || '').trim();
  } catch (e) {
    error.value = e?.message || 'Не удалось сохранить заголовок';
  } finally {
    settingsSaving.value = false;
  }
}

function startEdit(item) {
  editingId.value = Number(item.id);
  form.value = {
    title: item.title || '',
    price: String(item.price || '').replace(/\D+/g, ''),
    description: item.description || '',
    button_text: item.button_text || '',
    button_url: item.button_url || '',
  };
  resetFileInput();
  previewUrl.value = item.image_url || '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function buildFormData() {
  const fd = new FormData();
  fd.append('title', form.value.title || '');
  fd.append('price', String(form.value.price || '').replace(/\D+/g, ''));
  fd.append('description', form.value.description || '');
  fd.append('button_text', form.value.button_text || '');
  fd.append('button_url', form.value.button_url || '');
  if (pickedFile.value) fd.append('image', pickedFile.value);
  return fd;
}

async function submitForm() {
  sanitizePrice();
  if (!form.value.title.trim()) {
    error.value = 'Заполни название';
    return;
  }
  if (!editingId.value && !pickedFile.value) {
    error.value = 'Добавь фотографию';
    return;
  }

  saving.value = true;
  error.value = '';
  try {
    const fd = buildFormData();
    let url = API_CREATE;
    if (editingId.value) {
      url = API_UPDATE;
      fd.append('id', String(editingId.value));
    }

    const res = await fetch(url, { method: 'POST', body: fd, credentials: 'include' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || 'Не удалось сохранить карточку');

    await loadAll();
    const updatedId = Number(data?.item?.id || editingId.value || 0);
    if (updatedId) {
      const fresh = items.value.find((x) => x.id === updatedId);
      if (fresh) {
        if (editingId.value) startEdit(fresh);
      }
    }
    if (!editingId.value) resetForm();
  } catch (e) {
    error.value = e?.message || 'Не удалось сохранить карточку';
  } finally {
    saving.value = false;
  }
}

async function toggleVisibility(item) {
  const fd = new FormData();
  fd.append('id', String(item.id));
  try {
    const res = await fetch(API_TOGGLE, { method: 'POST', body: fd, credentials: 'include' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || 'Не удалось поменять видимость');
    items.value = items.value.map((row) => (row.id === item.id ? data.item : row));
    if (editingId.value === item.id) {
      const fresh = items.value.find((x) => x.id === item.id);
      if (fresh) previewUrl.value = fresh.image_url || previewUrl.value;
    }
  } catch (e) {
    error.value = e?.message || 'Не удалось поменять видимость';
  }
}

async function removeItem(item) {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Удалить карточку?',
    text: item.title,
    showCancelButton: true,
    confirmButtonText: 'Удалить',
    cancelButtonText: 'Отмена',
    confirmButtonColor: '#dc2626',
  });
  if (!result.isConfirmed) return;

  const fd = new FormData();
  fd.append('id', String(item.id));
  try {
    const res = await fetch(API_DELETE, { method: 'POST', body: fd, credentials: 'include' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data?.ok) throw new Error(data?.error || 'Не удалось удалить карточку');
    items.value = items.value.filter((row) => row.id !== item.id);
    if (editingId.value === item.id) resetForm();
  } catch (e) {
    error.value = e?.message || 'Не удалось удалить карточку';
  }
}

onMounted(loadAll);
</script>

<style scoped>
.page {
  min-height: 100dvh;
  padding: clamp(14px, 3vw, 28px);
  background: var(--bg-main);
  color: var(--text-main);
}

.layout, .head, .notice {
  max-width: 1100px;
  margin-left: auto;
  margin-right: auto;
}

.layout {
  display: grid;
  gap: 12px;
}

.card {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 14px;
}

.head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.title { margin: 0; font-size: clamp(22px, 2.2vw, 30px); }
.sub { margin-top: 6px; color: var(--text-muted); line-height: 1.4; }
.head-actions, .settings-row, .form-actions, .section-head, .row-actions { display: flex; gap: 8px; align-items: center; }
.section-head { justify-content: space-between; }
.section-title { font-size: 18px; font-weight: 900; }
.section-meta { color: var(--text-muted); font-size: 13px; }
.settings-row { flex-wrap: wrap; }

.input, .textarea {
  width: 100%;
  border: 1px solid var(--border-soft);
  border-radius: 14px;
  background: var(--bg-soft);
  color: var(--text-main);
  padding: 12px 14px;
  outline: none;
}
.textarea { resize: vertical; min-height: 100px; }
.file-native {
  position: absolute;
  opacity: 0;
  pointer-events: none;
  width: 0;
  height: 0;
}

.upload-box {
  display: grid;
  grid-template-columns: 52px minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  min-height: 84px;
  padding: 14px 16px;
  border: 1px dashed var(--border-soft);
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  cursor: pointer;
  transition: .18s ease;
}

.upload-box:hover {
  border-color: var(--accent);
  background: #f8fbff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.upload-box.active {
  border-color: var(--accent);
  background: rgba(59, 130, 246, 0.06);
}

.upload-icon {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(59, 130, 246, 0.12);
  color: var(--accent);
  font-size: 20px;
  flex-shrink: 0;
}

.upload-content {
  min-width: 0;
}

.upload-title {
  font-size: 14px;
  font-weight: 900;
  color: var(--text-main);
  line-height: 1.3;
}

.upload-sub {
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.35;
  word-break: break-word;
}

.upload-action {
  min-height: 38px;
  padding: 0 14px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--accent);
  color: #fff;
  font-size: 13px;
  font-weight: 900;
  white-space: nowrap;
}
.hint { color: var(--text-muted); }

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 12px;
}
.field { display: grid; gap: 6px; }
.field.wide { grid-column: 1 / -1; }
.field > span { font-weight: 800; }

.preview-box { margin-top: 12px; }
.preview-media {
  position: relative;
  height: 260px;
  border-radius: 18px;
  overflow: hidden;
  background: #eef2f7;
}
.preview-bg {
  position: absolute;
  inset: -12px;
  background-size: cover;
  background-position: center;
  filter: blur(18px);
  transform: scale(1.08);
  opacity: .45;
}
.preview-media img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 8px;
}

.rows { display: grid; gap: 10px; margin-top: 12px; }
.row-item {
  display: grid;
  grid-template-columns: 92px minmax(0, 1fr) auto;
  gap: 10px;
  align-items: center;
  border: 1px solid var(--border-soft);
  border-radius: 16px;
  padding: 10px;
  background: var(--bg-soft);
}
.row-item.off { opacity: .72; }
.row-thumb {
  position: relative;
  height: 92px;
  border-radius: 14px;
  overflow: hidden;
  background: #eef2f7;
}
.row-thumb-bg {
  position: absolute;
  inset: -10px;
  background-size: cover;
  background-position: center;
  filter: blur(14px);
  transform: scale(1.08);
  opacity: .42;
}
.row-thumb img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 6px;
}
.row-main { min-width: 0; }
.row-title { font-size: 15px; font-weight: 900; line-height: 1.35; }
.row-sub, .row-link, .row-desc { margin-top: 4px; color: var(--text-muted); font-size: 13px; }
.row-sub { display: flex; gap: 8px; flex-wrap: wrap; }
.row-desc, .row-link {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}
.row-actions { flex-wrap: wrap; justify-content: flex-end; }

.btn {
  min-height: 42px;
  padding: 0 14px;
  border-radius: 14px;
  border: 1px solid transparent;
  background: var(--accent);
  color: #fff;
  font-weight: 900;
  cursor: pointer;
}
.btn.ghost { background: var(--bg-soft); color: var(--text-main); border-color: var(--border-soft); }
.btn.danger { background: #dc2626; }
.btn.mini { min-height: 36px; padding: 0 12px; font-size: 13px; }
.btn:disabled { opacity: .65; cursor: default; }
.notice { margin-bottom: 12px; padding: 12px 14px; border-radius: 14px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.empty { color: var(--text-muted); padding-top: 4px; }

@media (max-width: 860px) {
  .form-grid { grid-template-columns: 1fr; }
  .row-item { grid-template-columns: 74px minmax(0, 1fr); }
  .row-actions { grid-column: 1 / -1; justify-content: stretch; }
}

@media (max-width: 640px) {
  .head, .section-head, .settings-row { align-items: flex-start; flex-direction: column; }
  .row-item { grid-template-columns: 64px minmax(0, 1fr); padding: 8px; gap: 8px; }
  .row-thumb { height: 64px; border-radius: 12px; }
  .row-title { font-size: 14px; }
  .row-sub, .row-link, .row-desc { font-size: 12px; }
  .btn { width: 100%; justify-content: center; }
}
</style>
