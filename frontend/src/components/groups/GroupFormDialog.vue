<template>
  <!-- OVERLAY -->
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">

    <!-- BACKDROP -->
    <div
      class="absolute inset-0 bg-black/50"
      @click="close"
    />

    <!-- MODAL -->
    <div
      class="relative bg-white w-[500px] rounded-2xl p-6 z-10"
    >
      <h2 class="text-lg font-semibold mb-4">
        {{ editingGroup ? "Редактировать группу" : "Создать группу" }}
      </h2>

      <!-- COURSE -->
      <div class="mb-3">
        <label class="text-sm">Курс</label>

        <select v-model="form.course_id" class="w-full border p-2 rounded">
          <option
            v-for="c in courses"
            :key="c.id"
            :value="c.id"
          >
            {{ c.name }} — {{ format(c.price_per_person) }} ₽
          </option>
        </select>
      </div>

      <!-- DATES -->
      <div class="grid grid-cols-2 gap-2 mb-3">
        <input v-model="form.start_date" type="date" class="border p-2 rounded" />
        <input v-model="form.end_date" type="date" class="border p-2 rounded" />
      </div>

      <!-- STATUS -->
      <div class="mb-4">
        <select v-model="form.status" class="w-full border p-2 rounded">
          <option>Планируется</option>
          <option>В процессе</option>
          <option>Завершено</option>
        </select>
      </div>

      <!-- ACTIONS -->
      <div class="flex gap-2">
        <button
          class="flex-1 bg-gray-200 p-2 rounded"
          @click="close"
        >
          Отмена
        </button>

        <button
          class="flex-1 bg-black text-white p-2 rounded"
          @click="save"
        >
          Сохранить
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from "vue";
import { base44 } from "@/api/base44Client";

const props = defineProps({
  open: Boolean,
  editingGroup: Object
});

const emit = defineEmits(["update:open", "saved"]);

const courses = ref([]);

const form = reactive({
  course_id: "",
  start_date: "",
  end_date: "",
  status: "Планируется"
});

function close() {
  emit("update:open", false);
}

function format(n) {
  return Number(n || 0).toLocaleString("ru-RU");
}

async function loadCourses() {
  courses.value = await base44.entities.Course.list("-created_date", 100);
}

watch(
  () => props.open,
  async (v) => {
    if (!v) return;

    await loadCourses();

    if (props.editingGroup) {
      form.course_id = props.editingGroup.course_id || "";
      form.start_date = props.editingGroup.start_date || "";
      form.end_date = props.editingGroup.end_date || "";
      form.status = props.editingGroup.status || "Планируется";
    } else {
      form.course_id = "";
      form.start_date = "";
      form.end_date = "";
      form.status = "Планируется";
    }
  }
);

async function save() {
  const course = courses.value.find(c => c.id === form.course_id);

  const data = {
    ...form,
    course_name: course?.name || "",
    price_per_person: course?.price_per_person || 0,
  };

  if (props.editingGroup) {
    await base44.entities.TrainingGroup.update(props.editingGroup.id, data);
  } else {
    await base44.entities.TrainingGroup.create(data);
  }

  emit("saved");
  close();
}
</script>