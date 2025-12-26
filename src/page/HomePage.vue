<template>
  <section class="home-entry">
    <HomeSearch
      :show-category="false"
      :sync-route="false"
      catalog-path="/catalogv2"
    />
  </section>

  <Aboutus />
  <Contact />
  <StoreConditions />
  <PhotoGallery />
</template>

<script setup lang="ts">
import HomeSearch from "@/components/HomeSearch.vue";
import Aboutus from "@/components/Aboutus.vue";
import Contact from "@/components/Contact.vue";
import StoreConditions from "@/components/StoreConditions.vue";
import PhotoGallery from "@/components/PhotoGallery.vue";
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
