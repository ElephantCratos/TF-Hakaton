<template>
  <div>
    <PageHeader title="Спецификации" description="Финансовые документы обучения">
      <Button class="gap-2" @click="openCreate">
        <Plus class="w-4 h-4" />
        Создать спецификацию
      </Button>
    </PageHeader>

    <!-- Search -->
    <div class="relative mb-6 max-w-sm">
      <Search
        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground"
      />
      <Input
        v-model="search"
        placeholder="Поиск..."
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
      :icon="FileText"
      title="Нет спецификаций"
      description="Создайте первую спецификацию"
    >
      <Button variant="outline" class="gap-2" @click="openCreate">
        <Plus class="w-4 h-4" />
        Создать
      </Button>
    </EmptyState>

    <!-- Table -->
    <div v-else class="bg-card rounded-2xl border border-border overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-muted/50">
            <th class="text-left text-xs px-6 py-3">№ документа</th>
            <th class="text-left text-xs px-6 py-3">Дата</th>
            <th class="text-left text-xs px-6 py-3">Компания</th>
            <th class="text-right text-xs px-6 py-3">Без НДС</th>
            <th class="text-right text-xs px-6 py-3">С НДС</th>
            <th class="text-right text-xs px-6 py-3">Действия</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-border">
          <tr
            v-for="spec in filtered"
            :key="spec.id"
            class="hover:bg-muted/30 transition-colors"
          >
            <td class="px-6 py-4 font-medium">
              {{ spec.document_number }}
            </td>

            <td class="px-6 py-4 text-sm">
              {{ spec.document_date }}
            </td>

            <td class="px-6 py-4 text-sm">
              {{ spec.company_name || "—" }}
            </td>

            <td class="px-6 py-4 text-sm text-right font-medium">
              {{ fmt(spec.total_without_vat) }} ₽
            </td>

            <td class="px-6 py-4 text-sm text-right font-semibold text-primary">
              {{ fmt(spec.total_with_vat) }} ₽
            </td>

            <td class="px-6 py-4 text-right">
              <Button size="icon" variant="ghost" class="h-8 w-8" @click="detailSpec = spec; detailDialogOpen = true">
                <Eye class="w-3.5 h-3.5" />
              </Button>

              <Button size="icon" variant="ghost" class="h-8 w-8" @click="openEdit(spec)">
                <Pencil class="w-3.5 h-3.5" />
              </Button>

              <Button size="icon" variant="ghost" class="h-8 w-8 text-destructive" @click="handleDelete(spec.id)">
                <Trash2 class="w-3.5 h-3.5" />
              </Button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CREATE / EDIT -->
    <Dialog v-model="dialogOpen">

            {{ editing ? "Редактировать" : "Новая спецификация" }}


        <div class="flex-1 overflow-y-auto space-y-4 mt-2 pr-1">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label>Номер документа *</Label>
              <Input v-model="form.document_number" />
            </div>

            <div>
              <Label>Дата *</Label>
              <Input type="date" v-model="form.document_date" />
            </div>
          </div>

          <div>
            <Label>Компания *</Label>
            <Select
              v-model="form.company_id"
              :options="companyOptions"
              placeholder="Выберите компанию"
            />
          </div>

          <!-- GROUPS -->
          <div>
            <Label>Группы обучения</Label>

            <div class="border rounded-lg divide-y divide-border max-h-40 overflow-y-auto mt-1">
              <label
                v-for="g in groups"
                :key="g.id"
                class="flex items-center gap-3 px-4 py-2.5 hover:bg-muted/50 cursor-pointer"
              >
                <Checkbox
                  :model-value="form.group_ids.includes(g.id)"
                  @update:model-value="() => toggleGroup(g.id)"
                />

                <div class="flex-1">
                  <span class="text-sm">{{ g.course_name }}</span>
                  <span class="text-xs text-muted-foreground ml-2">
                    {{ fmt(g.total_cost) }} ₽
                  </span>
                </div>
              </label>
            </div>
          </div>

          <!-- COST SUMMARY -->
          <div class="bg-muted/50 rounded-xl p-4 space-y-2">
            <h4 class="text-sm font-semibold">Итого по спецификации</h4>

            <div class="flex justify-between text-sm">
              <span class="text-muted-foreground">Без НДС</span>
              <span class="font-medium">{{ fmt(cost.totalWithoutVat) }} ₽</span>
            </div>

            <div class="flex justify-between text-sm">
              <span class="text-muted-foreground">НДС (22%)</span>
              <span class="font-medium">{{ fmt(cost.vatAmount) }} ₽</span>
            </div>

            <div class="border-t border-border my-2" />

            <div class="flex justify-between text-sm font-semibold">
              <span>С НДС</span>
              <span class="text-primary">{{ fmt(cost.totalWithVat) }} ₽</span>
            </div>
          </div>

          <Button
            class="w-full"
            :disabled="!form.document_number || !form.document_date || !form.company_id"
            @click="handleSave"
          >
            {{ editing ? "Сохранить" : "Создать" }}
          </Button>
        </div>

    </Dialog>

    <!-- DETAIL -->
    <Dialog v-model="detailDialogOpen" @update:open="val => { detailDialogOpen = val; if (!val) detailSpec = null; }">
     
            Спецификация №{{ detailSpec?.document_number }}


        <div v-if="detailSpec" class="space-y-3 mt-2">
          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Дата</span>
            <span>{{ detailSpec.document_date }}</span>
          </div>

          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Компания</span>
            <span>{{ detailSpec.company_name }}</span>
          </div>

          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Групп</span>
            <span>{{ detailSpec.group_ids?.length || 0 }}</span>
          </div>

          <div class="border-t border-border my-2" />

          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">Без НДС</span>
            <span class="font-medium">{{ fmt(detailSpec.total_without_vat) }} ₽</span>
          </div>

          <div class="flex justify-between text-sm">
            <span class="text-muted-foreground">НДС (22%)</span>
            <span class="font-medium">{{ fmt(detailSpec.vat_amount) }} ₽</span>
          </div>

          <div class="flex justify-between text-sm font-semibold">
            <span>С НДС</span>
            <span class="text-primary">{{ fmt(detailSpec.total_with_vat) }} ₽</span>
          </div>
        </div>

    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { base44 } from "@/api/base44Client";
import { toast } from '@/composables/use-toast'

import {
  FileText,
  Plus,
  Pencil,
  Trash2,
  Search,
  Eye,
} from "lucide-vue-next";

import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";

import  Button  from "@/components/ui/button.vue";
import  Input  from "@/components/ui/input.vue";

import  Dialog  from "@/components/ui/dialog.vue";

import  Label  from "@/components/ui/label.vue";

import Select  from "@/components/ui/select.vue";

import  Checkbox  from "@/components/ui/checkbox.vue";

const VAT_RATE = 0.22;

const specs = ref([]);
const companies = ref([]);
const groups = ref([]);

const loading = ref(true);
const search = ref("");

const dialogOpen = ref(false);
const editing = ref(null);
const detailSpec = ref(null);
const detailDialogOpen = ref(false);

const form = ref({
  document_number: "",
  document_date: "",
  company_id: "",
  group_ids: [],
});

async function load() {
  loading.value = true;

  const [s, c, g] = await Promise.all([
    base44.entities.Specification.list("-created_date", 100),
    base44.entities.Company.list("-created_date", 200),
    base44.entities.TrainingGroup.list("-created_date", 200),
  ]);

  specs.value = s;
  companies.value = c;
  groups.value = g;

  loading.value = false;
}

onMounted(load);

const filtered = computed(() => {
  const q = search.value.toLowerCase();

  return specs.value.filter((s) => {
    return (
      (s.document_number || "").toLowerCase().includes(q) ||
      (s.company_name || "").toLowerCase().includes(q)
    );
  });
});

const cost = computed(() => {
  const selected = groups.value.filter((g) =>
    form.value.group_ids.includes(g.id)
  );

  const totalWithoutVat = selected.reduce(
    (sum, g) => sum + (g.total_cost || 0),
    0
  );

  const vatAmount = totalWithoutVat * VAT_RATE;

  return {
    totalWithoutVat,
    vatAmount,
    totalWithVat: totalWithoutVat + vatAmount,
  };
});

function openCreate() {
  editing.value = null;
  form.value = {
    document_number: "",
    document_date: "",
    company_id: "",
    group_ids: [],
  };
  dialogOpen.value = true;
}

function openEdit(spec) {
  editing.value = spec;

  form.value = {
    document_number: spec.document_number || "",
    document_date: spec.document_date || "",
    company_id: spec.company_id || "",
    group_ids: spec.group_ids || [],
  };

  dialogOpen.value = true;
}

async function handleSave() {
  const company = companies.value.find(
    (c) => c.id === form.value.company_id
  );

  const data = {
    ...form.value,
    company_name: company?.name || "",
    total_without_vat: cost.value.totalWithoutVat,
    vat_amount: cost.value.vatAmount,
    total_with_vat: cost.value.totalWithVat,
  };

  if (editing.value) {
    await base44.entities.Specification.update(editing.value.id, data);
    toast({ title: "Спецификация обновлена" });
  } else {
    await base44.entities.Specification.create(data);
    toast({ title: "Спецификация создана" });
  }

  dialogOpen.value = false;
  await load();
}

async function handleDelete(id) {
  await base44.entities.Specification.delete(id);
  toast({ title: "Спецификация удалена" });
  await load();
}

function toggleGroup(groupId) {
  const arr = form.value.group_ids;

  if (arr.includes(groupId)) {
    form.value.group_ids = arr.filter((g) => g !== groupId);
  } else {
    form.value.group_ids = [...arr, groupId];
  }
}

function fmt(n) {
  return (n || 0).toLocaleString("ru-RU", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}
const companyOptions = computed(() =>
  companies.value.map(c => ({
    label: c.name,
    value: c.id,
  }))
);
</script>