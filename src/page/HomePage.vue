<template>
      <PhotoGallery />


  <Aboutus />
  <Contact />
</template>

<script setup lang="ts">
import PhotoGallery from "@/components/PhotoGallery.vue";
import HomeSearch from "@/components/HomeSearch.vue";
import Aboutus from "@/components/Aboutus.vue";
import Contact from "@/components/Contact.vue";

</script>

<style scoped>
.home-entry {
  width: min(1120px, 100%);
  margin: 0 auto;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

@media (max-width: 480px) {
  .home-entry {
    padding: 6px 6px;
    gap: 14px;
  }
}
</style>
