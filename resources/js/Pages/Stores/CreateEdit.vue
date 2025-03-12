<template>
  <div class="flex justify-center w-full px-4 py-6">
    <Card class="w-full max-w-[800px]">
      <template #title>
        <div class="flex items-center">
          <Button 
            icon="pi pi-arrow-left" 
            link 
            class="mr-2"
            @click="router.back()"
          />
          <h2 class="text-xl font-semibold">Create Store</h2>
        </div>
      </template>
      
      <template #content>
        <div class="space-y-6">
          <!-- Create Store Section -->
          <div class="space-y-4 flex justify-center gap-2 items-start">
            <div class="field w-full">
              <label class="block mb-2">Name:</label>
              <InputText 
                v-model="form.name" 
                placeholder="Enter store name"
                class="w-full" 
              />
            </div>
            
            <div class="field w-full !mt-0">
              <label class="block mb-2">Address:</label>
              <InputText 
                v-model="form.address" 
                placeholder="Enter store address"
                class="w-full" 
              />
            </div>
          </div>

          <!-- Create Default Admin Section -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium">Create Default Admin</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="field">
                <label class="block mb-2">Admin Name:</label>
                <InputText 
                  v-model="form.adminName" 
                  placeholder="Enter admin name"
                  class="w-full" 
                />
              </div>
              
              <div class="field">
                <label class="block mb-2">Email:</label>
                <InputText 
                  v-model="form.adminEmail" 
                  type="email" 
                  placeholder="Enter admin email"
                  class="w-full" 
                />
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="field w-full">
                <label class="block mb-2">Password:</label>
                <Password 
                  v-model="form.password" 
                  placeholder="Enter password"
                  class="w-full" 
                  toggleMask
                  fluid
                  :feedback="false"
                />
              </div>
              
              <div class="field w-full">
                <label class="block mb-2">Confirm Password:</label>
                <Password 
                  v-model="form.confirmPassword" 
                  placeholder="Confirm password"
                  class="w-full" 
                  toggleMask
                  fluid
                  :feedback="false"
                />
              </div>
            </div>
          </div>

          <!-- Receipt Settings Section -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium">Receipt Settings</h3>
            
            <div class="field">
              <label class="block mb-2">Header:</label>
              <Editor 
                v-model="form.header" 
                editorStyle="height: 200px"
                placeholder="Enter receipt header content"
              />
            </div>
            
            <div class="field">
              <label class="block mb-2">Footer:</label>
              <Editor 
                v-model="form.footer" 
                editorStyle="height: 200px"
                placeholder="Enter receipt footer content"
              />
            </div>
            
            <div class="field">
              <label class="block mb-2">Logo:</label>
              <div class="flex items-center">
                <FileUpload 
                  mode="basic" 
                  :multiple="false" 
                  accept="image/*" 
                  chooseLabel="Choose File"
                  class="w-auto"
                  @select="handleFileSelect"
                />
                <small class="text-gray-500 ml-3" v-if="!form.logo">No file chosen</small>
                <small class="text-gray-700 ml-3" v-else>{{ form.logo.name }}</small>
              </div>
            </div>

          <div class="flex justify-end gap-2 pt-4">
            <Button 
              label="RESET" 
              outlined
              @click="resetForm" 
            />
            <Button 
              label="SAVE" 
              @click="handleSubmit" 
            />
          </div>
        </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, toRefs, reactive } from 'vue';
import { Store } from '@/Lib/types';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Editor from 'primevue/editor';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import FileUpload from 'primevue/fileupload';
import Card from 'primevue/card';

const props = defineProps<{ store: Store }>();
const { store } = toRefs(props);
const toast = useToast();

const form = reactive({
  // Store details
  name: store.value?.name ?? '',
  address: store.value?.address ?? '',
  
  // Admin details
  adminName: '',
  adminEmail: '',
  password: '',
  confirmPassword: '',
  
  // Receipt settings
  header: store.value?.header ?? '',
  footer: store.value?.footer ?? '',
  logo: store.value?.logo ?? null
});

const handleFileSelect = (event: any) => {
  form.logo = event.files[0];
};

const handleSubmit = () => {
  if (form.password !== form.confirmPassword) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Passwords do not match', 
      life: 3000 
    });
    return;
  }
  
  console.log('Submitted form:', form);
  toast.add({ 
    severity: 'success', 
    summary: 'Success', 
    detail: 'Store saved successfully!', 
    life: 3000 
  });
};

const resetForm = () => {
  Object.assign(form, {
    name: store.value?.name ?? '',
    address: store.value?.address ?? '',
    adminName: '',
    adminEmail: '',
    password: '',
    confirmPassword: '',
    header: store.value?.header ?? '',
    footer: store.value?.footer ?? '',
    logo: store.value?.logo ?? null
  });
  toast.add({ 
    severity: 'info', 
    summary: 'Reset', 
    detail: 'Form has been reset', 
    life: 3000 
  });
};

defineOptions({ layout: AppLayout });
</script>