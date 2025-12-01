<template>
  <div class="login-wrap">
    <div class="login-box">
      <h2>Вход</h2>

      <input v-model="login" placeholder="Логин" />
      <input v-model="password" placeholder="Пароль" type="password" />

      <button @click="submit" class="button-main">Войти</button>

      <p v-if="error" class="login-error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const emit = defineEmits(["success"]);

const login = ref("");
const password = ref("");
const error = ref("");

async function submit() {
  error.value = "";

  const form = new FormData();
  form.append("username", login.value);
  form.append("password", password.value);

  const res = await fetch("/api/login.php", {
    method: "POST",
    body: form
  });

  const data = await res.json();

  if (data.status === "success") {
    localStorage.setItem("token", data.token);
    emit("success");
  } else {
    error.value = data.message;
  }
}
</script>

<style>
.login-wrap {
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-box {
  width: 300px;
  padding: 20px;
  border-radius: 12px;
  background: #212121;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.login-box input {
    border-radius: 10px;
    padding: 10px;
    border: none;
    color: black;
}

.login-error {
  color: red;
}
</style>
