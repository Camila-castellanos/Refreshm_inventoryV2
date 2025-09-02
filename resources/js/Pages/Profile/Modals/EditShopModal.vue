<template>
  <Dialog v-model:visible="visible" @hide="onHide" header="Edit Shop" modal style="width: 32rem">
    <div class="p-fluid p-2 flex flex-col items-center">
      <FloatLabel class="w-full sm:w-3/4 z-50" variant="on">
        <InputText id="shopName" v-model="name" class="w-full" />
        <label for="shopName">{{ name }}</label>
      </FloatLabel>
    </div>

    <div class="flex justify-end gap-2 mt-4">
      <Button class="p-button-text" label="Cancel" icon="pi pi-times" @click="cancel" />
      <Button label="Save" icon="pi pi-check" :loading="saving" @click="save" />
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'
import { useToast, Dialog, Button, InputText} from 'primevue'
import FloatLabel from 'primevue/floatlabel';
const props = defineProps({
  shopId: {
    type: [Number, String],
    required: true,
  },
  modelValue: {
    // using v-model:visible -> modelValue passed as visible
    type: Boolean,
    default: false,
  },
  initialName: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const visible = ref(props.modelValue)
const name = ref('')
const saving = ref(false)
const toast = useToast()

watch(() => props.modelValue, (v) => (visible.value = v))
watch(visible, (v) => emit('update:modelValue', v))

const loadShop = async () => {
  if (!props.shopId) return
  // if initialName provided, prefer it (no fetch) to avoid extra roundtrip
  if (props.initialName && props.initialName.length > 0) {
    name.value = props.initialName
    return
  }

  try {
    const res = await axios.get(route('shops.show', props.shopId))
    name.value = res.data.name || ''
  } catch (e) {
    console.error('Could not load shop', e)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Could not load shop details', life: 3000 })
  }
}

watch(() => [props.shopId, props.initialName, props.modelValue], () => {
  if (props.modelValue) loadShop()
})

onMounted(() => {
  if (visible.value) loadShop()
})

const save = async () => {
  if (!name.value || !name.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Invalid', detail: 'Name cannot be empty', life: 3000 })
    return
  }
  saving.value = true
  try {
    // send update request
    const payload = { name: name.value.trim() }
    await axios.put(route('shops.update', props.shopId), payload)
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Shop updated', life: 3000 })
    emit('saved', { id: props.shopId, name: name.value.trim() })
    visible.value = false
  } catch (e) {
    console.error('Save failed', e)
    toast.add({ severity: 'error', summary: 'Save Failed', detail: 'Could not save shop', life: 3000 })
  } finally {
    saving.value = false
  }
}

const cancel = () => {
  visible.value = false
}

const onHide = () => {
  // Ensure parent is updated when Dialog is closed via the X or backdrop
  visible.value = false
  emit('update:modelValue', false)
}
</script>

<style scoped>
.p-float-label label {
  white-space: normal;
  word-break: break-word;
  overflow: visible;
}
</style>
