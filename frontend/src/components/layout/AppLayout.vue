<template>
  <div class="flex">
    <Sidebar v-model:collapsed="collapsed" />

    <main
      class="flex-1 p-6 transition-all duration-300"
      :style="{
        marginLeft: collapsed ? '72px' : '260px'
      }"
    >
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref } from "vue";
import Sidebar from "@/components/layout/Sidebar.vue";

const collapsed = ref(false);
</script>