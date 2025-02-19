import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Aura from '@primevue/themes/aura';
import { definePreset } from '@primevue/themes';


const Noir = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{zinc.50}',
            100: '{zinc.100}',
            200: '{zinc.200}',
            300: '{zinc.300}',
            400: '{zinc.400}',
            500: '{zinc.500}',
            600: '{zinc.600}',
            700: '{zinc.700}',
            800: '{zinc.800}',
            900: '{zinc.900}',
            950: '{zinc.950}'
        },
        colorScheme: {
            light: {
                primary: {
                    color: '{zinc.950}',
                    inverseColor: '#ffffff',
                    hoverColor: '{zinc.900}',
                    activeColor: '{zinc.800}'
                },
                highlight: {
                    background: '{zinc.950}',
                    focusBackground: '{zinc.700}',
                    color: '#ffffff',
                    focusColor: '#ffffff'
                }
            },
            dark: {
                primary: {
                    color: '{zinc.50}',
                    inverseColor: '{zinc.950}',
                    hoverColor: '{zinc.100}',
                    activeColor: '{zinc.200}'
                },
                highlight: {
                    background: 'rgba(250, 250, 250, .16)',
                    focusBackground: 'rgba(250, 250, 250, .24)',
                    color: 'rgba(255,255,255,.87)',
                    focusColor: 'rgba(255,255,255,.87)'
                }
            }
        }
    }
});


import Button from "primevue/button"
import AppLayout from './Layouts/AppLayout.vue';
const appName = import.meta.env.VITE_APP_NAME || 'QuoteRefreshm';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
            .then((page) => {
                // Validar la estructura de la página resuelta
                const resolvedPage = page.default || page;
    
                // Aplicar el layout automáticamente para rutas bajo `/inventory`
                if (name.startsWith('Inventory/')) {
                    resolvedPage.layout = resolvedPage.layout || AppLayout;
                }
    
                return resolvedPage;
            });
    },
    
    setup({ el, App, props, plugin }) {
        // Crear la instancia de la aplicación
        const app = createApp({
            render: () => h(App, props),
        });

        // Registrar el mixin para manejar layouts dinámicos
        app.mixin({
            methods: {
                getLayout(component) {
                    if (component.layout) {
                        return require(`../Layouts/${component.layout}`).default;
                    }
                    return require('../Layouts/AppLayout').default; // Default layout
                },
            },
        });

        // Registrar plugins y montar la aplicación
        app.use(plugin).use(ZiggyVue).use(PrimeVue, {
            theme: {
                preset: Noir,
                options: {
                    darkModeSelector: '.my-app-dark',
                }
            }
        })
        .use(ToastService)
        .component('Button', Button)
        .component('Toast', Toast)
        .mount(el);

    
    },
    progress: {
        color: '#4B5563',
    },
});
