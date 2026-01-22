<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Scan Barcode"
    modal
    :closable="true"
    class="w-[450px] max-w-full"
  >
    <div class="space-y-4">
      <Tabs v-model="activeTab">
        <TabList>
          <Tab value="camera">Scan</Tab>
          <Tab value="image">Upload Image</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="camera">
            <div id="barcode-reader" class="w-full bg-gray-100 rounded-lg overflow-hidden" style="min-height: 250px;">
              <div v-if="!cameraActive" class="flex flex-col items-center justify-center h-[250px] text-gray-500">
                <i class="pi pi-camera !text-4xl mb-2"></i>
                <p>Camera preview will appear here</p>
              </div>
            </div>
            <div class="mt-3 flex justify-center">
              <Button
                :label="cameraActive ? 'Stop Camera' : 'Start Camera'"
                :icon="cameraActive ? 'pi pi-stop' : 'pi pi-camera'"
                @click="cameraActive ? stopCamera() : startCamera()"
                :severity="cameraActive ? 'danger' : 'primary'"
              />
            </div>
          </TabPanel>

          <TabPanel value="image">
            <div class="space-y-3">
              <FileUpload
                mode="basic"
                accept="image/*"
                choose-label="Choose Image"
                @select="onImageSelected"
                auto
              />
              <img
                v-if="previewImage"
                :src="previewImage"
                class="w-full rounded-lg border border-gray-200"
                style="max-height: 200px; object-fit: contain;"
              />
              <Button
                label="Scan Image"
                icon="pi pi-qrcode"
                :loading="processingImage"
                :disabled="!selectedFile"
                @click="scanFromImage"
                fluid
              />
            </div>
            <div id="qr-reader" class="hidden"></div>
          </TabPanel>
        </TabPanels>
      </Tabs>

      <div v-if="detectedCodes.length > 0" class="mt-4">
        <p class="text-sm font-medium text-gray-700 mb-2">
          {{ detectedCodes.length }} code(s) detected:
        </p>
        <div class="space-y-2 max-h-48 overflow-y-auto">
          <div
            v-for="(item, index) in detectedCodes"
            :key="index"
            @click="selectedCodeIndex = index"
            :class="[
              'p-3 rounded-lg cursor-pointer border transition-colors',
              selectedCodeIndex === index
                ? 'bg-blue-50 border-blue-500'
                : 'bg-gray-50 border-gray-200 hover:bg-gray-100'
            ]"
          >
            <div class="flex items-center justify-between">
              <span class="font-mono text-sm">{{ item.code }}</span>
              <span class="text-xs text-gray-500">{{ item.format }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-2">
        <Button
          label="Cancel"
          severity="secondary"
          text
          @click="close"
        />
        <Button
          label="Use Code"
          icon="pi pi-check"
          :disabled="selectedCodeIndex === null && detectedCodes.length === 0"
          :loading="processingImage"
          @click="confirm"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import Quagga from '@ericblade/quagga2';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import FileUpload from 'primevue/fileupload';

const props = defineProps<{
  visible: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void;
  (e: 'scanned', value: string): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: (v) => emit('update:visible', v)
});

const activeTab = ref<'camera' | 'image'>('camera');
const error = ref('');
const cameraActive = ref(false);
const processingImage = ref(false);
const previewImage = ref<string | null>(null);
const selectedFile = ref<File | null>(null);
const detectedCodes = ref<Array<{ code: string; format: string }>>([]);
const selectedCodeIndex = ref<number | null>(null);

let detectionHandler: ((result: any) => void) | null = null;

async function preprocessImage(file: File): Promise<void> {
  const imageBitmap = await createImageBitmap(file);
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  canvas.width = imageBitmap.width;
  canvas.height = imageBitmap.height;
  
  ctx.drawImage(imageBitmap, 0, 0);
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const data = imageData.data;
  
  for (let i = 0; i < data.length; i += 4) {
    const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
    const color = avg < 120 ? 0 : 255;
    data[i] = color;
    data[i + 1] = color;
    data[i + 2] = color;
  }
  
  ctx.putImageData(imageData, 0, 0);
}

async function scanWithPreprocessing(imageUrl: string): Promise<any[]> {
  const html5QrCode = new Html5Qrcode('qr-reader');
  
  try {
    const response = await fetch(imageUrl);
    const blob = await response.blob();
    const file = new File([blob], 'original.jpg', { type: 'image/jpeg' });
    
    await preprocessImage(file);
    
    const result = await html5QrCode.scanFile(file, false, {
      formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128]
    });
    
    const codes = Array.isArray(result) ? result : [result];
    return codes.map((code: string) => ({ code, format: 'CODE-128' }));
  } catch {
    try {
      const response = await fetch(imageUrl);
      const blob = await response.blob();
      const file = new File([blob], 'original.jpg', { type: 'image/jpeg' });
      
      const result = await html5QrCode.scanFile(file, false, {
        formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128]
      });
      
      const codes = Array.isArray(result) ? result : [result];
      return codes.map((code: string) => ({ code, format: 'CODE-128' }));
    } catch {
      return [];
    }
  }
}

function onDetected(result: any) {
  if (detectedCodes.value.length >= 4) return;
  if (!result || !result.codeResult) return;
  const code = result.codeResult.code;
  const format = result.codeResult.format;
  if (!detectedCodes.value.some(c => c.code === code)) {
    detectedCodes.value.push({ code, format });
  }
}

async function startCamera(): Promise<void> {
  error.value = '';
  detectedCodes.value = [];
  selectedCodeIndex.value = null;

  return new Promise((resolve, reject) => {
    Quagga.init({
      inputStream: {
        name: 'Live',
        type: 'LiveStream',
        target: document.querySelector('#barcode-reader'),
        constraints: { facingMode: 'environment', width: { min: 640 }, height: { min: 480 } }
      },
      locator: { patchSize: 'large', halfSample: true },
      numOfWorkers: navigator.hardwareConcurrency || 2,
      decoder: { readers: ['code_128_reader', 'code_39_reader', 'ean_reader', 'ean_8_reader', 'upc_reader', 'upc_e_reader'] },
      locate: true
    }, (err: any) => {
      if (err) {
        error.value = 'Could not start camera.';
        reject(err);
        return;
      }
      detectionHandler = onDetected;
      Quagga.onDetected(detectionHandler);
      Quagga.start();
      cameraActive.value = true;
      resolve();
    });
  });
}

function stopCamera() {
  if (detectionHandler) { Quagga.offDetected(detectionHandler); detectionHandler = null; }
  Quagga.stop();
  cameraActive.value = false;
}

function onImageSelected(event: { files: File[] }) {
  const file = event.files[0];
  if (file) {
    selectedFile.value = file;
    detectedCodes.value = [];
    selectedCodeIndex.value = null;
    const reader = new FileReader();
    reader.onload = (e) => { previewImage.value = e.target?.result as string; };
    reader.readAsDataURL(file);
  }
}

async function scanFromImage() {
  if (!selectedFile.value) return;
  error.value = '';
  processingImage.value = true;
  detectedCodes.value = [];
  selectedCodeIndex.value = null;

  try {
    const imageUrl = URL.createObjectURL(selectedFile.value);
    const codes = await scanWithPreprocessing(imageUrl);
    
    if (codes.length > 0) {
      detectedCodes.value = codes;
    } else {
      error.value = 'No barcode found. Try a clearer image.';
    }
  } catch {
    error.value = 'Error scanning image.';
  } finally {
    processingImage.value = false;
  }
}

function confirm() {
  if (selectedCodeIndex.value !== null) {
    const code = detectedCodes.value[selectedCodeIndex.value].code;
    emit('scanned', code);
    close();
  } else if (detectedCodes.value.length > 0) {
    error.value = 'Please select a code.';
  } else {
    error.value = 'No code detected.';
  }
}

function close() {
  if (detectionHandler) { Quagga.offDetected(detectionHandler); detectionHandler = null; }
  Quagga.stop();
  cameraActive.value = false;
  detectedCodes.value = [];
  selectedCodeIndex.value = null;
  error.value = '';
  previewImage.value = null;
  selectedFile.value = null;
  activeTab.value = 'camera';
  dialogVisible.value = false;
}

watch(() => props.visible, (isOpen) => {
  if (isOpen && activeTab.value === 'camera' && !cameraActive.value) {
    setTimeout(() => startCamera(), 100);
  }
});

onBeforeUnmount(() => { close(); });
</script>

<style scoped>
#barcode-reader { border: 2px dashed #e5e7eb; }
#barcode-reader :deep(video) { width: 100%; height: 250px; object-fit: cover; border-radius: 0.5rem; }
#barcode-reader :deep(canvas) { display: none; }
#qr-reader { display: none; }
</style>
