<template>
  <li class="cat-item">
    <div
      class="cat-row"
      @click="node.children?.length && toggleOpen()"
    >
      <i
        v-if="node.children?.length"
        class="fa-solid fa-chevron-right cat-arrow"
        :class="{ open }"
      ></i>

      <input
        type="checkbox"
        class="cat-checkbox"
        :checked="isChecked"
        @change.stop="toggleCheck"
      />

      <div class="cat-name" :class="{ active: isChecked }">
        {{ node.name }}
      </div>
    </div>

    <!-- DESKTOP CHILDREN -->
    <ul
      v-if="open && node.children?.length && !isMobile"
      class="cat-children"
    >
      <CategoryNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :selectedCategories="selectedCategories"
        @toggle-category="$emit('toggle-category', $event)"
      />
    </ul>
  </li>

  <!-- MOBILE -->
  <div v-if="isMobile && showMobileChildren" class="mobile-cat-overlay">
    <div class="mobile-cat-panel">
      <div class="mobile-cat-header">
        <button class="back-btn" @click="showMobileChildren = false">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="title">{{ node.name }}</div>
      </div>

      <ul class="category-tree-root">
        <CategoryNode
          v-for="child in node.children"
          :key="child.id"
          :node="child"
          :selectedCategories="selectedCategories"
          @toggle-category="$emit('toggle-category', $event)"
        />
      </ul>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from "vue";

/* ================= STATE ================= */
const isMobile = ref(window.innerWidth < 1024);
const showMobileChildren = ref(false);
const open = ref(false);

/* ================= PROPS ================= */
const props = defineProps({
  node: Object,
  selectedCategories: Array,
});

const emit = defineEmits(["toggle-category"]);

const isChecked = computed(() =>
  props.selectedCategories.includes(props.node.code)
);

/* ================= RESIZE ================= */
window.addEventListener("resize", () => {
  isMobile.value = window.innerWidth < 1024;
});

/* ================= TOGGLE ================= */
function toggleOpen() {
  if (isMobile.value) {
    showMobileChildren.value = true;
  } else {
    open.value = !open.value;
  }
}

function toggleCheck() {
  emit("toggle-category", props.node.code);
  showMobileChildren.value = false;
}

/* ================= MOBILE SCROLL LOCK ================= */
watch(showMobileChildren, (v) => {
  document.body.style.overflow = v ? "hidden" : "";
});
</script>
<style scoped>
  /* ===== ITEM ===== */
.cat-item {
  position: relative;
  margin: 6px 0;
}

/* ===== ROW ===== */
.cat-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: 6px;
  position: relative;
}

.cat-row:hover {
  background: #f0f2f7;
}

/* ===== ARROW ===== */
.cat-arrow {
  font-size: 13px;
  color: #2563eb;
  transition: transform 0.25s ease;
}

.cat-arrow.open {
  transform: rotate(90deg);
}

/* ===== NAME ===== */
.cat-name {
  font-size: 16px;
  cursor: pointer;
  position: relative;
}

.cat-name.active {
  font-weight: 700;
  color: #2563eb;
}

/* ===== CHILDREN TREE ===== */
.cat-children {
  list-style: none;
  margin-top: 4px;
  margin-left: 18px;
  padding-left: 24px;
  position: relative;
}

/* вертикальная линия (ствол) */
.cat-children::before {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  left: 6px;
  width: 1px;
  background: #d6dbe6;
}

/* горизонтальная ветка к каждому элементу */
.cat-item::before {
  content: "";
  position: absolute;
  top: 15px;
  left: -18px;
  width: 24px;
  height: 1px;
  background: #000000;
}

/* не рисуем линию у корня */
.category-tree-root > .cat-item::before {
  display: none;
}




</style>