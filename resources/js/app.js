import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { definePreset } from "@primevue/themes";
import Aura from "@primevue/themes/aura";
import axios from "axios";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { Tooltip } from "primevue";
import Button from "primevue/button";
import PrimeVue from "primevue/config";
import ConfirmationService from "primevue/confirmationservice";
import DialogService from "primevue/dialogservice";
import Toast from "primevue/toast";
import ToastService from "primevue/toastservice";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import AppLayout from "./Layouts/AppLayout.vue";
import { dialogManager } from "./dialogManager";
const appName = import.meta.env.VITE_APP_NAME || "QuoteRefreshm";

axios.defaults.withCredentials = true;
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 419) {
      dialogManager.showSessionDialog = true;
      dialogManager.onLoginPage = window.location.pathname === "/login";
      return new Promise(() => {}); 
    }

    return Promise.reject(error);
  }
);

const Noir = definePreset(Aura, {
  semantic: {
    primary: {
      50: "{zinc.50}",
      100: "{zinc.100}",
      200: "{zinc.200}",
      300: "{zinc.300}",
      400: "{zinc.400}",
      500: "{zinc.500}",
      600: "{zinc.600}",
      700: "{zinc.700}",
      800: "{zinc.800}",
      900: "{zinc.900}",
      950: "{zinc.950}",
    },
    colorScheme: {
      light: {
        primary: {
          color: "{zinc.950}",
          inverseColor: "#ffffff",
          hoverColor: "{zinc.900}",
          activeColor: "{zinc.800}",
        },
        highlight: {
          background: "{zinc.950}",
          focusBackground: "{zinc.700}",
          color: "#ffffff",
          focusColor: "#ffffff",
        },
      },
      dark: {
        primary: {
          color: "{zinc.50}",
          inverseColor: "{zinc.950}",
          hoverColor: "{zinc.100}",
          activeColor: "{zinc.200}",
        },
        highlight: {
          background: "rgba(250, 250, 250, .16)",
          focusBackground: "rgba(250, 250, 250, .24)",
          color: "rgba(255,255,255,.87)",
          focusColor: "rgba(255,255,255,.87)",
        },
      },
    },
  },
  components: {
    button: {
      defineProps: {
        severity: 'secondary'
      }
    }
  }
});

createInertiaApp({
  title: (title) => `${appName.toLowerCase()}.`,
  resolve: (name) => {
    return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob("./Pages/**/*.vue")).then((page) => {
      // Validar la estructura de la página resuelta
      const resolvedPage = page.default || page;

      // Aplicar el layout automáticamente para rutas bajo `/inventory`
      if (name.startsWith("Inventory/")) {
        resolvedPage.layout = resolvedPage.layout || AppLayout;
      }

      return resolvedPage;
    });
  },

  setup({ el, App, props, plugin }) {
    // Crear la instancia de la aplicación
    const app = createApp({
      render: () =>  h(App, props),
    });

    // Registrar el mixin para manejar layouts dinámicos
    app.mixin({
      methods: {
        getLayout(component) {
          if (component.layout) {
            return require(`../Layouts/${component.layout}`).default;
          }
          return require("../Layouts/AppLayout").default; // Default layout
        },
      },
    });

    app.config.globalProperties.$axios = axios;

    app.directive("tooltip", Tooltip);

    // Registrar plugins y montar la aplicación
    app
      .use(plugin)
      .use(ZiggyVue)
      .use(PrimeVue, {
        theme: {
          preset: Noir,
          options: {
            darkModeSelector: ".my-app-dark",
          },
        },
      })
      .use(DialogService)
      .use(ToastService)
      .use(ConfirmationService)
      .component("Button", Button)
      .component("Toast", Toast)
      .mount(el);
  },
  progress: {
    color: "#4B5563",
  },
});
