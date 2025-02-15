import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/asd',
        name: 'home',
        component: () => import('../Pages/Welcome.vue'), // Ruta al componente Home.vue
    },
    {
        path: '/about',
        name: 'about',
        component: () => import('../Pages/About.vue'), // Ruta al componente About.vue
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;