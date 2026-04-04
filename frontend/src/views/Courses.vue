<template>
  <div>
    <PageHeader title="Курсы обучения" description="Управление каталогом курсов">
      <Button @click="openCreate" class="gap-2">
        <Plus class="w-4 h-4" /> Создать курс
      </Button>
    </PageHeader>

    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input
        v-model="search"
        placeholder="Поиск по названию..."
        class="pl-10"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <EmptyState
        v-if="filtered.length === 0"
        :icon="BookOpen"
        title="Нет курсов"
        description="Создайте первый курс для начала работы"
      >
        <Button @click="openCreate" variant="outline" class="gap-2">
          <Plus class="w-4 h-4" /> Создать курс
        </Button>
      </EmptyState>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="course in filtered"
          :key="course.id"
          class="bg-card rounded-2xl border border-border p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <BookOpen class="w-5 h-5 text-primary" />
            </div>

            <div class="flex gap-1">
              <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(course)">
                <Pencil class="w-3.5 h-3.5" />
              </Button>

              <Button
                size="icon"
                variant="ghost"
                class="h-8 w-8 text-destructive"
                @click="handleDelete(course.id)"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </Button>
            </div>
          </div>

          <h3 class="font-semibold text-lg mb-1">
            {{ course.name }}
          </h3>

          <p
            v-if="course.description"
            class="text-sm text-muted-foreground line-clamp-2 mb-4"
          >
            {{ course.description }}
          </p>

          <div class="flex items-center justify-between pt-4 border-t border-border">
            <div>
              <p class="text-xs text-muted-foreground">Длительность</p>
              <p class="text-sm font-medium">{{ course.duration_days }} дн.</p>
            </div>

            <div class="text-right">
              <p class="text-xs text-muted-foreground">Цена за чел.</p>
              <p class="text-sm font-semibold text-primary">
                {{ Number(course.price_per_person).toLocaleString("ru-RU") }} ₽
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <Dialog v-model="dialogOpen">

            {{ editing ? "Редактировать курс" : "Новый курс" }}


        <div class="space-y-4 mt-2">
          <div>
            <Label>Название курса *</Label>
            <Input v-model="form.name" placeholder="Например: Охрана труда" />
          </div>

          <div>
            <Label>Описание</Label>
            <Textarea v-model="form.description" rows="3" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label>Длительность (дни) *</Label>
              <Input type="number" v-model="form.duration_days" />
            </div>

            <div>
              <Label>Цена за человека *</Label>
              <Input type="number" v-model="form.price_per_person" />
            </div>
          </div>

          <Button
            @click="handleSave"
            :disabled="!form.name || !form.duration_days || !form.price_per_person"
            class="w-full"
          >
            {{ editing ? "Сохранить" : "Создать" }}
          </Button>
        </div>

    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { base44 } from "@/api/base44Client";

import { BookOpen, Plus, Pencil, Trash2, Search } from "lucide-vue-next";

import  Button  from "@/components/ui/button.vue";
import  Input  from "@/components/ui/input.vue";
import  Dialog  from "@/components/ui/dialog.vue";
import  Label  from "@/components/ui/label.vue";
import  Textarea  from "@/components/ui/textarea.vue";

import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import { toast } from '@/composables/use-toast'



const emptyCourse = {
  name: "",
  description: "",
  duration_days: "",
  price_per_person: ""
};

const courses = ref([]);
const loading = ref(true);
const search = ref("");

const dialogOpen = ref(false);
const editing = ref(null);

const form = ref({ ...emptyCourse });

const load = async () => {
  const data = await base44.entities.Course.list("-created_date", 100);
  courses.value = data;
  loading.value = false;
};

onMounted(load);

const filtered = computed(() => {
  return courses.value.filter((c) =>
    c.name?.toLowerCase().includes(search.value.toLowerCase())
  );
});

const openCreate = () => {
  editing.value = null;
  form.value = { ...emptyCourse };
  dialogOpen.value = true;
};

const openEdit = (course) => {
  editing.value = course;
  form.value = {
    name: course.name || "",
    description: course.description || "",
    duration_days: course.duration_days || "",
    price_per_person: course.price_per_person || ""
  };
  dialogOpen.value = true;
};

const handleSave = async () => {
  const data = {
    ...form.value,
    duration_days: Number(form.value.duration_days),
    price_per_person: Number(form.value.price_per_person)
  };

  if (editing.value) {
    await base44.entities.Course.update(editing.value.id, data);
    toast({ title: "Курс обновлён" });
  } else {
    await base44.entities.Course.create(data);
    toast({ title: "Курс создан" });
  }

  dialogOpen.value = false;
  load();
};

const handleDelete = async (id) => {
  await base44.entities.Course.delete(id);
  toast({ title: "Курс удалён" });
  load();
};
</script>