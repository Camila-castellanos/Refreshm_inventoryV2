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

interface Props {
  items?: any[];
}

const props = withDefaults(defineProps<Props>(), {
  items: () => []
});

const emit = defineEmits<{
  exchangeToggled: [value: boolean];
}>();

const country = ref("CA");
const exchangeRate = ref(1);
const exchangeActive = ref(false);
const isLoadingExchangeRate = ref(false);
const isCached = ref(false);
const originalPrices = new Map(); // Store original CAD prices

const toggleExchange = () => {
  exchangeActive.value = !exchangeActive.value;
  // Toggle country manually when user clicks
  country.value = country.value === "USA" ? "CA" : "USA";
};

// Detect user's country using GeoIP
const detectUserCountry = async (): Promise<string> => {
  try {
    const response = await fetch('https://ipapi.co/json/');
    const data = await response.json();
    const countryCode = data.country_code;
    
    console.log(`User country detected: ${countryCode}`);
    
    if (countryCode === 'US') {
      return 'USA';
    }
    return 'CA';
  } catch (error) {
    console.error('Error detecting country:', error);
    return 'CA'; // Default to CA
  }
};

// Watch exchangeActive to update prices and emit event
watch(exchangeActive, (newValue) => {
  if (props.items && props.items.length > 0) {
    props.items.forEach(item => {
      // Store original price if not already stored
      if (!originalPrices.has(item.id)) {
        originalPrices.set(item.id, item.selling_price);
      }
      
      const originalPrice = originalPrices.get(item.id);
      // Convert based on exchange state
      item.selling_price = newValue 
        ? Math.round(originalPrice / parseFloat(exchangeRate.value.toFixed(2))) 
        : originalPrice;
    });
  }

  emit('exchangeToggled', newValue);
});

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
      isCached.value = data.cached || false;
      
      const source = isCached.value ? 'cache' : 'API';
      console.log(`Exchange rate loaded from ${source}:`, exchangeRate.value);
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

// Refresh exchange rate (bypass cache)
const refreshExchangeRate = async (): Promise<boolean> => {
  try {
    isLoadingExchangeRate.value = true;
    const response = await fetch('/api/exchange-rate/refresh', {
      method: 'POST'
    });
    
    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`);
    }

    const data = await response.json();

    if (data.success && data.rate) {
      exchangeRate.value = data.rate;
      isCached.value = false;
      console.log('Exchange rate refreshed:', exchangeRate.value);
      return true;
    } else {
      throw new Error(data.message || 'Failed to refresh exchange rate');
    }
  } catch (error) {
    console.error('Error refreshing exchange rates:', error);
    return false;
  } finally {
    isLoadingExchangeRate.value = false;
  }
};

// Fetch exchange rate on mount
onMounted(async () => {
  // Detect user country first
  const userCountry = await detectUserCountry();
  
  // Fetch exchange rate first
  await fetchExchangeRate();
  
  // Store original prices before any conversion
  if (props.items && props.items.length > 0) {
    props.items.forEach(item => {
      originalPrices.set(item.id, item.selling_price);
    });
  }
  
  // Set initial country based on detection
  if (userCountry === 'USA') {
    country.value = 'USA';
    // Automatically activate exchange for USA users
    exchangeActive.value = true;
    
    // Update prices for USA (convert from CAD to USD)
    if (props.items && props.items.length > 0) {
      props.items.forEach(item => {
        item.selling_price = Math.round(item.selling_price / parseFloat(exchangeRate.value.toFixed(2)));
      });
    }
  }
});

// Expose refresh method if needed
defineExpose({
  refreshExchangeRate
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
