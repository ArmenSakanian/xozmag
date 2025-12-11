<template>
  <li class="cat-item">

    <div class="cat-row">

      <!-- Стрелка -->
      <span
        v-if="node.children && node.children.length"
        class="arrow"
        :class="{ open }"
        @click.stop="toggleOpen"
      ></span>

      <!-- Чекбокс -->
      <label class="cat-check">
        <input
          type="checkbox"
          :value="node.code"
          :checked="selected.includes(node.code)"
          @change="toggleSelf"
        />
        <span class="cat-name">{{ node.name }}</span>
      </label>

    </div>

    <!-- Дочерние -->
    <ul v-if="open && node.children && node.children.length" class="cat-children">
      <CategoryNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :selected="selected"
        @toggle="$emit('toggle', $event)"
      />
    </ul>

  </li>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  node: { type: Object, required: true },
  selected: { type: Array, required: true }
});

const emit = defineEmits(["toggle"]);

const open = ref(false);

function toggleOpen() {
  open.value = !open.value;
}

function toggleSelf() {
  emit("toggle", props.node);
}
</script>

<style scoped>
/* Элемент */
.cat-item {
  margin: 6px 0;
}

/* Основная строка категории */
.cat-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: background .2s;
}

.cat-row:hover {
  background: rgba(255,255,255,0.06);
}

/* СТРЕЛКА */
.arrow {
  width: 10px;
  height: 10px;
  border-right: 2px solid var(--accent-color);
  border-bottom: 2px solid var(--accent-color);
  transform: rotate(45deg);
  cursor: pointer;
  transition: .25s;
}

.arrow.open {
  transform: rotate(-135deg);
}

/* ЧЕКБОКС */
.cat-check {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cat-check input {
  appearance: none;
  width: 18px;
  height: 18px;
  border: 2px solid #666;
  background: #1a1b1f;
  border-radius: 4px;
  cursor: pointer;
  transition: .15s;
}

.cat-check input:hover {
  border-color: var(--accent-color);
}

.cat-check input:checked {
  background: var(--accent-color);
  border-color: var(--accent-color);
}

.cat-check input:checked::after {
  content: "✔";
  display: block;
  text-align: center;
  color: #000;
  font-size: 12px;
  font-weight: 900;
  line-height: 16px;
}

/* Название категории */
.cat-name {
  color: white;
  font-size: 15px;
  user-select: none;
}

/* Дети */
.cat-children {
  list-style: none;
  padding-left: 18px;
  margin-top: 6px;
  border-left: 1px solid #2c2f36;
}
</style>
