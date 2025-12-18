<template>
  <li class="cat-item" :class="{ open, checked: isChecked }">
    <div class="cat-row">
      <!-- toggle -->
      <button
        v-if="hasKids"
        class="cat-toggle"
        @click.stop="toggleOpen"
        :aria-expanded="open ? 'true' : 'false'"
        title="Открыть/закрыть"
      >
        <i class="fa-solid fa-chevron-right" :class="{ open }"></i>
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

      <!-- name (клик тоже выбирает) -->
      <div class="cat-name" :class="{ active: isChecked }" @click="toggleCheck" :title="node.name">
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
          @toggle-category="$emit('toggle-category', $event)"
        />
      </ul>
    </transition>
  </li>
</template>
<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  node: { type: Object, required: true },
  selectedCategories: { type: Array, required: true },
});

const emit = defineEmits(["toggle-category"]);

const open = ref(false);

const hasKids = computed(() => Array.isArray(props.node?.children) && props.node.children.length > 0);

const isChecked = computed(() => props.selectedCategories.includes(props.node.code));

function toggleOpen() {
  open.value = !open.value;
}

function toggleCheck() {
  emit("toggle-category", props.node.code);
}
</script>
<style scoped>
/* ===== base item ===== */
.cat-item {
  position: relative;
  margin: 6px 0;
}

/* ===== row (card-like) ===== */
.cat-row {
  display: grid;
  grid-template-columns: 30px 34px 1fr;
  align-items: center;
  gap: 10px;

  padding: 10px 10px;
  border-radius: 12px;

  border: 1px solid transparent;
  background: transparent;

  transition: background 0.18s ease, border-color 0.18s ease, transform 0.12s ease;
}

.cat-row:hover {
  background: rgba(4, 0, 255, 0.05);
  border-color: rgba(4, 0, 255, 0.10);
}

/* subtle “selected” background */
.cat-item.checked > .cat-row {
  background: rgba(4, 0, 255, 0.08);
  border-color: rgba(4, 0, 255, 0.16);
}

/* ===== toggle button ===== */
.cat-toggle {
  width: 30px;
  height: 30px;
  border-radius: 10px;

  border: 1px solid rgba(228, 231, 239, 1);
  background: #fff;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;
  transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.18s ease;
}

.cat-toggle:hover {
  border-color: rgba(4, 0, 255, 0.22);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
}

.cat-toggle i {
  font-size: 12px;
  color: rgba(4, 0, 255, 0.85);
  transition: transform 0.18s ease;
}

.cat-toggle i.open {
  transform: rotate(90deg);
}

/* ===== custom checkbox ===== */
.cat-check {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.cat-checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.cat-check-ui {
  width: 20px;
  height: 20px;
  border-radius: 8px;

  border: 1.5px solid rgba(228, 231, 239, 1);
  background: #fff;

  display: inline-block;
  position: relative;
  transition: background 0.18s ease, border-color 0.18s ease, transform 0.12s ease;
}

.cat-row:hover .cat-check-ui {
  border-color: rgba(4, 0, 255, 0.22);
}

.cat-checkbox:checked + .cat-check-ui {
  background: rgba(4, 0, 255, 0.10);
  border-color: rgba(4, 0, 255, 0.40);
}

.cat-checkbox:checked + .cat-check-ui::after {
  content: "✓";
  position: absolute;
  left: 50%;
  top: 48%;
  transform: translate(-50%, -50%);
  font-size: 13px;
  font-weight: 900;
  color: rgba(4, 0, 255, 1);
}

/* ===== name ===== */
.cat-name {
  font-size: 14px;
  font-weight: 850;
  color: #1b1e28;
  line-height: 1.25;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  cursor: pointer;
  user-select: none;
}

.cat-name.active {
  color: rgba(4, 0, 255, 1);
}

/* ===== children tree ===== */
.cat-children {
  list-style: none;
  margin: 8px 0 2px;
  padding-left: 22px;
  position: relative;
}

/* vertical “tree line” */
.cat-children::before {
  content: "";
  position: absolute;
  top: 0;
  bottom: 10px;
  left: 10px;
  width: 1px;
  background: rgba(228, 231, 239, 1);
}

/* horizontal branch line for each child item */
.cat-children :deep(.cat-item)::before {
  content: "";
  position: absolute;
  top: 26px;
  left: -12px;
  width: 18px;
  height: 1px;
  background: rgba(228, 231, 239, 1);
}

/* no branch line on root items */
:deep(.category-tree-root > .cat-item)::before {
  display: none;
}

/* ===== nice open animation ===== */
.cat-slide-enter-active,
.cat-slide-leave-active {
  transition: max-height 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
}
.cat-slide-enter-from,
.cat-slide-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-4px);
}
.cat-slide-enter-to,
.cat-slide-leave-from {
  max-height: 800px;
  opacity: 1;
  transform: translateY(0);
}
</style>