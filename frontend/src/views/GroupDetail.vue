<template>
  <div>
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <router-link to="/groups">
        <Button variant="ghost" size="icon" class="h-9 w-9">
          <ArrowLeft class="w-5 h-5" />
        </Button>
      </router-link>

      <div class="flex-1">
        <h1 class="text-2xl font-bold">
          {{ group?.course_name || "Группа" }}
        </h1>
        <p class="text-sm text-muted-foreground">
          {{ group?.start_date }} — {{ group?.end_date }}
        </p>
      </div>

      <StatusBadge :status="group?.status" />

      <Button variant="outline" class="gap-2" @click="editDialogOpen = true">
        <Pencil class="w-4 h-4" /> Редактировать
      </Button>
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <div v-else-if="!group" class="text-center py-20 text-muted-foreground">
      Группа не найдена
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- LEFT -->
      <div class="lg:col-span-2">
        <div class="bg-card rounded-2xl border border-border">

          <div class="flex items-center justify-between px-6 py-4 border-b border-border">
            <h2 class="font-semibold">
              Участники группы ({{ groupParticipants.length }})
            </h2>

            <Button size="sm" class="gap-2" @click="selectorOpen = true">
              <UserPlus class="w-4 h-4" /> Добавить
            </Button>
          </div>

          <div v-if="groupParticipants.length === 0" class="p-8 text-center text-muted-foreground text-sm">
            Нет участников в группе
          </div>

          <div v-else class="divide-y divide-border">
            <div
              v-for="gp in groupParticipants"
              :key="gp.id"
              class="px-6 py-4 flex items-center gap-4"
            >
              <div class="flex-1 min-w-0">
                <p class="font-medium truncate">
                  {{ gp.participant_name }}
                </p>
              </div>

              <div class="w-48">
                <Slider
                  :model-value="gp.completion_percent || 0"
                  :min="0"
                  :max="100"
                  :step="5"
                  @update:modelValue="(v) => updateCompletion(gp.id, v)"
                />
              </div>

              <span class="text-sm font-medium w-12 text-right">
                {{ gp.completion_percent || 0 }}%
              </span>

              <Button
                size="icon"
                variant="ghost"
                class="h-8 w-8 text-destructive"
                @click="removeParticipant(gp.id)"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </Button>
            </div>
          </div>

        </div>
      </div>

      <!-- RIGHT -->
      <div class="space-y-6">

        <div class="bg-card rounded-2xl border border-border p-6">
          <h3 class="font-semibold mb-4">Общий прогресс</h3>

          <div class="text-4xl font-bold text-primary mb-2">
            {{ averageProgress }}%
          </div>

          <ProgressBar :value="averageProgress" size="lg" :showLabel="false" />
        </div>

        <CostSummary
          :pricePerPerson="group.price_per_person || 0"
          :participantCount="groupParticipants.length"
        />

      </div>
    </div>

    <!-- dialogs -->
    <GroupFormDialog
      v-model:open="editDialogOpen"
      :editingGroup="group"
      @saved="load"
    />

    <ParticipantSelector
      v-model:open="selectorOpen"
      :existingIds="groupParticipants.map(gp => gp.participant_id)"
      @select="handleAddParticipants"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { base44 } from "@/api/base44Client";

import { ArrowLeft, Pencil, Trash2, UserPlus } from "lucide-vue-next";

import  Button  from "@/components/ui/button.vue";
import Slider from "@/components/ui/Slider.vue";

import StatusBadge from "@/components/ui/StatusBadge.vue";
import ProgressBar from "@/components/ui/ProgressBar.vue";
import CostSummary from "@/components/groups/CostSummary.vue";

import GroupFormDialog from "@/components/groups/GroupFormDialog.vue";
import ParticipantSelector from "@/components/groups/ParticipantSelector.vue";

import { toast } from '@/composables/use-toast'


const route = useRoute();

const id = route.params.id;

const group = ref(null);
const groupParticipants = ref([]);
const loading = ref(true);

const editDialogOpen = ref(false);
const selectorOpen = ref(false);

const load = async () => {
  const [g, gp] = await Promise.all([
    base44.entities.TrainingGroup.list().then((l) =>
      l.find((x) => x.id === id)
    ),
    base44.entities.GroupParticipant.filter({ group_id: id })
  ]);

  group.value = g;
  groupParticipants.value = gp;
  loading.value = false;
};

onMounted(load);

// average progress
const averageProgress = computed(() => {
  if (!groupParticipants.value.length) return 0;

  const sum = groupParticipants.value.reduce(
    (acc, gp) => acc + (gp.completion_percent || 0),
    0
  );

  return Math.round(sum / groupParticipants.value.length);
});

// sync group stats
watch(
  [groupParticipants, averageProgress],
  async () => {
    if (!group.value) return;

    const count = groupParticipants.value.length;
    const cost = (group.value.price_per_person || 0) * count;

    await base44.entities.TrainingGroup.update(group.value.id, {
      participant_count: count,
      total_cost: cost,
      average_progress: averageProgress.value
    });
  },
  { deep: true }
);

const updateCompletion = async (gpId, value) => {
  // value уже число, а не массив
  groupParticipants.value = groupParticipants.value.map((gp) =>
    gp.id === gpId ? { ...gp, completion_percent: value } : gp
  )
  await base44.entities.GroupParticipant.update(gpId, { completion_percent: value })
}

const removeParticipant = async (gpId) => {
  await base44.entities.GroupParticipant.delete(gpId);
  toast({ title: "Участник удалён из группы" });
  load();
};

const handleAddParticipants = async (participantIds) => {
  const existing = new Set(groupParticipants.value.map((g) => g.participant_id));

  const newIds = participantIds.filter((id) => !existing.has(id));

  if (!newIds.length) {
    toast({ title: "Участники уже в группе", variant: "destructive" });
    return;
  }

  const all = await base44.entities.Participant.list("-created_date", 500);

  const records = newIds.map((pid) => {
    const p = all.find((x) => x.id === pid);

    return {
      group_id: id,
      participant_id: pid,
      participant_name: p?.full_name || "",
      completion_percent: 0
    };
  });

  await base44.entities.GroupParticipant.bulkCreate(records);

  toast({ title: `Добавлено участников: ${records.length}` });

  selectorOpen.value = false;
  load();
};
</script>