<script setup>
import { ref, onMounted } from "vue";
import LoginPage from "./components/LoginPage.vue";
import BarcodePage from "./components/BarcodePage.vue";

const isAuth = ref(false);

onMounted(() => {
  const token = localStorage.getItem("token");
  if (token) isAuth.value = true;
});

function loginSuccess() {
  isAuth.value = true;
}
</script>

<template>
  <LoginPage v-if="!isAuth" @success="loginSuccess" />
  <BarcodePage v-else />
</template>
