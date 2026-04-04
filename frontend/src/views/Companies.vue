<template>
  <div>
    <PageHeader title="Компании" description="Справочник компаний-заказчиков">
      <Button @click="openCreate" class="gap-2">
        <Plus class="w-4 h-4" /> Добавить компанию
      </Button>
    </PageHeader>

    <div class="relative mb-6 max-w-sm">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input
        placeholder="Поиск..."
        v-model="search"
        class="pl-10"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <template v-else>
      <EmptyState
        v-if="filtered.length === 0"
        :icon="Building2"
        title="Нет компаний"
        description="Добавьте первую компанию"
      >
        <Button @click="openCreate" variant="outline" class="gap-2">
          <Plus class="w-4 h-4" /> Добавить
        </Button>
      </EmptyState>

      <div v-else class="bg-card rounded-2xl border border-border overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="border-b border-border bg-muted/50">
              <th class="px-6 py-3 text-left text-xs uppercase">Код</th>
              <th class="px-6 py-3 text-left text-xs uppercase">Название</th>
              <th class="px-6 py-3 text-right text-xs uppercase">Действия</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-border">
            <tr
              v-for="company in filtered"
              :key="company.id"
              class="hover:bg-muted/30 transition-colors"
            >
              <td class="px-6 py-4 text-sm font-mono">
                {{ company.code }}
              </td>

              <td class="px-6 py-4 font-medium">
                {{ company.name }}
              </td>

              <td class="px-6 py-4 text-right">
                <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(company)">
                  <Pencil class="w-3.5 h-3.5" />
                </Button>

                <Button
                  size="icon"
                  variant="ghost"
                  class="h-8 w-8 text-destructive"
                  @click="handleDelete(company.id)"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <Dialog v-model="dialogOpen">
      
            {{ editing ? "Редактировать" : "Новая компания" }}

        <div class="space-y-4 mt-2">
          <div>
            <Label>Код компании *</Label>
            <Input
              v-model="form.code"
              placeholder="Например: ООО-001"
            />
          </div>

          <div>
            <Label>Название *</Label>
            <Input
              v-model="form.name"
              placeholder='ООО "Ромашка"'
            />
          </div>

          <Button
            @click="handleSave"
            :disabled="!form.code || !form.name"
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

import { Building2, Plus, Pencil, Trash2, Search } from "lucide-vue-next";

import  Button  from "@/components/ui/button.vue";
import  Input  from "@/components/ui/input.vue";
import  Dialog  from "@/components/ui/dialog.vue";
import  Label  from "@/components/ui/label.vue";

import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import { toast } from '@/composables/use-toast'



const companies = ref([]);
const loading = ref(true);
const search = ref("");

const dialogOpen = ref(false);
const editing = ref(null);

const form = ref({
  code: "",
  name: ""
});

const load = async () => {
  const data = await base44.entities.Company.list("-created_date", 200);
  companies.value = data;
  loading.value = false;
};

onMounted(load);

const filtered = computed(() => {
  return companies.value.filter(
    (c) =>
      c.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      c.code?.toLowerCase().includes(search.value.toLowerCase())
  );
});

const openCreate = () => {
  editing.value = null;
  form.value = { code: "", name: "" };
  dialogOpen.value = true;
};

const openEdit = (company) => {
  editing.value = company;
  form.value = {
    code: company.code || "",
    name: company.name || ""
  };
  dialogOpen.value = true;
};

const handleSave = async () => {
  if (editing.value) {
    await base44.entities.Company.update(editing.value.id, form.value);
    toast({ title: "Компания обновлена" });
  } else {
    await base44.entities.Company.create(form.value);
    toast({ title: "Компания создана" });
  }

  dialogOpen.value = false;
  load();
};

const handleDelete = async (id) => {
  await base44.entities.Company.delete(id);
  toast({ title: "Компания удалена" });
  load();
};
</script>