import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import Button from "primevue/button"
const appName = import.meta.env.VITE_APP_NAME || 'QuoteRefreshm';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
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
                preset: Aura
            }
        }).mount(el);

        app.component('Button', Button)
    },
    progress: {
        color: '#4B5563',
    },
});
