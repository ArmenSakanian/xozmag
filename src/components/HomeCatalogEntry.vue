<template>
  <section class="cats-root">
    <div v-if="showHead" class="cats-head">
      <div class="cats-head-main">
        <span class="cats-kicker">Каталог</span>
        <h2 class="cats-title">Основные категории</h2>
        <div class="cats-sub">
          Быстрый переход по верхним разделам каталога без лишних экранов и промежуточных карточек.
        </div>
      </div>

      <div class="cats-note">
        <span class="cats-note-ic" aria-hidden="true">
          <Fa :icon="['fas', 'circle-info']" />
        </span>
        <div class="cats-note-body">
          Показаны только основные разделы. Остальные товары доступны через поиск и полный каталог.
        </div>
      </div>
    </div>

    <div v-else class="cats-note cats-note-inline">
      <span class="cats-note-ic" aria-hidden="true">
        <Fa :icon="['fas', 'circle-info']" />
      </span>
      <div class="cats-note-body">
        Выберите основной раздел каталога. Детальные категории и товары откроются дальше.
      </div>
    </div>

    <div v-if="topCats.length" class="cats-grid">
      <template v-for="c in topCats" :key="c.id">
        <RouterLink
          v-if="props.navigateOnPick"
          class="cat-card cat-card-link"
          :to="categoryTo(c)"
          :title="c.name"
        >
          <div class="cat-media">
            <img
              v-if="c.photo && !catImgErr[c.id]"
              :src="c.photo"
              :alt="c.name"
              loading="lazy"
              decoding="async"
              @error="catImgErr[c.id] = true"
            />
            <div v-else class="cat-visual-ph" aria-hidden="true">
              <Fa :icon="['far', 'image']" />
            </div>
            <div class="cat-media-shade"></div>
          </div>

          <div class="cat-card-body">
            <div class="cat-text">{{ c.name }}</div>
            <div class="cat-meta">
              <span>Открыть категорию</span>
              <span class="cat-arrow" aria-hidden="true">
                <Fa :icon="['fas', 'chevron-right']" />
              </span>
            </div>
          </div>
        </RouterLink>

        <button
          v-else
          class="cat-card"
          :title="c.name"
          type="button"
          @click="goCategory(c)"
        >
          <div class="cat-media">
            <img
              v-if="c.photo && !catImgErr[c.id]"
              :src="c.photo"
              :alt="c.name"
              loading="lazy"
              decoding="async"
              @error="catImgErr[c.id] = true"
            />
            <div v-else class="cat-visual-ph" aria-hidden="true">
              <Fa :icon="['far', 'image']" />
            </div>
            <div class="cat-media-shade"></div>
          </div>

          <div class="cat-card-body">
            <div class="cat-text">{{ c.name }}</div>
            <div class="cat-meta">
              <span>Открыть категорию</span>
              <span class="cat-arrow" aria-hidden="true">
                <Fa :icon="['fas', 'chevron-right']" />
              </span>
            </div>
          </div>
        </button>
      </template>
    </div>

    <div v-else class="cats-empty">Категории не загружены</div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
  showHead: { type: Boolean, default: true },
  items: { type: Array, default: () => [] },
  navigateOnPick: { type: Boolean, default: true },
});

const emit = defineEmits(["select-category"]);
const catImgErr = ref({});

const topCats = computed(() =>
  (Array.isArray(props.items) ? props.items : [])
    .filter((c) => !c?.parent && !c?.parent_id)
    .sort((a, b) =>
      String(a.name).localeCompare(String(b.name), "ru", { sensitivity: "base" })
    )
);

function categoryTo(cat) {
  return { path: "/catalog", query: { cat: cat.slug || cat.code } };
}

function goCategory(cat) {
  emit("select-category", cat);
}

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
</script>

<style scoped>
.cats-root {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.cats-head {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(320px, 420px);
  gap: 20px;
  align-items: end;
}

.cats-head-main {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cats-kicker {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(15, 23, 42, 0.04);
  color: #334155;
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.cats-title {
  margin: 0;
  font-size: clamp(26px, 3vw, 40px);
  line-height: 1;
  letter-spacing: -0.04em;
  font-weight: 1000;
  color: #0f172a;
}

.cats-sub {
  max-width: 760px;
  font-size: 14px;
  line-height: 1.55;
  font-weight: 700;
  color: #475569;
}

.cats-note {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  min-height: 100%;
  padding: 16px 18px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.03), rgba(15, 23, 42, 0.06));
}

.cats-note-inline {
  align-items: center;
}

.cats-note-ic {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.08);
  color: #0f172a;
}

.cats-note-body {
  font-size: 13px;
  line-height: 1.55;
  font-weight: 700;
  color: #334155;
}

.cats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}

.cat-card,
.cat-card-link {
  position: relative;
  min-height: 310px;
  padding: 0;
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  overflow: hidden;
  border: 0;
  background: #0f172a;
  color: #fff;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.14);
}

.cat-card:hover,
.cat-card-link:hover {
  transform: translateY(-4px);
  box-shadow: 0 24px 44px rgba(15, 23, 42, 0.2);
  filter: saturate(1.02);
}

.cat-card:focus-visible,
.cat-card-link:focus-visible {
  outline: 2px solid rgba(15, 23, 42, 0.18);
  outline-offset: 2px;
}

.cat-media,
.cat-visual-ph {
  position: absolute;
  inset: 0;
}

.cat-media img,
.cat-visual-ph {
  width: 100%;
  height: 100%;
}

.cat-media img {
  display: block;
  object-fit: cover;
  object-position: center;
  transform: scale(1.001);
  transition: transform 0.28s ease;
}

.cat-card:hover .cat-media img,
.cat-card-link:hover .cat-media img {
  transform: scale(1.05);
}

.cat-media-shade {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(180deg, rgba(2, 6, 23, 0.16) 0%, rgba(2, 6, 23, 0.28) 34%, rgba(2, 6, 23, 0.9) 100%),
    linear-gradient(90deg, rgba(2, 6, 23, 0.36) 0%, rgba(2, 6, 23, 0.08) 48%, rgba(2, 6, 23, 0.42) 100%);
}

.cat-visual-ph {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 34px;
  color: rgba(255, 255, 255, 0.72);
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0) 30%),
    linear-gradient(135deg, #111827 0%, #0f172a 100%);
}

.cat-card-body {
  position: relative;
  z-index: 1;
  width: 100%;
  margin-top: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}


.cat-text {
  min-height: 2.48em;
  font-size: 22px;
  line-height: 1.16;
  font-weight: 900;
  letter-spacing: -0.03em;
  color: #fff;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.cat-meta {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 42px;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 13px;
  font-weight: 800;
  color: rgba(255, 255, 255, 0.88);
}

.cat-arrow {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.cats-empty {
  padding: 18px;
  border: 1px dashed rgba(15, 23, 42, 0.16);
  background: rgba(15, 23, 42, 0.03);
  color: #64748b;
  font-weight: 800;
}

@media (max-width: 1180px) {
  .cats-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 920px) {
  .cats-head {
    grid-template-columns: 1fr;
    align-items: stretch;
  }

  .cats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .cats-root {
    gap: 16px;
  }

  .cats-title {
    font-size: 28px;
  }

  .cats-sub,
  .cats-note-body {
    font-size: 13px;
  }

  .cats-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .cat-card,
  .cat-card-link {
    min-height: 230px;
  }

  .cat-card-body {
    padding: 16px;
  }

  .cat-text {
    font-size: 20px;
  }
}
</style>
