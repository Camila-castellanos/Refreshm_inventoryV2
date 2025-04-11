<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 p-4 gap-4">
        <section class="text-center">
            <h1 class="text-3xl md:text-4xl font-semibold text-gray-600">
                You've been invited to join {{ companyName }}
            </h1>

        </section>
        <Button label="Join" @click="acceptInvitation" icon="pi pi-users" class="p-4" />
    </div>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted } from "vue";
import { Button } from "primevue";
import { router } from '@inertiajs/vue3';

const companyName = ref()

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const encodedCompany = urlParams.get("company")
    companyName.value = encodedCompany ? atob(encodedCompany) : "";
});

const acceptInvitation = () => {
    const payload = {
        companyName: companyName.value
    }

    router.post(route('invitation'), payload, {
        onSuccess: () => {
            console.log('Item approved successfully!');
        },
        onError: (errors) => {
            console.error('Error approving item:', errors);
        },
        onFinish: () => {
            console.log('Approval request finished.');
        },
    });
}

defineOptions({ layout: AppLayout });
</script>s