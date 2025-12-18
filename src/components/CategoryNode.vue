<template>
  <li class="cat-item" :class="{ open, checked: isChecked }">
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

  <!-- name -->
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
/* Спокойное дерево: без карточек, без жирных блоков */
:global(:root){
  --cat-accent: #0400ff;
  --cat-text: #1b1e28;
  --cat-muted: #6b7280;
  --cat-line: #d9dee8;
  --cat-hover: rgba(4,0,255,0.06);
}

/* ===== item ===== */
.cat-item{
  position: relative;
  margin: 2px 0;
  min-width: 0;
}

/* ===== row ===== */
.cat-row{
  display: grid;
  grid-template-columns: 22px 22px 1fr; /* toggle | checkbox | name */
  align-items: center;
  gap: 8px;

  padding: 6px 6px;
  border-radius: 10px;

  min-width: 0;
  user-select: none;
}

.cat-row:hover{
  background: var(--cat-hover);
}

/* когда выбрано — только лёгкая подсветка текста (не контейнер) */
.cat-item.checked > .cat-row{
  background: transparent;
}
.cat-item.checked .cat-name{
  color: var(--cat-accent);
  font-weight: 650;
}

/* ===== toggle ===== */
.cat-toggle{
  width: 22px;
  height: 22px;
  border: none;
  background: transparent;
  border-radius: 6px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;
  color: var(--cat-muted);
  padding: 0;
}

.cat-toggle:hover{
  background: rgba(0,0,0,0.05);
}

.cat-toggle i{
  font-size: 12px;
  transition: transform .18s ease;
  color: var(--cat-accent);
  opacity: .85;
}

.cat-toggle i.open{
  transform: rotate(90deg);
}

/* место под стрелку сохраняем, но не показываем */
.cat-toggle.ghost{
  visibility: hidden;
  pointer-events: none;
}

/* ===== checkbox ===== */
.cat-check{
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

/* скрываем реальный checkbox */
.cat-checkbox{
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.cat-check-ui{
  width: 16px;
  height: 16px;
  border-radius: 6px;
  border: 1px solid #cfd6e4;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: border-color .15s ease, background .15s ease;
}

.cat-checkbox:checked + .cat-check-ui{
  border-color: rgba(4,0,255,0.35);
  background: rgba(4,0,255,0.10);
}

.cat-checkbox:checked + .cat-check-ui::after{
  content: "";
  width: 8px;
  height: 4px;
  border-left: 2px solid var(--cat-accent);
  border-bottom: 2px solid var(--cat-accent);
  transform: rotate(-45deg);
  margin-top: -1px;
}

/* ===== name ===== */
.cat-name{
  min-width: 0;
  color: var(--cat-text);
  font-size: 14px;
  font-weight: 500;     /* спокойно */
  line-height: 1.25;

  /* важно: длинные названия НЕ режем, переносим */
  white-space: normal;
  overflow-wrap: anywhere;
}

/* ===== children tree ===== */
.cat-children{
  list-style: none;
  margin: 4px 0 4px 0;
  padding: 0 0 0 18px;
  position: relative;
}

/* вертикальная линия */
.cat-children::before{
  content:"";
  position:absolute;
  top: 0;
  bottom: 8px;
  left: 8px;
  width: 1px;
  background: var(--cat-line);
}

/* горизонтальная ветка к каждому ребёнку */
.cat-children > .cat-item::before{
  content:"";
  position:absolute;
  top: 16px;
  left: -10px;
  width: 18px;
  height: 1px;
  background: var(--cat-line);
}

/* ===== animation ===== */
.cat-slide-enter-active,
.cat-slide-leave-active{
  transition: max-height .2s ease, opacity .15s ease;
  overflow: hidden;
}
.cat-slide-enter-from,
.cat-slide-leave-to{
  max-height: 0;
  opacity: 0;
}
.cat-slide-enter-to,
.cat-slide-leave-from{
  max-height: 1200px;
  opacity: 1;
}
</style>