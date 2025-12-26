<template>
  <div class="cats-root">
    <!-- Заголовок -->
    <div v-if="showHead" class="cats-head">
      <div class="cats-title">Категории</div>
      <div class="cats-sub">Только первый уровень</div>
    </div>

    <!-- Сетка -->
    <div class="cats-grid" v-if="displayCats.length">
      <button
        v-for="c in topCats"
        :key="c.id"
        class="cat-card"
        @click="goCategory(c)"
        :title="c.name"
        type="button"
      >
        <div class="cat-photo">
          <img
            v-if="c.photo && !catImgErr[c.id]"
            :src="c.photo"
            :alt="c.name"
            loading="lazy"
            @error="catImgErr[c.id] = true"
          />
          <div v-else class="cat-photo-ph" aria-hidden="true">
            <i class="fa-regular fa-image"></i>
          </div>
        </div>

        <div class="cat-text">{{ c.name }}</div>
      </button>
    </div>

    <div v-else class="cats-empty">
      Категории не загружены
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";

const props = defineProps({
  showHead: { type: Boolean, default: true },
  items: { type: Array, default: null },          // если передали — используем их
  navigateOnPick: { type: Boolean, default: true }
});

const emit = defineEmits(["select-category"]);
const router = useRouter();

const categories = ref([]);
const catImgErr = ref({});

// если передали items — ничего не грузим, просто работаем от них
const displayCats = computed(() => {
  if (Array.isArray(props.items) && props.items.length) return props.items;
  return categories.value;
});

const topCats = computed(() =>
  displayCats.value
    .filter((c) => !c.parent) // у твоих загруженных: parent_id -> parent, у переданных тоже ожидаем parent/parent_id
    .sort((a, b) => String(a.name).localeCompare(String(b.name), "ru", { sensitivity: "base" }))
);

function normalizeCat(c) {
  // поддерживаем и формат из API, и формат уже подготовленных items
  const parent = c.parent ?? c.parent_id ?? null;

  const photo =
    c.photo ??
    c.photo_url_abs ??
    c.photo_url ??
    (c.photo_categories ? `/photo_categories_vitrina/${c.photo_categories}` : null);

  return {
    id: c.id,
    name: c.name,
    code: c.code,
    parent,
    photo,
  };
}

async function loadCats() {
  try {
    const r = await fetch("/api/admin/product/get_categories_flat.php");
    const raw = await r.json();

    categories.value = (Array.isArray(raw) ? raw : []).map(normalizeCat);

    const next = { ...catImgErr.value };
    categories.value.forEach((x) => {
      if (x?.id != null) next[x.id] = false;
    });
    catImgErr.value = next;
  } catch {
    categories.value = [];
  }
}

function goCategory(cat) {
  emit("select-category", cat);

  if (props.navigateOnPick) {
    router.push({ path: "/catalogv2", query: { cat: cat.code } });
  }
}

// если items меняются — обновим catImgErr под них
watch(
  () => props.items,
  (val) => {
    if (!Array.isArray(val)) return;
    const next = { ...catImgErr.value };
    val.forEach((x) => {
      if (x?.id != null) next[x.id] = false;
    });
    catImgErr.value = next;
  },
  { immediate: true }
);

onMounted(() => {
  if (!Array.isArray(props.items) || !props.items.length) {
    loadCats();
  }
});
</script>

<style scoped>
.cats-root{
  display:flex;
  flex-direction: column;
  gap: 12px;
}

/* header */
.cats-head{
  display:flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  padding: 2px 2px;
}

.cats-title{
  font-size: 18px;
  font-weight: 900;
  color: var(--text-main);
}

.cats-sub{
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
}

/* grid */
.cats-grid{
  display:grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.cat-card{
  text-align:left;
  border: 1px solid var(--border-soft);
  background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,255,255,.90));
  border-radius: var(--radius-lg);
  padding: 10px;

  box-shadow: var(--shadow-sm);
  cursor: pointer;

  display:flex;
  flex-direction: column;
  gap: 10px;

  transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}

.cat-card:hover{
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: rgba(4,0,255,.16);
}

.cat-photo{
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 14px;
  overflow: hidden;

  border: 1px solid var(--border-soft);
  background: #fff;

  display:flex;
  align-items:center;
  justify-content:center;

  box-shadow: 0 10px 20px rgba(0,0,0,.06);
}

.cat-photo img{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display:block;
}

.cat-photo-ph{
  width: 100%;
  height: 100%;
  display:flex;
  align-items:center;
  justify-content:center;
  color: var(--text-muted);
  background: linear-gradient(180deg, #fff, #f7f9ff);
  font-size: 18px;
}

.cat-text{
  font-size: 13px;
  font-weight: 900;
  color: var(--text-main);
  line-height: 1.2;
  padding: 2px 4px 4px 4px;

  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.4em;
}

.cats-empty{
  padding: 14px 14px;
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  color: var(--text-muted);
  font-weight: 900;
}

/* responsive */
@media (max-width: 1080px){
  .cats-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 820px){
  .cats-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 480px){
  .cats-head{ flex-direction: column; align-items: flex-start; }
  .cats-grid{ grid-template-columns: 1fr; }
}
</style>
