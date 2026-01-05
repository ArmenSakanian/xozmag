<template>
  <div class="apg">
    <div class="apg-head">
      <div>
        <h1 class="apg-title">Фото для главного слайдера</h1>
        <div class="apg-sub">Выбери несколько фото — загрузятся сразу. Можно выделить и удалить пачкой.</div>
      </div>

      <div class="apg-actions">
        <input
          ref="fileInput"
          type="file"
          multiple
          accept="image/jpeg,image/png,image/webp"
          class="apg-file"
          @change="onPickFiles"
        />

        <button class="btn" :disabled="uploading" @click="openPicker">
          <span v-if="!uploading">Добавить фото</span>
          <span v-else>Загрузка...</span>
        </button>

        <button class="btn ghost" :disabled="loading" @click="fetchPhotos">Обновить</button>

        <button
          class="btn danger"
          :disabled="selectedIds.length === 0 || deleting"
          @click="deleteSelected"
        >
          <span v-if="!deleting">Удалить выбранные ({{ selectedIds.length }})</span>
          <span v-else>Удаляю...</span>
        </button>
      </div>
    </div>

    <div class="apg-toolbar" v-if="photos.length">
      <label class="chk">
        <input type="checkbox" :checked="isAllSelected" @change="toggleAll" />
        <span>Выбрать все</span>
      </label>

      <div class="muted">Всего: {{ photos.length }}</div>
    </div>

    <div v-if="error" class="apg-error">{{ error }}</div>
    <div v-if="loading" class="apg-loading">Загрузка списка...</div>

    <div v-else class="grid">
      <div v-if="photos.length === 0" class="empty">
        Пока нет фото. Нажми “Добавить фото”.
      </div>

      <div v-for="p in photos" :key="p.id" class="card">
        <div class="img-wrap">
          <img :src="p.url" :alt="`photo-${p.id}`" loading="lazy" decoding="async" />

          <label class="select">
            <input type="checkbox" :checked="isSelected(p.id)" @change="toggleOne(p.id)" />
            <span class="box"></span>
          </label>
        </div>

        <div class="card-foot">
          <div class="meta">
            <div class="id">#{{ p.id }}</div>
            <div class="url">{{ p.url }}</div>
          </div>

          <button class="icon-btn danger" title="Удалить" @click="deleteOne(p.id)">
              <Fa :icon="['fas','trash']" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import Swal from "sweetalert2";

const API_GET = "/api/admin/photogallery/get_photo_gallery.php";
const API_CREATE = "/api/admin/photogallery/create_photo_gallery.php";
const API_DELETE = "/api/admin/photogallery/delete_photo_gallery.php";

const photos = ref([]);
const loading = ref(false);
const uploading = ref(false);
const deleting = ref(false);
const error = ref("");

const fileInput = ref(null);

const selected = ref([]); // массив id

const selectedIds = computed(() => selected.value);

const isSelected = (id) => selected.value.includes(Number(id));

const isAllSelected = computed(() => photos.value.length > 0 && selected.value.length === photos.value.length);

function toggleOne(id) {
  id = Number(id);
  if (isSelected(id)) selected.value = selected.value.filter(x => x !== id);
  else selected.value = [...selected.value, id];
}

function toggleAll(ev) {
  const checked = !!ev.target.checked;
  if (checked) selected.value = photos.value.map(x => Number(x.id));
  else selected.value = [];
}

function openPicker() {
  error.value = "";
  fileInput.value?.click();
}

async function fetchPhotos() {
  error.value = "";
  loading.value = true;
  try {
    const r = await fetch(API_GET, { credentials: "include" });
    const j = await r.json();
    if (!j?.ok) throw new Error(j?.error || "GET_FAILED");

    photos.value = Array.isArray(j.items) ? j.items : [];
    // если список изменился — подчистим выделение
    const ids = new Set(photos.value.map(x => Number(x.id)));
    selected.value = selected.value.filter(id => ids.has(id));
  } catch {
    error.value = "Не удалось получить список фото (api).";
  } finally {
    loading.value = false;
  }
}

async function onPickFiles(ev) {
  const files = Array.from(ev.target.files || []);
  ev.target.value = ""; // можно выбрать те же файлы повторно
  if (!files.length) return;

  // легкая валидация
  const okTypes = new Set(["image/jpeg", "image/png", "image/webp"]);
  for (const f of files) {
    if (!okTypes.has(f.type)) {
      error.value = "Формат только JPG / PNG / WEBP";
      return;
    }
    if (f.size > 8 * 1024 * 1024) {
      error.value = "Один из файлов больше 8MB.";
      return;
    }
  }

  uploading.value = true;
  error.value = "";

  try {
    const fd = new FormData();
    // ✅ важно: files[]
    files.forEach(f => fd.append("files[]", f));

    const r = await fetch(API_CREATE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const j = await r.json();
    if (!j?.ok) throw new Error(j?.error || "CREATE_FAILED");

    const newItems = Array.isArray(j.items) ? j.items : [];
    const dup = Array.isArray(j.duplicates) ? j.duplicates.length : 0;

    photos.value = [...photos.value, ...newItems].sort((a, b) => {
      const sa = Number(a.sort_order ?? 0);
      const sb = Number(b.sort_order ?? 0);
      if (sa !== sb) return sa - sb;
      return Number(a.id) - Number(b.id);
    });

await Swal.fire({
  icon: "success",
  title: "Готово",
  text: `Загружено: ${newItems.length}. Дубликатов пропущено: ${dup}`,
  timer: 1600,
  showConfirmButton: false,
});
  } catch {
    error.value = "Не удалось загрузить фото (api).";
  } finally {
    uploading.value = false;
  }
}

async function deleteOne(id) {
  const res = await Swal.fire({
    icon: "warning",
    title: "Удалить фото?",
    text: `ID: ${id}`,
    showCancelButton: true,
    confirmButtonText: "Удалить",
    cancelButtonText: "Отмена",
    confirmButtonColor: "#dc2626",
  });
  if (!res.isConfirmed) return;

  return deleteIds([Number(id)]);
}

async function deleteSelected() {
  const res = await Swal.fire({
    icon: "warning",
    title: "Удалить выбранные фото?",
    text: `Количество: ${selected.value.length}`,
    showCancelButton: true,
    confirmButtonText: "Удалить",
    cancelButtonText: "Отмена",
    confirmButtonColor: "#dc2626",
  });
  if (!res.isConfirmed) return;

  return deleteIds([...selected.value]);
}

async function deleteIds(ids) {
  if (!ids.length) return;

  deleting.value = true;
  error.value = "";

  try {
    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", String(id)));

    const r = await fetch(API_DELETE, {
      method: "POST",
      body: fd,
      credentials: "include",
    });
    const j = await r.json();
    if (!j?.ok) throw new Error(j?.error || "DELETE_FAILED");

    const set = new Set(ids.map(Number));
    photos.value = photos.value.filter(x => !set.has(Number(x.id)));
    selected.value = selected.value.filter(id => !set.has(Number(id)));

    await Swal.fire({
      icon: "success",
      title: "Удалено",
      text: `Удалено: ${j.deleted ?? ids.length}`,
      timer: 1400,
      showConfirmButton: false,
    });
  } catch {
    error.value = "Не удалось удалить фото (api).";
  } finally {
    deleting.value = false;
  }
}

onMounted(fetchPhotos);
</script>

<style scoped>
.apg { padding: 16px; color: var(--text-main); background: var(--bg-main); }

.apg-head{
  display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap;
  padding:14px; background:var(--bg-panel); border:1px solid var(--border-soft);
  border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);
}
.apg-title{ font-size:18px; margin:0; }
.apg-sub{ margin-top:4px; font-size:13px; color:var(--text-muted); }

.apg-actions{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.apg-file{ display:none; }

.btn{
  height:40px; padding:0 14px; border-radius:var(--radius-md);
  border:1px solid rgba(0,0,0,0.08);
  background:var(--accent); color:#fff; box-shadow:var(--shadow-sm); cursor:pointer;
}
.btn:disabled{ opacity:.65; cursor:not-allowed; }
.btn.ghost{ background:#fff; color:var(--text-main); border:1px solid var(--border-soft); }
.btn.danger{ background:var(--accent-danger); border-color:rgba(0,0,0,0.06); }

.apg-toolbar{
  margin-top:12px;
  display:flex; justify-content:space-between; align-items:center;
  padding:10px 12px;
  background:var(--bg-panel);
  border:1px solid var(--border-soft);
  border-radius:var(--radius-lg);
  box-shadow:var(--shadow-sm);
}
.chk{ display:flex; gap:8px; align-items:center; user-select:none; }
.muted{ color:var(--text-muted); font-size:13px; }

.apg-error{
  margin-top:12px; padding:10px 12px; border-radius:var(--radius-md);
  background:rgba(220,38,38,0.10); border:1px solid rgba(220,38,38,0.25); color:#991b1b;
}
.apg-loading{ margin-top:12px; color:var(--text-muted); }

.grid{
  margin-top:14px; display:grid;
  grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
  gap:12px;
}
.empty{
  padding:14px; background:var(--bg-panel); border:1px solid var(--border-soft);
  border-radius:var(--radius-lg); color:var(--text-muted);
}
.card{
  background:var(--bg-panel); border:1px solid var(--border-soft);
  border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm);
  display:flex; flex-direction:column;
}

.img-wrap{ position:relative; aspect-ratio:16/9; background:#0f1115; }
.img-wrap img{ width:100%; height:100%; object-fit:cover; display:block; }

.select{
  position:absolute; top:10px; left:10px;
  display:flex; align-items:center; justify-content:center;
  width:28px; height:28px; border-radius:10px;
  background:rgba(255,255,255,0.88); border:1px solid rgba(0,0,0,0.10);
  cursor:pointer;
}
.select input{ display:none; }
.select .box{
  width:14px; height:14px; border-radius:4px;
  border:2px solid rgba(0,0,0,0.45);
}
.select input:checked + .box{
  background: var(--accent);
  border-color: var(--accent);
}

.card-foot{
  padding:10px; display:flex; gap:10px; align-items:center; justify-content:space-between;
}
.meta{ min-width:0; }
.id{ font-weight:700; font-size:13px; }
.url{
  font-size:12px; color:var(--text-muted);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;
}
.icon-btn{
  width:38px; height:38px; border-radius:var(--radius-md);
  border:1px solid var(--border-soft); background:#fff; cursor:pointer;
  display:inline-flex; align-items:center; justify-content:center;
}
.icon-btn.danger{ color:var(--accent-danger); }
</style>
