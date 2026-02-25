<template>
  <div class="col-span-6">
    <div class="w-full">
      <Card class="shadow-none">
        <template #content>
          <div class="space-y-6">
            <div class="flex justify-between items-center mb-4 px-6">
              <h3 class="text-lg font-medium">Team Members</h3>
              <Button label="Add Member" icon="pi pi-plus" size="small" @click="openAddMemberModal" />
            </div>

            <!-- Team Members List -->
            <DataTable :value="members" stripedRows tableStyle="min-width: 50rem">
                <Column field="name" header="Name">
                  <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <img v-if="slotProps.data.profile_photo_url" :src="slotProps.data.profile_photo_url" alt="" class="w-8 h-8 rounded-full object-cover" />
                        <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                            {{ slotProps.data.name.charAt(0) }}
                        </div>
                        <span>{{ slotProps.data.name }}</span>
                    </div>
                  </template>
                </Column>
                <Column field="email" header="Email"></Column>
                <Column field="role" header="Role">
                  <template #body="slotProps">
                    <Tag :value="slotProps.data.role" :severity="getRoleSeverity(slotProps.data.role)" />
                  </template>
                </Column>
                <Column header="Actions" :style="{ width: '10%' }">
                  <template #body="slotProps">
                    <div class="flex gap-2 justify-end" v-if="$page.props.auth.user.id !== slotProps.data.id">
                        <Button icon="pi pi-pencil" text rounded size="small" aria-label="Edit Role" @click="openEditRoleModal(slotProps.data)" />
                        <Button icon="pi pi-eye" text rounded size="small" aria-label="Permissions" @click="openPermissionsModal(slotProps.data)" />
                        <Button icon="pi pi-trash" text rounded severity="danger" size="small" aria-label="Remove" @click="confirmRemoveMember(slotProps.data)" />
                    </div>
                    <span v-else class="text-xs text-gray-400 italic">You</span>
                  </template>
                </Column>
              </DataTable>
            </div>
          </template>
        </Card>
      </div>

    <!-- Edit Permissions Dialog -->
    <Dialog v-model:visible="editPermissionsDialog" modal header="Manage Permissions" :style="{ width: '30rem' }">
        <div class="flex flex-col gap-4">
             <p>Select the pages <strong>{{ editingPermissionsMember?.name }}</strong> can access.</p>
             <div class="grid grid-cols-2 gap-4">
                 <div v-for="perm in availablePermissions" :key="perm" class="flex items-center gap-2">
                     <Checkbox v-model="permissionForm.permissions" :inputId="perm" :value="perm" />
                     <label :for="perm" class="cursor-pointer">{{ perm }}</label>
                 </div>
             </div>
        </div>
        <template #footer>
            <Button label="Cancel" text severity="secondary" @click="editPermissionsDialog = false" />
            <Button label="Save" icon="pi pi-check" @click="updatePermissions" :loading="permissionForm.processing" />
        </template>
    </Dialog>

    <!-- Add Member Dialog -->
    <Dialog v-model:visible="addMemberDialog" modal header="Add New Team Member" :style="{ width: '30rem' }">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="new-name">Name</label>
                <InputText id="new-name" v-model="addForm.name" autocomplete="off" />
                <small v-if="addForm.errors.name" class="text-red-500">{{ addForm.errors.name }}</small>
            </div>
            <div class="flex flex-col gap-2">
                <label for="new-email">Email</label>
                <InputText id="new-email" v-model="addForm.email" autocomplete="off" />
                <small v-if="addForm.errors.email" class="text-red-500">{{ addForm.errors.email }}</small>
            </div>
            <div class="flex flex-col gap-2">
                <label for="new-password">Password</label>
                <Password id="new-password" v-model="addForm.password" :feedback="false" toggleMask fluid />
                <small v-if="addForm.errors.password" class="text-red-500">{{ addForm.errors.password }}</small>
            </div>
            <div class="flex flex-col gap-2">
                <label for="new-password-confirmation">Confirm Password</label>
                <Password id="new-password-confirmation" v-model="addForm.password_confirmation" :feedback="false" toggleMask fluid />
            </div>
            <div class="flex flex-col gap-2">
                <label for="new-role">Role</label>
                <Select v-model="addForm.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Select a role" fluid />
                <small v-if="addForm.errors.role" class="text-red-500">{{ addForm.errors.role }}</small>
            </div>
        </div>
        <template #footer>
            <Button label="Cancel" text severity="secondary" @click="addMemberDialog = false" />
            <Button label="Add" icon="pi pi-check" @click="storeMember" :loading="addForm.processing" />
        </template>
    </Dialog>

    <!-- Edit Role Dialog -->
    <Dialog v-model:visible="editRoleDialog" modal header="Update Member Role" :style="{ width: '25rem' }">
        <div class="flex flex-col gap-4">
            <p>Select a new role for <strong>{{ editingMember?.name }}</strong>.</p>
            <div class="flex flex-col gap-2">
                <label for="edit-role">Role</label>
                <Select v-model="updateRoleForm.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Select a role" fluid />
                <small v-if="updateRoleForm.errors.role" class="text-red-500">{{ updateRoleForm.errors.role }}</small>
            </div>
        </div>
        <template #footer>
            <Button label="Cancel" text severity="secondary" @click="editRoleDialog = false" />
            <Button label="Update" icon="pi pi-check" @click="updateMemberRole" :loading="updateRoleForm.processing" />
        </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useToast, useConfirm, Button, Card, DataTable, Column, Tag, Dialog, InputText, Password, Select, Checkbox } from 'primevue';

const props = defineProps({
  members: Array,
});

const toast = useToast();
const confirm = useConfirm();

// --- Data ---
const roleOptions = [
    { label: 'Admin', value: 'ADMIN' },
    { label: 'User', value: 'USER' },
];

const getRoleSeverity = (role) => {
    switch (role) {
        case 'OWNER': return 'contrast';
        case 'ADMIN': return 'info';
        case 'USER': return 'success';
        default: return 'secondary';
    }
};

// --- Permissions ---
const editPermissionsDialog = ref(false);
const editingPermissionsMember = ref(null);
const availablePermissions = [
    'Dashboard', 'Inventory', 'Markets', 'Accounting', 'Contacts', 'Stores', 'Company', 'Users'
];

const permissionForm = useForm({
    permissions: [],
});

const openPermissionsModal = (member) => {
    editingPermissionsMember.value = member;
    
    let currentPerms = member.page_permissions;
    if (typeof currentPerms === 'string') {
        try {
             currentPerms = JSON.parse(currentPerms);
        } catch(e) {
             currentPerms = [];
        }
    }
    
    // Default to all permissions if none set (or empty array if strict?)
    // If null, it means no restrictions set yet, so maybe default to all?
    // Based on User model default: `["Inventory"]`
    if (!currentPerms) {
         currentPerms = ['Inventory'];
    }
    
    permissionForm.permissions = currentPerms;
    editPermissionsDialog.value = true;
};

const updatePermissions = () => {
    if (!editingPermissionsMember.value) return;
    
    permissionForm.put(route('company.members.permissions.update', editingPermissionsMember.value.id), {
        preserveScroll: true,
        onSuccess: () => {
             editPermissionsDialog.value = false;
             toast.add({ severity: 'success', summary: 'Success', detail: 'Permissions updated successfully', life: 3000 });
             editingPermissionsMember.value = null;
        }
    });
};

// --- Add Member ---
const addMemberDialog = ref(false);
const addForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'USER',
});

const openAddMemberModal = () => {
    addForm.reset();
    addForm.clearErrors();
    addMemberDialog.value = true;
};

const storeMember = () => {
    addForm.post(route('company.members.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addMemberDialog.value = false;
            toast.add({ severity: 'success', summary: 'Success', detail: 'Team member added successfully', life: 3000 });
            addForm.reset();
        },
    });
};

// --- Update Role ---
const editRoleDialog = ref(false);
const editingMember = ref(null);
const updateRoleForm = useForm({
    role: '',
});

const openEditRoleModal = (member) => {
    editingMember.value = member;
    updateRoleForm.role = member.role;
    updateRoleForm.clearErrors();
    editRoleDialog.value = true;
};

const updateMemberRole = () => {
    if (!editingMember.value) return;

    updateRoleForm.put(route('company.members.update', editingMember.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editRoleDialog.value = false;
            toast.add({ severity: 'success', summary: 'Success', detail: 'Member role updated', life: 3000 });
            editingMember.value = null;
        },
    });
};

// --- Remove Member ---
const confirmRemoveMember = (member) => {
    confirm.require({
        message: `Are you sure you want to remove ${member.name} from the team?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const form = useForm({});
            form.delete(route('company.members.destroy', member.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Success', detail: 'Member removed successfully', life: 3000 });
                },
            });
        },
    });
};
</script>

<style scoped>
:deep(.p-card) {
  box-shadow: none;
  border: none;
}

:deep(.p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}
</style>
