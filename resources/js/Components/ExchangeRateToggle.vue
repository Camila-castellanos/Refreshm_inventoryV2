<template>
  <button 
    @click="toggleExchange()"
    class="max-w-24 px-6 py-4 border rounded-full border-gray-400 font-semibold shadow hover:shadow-md transition-shadow"
  >
    <span v-if="country.toLowerCase() == 'ca'" class="flex justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="16" viewBox="0 0 9600 4800">
        <title>Flag of Canada</title>
        <path fill="#f00" d="m0 0h2400l99 99h4602l99-99h2400v4800h-2400l-99-99h-4602l-99 99H0z" />
        <path fill="#fff"
          d="m2400 0h4800v4800h-4800zm2490 4430-45-863a95 95 0 0 1 111-98l859 151-116-320a65 65 0 0 1 20-73l941-762-212-99a65 65 0 0 1-34-79l186-572-542 115a65 65 0 0 1-73-38l-105-247-423 454a65 65 0 0 1-111-57l204-1052-327 189a65 65 0 0 1-91-27l-332-652-332 652a65 65 0 0 1-91 27l-327-189 204 1052a65 65 0 0 1-111 57l-423-454-105 247a65 65 0 0 1-73 38l-542-115 186 572a65 65 0 0 1-34 79l-212 99 941 762a65 65 0 0 1 20 73l-116 320 859-151a95 95 0 0 1 111 98l-45 863z" />
      </svg>
    </span>
    <span v-else>
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32"
        height="16" viewBox="0 0 7410 3900">
        <path d="M0,0h7410v3900H0" fill="#b31942" />
        <path d="M0,450H7410m0,600H0m0,600H7410m0,600H0m0,600H7410m0,600H0" stroke="#FFF"
          stroke-width="300" />
        <path d="M0,0h2964v2100H0" fill="#0a3161" />
        <g fill="#FFF">
          <g id="s18">
            <g id="s9">
              <g id="s5">
                <g id="s4">
                  <path id="s"
                    d="M247,90 317.534230,307.082039 132.873218,172.917961H361.126782L176.465770,307.082039z" />
                  <use xlink:href="#s" y="420" />
                  <use xlink:href="#s" y="840" />
                  <use xlink:href="#s" y="1260" />
                </g>

                <use xlink:href="#s" y="1680" />
              </g>
              <use xlink:href="#s4" x="247" y="210" />
            </g>
            <use xlink:href="#s9" x="494" />
          </g>
          <use xlink:href="#s18" x="988" />
          <use xlink:href="#s9" x="1976" />
          <use xlink:href="#s5" x="2470" />
        </g>
      </svg>
    </span>
  </button>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

interface Props {
  // Items no longer needed here
}

const props = defineProps<Props>();
const page = usePage();

const emit = defineEmits<{
  exchangeToggled: [value: boolean, exchangeRate: number | null, currency: string];
}>();

const country = ref("CA");
const exchangeRate = ref(1);
const exchangeActive = ref(false);
const isLoadingExchangeRate = ref(false);

const updateStateFromCurrency = (currency: string) => {
  if (currency === 'USD') {
    country.value = 'USA';
    exchangeActive.value = true;
  } else {
    country.value = 'CA';
    exchangeActive.value = false;
  }
};

const toggleExchange = async () => {
  const newCurrency = exchangeActive.value ? 'CAD' : 'USD';
  updateStateFromCurrency(newCurrency);
  
  const rate = exchangeActive.value ? exchangeRate.value : null;
  
  // Persist preference
  if (page.props.customer_auth?.user) {
    try {
      await axios.put(route('public-store.portal.profile'), {
        currency: newCurrency
      });
      // Update local Inertia state
      page.props.customer_auth.user.currency = newCurrency;
    } catch (error) {
      console.error('Failed to persist currency preference:', error);
    }
  } else {
    localStorage.setItem('selected_currency', newCurrency);
  }

  emit('exchangeToggled', exchangeActive.value, rate, newCurrency);
};

// Fetch exchange rate from server API
const fetchExchangeRate = async (): Promise<boolean> => {
  try {
    isLoadingExchangeRate.value = true;
    const response = await fetch('/api/exchange-rate');
    
    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`);
    }

    const data = await response.json();

    if (data.success && data.rate) {
      exchangeRate.value = data.rate;
      console.log(`Exchange rate loaded:`, exchangeRate.value);
      return true;
    } else {
      throw new Error(data.message || 'Failed to fetch exchange rate');
    }
  } catch (error) {
    console.error('Error fetching exchange rates:', error);
    return false;
  } finally {
    isLoadingExchangeRate.value = false;
  }
};

// Initialize on mount
onMounted(async () => {
  const currentCurrency = page.props.customer_auth?.user?.currency || 
                          localStorage.getItem('selected_currency') || 
                          page.props.customer_auth?.guest_currency ||
                          'CAD';
  updateStateFromCurrency(currentCurrency);
  
  // Fetch exchange rate
  await fetchExchangeRate();
  
  // Notify parent if USD is active
  if (exchangeActive.value) {
    emit('exchangeToggled', true, exchangeRate.value, 'USD');
  }
});

// Expose methods if needed
defineExpose({
  fetchExchangeRate
});
</script>

<style scoped>
button {
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

button:hover {
  opacity: 0.8;
}
</style>
