<template>
  <div>
    <PageHeader
      title="Панель управления"
      description="Обзор системы управления обучением"
    />

    <!-- LOADING -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- STATS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <StatCard title="Курсы" :value="courses.length" :icon="BookOpen" />
        <StatCard title="Участники" :value="participants.length" :icon="Users" />

        <StatCard
          title="Группы"
          :value="groups.length"
          :subtitle="`${activeGroups} активных`"
          :icon="UsersRound"
        />

        <StatCard title="Компании" :value="companies.length" :icon="Building2" />
        <StatCard title="Спецификации" :value="specs.length" :icon="FileText" />
      </div>

      <!-- RECENT GROUPS -->
      <div class="bg-card rounded-2xl border border-border">
        <div class="flex items-center justify-between px-6 py-4 border-b border-border">
          <h2 class="text-lg font-semibold">Последние группы</h2>

          <router-link
            to="/groups"
            class="text-sm text-primary hover:underline flex items-center gap-1"
          >
            Все группы <ArrowRight class="w-4 h-4" />
          </router-link>
        </div>

        <div v-if="recentGroups.length === 0" class="p-8 text-center text-muted-foreground text-sm">
          Группы пока не созданы
        </div>

        <div v-else class="divide-y divide-border">
          <router-link
            v-for="group in recentGroups"
            :key="group.id"
            :to="`/groups/${group.id}`"
            class="flex items-center gap-4 px-6 py-4 hover:bg-muted/50 transition-colors"
          >
            <div class="flex-1 min-w-0">
              <p class="font-medium truncate">
                {{ group.course_name || "Курс не указан" }}
              </p>

              <p class="text-sm text-muted-foreground">
                {{ group.start_date }} — {{ group.end_date }}
              </p>
            </div>

            <StatusBadge :status="group.status" />

            <div class="w-32 hidden sm:block">
              <ProgressBar :value="group.average_progress || 0" size="sm" />
            </div>
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { base44 } from "@/api/base44Client";

import {
  BookOpen,
  Users,
  UsersRound,
  Building2,
  FileText,
  ArrowRight
} from "lucide-vue-next";

import PageHeader from "@/components/ui/PageHeader.vue";
import StatCard from "@/components/ui/StatCard.vue";
import StatusBadge from "@/components/ui/StatusBadge.vue";
import ProgressBar from "@/components/ui/ProgressBar.vue";

const courses = ref([]);
const participants = ref([]);
const groups = ref([]);
const companies = ref([]);
const specs = ref([]);

const loading = ref(true);

const load = async () => {
  const [c, p, g, co, s] = await Promise.all([
    base44.entities.Course.list(),
    base44.entities.Participant.list(),
    base44.entities.TrainingGroup.list("-created_date", 50),
    base44.entities.Company.list(),
    base44.entities.Specification.list()
  ]);

  courses.value = c;
  participants.value = p;
  groups.value = g;
  companies.value = co;
  specs.value = s;

  loading.value = false;
};

onMounted(load);

// last 5 groups
const recentGroups = computed(() => groups.value.slice(0, 5));

// active groups
const activeGroups = computed(() =>
  groups.value.filter((g) => g.status === "В процессе").length
);
</script>