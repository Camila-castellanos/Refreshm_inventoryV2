<template>
  <FloatLabel class="w-full">
    <AutoComplete
      v-model="query"
      :suggestions="suggestions"
      @complete="loadSuggestions"
      @item-select="onSelect"
      class="w-full"                       
      inputClass="w-full"         
      optionLabel="label"
      :delay="300"
      :minLength="4"
    />
    <label>Global Search</label>
  </FloatLabel>
</template>

<script setup lang="ts">
import { ref, watch, defineEmits } from 'vue';
import axios from 'axios';
import AutoComplete from 'primevue/autocomplete';
import FloatLabel from 'primevue/floatlabel';
import { router } from "@inertiajs/vue3";

const emits = defineEmits<{
  (e: 'search', payload: string): void
}>();

const query = ref('');
const suggestions = ref<Array<{ label: string; value: any }>>([]);

// Cada vez que el usuario escribe, emitimos el evento
watch(query, q => emits('search', q));

async function loadSuggestions({ query }: { query: string }) {
  try {
    const res = await axios.get(route('items.search'), { params: { q: query } });
    console.log('Suggestions loaded:', res.data);
    suggestions.value = res.data.map((it: any) => ({
      value: it.id,
      label: [           
        it.model,                             
        it.imei ? `IMEI: ${it.imei}` : null,   
        it.vendor?.vendor                     
      ]
      .filter(Boolean)                         
      .join(' · ')                            
    }));
    console.log('Formatted suggestions:', suggestions.value);
  } catch {
    suggestions.value = [];
    console.error('Error loading suggestions');
  }
}

function onSelect(item: any) {
  console.log('Selected item:', item);
  router.visit(route('items.edit', btoa(item.value.value)));
}
</script>