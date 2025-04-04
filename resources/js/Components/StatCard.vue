<!-- StatCard.vue -->
<script setup>
import { computed, ref, watch } from "vue";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: String, required: true },
  color: { type: String, required: true },
  currency: { type: String, required: false, default: "" },
  editable: { type: Boolean, default: false },
});

const emit = defineEmits(["update"]);

const isEditing = ref(false);
const editValue = ref(props.value);

watch(
  () => props.value,
  (val) => {
    editValue.value = val;
  }
);

const bgClass = computed(() => {
  return `bg-${props.color}-100 dark:bg-${props.color}-400/10`;
});
const iconClass = computed(() => {
  return `pi ${props.icon} text-${props.color}-500 !text-xl`;
});

function save() {
  emit("update", editValue.value);
  isEditing.value = false;
}

function cancel() {
  editValue.value = props.value;
  isEditing.value = false;
}
</script>

<template>
  <section>
    <article
      class="card mb-0 h-full p-4 rounded-lg shadow-sm bg-white dark:bg-surface-800 transition-all hover:shadow-md">
      <header class="flex justify-between items-center mb-4">
        <div>
          <h3 class="block text-muted-color font-medium mb-2 text-sm">{{ label }}</h3>
          <div v-if="editable && isEditing">
            <InputNumber v-model="editValue" mode="currency" currency="USD" :min="0" class="!w-full mb-2"
              size="small" />
            <div class="flex gap-1">
              <Button icon="pi pi-check" severity="success" size="small" @click="save" />
              <Button icon="pi pi-times" severity="secondary" size="small" @click="cancel" />
            </div>
          </div>
          <p v-else class="text-surface-900 dark:text-surface-0 font-semibold text-xl">
            {{ currency }}{{ value }}
            <Button v-if="editable" icon="pi pi-pencil" size="small" class="ml-2" @click="isEditing = true"
              variant="text" />
          </p>
        </div>
        <div class="flex items-end justify-center rounded-xl" :class="[bgClass]" style="width: 2.5rem; height: 2.5rem">
          <i :class="[iconClass]"></i>
        </div>
      </header>
    </article>
  </section>
</template>
