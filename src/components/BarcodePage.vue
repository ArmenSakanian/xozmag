<template>
  <div class="container">
    <h2>Создание штрих-кода</h2>

    <!-- Поля -->
    <div class="form">
      <input
        v-model="productName"
        placeholder="Название товара"
        class="input"
        type="text"
      />

      <input
        v-model="sku"
        placeholder="Артикул"
        class="input"
        type="text"
      />

      <input
        v-model="contractor"
        placeholder="Контрагент"
        class="input"
        type="text"
      />

      <!-- Кнопка -->
      <button class="btn" @click="createBarcode">
        Создать штрих-код
      </button>
    </div>

    <!-- Показ сгенерированного кода -->
    <div v-if="generatedCode" class="result">
      <p>Сгенерированный штрих-код:</p>
      <h3>{{ generatedCode }}</h3>
    </div>

    <!-- Ошибка -->
    <p v-if="error" class="error">{{ error }}</p>

    <!-- Успех -->
    <p v-if="success" class="success">Успешно сохранено!</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const productName = ref("");
const sku = ref("");
const contractor = ref("");
const generatedCode = ref("");
const error = ref("");
const success = ref(false);

// Генерация 13-значного кода
function generateBarcode() {
  let code = "";
  for (let i = 0; i < 13; i++) {
    code += Math.floor(Math.random() * 10).toString();
  }
  return code;
}

async function createBarcode() {
  error.value = "";
  success.value = false;

  // Проверка — хотя бы одно поле не пустое
  if (!productName.value && !sku.value) {
    error.value = "Введите название товара или артикул (достаточно одного).";
    return;
  }

  // Генерация штрихкода
  generatedCode.value = generateBarcode();

  // Подготовка данных
  const payload = {
    barcode: generatedCode.value,
    product_name: productName.value,
    sku: sku.value,
    contractor: contractor.value,
  };

  try {
    // Запрос к PHP API
    const response = await fetch("/api/create_barcode.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!data.success) {
      error.value = data.message || "Ошибка при сохранении.";
      return;
    }

    success.value = true;

  } catch (e) {
    error.value = "Ошибка подключения к серверу.";
  }
}
</script>

<style scoped>
.container {
  max-width: 450px;
  margin: 40px auto;
  padding: 20px;
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 0 12px rgba(0,0,0,0.1);
}

h2 {
  text-align: center;
  margin-bottom: 20px;
  font-weight: 600;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.input {
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #d9d9d9;
  font-size: 16px;
}

.btn {
  padding: 12px;
  background: #008cff;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
  font-weight: 600;
}

.btn:hover {
  background: #006bd6;
}

.result {
  margin-top: 20px;
  padding: 12px;
  background: #f5f5f5;
  border-radius: 6px;
  text-align: center;
}

.error {
  margin-top: 15px;
  color: #d60000;
  font-weight: 600;
}

.success {
  margin-top: 15px;
  color: #008a1f;
  font-weight: 600;
}
</style>
