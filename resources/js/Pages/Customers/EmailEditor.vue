<template>
  
  <div>
    <section class="w-full max-w-[90%] mx-auto mt-4 p-4 pt-0">
      <ContactTabs>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <!-- Email Templates List Section -->
          <div class="md:col-span-5 bg-[var(--p-tabs-tablist-background)] rounded-lg shadow-md p-4">
            <h2 class="text-xl font-bold mb-4">Email Templates</h2>
            <div class="border-b pb-2 mb-2">
              <Button label="New Template" icon="pi pi-plus" size="small" outlined class="w-full" @click="createNewTemplate" />
            </div>
            <ul class="overflow-y-auto max-h-[500px]">
              <li
                v-for="(template, index) in templates"
                :key="index"
                class="p-2 rounded-md mb-2 cursor-pointer hover:bg-gray-100 transition-colors"
                :class="{ 'bg-blue-50 border-l-4 border-blue-500': selectedTemplateId === template.id }"
                @click="selectTemplate(template)">
                <div class="flex justify-between items-start w-full">
                  <div class="w-3/4" @click="selectTemplate(template)">
                    <div class="font-medium">{{ template.subject }}</div>
                    <div class="text-sm text-gray-500 truncate w-full !overflow-x-hidden">{{ stripHtml(template.content) }}</div>
                  </div>
                  <div class="flex flex-col gap-1 ml-2">
                    <Button
                      icon="pi pi-trash"
                      severity="danger"
                      text
                      rounded
                      size="small"
                      @click="confirmDelete(template)"
                      aria-label="Delete" />
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <!-- Email Form Section -->
          <div class="md:col-span-7">
            <form @submit.prevent="submitForm" class="bg-[var(--p-tabs-tablist-background)] rounded-lg shadow-md">
              <!-- Message Form Section -->
              <div class="p-3">
                <div class="mb-3">
                  <FloatLabel variant="in">
                    <InputText id="subject" v-model="subject" class="w-full" :class="{ 'p-invalid': submitted && !subject }" />
                    <label for="subject">Subject</label>
                  </FloatLabel>
                  <small v-if="submitted && !subject" class="p-error">Subject is required.</small>
                </div>

                <div class="mb-3">
                  <label class="block font-medium mt-4 mb-2">Content</label>
                  <Editor
                    v-model="messageContent"
                    editorStyle="height: 400px"
                    class="w-full"
                    :class="{ 'p-invalid': submitted && !messageContent }">
                  </Editor>
                  <small v-if="submitted && !messageContent" class="p-error">Content is required.</small>
                </div>

                <div class="flex justify-end gap-2 mt-3">
                  <Button label="Cancel" severity="secondary" outlined @click="resetForm" type="button" />
                  <Button :label="isEditing ? 'Update' : 'Create'" severity="primary" type="submit" :loading="loading" />
                </div>
              </div>
            </form>
          </div>
        </div>
      </ContactTabs>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, Ref, ref, computed, watchEffect } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";

// PrimeVue components
import InputText from "primevue/inputtext";
import Editor from "primevue/editor";
import Button from "primevue/button";
import FloatLabel from "primevue/floatlabel";
import Dialog from "primevue/dialog";
import { useToast } from "primevue/usetoast";
import { EmailTemplate } from "@/Lib/types";
import axios from "axios";
import ContactTabs from "@/Components/ContactTabs.vue";
import { ConfirmDialog, useConfirm } from "primevue";
import { router } from "@inertiajs/vue3";
// Define layout option
defineOptions({ layout: AppLayout });
const props = defineProps<{ emails: EmailTemplate[] }>();

// Toast setup
const toast = useToast();
const confirm = useConfirm();

// Form state
const subject = ref("");
const messageContent = ref("");
const submitted = ref(false);
const loading = ref(false);
const deleteDialog = ref(false);
const templateToDelete = ref<EmailTemplate | null>(null);

// Email templates data
const templates: Ref<EmailTemplate[]> = ref([]);
const selectedTemplateId = ref<number | null>(null);
const isEditing = computed(() => selectedTemplateId.value !== null);

// Helper function to strip HTML for preview
const stripHtml = (html: string) => {
  if (!html) return "";
  const tmp = document.createElement("DIV");
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || "";
};

// Template selection
const selectTemplate = (template: EmailTemplate) => {
  selectedTemplateId.value = template.id;
  subject.value = template.subject;
  messageContent.value = template.content;
};

// Create new template
const createNewTemplate = () => {
  resetForm();
};

// Form methods
const resetForm = () => {
  subject.value = "";
  messageContent.value = "";
  selectedTemplateId.value = null;
  submitted.value = false;
};

// Confirm delete dialog
const confirmDelete = (template: EmailTemplate) => {
  templateToDelete.value = template;
  confirm.require({
    message: "Are you sure you want to delete this template?",
    header: "Confirmation",
    icon: "pi pi-exclamation-triangle",
    accept: deleteTemplate,
    acceptProps: {
      severity: "danger",
    },
  });
};

// Delete template
const deleteTemplate = async () => {
  if (!templateToDelete.value) return;

  try {
    loading.value = true;
    const response = await axios.delete(`/email_templates/${templateToDelete.value.id}`);

    // Remove from local array
    templates.value = templates.value.filter((t) => t.id !== templateToDelete.value?.id);

    // Show success message
    toast.add({
      severity: "success",
      summary: "Success",
      detail: "Email template deleted successfully",
      life: 3000,
    });

    // Reset if we were editing the deleted template
    if (selectedTemplateId.value === templateToDelete.value.id) {
      resetForm();
    }
    router.reload();
  } catch (error) {
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Failed to delete template",
      life: 3000,
    });
  } finally {
    loading.value = false;
    deleteDialog.value = false;
    templateToDelete.value = null;
  }
};

// Submit form (create or update)
const submitForm = async () => {
  submitted.value = true;

  // Validate form
  if (!subject.value || !messageContent.value) {
    toast.add({
      severity: "error",
      summary: "Validation Error",
      detail: "Subject and content are required",
      life: 3000,
    });
    return;
  }

  const templateData = {
    subject: subject.value,
    content: messageContent.value,
  };

  try {
    loading.value = true;

    if (isEditing.value) {
      const response = await axios.put(`/email_templates/${selectedTemplateId.value}`, templateData);

      const index = templates.value.findIndex((t) => t.id === selectedTemplateId.value);
      if (index !== -1) {
        templates.value[index] = {
          ...templates.value[index],
          ...templateData,
        };
      }

      toast.add({
        severity: "success",
        summary: "Success",
        detail: "Email template updated successfully",
        life: 3000,
      });
    } else {
      // Create new template
      const response = await axios.post("/email_templates", templateData);

      toast.add({
        severity: "success",
        summary: "Success",
        detail: "Email template created successfully",
        life: 3000,
      });
    }
    router.reload();

    // Reset form after successful submission
    resetForm();
  } catch (error) {
    toast.add({
      severity: "error",
      summary: "Error",
      detail: isEditing.value ? "Failed to update template" : "Failed to create template",
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Initialize data
const parseItemsData = () => {
  templates.value = props?.emails || [];
};

onMounted(() => {
  parseItemsData();
});

watchEffect(() => {
  if (props?.emails) {
    parseItemsData();
  }
});
</script>
