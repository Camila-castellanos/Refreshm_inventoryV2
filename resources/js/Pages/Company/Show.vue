<template>
    <AppLayout title="Company Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Company Settings
            </h2>
        </template>

        <div>
            <div class="max-w-[95%] mx-auto py-10 sm:px-6 lg:px-8">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">General</Tab>
                        <Tab value="1">Team Members</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="0">
                            <div class="p-6">
                                <div v-if="company" class="space-y-10">
                                    <div>
                                        <UpdateCompanyNameForm :company="company" />
                                        <SectionBorder />
                                    </div>
                                    <div>
                                        <UpdateCompanyLogoForm />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel value="1">
                            <div class="mt-6">
                                <TeamManager :members="members" />
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateCompanyNameForm from './Partials/UpdateCompanyNameForm.vue';
import UpdateCompanyLogoForm from './Partials/UpdateCompanyLogoForm.vue';
import TeamManager from './Partials/TeamManager.vue';
import { Tabs, TabList, Tab, TabPanels, TabPanel } from 'primevue';

defineProps({
    company: Object,
    members: Array,
});
</script>

<style scoped>
:deep(.p-tabpanels) {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

:deep(.p-tabpanel) {
    padding-left: 0 !important;
    padding-right: 0 !important;
}
</style>
