<template>
  <div class="admin-page">

    <h2 class="title">Дерево категорий</h2>

    <div class="card">
      <div v-for="node in tree" :key="node.id">
        <TreeNode :node="node" :depth="0" />
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import TreeNode from "@/components/TreeNode.vue";

const categories = ref([]);
const tree = ref([]);

async function load() {
  const res = await fetch("/api/admin/tree/get_categories_tree.php");
  categories.value = await res.json();
  buildTree();
}

onMounted(load);

function buildTree() {
  const map = {};
  const roots = [];

  categories.value.forEach(cat => {
    map[cat.id] = { ...cat, children: [] };
  });

  categories.value.forEach(cat => {
    if (cat.parent_id === null) {
      roots.push(map[cat.id]);
    } else {
      if (map[cat.parent_id]) {
        map[cat.parent_id].children.push(map[cat.id]);
      }
    }
  });

  tree.value = roots;
}
</script>
