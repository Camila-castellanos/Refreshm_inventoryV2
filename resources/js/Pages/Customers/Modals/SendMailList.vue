<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <Button label="New Email Template" icon="pi pi-plus" severity="primary" @click="createNewTemplate" />
      <Dropdown v-model="selectedTemplate" :options="templates" optionLabel="subject" placeholder="Select a template" class="w-[300px]" />
    </div>

    <div class="bg-white rounded-lg shadow">
      <Editor v-model="content" editorStyle="height: 320px" :modules="editorModules">
        <template #toolbar>
          <span class="ql-formats">
            <button class="ql-bold" aria-label="Bold"></button>
            <button class="ql-italic" aria-label="Italic"></button>
            <button class="ql-underline" aria-label="Underline"></button>
          </span>
          <span class="ql-formats">
            <button class="ql-align" value="" aria-label="Align Left"></button>
            <button class="ql-align" value="center" aria-label="Align Center"></button>
            <button class="ql-align" value="right" aria-label="Align Right"></button>
          </span>
          <span class="ql-formats">
            <button class="ql-header" value="1" aria-label="Heading 1"></button>
            <button class="ql-link" aria-label="Insert Link"></button>
            <button class="ql-code-block" aria-label="Insert Code"></button>
          </span>
          <span class="ql-formats">
            <button class="ql-list" value="ordered" aria-label="Ordered List"></button>
            <button class="ql-list" value="bullet" aria-label="Bullet List"></button>
          </span>
          <span class="ql-formats">
            <button class="ql-clean" aria-label="Clear Formatting"></button>
          </span>
        </template>
      </Editor>

      <div class="flex justify-end gap-3 p-4 bg-gray-50 rounded-b-lg">
        <Button label="Send" severity="primary" :loading="loading" @click="handleSendEmailToMailList" />
      </div>
    </div>

    <!-- PrimeVue Confirm Dialog -->
    <ConfirmDialog></ConfirmDialog>
  </div>
</template>

<script setup lang="ts">
import { Button, Dropdown, ConfirmDialog, useConfirm } from "primevue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import Editor from "primevue/editor";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { inject, Ref, ref, watchEffect } from "vue";
import { EmailTemplate } from "@/Lib/types";

// Inyectar el diálogo de PrimeVue
const dialogRef = inject("dialogRef") as Ref<DynamicDialogInstance>;

// Variables reactivas
const templates = ref<EmailTemplate[]>([]);
const selectedTemplate = ref<EmailTemplate | null>(null);
const content = ref("");
const loading = ref(false);
const contacts = ref<string[]>([]);
const toast = useToast();
const confirm = useConfirm();

// Cargar datos del diálogo cuando esté disponible
watchEffect(() => {
  if (dialogRef.value) {
    templates.value = dialogRef.value.data.templates;
    contacts.value = dialogRef.value.data.contacts || [];
  }
});

// Configuración de Quill Editor
const editorModules = {
  toolbar: {
    container: [
      [{ header: 1 }, { header: 2 }],
      ["bold", "italic", "underline"],
      [{ list: "ordered" }, { list: "bullet" }],
      [{ align: [] }],
      ["link", "code-block"],
      ["clean"],
    ],
  },
};

// Actualizar el contenido cuando cambia la plantilla seleccionada
watchEffect(() => {
  if (selectedTemplate.value) {
    content.value = selectedTemplate.value.content;
  }
});

// Crear nueva plantilla (redirige a la página de edición)
const createNewTemplate = () => {
  window.location.assign("/email_templates");
  dialogRef.value?.close();
};

// Confirmar y enviar el email a la lista de contactos
const handleSendEmailToMailList = () => {
  if (!selectedTemplate.value || !content.value) {
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Please select a template and enter content",
      life: 3000,
    });
    return;
  }

  if (contacts.value.length === 0) {
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "No contacts selected",
      life: 3000,
    });
    return;
  }

  confirm.require({
    message: "Are you sure you want to send the email(s)?",
    header: "Confirm Email Sending",
    icon: "pi pi-exclamation-triangle",
    acceptLabel: "Yes, send it!",
    rejectLabel: "Cancel",
    accept: async () => {
      loading.value = true;

      try {
        const sendEmail = {
          contacts: contacts.value,
          subject: selectedTemplate.value?.subject,
          content: content.value,
        };

        await axios.post(route("send.mailing.list"), sendEmail);

        toast.add({
          severity: "success",
          summary: "Success",
          detail: "Emails sent successfully!",
          life: 3000,
        });

        // Cerrar el diálogo tras el envío
        dialogRef.value?.close();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: "Error Sending Emails",
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
