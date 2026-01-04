<template>
  <div class="pcg" :class="{ compact }">
    <!-- если фото нет -->
    <img
      v-if="!first"
      class="pcg-img"
      :src="fallback"
      :alt="alt"
      loading="lazy"
      decoding="async"
      fetchpriority="low"
    />

    <!-- если фото есть -->
    <img
      v-else
      class="pcg-img"
      :src="first"
      :alt="alt"
      loading="lazy"
      decoding="async"
      fetchpriority="low"
    />
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  images: { type: Array, default: () => [] },
  alt: { type: String, default: "" },
  compact: { type: Boolean, default: false }, // оставил, чтобы не ломать вызовы
  fallback: { type: String, default: "/img/no-photo.png" },
});

const first = computed(() => {
  const arr = (props.images || []).filter(Boolean);
  return arr[0] || "";
});
</script>

<style scoped>
.pcg {
  width: 100%;
  height: 100%;
  overflow: hidden;
  min-width: 0;
}

.pcg-img {
  width: 100%;
  height: 100%;
  display: block;
  max-width: 100%;
  object-fit: contain; /* если хочешь заполнение - поменяй на cover */
  object-position: center;
  background: #fff;
}
</style>