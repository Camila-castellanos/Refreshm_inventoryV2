<template>
  <Dialog 
    v-model:visible="visible" 
    header="Recent System Logins" 
    :modal="true" 
    class="w-full max-w-2xl"
    :dismissableMask="true"
  >
    <DataTable 
      :value="logins" 
      class="p-datatable-sm" 
      stripedRows 
      paginator 
      :rows="10" 
      responsiveLayout="scroll"
    >
      <Column field="user.name" header="User" sortable>
        <template #body="slotProps">
          <div class="flex items-center gap-2">
            <i class="pi pi-user text-gray-400"></i>
            <span class="font-medium text-sm">{{ slotProps.data.user?.name || 'Unknown' }}</span>
          </div>
        </template>
      </Column>
      
      <Column field="login_at" header="Date & Time" sortable>
        <template #body="slotProps">
          <span class="text-xs text-gray-600">
            {{ formatDateTime(slotProps.data.login_at) }}
          </span>
        </template>
      </Column>
      
      <Column field="ip_address" header="IP Address">
        <template #body="slotProps">
          <code class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded text-gray-500 font-mono">
            {{ slotProps.data.masked_ip }}
          </code>
        </template>
      </Column>
      
      <Column header="Device" class="hidden md:table-cell">
        <template #body="slotProps">
          <i :class="getDeviceIcon(slotProps.data.user_agent)" class="text-gray-400 mr-2" v-tooltip="slotProps.data.user_agent"></i>
          <span class="text-[10px] text-gray-500 truncate max-w-[100px] inline-block align-middle">
            {{ simplifyUserAgent(slotProps.data.user_agent) }}
          </span>
        </template>
      </Column>
    </DataTable>

    <template #footer>
      <Button label="Close" icon="pi pi-times" @click="visible = false" text />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { format } from 'date-fns';

const props = defineProps<{
  modelValue: boolean;
  logins: any[];
}>();

const emit = defineEmits(['update:modelValue']);

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

const formatDateTime = (dateString: string) => {
  try {
    return format(new Date(dateString), 'MMM d, h:mm a');
  } catch (e) {
    return dateString;
  }
};

const getDeviceIcon = (ua: string) => {
  if (!ua) return 'pi pi-question';
  const lower = ua.toLowerCase();
  if (lower.includes('mobi')) return 'pi pi-mobile';
  if (lower.includes('tablet')) return 'pi pi-tablet';
  return 'pi pi-desktop';
};

const simplifyUserAgent = (ua: string) => {
  if (!ua) return 'Unknown';
  if (ua.includes('Chrome')) return 'Chrome';
  if (ua.includes('Firefox')) return 'Firefox';
  if (ua.includes('Safari')) return 'Safari';
  if (ua.includes('Edge')) return 'Edge';
  return 'Browser';
};
</script>
