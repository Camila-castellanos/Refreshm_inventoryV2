<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <Button label="New Email Template" icon="pi pi-plus" severity="primary" @click="createNewTemplate" />
      <Dropdown v-model="selectedTemplate" :options="templates" optionLabel="subject" placeholder="Select a template" class="w-[300px]" />
    </div>

    <div class="rounded-lg shadow">
      <div class="mb-4">
        <FloatLabel variant="in">
          <InputText v-model="subject" class="w-full" id="subject" />
          <label for="subject">Subject</label>
        </FloatLabel>
      </div>
      <Editor v-model="content" editorStyle="height: 320px">
      </Editor>

      <div class="flex justify-end gap-3 p-4 rounded-b-lg">
        <Button label="Send" severity="primary" :loading="loading" @click="handleSend" />
      </div>
    </div>

    <!-- PrimeVue Confirm Dialog -->
    
  </div>
</template>

<script setup lang="ts">
import { EmailTemplate } from "@/Lib/types";
import { Button, Dropdown, FloatLabel, InputText, ConfirmDialog, useConfirm } from "primevue";
import Editor from "primevue/editor";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { inject, Ref, ref, watch, watchEffect } from "vue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";

const templates = ref<EmailTemplate[]>([]);
const selectedTemplate = ref<EmailTemplate | null>(null);
const subject = ref("");
const content = ref("");
const loading = ref(false);
const customer_id = ref<number | null>(null);
const invoice_id = ref<number | null>(null);
const toast = useToast();
const confirm = useConfirm();

const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;

watchEffect(() => {
  if (dialogRef.value) {
    templates.value = dialogRef.value.data.templates;
    customer_id.value = dialogRef.value.data.customer_id;
    invoice_id.value = dialogRef.value.data.invoice_id;
  }
});

// Actualiza el contenido y asunto cuando se selecciona una plantilla
watch(selectedTemplate, (newTemplate) => {
  if (newTemplate) {
    content.value = newTemplate.content;
    subject.value = newTemplate.subject;
  }
});

// Función para redirigir a la creación de plantillas
const createNewTemplate = () => {
  window.location.assign("/email_templates");
};

// Confirmar y enviar la factura por correo
const handleSend = () => {
  if (!subject.value || !content.value) {
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Please fill in all required fields",
      life: 3000,
    });
    return;
  }

  confirm.require({
    message: "Are you sure you want to send the invoice? This action cannot be undone.",
    header: "Confirm Invoice Sending",
    icon: "pi pi-exclamation-triangle",
    acceptLabel: "Yes, send it!",
    rejectLabel: "Cancel",
    accept: async () => {
      loading.value = true;
      try {
        const emailInvoice = {
          customer_id: customer_id.value,
          id: invoice_id.value,
          subject: subject.value,
          message: content.value,
        };

        await axios.post(route("payments.send.invoice", invoice_id.value), emailInvoice);

        toast.add({
          severity: "success",
          summary: "Invoice Sent",
          detail: "The invoice was sent successfully!",
          life: 3000,
        });
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: "Error Sending Invoice",
          detail: error.response?.data?.message || "Something went wrong",
          life: 3000,
        });
      } finally {
        loading.value = false;
      }
    },
  });
};
</script>

<style>
/* Estilos personalizados */
.p-editor-container .p-editor-content {
  border: 1px solid #e2e8f0;
  border-top: none;
}

.p-editor-container .p-editor-toolbar {
  border: 1px solid #e2e8f0;
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
  height: 0px !important;
}
</style>
