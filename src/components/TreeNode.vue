<template>
  <div class="node">

    <div 
      class="node-line"
      :style="{ marginLeft: depth * 18 + 'px' }"
      @click="toggle"
    >
      <span v-if="hasChildren" class="arrow">
        {{ open ? "▼" : "▶" }}
      </span>
      <span v-else class="arrow-empty"></span>

      <span class="code">{{ node.level_code }}</span>
      <span class="name">{{ node.name }}</span>
    </div>

    <div v-if="open">
      <TreeNode 
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :depth="depth + 1"
      />
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  node: Object,
  depth: { type: Number, default: 0 }
});

const open = ref(true);

const hasChildren = computed(() => props.node.children?.length > 0);

function toggle() {
  if (hasChildren.value) {
    open.value = !open.value;
  }
}
</script>

<style scoped>
.node {
  margin: 2px 0;
}

.node-line {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: 6px;
  transition: 0.15s;
  cursor: pointer;
}

.node-line:hover {
  background: rgba(255, 255, 255, 0.07);
}

.arrow, .arrow-empty {
  width: 16px;
  display: inline-block;
}

.code {
  font-weight: 600;
  color: #4c9fff;
}

.name {
  opacity: 0.9;
  font-size: 15px;
}
</style>
