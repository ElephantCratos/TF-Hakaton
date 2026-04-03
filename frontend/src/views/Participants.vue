<template>
  <div>
    <PageHeader title="Участники обучения" description="Реестр сотрудников для обучения">
      <Button class="gap-2" @click="openCreate">
        <Plus class="w-4 h-4" />
        Добавить участника
      </Button>
    </PageHeader>

    <!-- SEARCH -->
    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input
        v-model="search"
        placeholder="Поиск по ФИО, email, компании..."
        class="pl-10"
      />
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <!-- EMPTY -->
    <EmptyState
      v-else-if="filtered.length === 0"
      :icon="Users"
      title="Нет участников"
      description="Добавьте первого участника"
    >
      <Button variant="outline" class="gap-2" @click="openCreate">
        <Plus class="w-4 h-4" />
        Добавить
      </Button>
    </EmptyState>

    <!-- TABLE -->
    <div v-else class="bg-card rounded-2xl border border-border overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-muted/50">
            <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground">ФИО</th>
            <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground">Компания</th>
            <th class="text-left px-6 py-3 text-xs uppercase text-muted-foreground">Email</th>
            <th class="text-right px-6 py-3 text-xs uppercase text-muted-foreground">Действия</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-border">
          <tr v-for="p in filtered" :key="p.id" class="hover:bg-muted/30">
            <td class="px-6 py-4 font-medium">{{ p.full_name }}</td>
            <td class="px-6 py-4 text-sm text-muted-foreground">{{ p.company_name || "—" }}</td>
            <td class="px-6 py-4 text-sm">{{ p.email || "—" }}</td>

            <td class="px-6 py-4 text-right">
              <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(p)">
                <Pencil class="w-3.5 h-3.5" />
              </Button>

              <Button size="icon" variant="ghost" class="h-8 w-8 text-destructive" @click="handleDelete(p.id)">
                <Trash2 class="w-3.5 h-3.5" />
              </Button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL (100% WORKING) -->
    <div v-if="dialogOpen" class="fixed inset-0 z-50 flex items-center justify-center">

      <!-- overlay -->
      <div class="absolute inset-0 bg-black/50" @click="dialogOpen = false" />

      <!-- modal -->
      <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md z-10">

        <h2 class="text-lg font-semibold mb-4">
          {{ editing ? "Редактировать участника" : "Новый участник" }}
        </h2>

        <div class="space-y-4">
          <!-- NAME -->
          <div>
            <Label>ФИО *</Label>
            <Input v-model="form.full_name" placeholder="Иванов Иван Иванович" />
          </div>

          <!-- COMPANY (SIMPLE SELECT) -->
          <div>
            <Label>Компания *</Label>
            <select v-model="form.company_id" class="w-full border rounded-md p-2">
              <option value="" disabled>Выберите компанию</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>
          </div>

          <!-- EMAIL -->
          <div>
            <Label>Email</Label>
            <Input v-model="form.email" type="email" placeholder="email@example.com" />
          </div>

          <!-- BUTTONS -->
          <div class="flex gap-2 pt-2">
            <Button class="w-full" @click="handleSave" :disabled="!form.full_name || !form.company_id">
              {{ editing ? "Сохранить" : "Создать" }}
            </Button>

            <Button variant="outline" class="w-full" @click="dialogOpen = false">
              Отмена
            </Button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { base44 } from "@/api/base44Client";
import { toast } from "@/composables/use-toast";

import { Users, Plus, Pencil, Trash2, Search } from "lucide-vue-next";

import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import Button from "@/components/ui/button.vue";
import Input from "@/components/ui/input.vue";
import Label from "@/components/ui/label.vue";

const participants = ref([]);
const companies = ref([]);
const loading = ref(true);

const search = ref("");
const dialogOpen = ref(false);

const editing = ref(null);

const form = ref({
  full_name: "",
  company_id: "",
  email: "",
});

async function load() {
  loading.value = true;

  const [p, c] = await Promise.all([
    base44.entities.Participant.list("-created_date", 200),
    base44.entities.Company.list("-created_date", 200),
  ]);

  participants.value = p;
  companies.value = c;

  loading.value = false;
}

onMounted(load);

const filtered = computed(() => {
  const q = search.value.toLowerCase();

  return participants.value.filter((p) => {
    return (
      (p.full_name || "").toLowerCase().includes(q) ||
      (p.email || "").toLowerCase().includes(q) ||
      (p.company_name || "").toLowerCase().includes(q)
    );
  });
});

function openCreate() {
  editing.value = null;
  form.value = { full_name: "", company_id: "", email: "" };
  dialogOpen.value = true;
}

function openEdit(p) {
  editing.value = p;
  form.value = {
    full_name: p.full_name || "",
    company_id: p.company_id || "",
    email: p.email || "",
  };
  dialogOpen.value = true;
}

async function handleSave() {
  const data = { ...form.value };

  const company = companies.value.find((c) => c.id === form.value.company_id);
  data.company_name = company?.name || "";

  if (editing.value) {
    await base44.entities.Participant.update(editing.value.id, data);
    toast({ title: "Участник обновлён" });
  } else {
    await base44.entities.Participant.create(data);
    toast({ title: "Участник создан" });
  }

  dialogOpen.value = false;
  await load();
}

async function handleDelete(id) {
  await base44.entities.Participant.delete(id);
  toast({ title: "Участник удалён" });
  await load();
}
</script>