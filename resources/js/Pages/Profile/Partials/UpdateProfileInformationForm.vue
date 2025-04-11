<template>
  <div class="flex justify-between">
    <div class="flex items-start mb-2 w-1/3">
      <div>
        <h2 class="text-xl font-medium mb-1">Profile Information</h2>
        <p class="text-sm">Update your account's profile information and email address.</p>
      </div>
    </div>

    <Card class="shadow-none w-2/3">

      <template #content>
        <div class="flex gap-4 items-center mb-4 mt-5">
          <h2>Company: {{ user.companyName }}</h2>
          <Button icon="pi pi-copy" severity="secondary" label="Invitation Link"
            @click="copyToClipboard(invitationLink)" />
        </div>
        <div v-for="(store) in storeURLs" class="flex gap-4 items-center mb-4 mt-5">
          <h2>Public store: {{ store.name }}</h2>
          <Button icon="pi pi-external-link" severity="secondary" label="Go To Store" @click="goToStore(store.url)" />
          <Button icon="pi pi-copy" severity="secondary" label="Copy Link" @click="copyToClipboard(store.url)" />
        </div>


        <form @submit.prevent="updateProfileInformation" class="space-y-6">
          <!-- Profile Photo -->
          <div v-if="$page.props.jetstream.managesProfilePhotos" class="mb-4">
            <div class="flex items-center justify-between">
              <label for="photo" class="block text-sm font-medium mb-1">Photo</label>

              <div class="flex gap-2">
                <Button type="button" outlined size="small" icon="pi pi-image" label="Select New Photo"
                  @click.prevent="selectNewPhoto" />

                <Button v-if="user.profile_photo_path" type="button" outlined severity="danger" size="small"
                  icon="pi pi-trash" label="Remove Photo" @click.prevent="deletePhoto" />
              </div>
            </div>

            <!-- Profile Photo File Input -->
            <input id="photo" ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" />

            <!-- Current Profile Photo -->
            <div v-show="!photoPreview" class="mt-2">
              <img :src="user.profile_photo_url" :alt="user.name" class="rounded-full size-20 object-cover" />
            </div>

            <!-- New Profile Photo Preview -->
            <div v-show="photoPreview" class="mt-2">
              <span class="block rounded-full size-20 bg-cover bg-no-repeat bg-center"
                :style="'background-image: url(\'' + photoPreview + '\');'" />
            </div>

            <small v-if="form.errors.photo" class="text-red-500">{{ form.errors.photo }}</small>
          </div>

          <!-- Name -->
          <div class="field">
            <label for="name" class="block text-sm font-medium mb-1">Name</label>
            <InputText id="name" v-model="form.name" class="w-full" required autocomplete="name" />
            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
          </div>

          <!-- Email -->
          <div class="field">
            <label for="email" class="block text-sm font-medium mb-1">Email</label>
            <InputText id="email" v-model="form.email" type="email" class="w-full" required autocomplete="username" />
            <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>

            <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null" class="mt-2">
              <p class="text-sm">
                Your email address is unverified.
                <a href="#" class="underline text-sm text-primary hover:text-primary-dark"
                  @click.prevent="sendEmailVerification">
                  Click here to re-send the verification email.
                </a>
              </p>

              <div v-show="verificationLinkSent" class="mt-2 font-medium text-sm text-green-600">
                A new verification link has been sent to your email address.
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <span v-if="form.recentlySuccessful" class="text-green-500 self-center mr-2">Saved successfully</span>
            <Button type="submit" :loading="form.processing" :disabled="form.processing" label="Save"
              icon="pi pi-save" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { useToast, Button, Card, InputText } from "primevue";
import createStoreUrl from "@/Utils/createPublicLink"
import createInvitationLink from "@/Utils/createInvitationLink"
import { stringifyQuery } from "vue-router";


const storeURLs = ref([])
const invitationLink = ref("")

const props = defineProps({
  user: Object,
});
const toast = useToast();



const form = useForm({
  _method: "PUT",
  name: props.user.name,
  email: props.user.email,
  photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
  if (photoInput.value) {
    form.photo = photoInput.value.files[0];
  }

  form.post(route("user-profile-information.update"), {
    errorBag: "updateProfileInformation",
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: "Success",
        detail: "Profile updated successfully",
        life: 3000,
      });
      clearPhotoFileInput();
    },
  });
};

const sendEmailVerification = () => {
  verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
  photoInput.value.click();
};

const updatePhotoPreview = () => {
  const photo = photoInput.value.files[0];

  if (!photo) return;

  const reader = new FileReader();

  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };

  reader.readAsDataURL(photo);
};

const deletePhoto = () => {
  router.delete(route("current-user-photo.destroy"), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null;
      clearPhotoFileInput();
    },
  });
};

const clearPhotoFileInput = () => {
  if (photoInput.value?.value) {
    photoInput.value.value = null;
  }
};

const goToStore = (storeURL) => {
  window.open(storeURL);
};

const copyToClipboard = (storeURL) => {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(storeURL)
      .then(() => {
        toast.add({ severity: 'success', summary: 'Link Copied', detail: 'The link has been copied to your clipboard.', life: 3000 });
      })
      .catch(err => {
        toast.add({ severity: 'error', summary: 'Copy Failed', detail: 'Failed to copy the link to the clipboard.', life: 3000 });
        console.error('Could not copy text: ', err);
      });
  } else {
    toast.add({ severity: 'warn', summary: 'Clipboard Not Supported', detail: 'Your browser does not support clipboard access.', life: 3000 });
  }
};

onMounted(() => {
  invitationLink.value = createInvitationLink(props.user.companyName)
  props.user.shops.map(shop => {
    const store = {}
    store.name = shop.name
    store.url = createStoreUrl(props.user.companyName, shop.name)
    storeURLs.value.push(store);

  })
});
</script>

<style scoped>
:deep(.p-card) {
  box-shadow: none;
  border: none;
}

:deep(.p-card-content) {
  padding: 1.5rem;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}
</style>
