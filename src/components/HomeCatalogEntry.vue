<template>
  <div class="cats-root">
    <div v-if="showHead" class="cats-head">
      <div class="cats-title">Категории</div>
      <div class="cats-sub">Только первый уровень</div>
    </div>
<div class="cats-note">
  <span class="cats-note-ic" aria-hidden="true">
    <Fa :icon="['fas','circle-info']" />
  </span>
  <span>
    Пока что показаны не все категории. Но вы можете найти любой товар через поиск -
    по поиску отображается весь ассортимент магазина.
  </span>
</div>
    <div class="cats-grid" v-if="topCats.length">
      <template v-for="c in topCats" :key="c.id">
        <RouterLink
          v-if="props.navigateOnPick"
          class="cat-card cat-card-link"
          :to="categoryTo(c)"
          :title="c.name"
        >
          <div class="cat-photo">
            <img
              v-if="c.photo && !catImgErr[c.id]"
              :src="c.photo"
              :alt="c.name"
              loading="lazy"
              decoding="async"
              @error="catImgErr[c.id] = true"
            />
            <div v-else class="cat-photo-ph" aria-hidden="true">
              <Fa :icon="['far', 'image']" />
            </div>
          </div>

          <div class="cat-text">{{ c.name }}</div>
        </RouterLink>

        <button
          v-else
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
              decoding="async"
              @error="catImgErr[c.id] = true"
            />
            <div v-else class="cat-photo-ph" aria-hidden="true">
              <Fa :icon="['far', 'image']" />
            </div>
          </div>

          <div class="cat-text">{{ c.name }}</div>
        </button>
      </template>
    </div>

    <div v-else class="cats-empty">
      Категории не загружены
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
  showHead: { type: Boolean, default: true },
  items: { type: Array, default: () => [] }, // получаем извне
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
  gap: 16px;
}

.cats-head {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 12px;
}

.cats-title {
  font-size: clamp(20px, 2vw, 28px);
  font-weight: 900;
  line-height: 1.05;
  color: var(--text-main);
  letter-spacing: -0.02em;
}

.cats-sub {
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
  white-space: nowrap;
}

.cats-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid var(--border-soft);
  border-radius: 18px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
  box-shadow:
    0 8px 24px rgba(15, 23, 42, 0.06),
    inset 0 1px 0 rgba(255,255,255,0.85);
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 800;
  line-height: 1.55;
}

.cats-note-ic {
  width: 26px;
  height: 26px;
  flex: 0 0 26px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: rgba(59, 130, 246, 0.10);
  color: var(--accent);
  font-size: 14px;
  margin-top: 1px;
}

.cats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}

.cat-card,
.cat-card-link {
  width: 100%;
  min-width: 0;
  min-height: 100%;
  padding: 14px;
  border: 1px solid var(--border-soft);
  border-radius: 24px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.94));
  box-shadow:
    0 12px 30px rgba(15, 23, 42, 0.08),
    inset 0 1px 0 rgba(255,255,255,0.88);
  display: flex;
  flex-direction: column;
  gap: 14px;
  text-align: left;
  cursor: pointer;
  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease,
    border-color 0.22s ease,
    background 0.22s ease;
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
  appearance: none;
  -webkit-appearance: none;
}

.cat-card-link {
  text-decoration: none;
  color: inherit;
}

.cat-card::before,
.cat-card-link::before {
  content: "";
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at top left, rgba(59,130,246,0.10), transparent 36%),
    radial-gradient(circle at bottom right, rgba(99,102,241,0.08), transparent 34%);
  pointer-events: none;
}

.cat-card:hover,
.cat-card-link:hover {
  transform: translateY(-4px);
  border-color: rgba(59, 130, 246, 0.22);
  box-shadow:
    0 16px 36px rgba(15, 23, 42, 0.12),
    0 0 0 1px rgba(59, 130, 246, 0.06);
}

.cat-card:focus-visible,
.cat-card-link:focus-visible {
  outline: none;
  border-color: rgba(59, 130, 246, 0.34);
  box-shadow:
    0 0 0 3px rgba(59, 130, 246, 0.16),
    0 16px 36px rgba(15, 23, 42, 0.12);
}

.cat-photo {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(15, 23, 42, 0.06);
  background:
    radial-gradient(circle at top, rgba(255,255,255,0.95), rgba(241,245,249,0.94)),
    linear-gradient(180deg, #ffffff, #f8fafc);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,0.9),
    0 10px 22px rgba(15, 23, 42, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
}

.cat-photo::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.10), transparent 32%);
  pointer-events: none;
}

.cat-photo img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  display: block;
  padding: 12px;
  box-sizing: border-box;
  transition: transform 0.28s ease;
}

.cat-card:hover .cat-photo img,
.cat-card-link:hover .cat-photo img {
  transform: scale(1.03);
}

.cat-photo-ph {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 24px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.92), rgba(241,245,249,0.96));
}

.cat-text {
  position: relative;
  z-index: 1;
  font-size: 15px;
  font-weight: 900;
  line-height: 1.3;
  color: var(--text-main);
  padding: 0 4px 4px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.6em;
}

.cats-empty {
  padding: 16px;
  border: 1px dashed var(--border-soft);
  border-radius: 18px;
  background: var(--bg-panel);
  color: var(--text-muted);
  font-weight: 900;
}

@media (max-width: 1080px) {
  .cats-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
  }
}

@media (max-width: 820px) {
  .cats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .cat-card,
  .cat-card-link {
    padding: 12px;
    border-radius: 20px;
    gap: 12px;
  }

  .cat-photo {
    border-radius: 17px;
  }

  .cat-text {
    font-size: 14px;
  }
}

@media (max-width: 560px) {
  .cats-root {
    gap: 14px;
  }



  .cats-title {
    font-size: 20px;
  }

  .cats-sub {
    white-space: normal;
  }

  .cats-note {
    padding: 12px 13px;
    gap: 10px;
    border-radius: 16px;
    font-size: 12.5px;
  }

.cats-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .cats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .cat-card,
  .cat-card-link {
    padding: 10px;
    border-radius: 18px;
    gap: 10px;
  }

  .cat-photo {
    border-radius: 14px;
  }

  .cat-photo img {
    padding: 10px;
  }

  .cat-text {
    font-size: 13px;
    min-height: 2.5em;
    padding: 0 2px 2px;
  }
}

@media (max-width: 380px) {
.cats-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .cats-note {
    font-size: 12px;
  }

  .cat-card,
  .cat-card-link {
    padding: 8px;
    border-radius: 14px;
  }

  .cat-photo {
    border-radius: 12px;
  }

  .cat-text {
    font-size: 12.5px;
  }
}
</style>