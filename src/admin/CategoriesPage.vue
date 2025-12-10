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

  const res = await fetch("/api/admin/create_category.php", {
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

<style scoped>
.admin-page {
  padding: 30px;
  max-width: 550px;
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
  padding: 22px;
  border-radius: 14px;
  backdrop-filter: blur(6px);
  box-shadow: 0 0 20px rgba(0,0,0,0.25);
}

.label {
  font-size: 14px;
  opacity: .8;
  margin-bottom: 4px;
  display: block;
}

.input {
  width: 100%;
  padding: 10px 12px;
  background: #1d1f22;
  border: 1px solid #333;
  border-radius: 8px;
  color: white;
  margin-bottom: 14px;
}
.input:focus {
  outline: none;
  border-color: #4c9fff;
}

.btn {
  background: #4c9fff;
  padding: 12px;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  color: white;
  cursor: pointer;
  width: 100%;
  transition: .2s;
}
.btn:hover {
  background: #3986e0;
}

.success {
  margin-top: 15px;
  padding: 10px;
  background: rgba(60,255,90,0.15);
  border-left: 4px solid #32d14a;
  border-radius: 8px;
}
</style>
