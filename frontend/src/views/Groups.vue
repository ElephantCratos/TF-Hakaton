<template>
  <div>
    <PageHeader
      title="Учебные группы"
      description="Управление группами обучения"
    >
      <Button class="gap-2" @click="dialogOpen = true">
        <Plus class="w-4 h-4" />
        Создать группу
      </Button>
    </PageHeader>

    <!-- Search -->
    <div class="relative mb-6 max-w-sm">
      <Search
        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground"
      />

      <Input
        v-model="search"
        placeholder="Поиск по курсу, статусу..."
        class="pl-10"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div
        class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin"
      />
    </div>

    <!-- Empty -->
    <EmptyState
      v-else-if="filtered.length === 0"
      :icon="UsersRound"
      title="Нет групп"
      description="Создайте первую учебную группу"
    >
      <Button variant="outline" class="gap-2" @click="dialogOpen = true">
        <Plus class="w-4 h-4" />
        Создать группу
      </Button>
    </EmptyState>

    <!-- Grid -->
    <div
      v-else
      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
    >
      <div
        v-for="group in filtered"
        :key="group.id"
        class="bg-card rounded-2xl border border-border p-6 hover:shadow-md transition-shadow"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="font-semibold">
              {{ group.course_name || "Курс не указан" }}
            </h3>

            <p class="text-sm text-muted-foreground mt-0.5">
              {{ group.start_date || "—" }} — {{ group.end_date || "—" }}
            </p>
          </div>

          <StatusBadge :status="group.status || 'draft'" />
        </div>

        <!-- Progress -->
        <ProgressBar
          :value="group.average_progress || 0"
          class="mb-4"
        />

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="bg-muted/50 rounded-xl p-3">
            <p class="text-xs text-muted-foreground">Участники</p>
            <p class="text-lg font-semibold">
              {{ group.participant_count || 0 }}
            </p>
          </div>

          <div class="bg-muted/50 rounded-xl p-3">
            <p class="text-xs text-muted-foreground">Стоимость</p>
            <p class="text-lg font-semibold">
              {{ formatPrice(group.total_cost) }} ₽
            </p>
          </div>
        </div>

        <!-- Button -->
        <router-link :to="`/groups/${group.id}`">
          <Button variant="outline" class="w-full gap-2">
            <Eye class="w-4 h-4" />
            Подробнее
          </Button>
        </router-link>
      </div>
    </div>

    <!-- Dialog -->
    <GroupFormDialog
      v-model:open="dialogOpen"
      @saved="load"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { base44 } from "@/api/base44Client";

import {
  UsersRound,
  Plus,
  Search,
  Eye,
} from "lucide-vue-next";

import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import StatusBadge from "@/components/ui/StatusBadge.vue";
import ProgressBar from "@/components/ui/ProgressBar.vue";
import GroupFormDialog from "@/components/groups/GroupFormDialog.vue";

import Button from "@/components/ui/button.vue";
import Input from "@/components/ui/input.vue";

const groups = ref([]);
const loading = ref(true);
const search = ref("");
const dialogOpen = ref(false);

async function load() {
  try {
    loading.value = true;

    const res = await base44.entities.TrainingGroup.list(
      "-created_date",
      100
    );

    groups.value = res || [];
  } catch (e) {
    console.error("Failed to load groups:", e);
    groups.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(load);

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim();

  if (!q) return groups.value;

  return groups.value.filter((g) => {
    return (
      (g.course_name || "").toLowerCase().includes(q) ||
      (g.status || "").toLowerCase().includes(q)
    );
  });
});

function formatPrice(value) {
  if (!value) return "0";
  return Number(value).toLocaleString("ru-RU");
}
</script>