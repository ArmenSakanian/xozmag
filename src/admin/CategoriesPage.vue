<template>
  <div class="admin-page">

    <h2 class="title">Создать категорию</h2>

    <div class="card">

      <label class="label">Название категории</label>
      <input 
        v-model="name" 
        class="input"
        placeholder="Введите название..."
      />

      <button class="btn" @click="create">
        Создать категорию
      </button>

      <div v-if="createdCode" class="success">
        Создано: <b>{{ createdCode }}</b>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref } from "vue";

const name = ref("");
const createdCode = ref("");

async function create() {
  if (!name.value.trim()) {
    alert("Введите название");
    return;
  }

  const res = await fetch("/api/admin/categories/create_category.php", {
    method: "POST",
    body: JSON.stringify({ name: name.value })
  });

  const data = await res.json();

  if (data.success) {
    createdCode.value = data.level_code;
    name.value = "";
  } else {
    alert("Ошибка: " + data.error);
  }
}
</script>
