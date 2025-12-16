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

    <div
      class="cat-name"
      :class="{ active: isChecked }"
    >
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

  <!-- ================= MOBILE FULLSCREEN CHILDREN ================= -->
  <div v-if="isMobile && showMobileChildren" class="mobile-cat-overlay">
    <div class="mobile-cat-panel">
      <!-- HEADER -->
      <div class="mobile-cat-header">
        <button class="back-btn" @click="showMobileChildren = false"><i class="fa-solid fa-arrow-left"></i></button>

        <div class="title">
          {{ node.name }}
        </div>
      </div>

      <!-- CHILDREN -->
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

const isMobile = ref(window.innerWidth < 1024);

window.addEventListener("resize", () => {
  isMobile.value = window.innerWidth < 1024;
});

const showMobileChildren = ref(false);

const props = defineProps({
  node: Object,
  selectedCategories: Array,
});

const emit = defineEmits(["toggle-category"]);

const open = ref(false);

const isChecked = computed(() =>
  props.selectedCategories.includes(props.node.code)
);
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

watch(showMobileChildren, (v) => {
  document.body.style.overflow = v ? "hidden" : "";
});
</script>

<style scoped>
.cat-item {
  margin: 6px 0;
}

.cat-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: 6px;
}

.cat-row:hover {
  background: #f0f2f7;
}

.cat-arrow {
  font-size: 13px;
  color: #2563eb;
  transition: transform 0.25s ease;
}

.cat-arrow.open {
  transform: rotate(90deg);
}

.cat-checkbox input {
  cursor: pointer;
}

.cat-name {
  font-size: 18px;
  cursor: pointer;
}

.cat-name.active {
  font-weight: 700;
  color: #2563eb;
}

.cat-children {
  list-style: none;
  padding-left: 18px;
  margin-top: 6px;
  border-left: 1px solid #e4e7ef;
}

@media (max-width: 1024px) {
  .mobile-cat-overlay {
    position: fixed;
    inset: 0;
    background: #fff;
    z-index: 2000;
  }

  .mobile-cat-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 90px 16px;
    overflow-y: auto;
  }

  .mobile-cat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e4e7ef;
    margin-bottom: 12px;
  }

  .mobile-cat-header .title {
    font-size: 18px;
    font-weight: 700;
  }

  .mobile-cat-header .back-btn {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
  }
}
</style>
