<template>
  <div class="p-4">
    <!-- Default Fields Section -->
    <div class="mb-8">
      <h2 class="text-lg font-semibold mb-4">Default Fields</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div 
          v-for="header in props.headers" 
          :key="header.name"
          class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition-colors duration-150"
        >
        <template v-if="header.name !== 'actions'">
          <Checkbox
            v-model="selectedHeaders"
            :inputId="header.name"
            name="header"
            :value="header.name"
            @change="emit('updateHeaders', allHeaders)"
            class="!w-5 !h-5"
            />
            <label 
            :for="header.name"
            class="flex-1 text-sm text-gray-700 cursor-pointer"
          >
            {{ header.label }}
          </label>
        </template>
        </div>
      </div>
    </div>

    <!-- Custom Fields Section -->
    <div>
      <h2 class="text-lg font-semibold mb-4">Custom Fields</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div 
          v-for="header in selectedCustomHeaders" 
          :key="header.value"
          class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg group transition-colors duration-150"
        >
          <Checkbox
            :inputId="header.value"
            name="header"
            binary
            v-model="header.active"
            :true-value="1"
            :false-value="0"
            class="!w-5 !h-5"
          />
          <label 
            :for="header.value"
            class="flex-1 text-sm text-gray-700 cursor-pointer"
          >
            {{ header.text }}
          </label>
          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <Button
              icon="pi pi-pencil"
              text
              size="small"
              class="!p-1.5 !min-w-[2rem] !h-8 !w-8"
              @click="() => openDialog({ name: header.value, label: header.text, type: header.type }, header.id)"
            />
            <Button 
              icon="pi pi-trash" 
              text 
              severity="danger"
              size="small"
              class="!p-1.5 !min-w-[2rem] !h-8 !w-8"
              @click="() => deleteCustomField(header)"
            />
          </div>
        </div>
        
        <!-- Add New Field Button -->
        <Button
          label="Add new field"
          icon="pi pi-plus"
          outlined
          class="!p-2 !border-gray-200 !text-gray-600 hover:!border-gray-300 hover:!bg-gray-50 hover:!text-gray-700 transition-colors duration-150"
          @click="() => openDialog()"
        />
      </div>
    </div>
  </div>

  <!-- Add/Edit Field Dialog -->
  <Dialog 
    v-model:visible="dlgVisible" 
    :modal="true"
    class="p-fluid"
    :style="{ width: '450px' }"
    :contentClass="'p-0'"
    :headerClass="'px-6 pt-6 pb-2 border-b-0'"
    :contentStyle="{ padding: '0 1.5rem 1.5rem 1.5rem' }"
  >
    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">
        {{ editingField ? 'Edit Field' : 'Add New Field' }}
      </h2>
    </template>

    <form @submit.prevent="submitForm" class="space-y-4">
      <div class="field">
        <FloatLabel variant="in" class="w-full">
          <InputText 
            v-model="newName" 
            id="new_name"
            class="w-full"
            required
          />
          <label for="new_name" class="text-gray-600">Name</label>
        </FloatLabel>
      </div>

      <div class="field">
        <FloatLabel variant="in" class="w-full">
          <Select
            v-model="newType"
            :options="types"
            optionLabel="name"
            optionValue="value"
            class="w-full"
            required
          />
          <label class="text-gray-600">Type</label>
        </FloatLabel>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <Button
          type="button"
          label="Cancel"
          outlined
          class="!px-4 !py-2 !border-gray-300 !text-gray-700 hover:!bg-gray-50"
          @click="dlgVisible = false"
        />
        <Button
          type="submit"
          label="Confirm"
          class="!px-4 !py-2"
        />
      </div>
    </form>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, ComputedRef, watchEffect, watch } from "vue";
import axios from "axios";
import { Button, Checkbox, Dialog, FloatLabel, InputText, Select, useToast, useConfirm } from "primevue";
import type { CustomField, Field } from "@/Lib/types";
import {router} from "@inertiajs/vue3";

const props = defineProps<{
  headers: CustomField[];
  customHeaders: Field[];
}>();

const emit = defineEmits<{ updateHeaders: (headers: CustomField[]) => void }>();

const toast = useToast();
const confirm = useConfirm();

const types = [
  { name: "Text", value: "string" },
  { name: "Number", value: "number" },
  { name: "Currency", value: "number" },
  { name: "Date", value: "string" },
  { name: "Percentage", value: "string" },
];

const selectedHeaders = ref<string[]>(props.headers.map((h) => h.name));
const selectedCustomHeaders = ref<Field[]>([...props.customHeaders]);

const allHeaders: ComputedRef<CustomField[]> = computed(() => {
  return [
    ...props.headers.filter((h) => selectedHeaders.value.includes(h.name)),
    ...selectedCustomHeaders.value
      .filter((h) => h.active === 1)
      .map((h) => ({ name: h.value, label: h.text, type: h.type })),
  ];
});

watchEffect(() => {
  emit("updateHeaders", allHeaders.value);
});

const newName = ref("");
const newType = ref("");
const dlgVisible = ref(false);
const editingField = ref<CustomField | null>(null);
const fieldId = ref(0);

const openDialog = (field: CustomField | null = null, id?: number) => {
  editingField.value = field;
  newName.value = field ? field.label : "";
  newType.value = field ? field.type : "";
  dlgVisible.value = true;
  fieldId.value = id ?? 0;
};

const submitForm = () => {
  let newField = {
    text: newName.value,
    value: newName.value.toLowerCase(),
    type: newType.value,
    active: 1,
  };

  if (editingField.value) {
    axios.put(route("customfields.update", { id: fieldId.value }), newField).then(() => {
      toast.add({ severity: "success", summary: "Success", detail: "Field updated", life: 3000 });
      dlgVisible.value = false;
      emit("updateHeaders", allHeaders.value);
      router.reload();
    });
  } else {
    axios.post(route("customfields.store"), newField).then(() => {
      selectedCustomHeaders.value.push({ ...newField, id: Math.random(), created_at: "", updated_at: "", user_id: 1 });
      toast.add({ severity: "success", summary: "Success", detail: "Field added", life: 3000 });
      dlgVisible.value = false;
      emit("updateHeaders", allHeaders.value);
      router.reload();
    });
  }
};

const deleteCustomField = (field: Field) => {
  confirm.require({
    message: "Are you sure you want to delete this field?",
    header: "Delete Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: () => {
      axios.delete(route("customfields.destroy", field.id)).then(() => {
        selectedCustomHeaders.value = selectedCustomHeaders.value.filter((f) => f.value !== field.value);
        toast.add({ severity: "success", summary: "Success", detail: "Field deleted", life: 3000 });
        emit("updateHeaders", allHeaders.value);
        router.reload();
      });
    },
  });
};
</script>