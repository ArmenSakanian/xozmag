<template>
  <section class="cats-root">
    <div v-if="showHead" class="cats-head">
      <div class="cats-head-main">
        <span class="cats-kicker">Категории </span>
        <div class="cats-title-wrap">
          <h2 class="cats-title">Популярные категории</h2>
          <div class="cats-sub">Только первый уровень - быстрое открытие нужного раздела</div>
        </div>
      </div>
    </div>

    <div class="cats-note">
      <span class="cats-note-ic" aria-hidden="true">
        <Fa :icon="['fas','circle-info']" />
      </span>
      <div class="cats-note-body">
        <div class="cats-note-title">Не видите нужный раздел?</div>
        <div class="cats-note-text">
          Пока что показаны не все категории. Но вы можете найти любой товар через поиск -
          по поиску отображается весь ассортимент магазина.
        </div>
      </div>
    </div>

    <div class="cats-grid" v-if="topCats.length">
      <template v-for="c in topCats" :key="c.id">
        <RouterLink
          v-if="props.navigateOnPick"
          class="cat-card cat-card-link"
          :to="categoryTo(c)"
          :title="c.name"
        >
          <span class="cat-card-glow" aria-hidden="true"></span>
          <div class="cat-card-top">
            <span class="cat-chip">Категория</span>
            <span class="cat-arrow" aria-hidden="true">
              <Fa :icon="['fas', 'arrow-up-right-from-square']" />
            </span>
          </div>

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

          <div class="cat-content">
            <div class="cat-text">{{ c.name }}</div>
            <div class="cat-meta">
              <span>Открыть раздел</span>
              <Fa :icon="['fas', 'chevron-right']" />
            </div>
          </div>
        </RouterLink>

        <button
          v-else
          class="cat-card"
          @click="goCategory(c)"
          :title="c.name"
          type="button"
        >
          <span class="cat-card-glow" aria-hidden="true"></span>
          <div class="cat-card-top">
            <span class="cat-chip">Категория</span>
            <span class="cat-arrow" aria-hidden="true">
              <Fa :icon="['fas', 'arrow-up-right-from-square']" />
            </span>
          </div>

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

          <div class="cat-content">
            <div class="cat-text">{{ c.name }}</div>
            <div class="cat-meta">
              <span>Открыть раздел</span>
              <Fa :icon="['fas', 'chevron-right']" />
            </div>
          </div>
        </button>
      </template>
    </div>

    <div v-else class="cats-empty">
      Категории не загружены
    </div>
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
  gap: 18px;
}

.cats-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 14px;
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
  gap: 8px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.03));
  color: var(--text-main);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.cats-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.cats-title {
  font-size: clamp(24px, 2.7vw, 36px);
  font-weight: 900;
  line-height: 1;
  color: var(--text-main);
  letter-spacing: -0.03em;
}

.cats-sub {
  font-size: 14px;
  line-height: 1.45;
  color: var(--text-muted);
  font-weight: 700;
}

.cats-note {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px 18px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(246,247,250,0.98));
  box-shadow:
    0 14px 34px rgba(15, 23, 42, 0.08),
    inset 0 1px 0 rgba(255,255,255,0.9);
}

.cats-note-ic {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.12), rgba(15, 23, 42, 0.06));
  color: var(--text-main);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
}

.cats-note-body {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.cats-note-title {
  font-size: 14px;
  line-height: 1.3;
  font-weight: 900;
  color: var(--text-main);
}

.cats-note-text {
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.55;
  font-weight: 700;
}

.cats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}

.cat-card,
.cat-card-link {
  position: relative;
  width: 100%;
  min-width: 0;
  min-height: 100%;
  padding: 14px;
  border: 1px solid rgba(15, 23, 42, 0.09);
  border-radius: 28px;
  background: linear-gradient(180deg, rgba(255,255,255,0.99), rgba(243,245,249,0.98));
  box-shadow:
    0 18px 40px rgba(15, 23, 42, 0.1),
    inset 0 1px 0 rgba(255,255,255,0.9);
  display: flex;
  flex-direction: column;
  gap: 14px;
  text-align: left;
  cursor: pointer;
  transition:
    transform 0.24s ease,
    box-shadow 0.24s ease,
    border-color 0.24s ease;
  overflow: hidden;
  box-sizing: border-box;
  appearance: none;
  -webkit-appearance: none;
}

.cat-card-link {
  text-decoration: none;
  color: inherit;
}

.cat-card-glow {
  display: none;
}

.cat-card::before,
.cat-card-link::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.04), transparent 28%);
  pointer-events: none;
}

.cat-card:hover,
.cat-card-link:hover {
  transform: translateY(-6px);
  border-color: rgba(15, 23, 42, 0.16);
  box-shadow:
    0 22px 46px rgba(15, 23, 42, 0.14),
    0 0 0 1px rgba(15, 23, 42, 0.04);
}

.cat-card:focus-visible,
.cat-card-link:focus-visible {
  outline: none;
  border-color: rgba(15, 23, 42, 0.24);
  box-shadow:
    0 0 0 3px rgba(15, 23, 42, 0.12),
    0 18px 38px rgba(15, 23, 42, 0.14);
}

.cat-card-top,
.cat-content {
  position: relative;
  z-index: 1;
}

.cat-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.cat-chip {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.08);
  color: var(--text-main);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.cat-arrow {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(15, 23, 42, 0.1);
  color: var(--text-main);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
}

.cat-photo {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 22px;
  overflow: hidden;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    radial-gradient(circle at top, rgba(255,255,255,0.98), rgba(239,242,247,0.98)),
    linear-gradient(180deg, #ffffff, #f3f5f8);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,0.92),
    0 12px 24px rgba(15, 23, 42, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.cat-photo::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.14), transparent 34%);
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
  padding: 14px;
  box-sizing: border-box;
  transition: transform 0.3s ease;
}

.cat-card:hover .cat-photo img,
.cat-card-link:hover .cat-photo img {
  transform: scale(1.04);
}

.cat-photo-ph {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-light);
  font-size: 28px;
  background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(241,245,249,0.98));
}

.cat-content {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cat-text {
  font-size: 16px;
  font-weight: 900;
  line-height: 1.28;
  color: var(--text-main);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.56em;
}

.cat-meta {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 42px;
  padding: 0 14px;
  border-radius: 14px;
  background: rgba(240, 243, 248, 0.98);
  border: 1px solid rgba(15, 23, 42, 0.08);
  color: var(--text-main);
  font-size: 13px;
  font-weight: 900;
}

.cat-meta :deep(svg) {
  color: var(--text-main);
}

.cats-empty {
  padding: 18px;
  border: 1px dashed var(--border-soft);
  border-radius: 20px;
  background: var(--bg-panel);
  color: var(--text-muted);
  font-weight: 900;
}

@media (max-width: 1180px) {
  .cats-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
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
    border-radius: 24px;
    gap: 12px;
  }

  .cat-photo {
    border-radius: 18px;
  }

  .cat-text {
    font-size: 15px;
  }
}

@media (max-width: 560px) {
  .cats-root {
    gap: 14px;
  }

  .cats-head {
    align-items: flex-start;
  }

  .cats-kicker {
    padding: 7px 12px;
    font-size: 10px;
  }

  .cats-title {
    font-size: 24px;
  }

  .cats-sub {
    font-size: 13px;
  }

  .cats-note {
    padding: 13px 14px;
    gap: 12px;
    border-radius: 18px;
  }

  .cats-note-title {
    font-size: 13px;
  }

  .cats-note-text {
    font-size: 12px;
  }

  .cats-grid {
    gap: 12px;
  }

  .cat-card,
  .cat-card-link {
    padding: 10px;
    border-radius: 20px;
    gap: 10px;
  }

  .cat-chip {
    min-height: 24px;
    padding: 0 10px;
    font-size: 10px;
  }

  .cat-arrow {
    width: 30px;
    height: 30px;
    flex-basis: 30px;
  }

  .cat-photo {
    border-radius: 16px;
  }

  .cat-photo img {
    padding: 10px;
  }

  .cat-text {
    font-size: 13px;
    min-height: 2.5em;
  }

  .cat-meta {
    min-height: 36px;
    padding: 0 12px;
    font-size: 12px;
  }
}

@media (max-width: 380px) {
  .cats-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .cat-card,
  .cat-card-link {
    border-radius: 18px;
  }
}
</style>
