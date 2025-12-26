<template>
  <li class="cat-item" :class="{ open, checked: isChecked, active: isActivePath }">
    <div class="cat-row">
      <!-- toggle (место всегда есть) -->
      <button
        class="cat-toggle"
        :class="{ ghost: !hasKids }"
        :disabled="!hasKids"
        @click.stop="hasKids && toggleOpen()"
        :aria-expanded="open ? 'true' : 'false'"
        title="Открыть/закрыть"
      >
        <Fa :class="{ open }" :icon="['fas','chevron-right']" />
      </button>

      <!-- checkbox -->
      <label class="cat-check" @click.stop>
        <input
          type="checkbox"
          class="cat-checkbox"
          :checked="isChecked"
          @change.stop="toggleCheck"
        />
        <span class="cat-check-ui"></span>
      </label>

      <!-- name -->
      <div
        class="cat-name"
        :class="{ active: isChecked || isActivePath }"
        @click="toggleCheck"
        :title="node.name"
      >
        {{ node.name }}
      </div>
    </div>

    <transition name="cat-slide">
      <ul v-if="open && hasKids" class="cat-children">
        <CategoryNode
          v-for="child in node.children"
          :key="child.id"
          :node="child"
          :selectedCategories="selectedCategories"
          :activeCat="activeCat"
          @toggle-category="$emit('toggle-category', $event)"
        />
      </ul>
    </transition>
  </li>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  node: { type: Object, required: true },
  selectedCategories: { type: Array, required: true },
  // ✅ текущая категория из URL (?cat=...)
  activeCat: { type: [String, null], default: null },
});

const emit = defineEmits(["toggle-category"]);

const open = ref(false);

const hasKids = computed(
  () => Array.isArray(props.node?.children) && props.node.children.length > 0
);

const isChecked = computed(() =>
  props.selectedCategories.includes(props.node.code)
);

// ✅ подсветка активной ветки (если ?cat=... находится внутри этой ветки)
const isActivePath = computed(() => {
  if (!props.activeCat) return false;
  return String(props.activeCat).startsWith(String(props.node.code));
});

// ✅ если где-то внутри выбраны подкатегории — тоже считаем «ветка активна» для автораскрытия
const hasCheckedDescendant = computed(() => {
  const prefix = String(props.node.code);
  return (props.selectedCategories || []).some((c) => String(c).startsWith(prefix));
});

// ✅ авто-раскрытие активной ветки / выбранной ветки
watch(
  [isActivePath, hasCheckedDescendant, hasKids],
  ([a, d, kids]) => {
    if (!kids) return;
    if (a || d) open.value = true;
  },
  { immediate: true }
);

function toggleOpen() {
  open.value = !open.value;
}

function toggleCheck() {
  emit("toggle-category", props.node.code);
}
</script>

<style scoped>
/* ===== item ===== */
.cat-item {
  position: relative;
  margin: 2px 0;
  min-width: 0;
}

/* ===== row ===== */
.cat-row {
  display: grid;
  grid-template-columns: 22px 22px 1fr; /* toggle | checkbox | name */
  align-items: center;
  gap: 8px;

  padding: 6px 6px;
  border-radius: var(--radius-md);

  min-width: 0;
  user-select: none;
}

.cat-row:hover {
  background: var(--bg-soft);
}

/* ✅ активная ветка (когда пришли по ?cat=...) */
.cat-item.active > .cat-row {
  background: rgba(4, 0, 255, 0.06);
}

/* когда выбрано — лёгкая подсветка текста */
.cat-item.checked > .cat-row {
  background: transparent;
}
.cat-item.checked .cat-name {
  color: var(--accent);
  font-weight: 650;
}

/* ===== toggle ===== */
.cat-toggle {
  width: 22px;
  height: 22px;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm);

  display: inline-flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;
  color: var(--text-muted);
  padding: 0;
}

.cat-toggle:hover {
  background: var(--bg-soft);
}

.cat-toggle i {
  font-size: 12px;
  transition: transform 0.18s ease;
  color: var(--accent);
  opacity: 0.85;
}

.cat-toggle i.open {
  transform: rotate(90deg);
}

/* место под стрелку сохраняем, но не показываем */
.cat-toggle.ghost {
  visibility: hidden;
  pointer-events: none;
}

/* ===== checkbox ===== */
.cat-check {
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

/* скрываем реальный checkbox */
.cat-checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.cat-check-ui {
  width: 16px;
  height: 16px;
  border-radius: 6px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.cat-checkbox:checked + .cat-check-ui {
  border-color: var(--accent);
  background: var(--bg-soft);
}

.cat-checkbox:checked + .cat-check-ui::after {
  content: "";
  width: 8px;
  height: 4px;
  border-left: 2px solid var(--accent);
  border-bottom: 2px solid var(--accent);
  transform: rotate(-45deg);
  margin-top: -1px;
}

.cat-check:focus-within .cat-check-ui {
  box-shadow: 0 0 0 3px rgba(4, 0, 255, 0.12);
}

/* ===== name ===== */
.cat-name {
  min-width: 0;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 500;
  line-height: 1.25;

  white-space: normal;
  overflow-wrap: anywhere;
}

.cat-name.active {
  color: var(--text-main);
  font-weight: 650;
}

/* ===== children tree ===== */
.cat-children {
  list-style: none;
  margin: 4px 0 4px 0;
  padding: 0 0 0 18px;
  position: relative;
}

/* вертикальная линия */
.cat-children::before {
  content: "";
  position: absolute;
  top: 0;
  bottom: 8px;
  left: 8px;
  width: 1px;
  background: var(--border-soft);
}

/* горизонтальная ветка к каждому ребёнку */
.cat-children > .cat-item::before {
  content: "";
  position: absolute;
  top: 16px;
  left: -10px;
  width: 18px;
  height: 1px;
  background: var(--border-soft);
}

/* ===== animation ===== */
.cat-slide-enter-active,
.cat-slide-leave-active {
  transition: max-height 0.2s ease, opacity 0.15s ease;
  overflow: hidden;
}
.cat-slide-enter-from,
.cat-slide-leave-to {
  max-height: 0;
  opacity: 0;
}
.cat-slide-enter-to,
.cat-slide-leave-from {
  max-height: 1200px;
  opacity: 1;
}
</style>
