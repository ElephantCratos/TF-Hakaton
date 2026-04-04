<template>
  <div>
    <PageHeader title="График Ганта" description="Визуализация расписания обучения">
      <div class="flex items-center gap-2">
        <Button variant="outline" size="icon" @click="navigate(-1)">
          <ChevronLeft class="w-4 h-4" />
        </Button>

        <!-- Заменяем кастомный Select на обычный -->
        <select v-model="scale" class="h-10 px-3 rounded-md border border-input bg-background text-sm">
          <option v-for="(val, key) in SCALES" :key="key" :value="key">
            {{ val.label }}
          </option>
        </select>

        <Button variant="outline" size="icon" @click="navigate(1)">
          <ChevronRight class="w-4 h-4" />
        </Button>
      </div>
    </PageHeader>

    <EmptyState
      v-if="groups.length === 0"
      :icon="GanttIcon"
      title="Нет данных"
      description="Создайте учебные группы для отображения на графике"
    />

    <div
      v-else
      class="bg-card rounded-2xl border border-border overflow-hidden"
    >
      <div class="flex">
        <!-- LEFT PANEL -->
        <div class="w-64 shrink-0 border-r border-border">
          <div class="h-12 px-4 flex items-center border-b bg-muted/50">
            <span class="text-xs font-medium uppercase text-muted-foreground">Группа</span>
          </div>
          <div
            v-for="group in groups"
            :key="group.id"
            class="h-14 px-4 flex items-center border-b"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ group.course_name || "—" }}</p>
              <p class="text-xs text-muted-foreground">{{ group.participant_count || 0 }} участников</p>
            </div>
          </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="flex-1 overflow-x-auto" ref="scrollRef">
          <div :style="{ width: totalWidth + 'px', minWidth: '100%' }">
            <!-- HEADER -->
            <div class="h-12 relative border-b bg-muted/50">
              <div
                v-for="(col, i) in dateColumns"
                :key="i"
                class="absolute top-0 h-full flex items-center justify-center text-xs text-muted-foreground border-r"
                :style="{ left: col.left + 'px', width: col.width + 'px' }"
              >
                {{ col.label }}
              </div>
            </div>

            <!-- ROWS -->
            <div v-for="group in groups" :key="group.id" class="h-14 relative border-b">
              <!-- GRID LINES -->
              <div
                v-for="(col, i) in dateColumns"
                :key="i"
                class="absolute top-0 h-full border-r border-border/30"
                :style="{ left: col.left + col.width + 'px' }"
              />

              <!-- BAR с нативным title вместо Tooltip -->
              <div
                class="absolute top-3 h-8 rounded-lg cursor-pointer transition-all hover:shadow-md"
                :class="getColors(group.status).track"
                :style="{
                  left: Math.max(0, getBar(group).left) + 'px',
                  width: Math.max(40, getBar(group).width) + 'px',
                }"
                :title="`${group.course_name}\n${group.start_date || '?'} — ${group.end_date || '?'}\nУчастников: ${group.participant_count || 0}\nСтоимость: ${(group.total_cost || 0).toLocaleString('ru-RU')} ₽\nПрогресс: ${group.average_progress || 0}%`"
              >
                <div
                  class="h-full rounded-lg transition-all duration-500"
                  :class="getColors(group.status).bg"
                  :style="{ width: (group.average_progress || 0) + '%' }"
                />
                <span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-white">
                  {{ group.average_progress || 0 }}%
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { base44 } from "@/api/base44Client";
import {
  GanttChart as GanttIcon,
  ChevronLeft,
  ChevronRight,
} from "lucide-vue-next";
import { addDays, differenceInDays, parseISO, isValid, format } from "date-fns";
import { ru } from "date-fns/locale";

import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import Button from "@/components/ui/button.vue";

// Удаляем неиспользуемые импорты Select, Tooltip, StatusBadge

const SCALES = {
  week: { label: "Неделя", days: 7, step: 1 },
  month: { label: "Месяц", days: 30, step: 7 },
  quarter: { label: "Квартал", days: 90, step: 14 },
};

const BAR_COLORS = {
  "Планируется": { bg: "bg-blue-500", track: "bg-blue-200" },
  "В процессе": { bg: "bg-amber-500", track: "bg-amber-200" },
  "Завершено": { bg: "bg-emerald-500", track: "bg-emerald-200" },
};

const DAY_WIDTH = 40;

const groups = ref([]);
const loading = ref(true);
const scale = ref("month");
const viewStart = ref(new Date());
const scrollRef = ref(null);

onMounted(async () => {
  const data = await base44.entities.TrainingGroup.list("-start_date", 200);
  groups.value = data;

  if (data?.length) {
    const dates = data
      .map((g) => g.start_date && parseISO(g.start_date))
      .filter((d) => d && isValid(d));
    if (dates.length) {
      const earliest = new Date(Math.min(...dates.map((d) => d.getTime())));
      viewStart.value = addDays(earliest, -7);
    }
  }
  loading.value = false;
});

const scaleConfig = computed(() => SCALES[scale.value]);
const totalWidth = computed(() => scaleConfig.value.days * DAY_WIDTH);

const dateColumns = computed(() => {
  const cols = [];
  for (let i = 0; i < scaleConfig.value.days; i += scaleConfig.value.step) {
    const date = addDays(viewStart.value, i);
    cols.push({
      left: i * DAY_WIDTH,
      width: scaleConfig.value.step * DAY_WIDTH,
      label: format(date, scale.value === "week" ? "dd.MM" : "dd MMM", { locale: ru }),
    });
  }
  return cols;
});

function navigate(dir) {
  const amount = Math.floor(scaleConfig.value.days / 2);
  viewStart.value = addDays(viewStart.value, dir * amount);
}

function getColors(status) {
  return BAR_COLORS[status] || BAR_COLORS["Планируется"];
}

function getBar(group) {
  const start = group.start_date ? parseISO(group.start_date) : null;
  const end = group.end_date ? parseISO(group.end_date) : null;
  if (!start || !end || !isValid(start) || !isValid(end)) {
    return { left: 0, width: 0 };
  }
  const left = differenceInDays(start, viewStart.value) * DAY_WIDTH;
  const width = Math.max(1, differenceInDays(end, start)) * DAY_WIDTH;
  return { left, width };
}
</script>