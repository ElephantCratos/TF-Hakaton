<template>
  <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
    <div
      v-for="t in state.toasts"
      :key="t.id"
      class="bg-background border rounded-md shadow p-4 w-80"
    >
      <div class="flex justify-between items-start">
        <div>
          <p class="font-medium">{{ t.title }}</p>
          <p v-if="t.description" class="text-sm text-muted-foreground">
            {{ t.description }}
          </p>
        </div>

        <button
          class="text-sm ml-2"
          @click="dismiss(t.id)"
        >
          ✕
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from "vue";

const TOAST_LIMIT = 20;
const TOAST_REMOVE_DELAY = 3000;

let count = 0;
const toastTimeouts = new Map();

function genId() {
  count = (count + 1) % Number.MAX_VALUE;
  return count.toString();
}

const state = reactive({
  toasts: [],
});

function addToRemoveQueue(id) {
  if (toastTimeouts.has(id)) return;

  const timeout = setTimeout(() => {
    toastTimeouts.delete(id);
    remove(id);
  }, TOAST_REMOVE_DELAY);

  toastTimeouts.set(id, timeout);
}

function remove(id) {
  state.toasts = state.toasts.filter((t) => t.id !== id);
}

function dismiss(id) {
  addToRemoveQueue(id);
  state.toasts = state.toasts.map((t) =>
    t.id === id ? { ...t, open: false } : t
  );
}

function toast(props) {
  const id = genId();

  const t = {
    ...props,
    id,
    open: true,
  };

  state.toasts = [t, ...state.toasts].slice(0, TOAST_LIMIT);

  addToRemoveQueue(id);

  return {
    id,
    dismiss: () => dismiss(id),
  };
}

// экспорт для использования
defineExpose({
  toast,
  dismiss,
});
</script>