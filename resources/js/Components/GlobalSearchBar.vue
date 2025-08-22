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
      :minLength="3"
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

// Ziggy `route` helper is provided globally at runtime; declare for TS
declare const route: any;

const emits = defineEmits<{
  (e: 'search', payload: string): void
}>();

const query = ref('');
const suggestions = ref<Array<{ label: string; value: any }>>([]);

// Cada vez que el usuario escribe, emitimos el evento
watch(query, (q: string) => emits('search', q));

async function loadSuggestions({ query }: { query: string }) {
  try {
    const res = await axios.get(route('items.search'), { params: { q: query } });
    console.log('Suggestions loaded:', res.data);
    suggestions.value = res.data.map((it: any) => ({
      value: it.id,
      label: [
        it.model,
        it.grade ? `Grade: ${it.grade}` : null,
        it.battery ? `Battery: ${it.battery}%` : null,
        it.cost != null ? `Purchase Price: $${Number(it.cost).toFixed(2)}` : null,
        it.selling_price != null ? `Sold Price: $${Number(it.selling_price).toFixed(2)}` : null,
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