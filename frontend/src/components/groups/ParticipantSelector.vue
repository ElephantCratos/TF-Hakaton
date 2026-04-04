<template>
  <Dialog v-model="dialogOpen">
    <div class="sm:max-w-lg max-h-[80vh] flex flex-col">
      <h2 class="text-lg font-semibold mb-4">Выбрать участников</h2>

      <!-- поиск -->
      <div class="relative mb-3">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Поиск по ФИО, компании..." class="pl-10" />
      </div>

      <!-- список -->
      <div class="flex-1 overflow-y-auto divide-y divide-border border rounded-lg">
        <div v-if="filtered.length === 0" class="p-6 text-center text-sm text-muted-foreground">
          Нет доступных участников
        </div>
        <label
          v-for="p in filtered"
          :key="p.id"
          class="flex items-center gap-3 px-4 py-3 hover:bg-muted/50 cursor-pointer"
        >
          <input type="checkbox" :checked="selected.includes(p.id)" @change="toggleSelect(p.id)" />
          <div>
            <p class="text-sm font-medium">{{ p.full_name }}</p>
            <p class="text-xs text-muted-foreground">{{ p.company_name || '—' }}</p>
          </div>
        </label>
      </div>

      <Button class="w-full gap-2 mt-3" :disabled="selected.length === 0" @click="submit">
        <UserPlus class="w-4 h-4" />
        Добавить выбранных ({{ selected.length }})
      </Button>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { base44 } from '@/api/base44Client'
import { Search, UserPlus } from 'lucide-vue-next'
import Dialog from '@/components/ui/dialog.vue'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'

const props = defineProps({
  open: Boolean,
  existingIds: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open', 'select'])

const participants = ref([])
const selected = ref([])
const search = ref('')

// Привязываем v-model к диалогу
const dialogOpen = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val),
})

// Загружаем участников при открытии
watch(dialogOpen, async (isOpen) => {
  if (!isOpen) return
  participants.value = await base44.entities.Participant.list('-created_date', 500)
  selected.value = []
  search.value = ''
})

const available = computed(() =>
  participants.value.filter((p) => !props.existingIds.includes(p.id))
)

const filtered = computed(() => {
  const s = search.value.toLowerCase()
  return available.value.filter(
    (p) =>
      p.full_name?.toLowerCase().includes(s) ||
      p.company_name?.toLowerCase().includes(s)
  )
})

function toggleSelect(id) {
  if (selected.value.includes(id)) {
    selected.value = selected.value.filter((x) => x !== id)
  } else {
    selected.value.push(id)
  }
}

function submit() {
  emit('select', selected.value)
  dialogOpen.value = false
}
</script>