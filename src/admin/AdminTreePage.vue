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
  const res = await fetch("/api/admin/get_categories_tree.php");
  categories.value = await res.json();
  buildTree();
}

onMounted(load);

function buildTree() {
  const map = {};
  const roots = [];

  // создаём карту id → node
  categories.value.forEach(cat => {
    map[cat.id] = { ...cat, children: [] };
  });

  // добавляем каждому детей
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

<style scoped>
.admin-page {
  padding: 30px;
  max-width: 700px;
  margin: auto;
  color: white;
}

.title {
  font-size: 26px;
  margin-bottom: 20px;
  font-weight: 600;
}

.card {
  background: rgba(38,40,44,0.6);
  padding: 20px;
  border-radius: 14px;
  backdrop-filter: blur(6px);
  box-shadow: 0 0 20px rgba(0,0,0,0.25);
}
</style>
