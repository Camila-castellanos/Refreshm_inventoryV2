<template>
  <form method="post" ref="customerForm" @submit.prevent="onFormSubmit" class="flex flex-col gap-4">
    <Card class="card">
      <template #title> Basic Information </template>
      <template #content class="px-6 py-3">
        <InputLabel for="customer" value="Customer name" />
        <InputText
          type="text"
          class="w-full"
          id="customer"
          name="customer_name"
          v-model="form.customer_name"
          placeholder="Insert"></InputText>
        <p class="block my-2 text-sm font-normal">Name of a business or person.</p>
        <Message v-if="nameInvalid" severity="error" size="small" variant="simple"> Name is required</Message>
      </template>
    </Card>
    <Card class="card">
      <template #title>Primary Contact</template>
      <template #content class="w-full">
        <div
          class="grid grid-cols-6 gap-4 px-6 pt-6 pb-8 mb-4 rounded"
          v-for="(personal_info, index) in personal_info_content"
          :key="index">
          <div class="col-span-6 text-right" v-if="index != 0">
            <a href="javascript:;" class="text-red-500 removecontact" @click="removeContact(index)">
              <i class="mr-1 pi pi-trash"></i>
              Remove
            </a>
          </div>
          <div class="col-span-3">
            <InputLabel for="first_name" value="First name" />
            <InputText
              type="text"
              class="w-full"
              id="first_name"
              name="first_name[]"
              v-model="form.first_name[index]"
              placeholder="Insert"></InputText>
          </div>
          <div class="col-span-3">
            <InputLabel for="last_name" value="Last name" />
            <InputText
              type="text"
              class="w-full col-span-2"
              name="last_name[]"
              id="last_name"
              placeholder="Insert"
              v-model="form.last_name[index]"></InputText>
          </div>
          <div class="col-span-3">
            <InputLabel for="email" value="Email" />
            <InputText
              type="email"
              class="w-full col-span-2"
              name="email[]"
              id="email"
              placeholder="Insert"
              v-model="form.email[index]"></InputText>
          </div>
          <div class="col-span-3">
            <InputLabel for="personal_phone" value="Phone" />
            <InputText
              type="tel"
              class="w-full col-span-2"
              id="personal_phone"
              placeholder="Insert"
              name="personal_phone[]"
              v-model="form.personal_phone[index]">
            </InputText>
          </div>
          <div v-if="form.personal_phone_optional[index]?.length != 0" class="grid grid-cols-6 col-span-6 gap-4 px-6 py-3">
            <div class="col-span-2 mb-6" id="phone_optional" v-for="(personal_phone, p_index) in phone_optional[index]" :key="p_index">
              <div class="flex justify-between col-span-2">
                <label class="mb-2 text-sm font-bold text-gray-700" for="personal_phone_optional"> Phone (Optional)</label>
                <a href="javascript:;" class="text-red-500" @click="removePhoneField(index, p_index)">
                  <i class="pi pi-times"></i>
                </a>
              </div>
              <InputText
                type="tel"
                class="w-full col-span-2"
                :name="'personal_phone_optional[' + index + '][' + p_index + ']'"
                id="personal_phone_optional"
                placeholder="Phone Number (Optional)"
                v-model="form.optional_number[index][p_index]"></InputText>
            </div>
          </div>
          <div v-else>
            <InputText type="hidden" :name="'personal_phone_optional[' + index + ']'" :value="[]"></InputText>
          </div>
          <div class="col-span-6 text-center">
            <Button href="javascript:;" class="text-blue-500 w-full" @click="addPhoneField(index)">
              <i class="pi pi-plus-circle"></i>
              Add Phone
            </Button>
          </div>
        </div>
        <div class="col-span-6 text-center">
          <Button href="javascript:;" class="text-blue-500 w-full" @click="addContactDetails"
            ><i class="pi pi-plus-circle"></i>
            Add Contact
          </Button>
        </div>

        <div class="grid w-full grid-cols-6 py-5">
          <div class="col-span-2 px-6 py-3">
            <InputLabel for="accnumber" value="Account number" />
            <InputText
              type="number"
              class="w-full col-span-2 bg-transparent"
              name="accnumber"
              placeholder="Insert"
              v-model="form.accnumber"></InputText>
          </div>
          <div class="col-span-2 px-6 py-3">
            <InputLabel for="website" value="Website" />
            <InputText class="w-full col-span-2" name="website" placeholder="Insert" v-model="form.website" type="url"></InputText>
          </div>
          <div class="col-span-2 px-6 py-3">
            <InputLabel for="credit" value="Credit ($)" />
            <InputText class="w-full col-span-2" name="credit" placeholder="Insert" v-model="form.credit" type="number"></InputText>
          </div>
          <div class="col-span-6 px-6 py-3">
            <InputLabel for="note" value="Note" />
            <Textarea
              class="w-full col-span-2 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50 filter--value"
              rows="3"
              name="note"
              placeholder="Insert"
              v-model="form.note"></Textarea>
          </div>
        </div>
      </template>
    </Card>

    <Card class="card">
      <template #title>Billing</template>
      <template #content class="col-span-3 px-6 py-3">
        <InputLabel for="billing_currency" value="Currency" />
        <InputText type="text" class="w-full" v-model="form.billing_currency" name="billing_currency" placeholder="Currency"></InputText>
      </template>
    </Card>

    <Card class="card">
      <template #title class="my-2 text-xl font-medium"> Billing Address </template>
      <template #content>
        <div class="grid w-full grid-cols-8 gap-4 px-6 py-3">
          <div class="col-span-4">
            <InputLabel for="billing_address" value="Address" />
            <InputText
              type="text"
              class="w-full"
              v-model="form.billing_address"
              name="billing_address"
              id="billing_address"
              placeholder="Insert"></InputText>
          </div>
          <div class="col-span-4">
            <InputLabel for="billing_address_optional" value="Address 2 (optional)" />
            <InputText
              type="text"
              class="w-full"
              v-model="form.billing_address_optional"
              name="billing_address_optional"
              id="billing_address_optional"
              placeholder="Insert">
            </InputText>
          </div>
          <div class="col-span-2">
            <InputLabel for="billing_country" value="Country" />
            <InputText
              type="text"
              class="w-full"
              v-model="form.billing_country"
              name="billing_country"
              id="billing_country"
              placeholder="Insert"></InputText>
          </div>
          <div class="col-span-2">
            <InputLabel for="billing_state" value="Province / State" />
            <InputText type="text" class="w-full" v-model="form.billing_state" name="billing_state" placeholder="Insert"></InputText>
          </div>
          <div class="col-span-2">
            <InputLabel for="billing_city" value="City" />
            <InputText type="text" class="w-full" v-model="form.billing_city" name="billing_city" placeholder="Insert"></InputText>
          </div>
          <div class="col-span-2">
            <InputLabel for="billing_postal_code" value="Postal/Zip Code" />
            <InputText
              type="text"
              class="w-full"
              v-model="form.billing_postal_code"
              name="billing_postal_code"
              placeholder="Insert"></InputText>
          </div>
          <div class="col-span-6">
            <a href="javascript:;" class="text-blue-500" @click="cleanBillingForm"> Clear Address </a>
          </div>
        </div>
      </template>
    </Card>

    <Card class="card">
      <template #title class="pt-4 text-xl font-bold">Shipping</template>
      <template #content>
        <div class="w-full px-6 py-3">
          <InputLabel for="shipto" value="Ship to" />
          <InputText type="text" class="w-full col-span-2" name="shipto" placeholder="Insert" v-model="form.shipto"></InputText>
        </div>

        <h3 class="my-2 text-xl font-medium">Shipping Address</h3>
        <div class="w-full col-span-4">
          <div class="rounded-md">
            <div class="grid w-full grid-cols-8 gap-4 px-6 py-3">
              <div class="col-span-4">
                <InputLabel for="shipping_address" value="Address" />
                <InputText
                  type="text"
                  class="w-full"
                  v-model="form.shipping_address"
                  name="shipping_address"
                  id="shipping_address"
                  placeholder="Insert">
                </InputText>
              </div>
              <div class="col-span-4">
                <InputLabel for="shipping_address_optional" value="Address 2 (optional)" />
                <InputText
                  type="text"
                  class="w-full"
                  v-model="form.shipping_address_optional"
                  name="shipping_address_optional"
                  id="shipping_address_optional"
                  placeholder="Insert"></InputText>
              </div>
              <div class="col-span-2">
                <InputLabel for="shipping_country" value="Country" />
                <InputText
                  type="text"
                  class="w-full"
                  v-model="form.shipping_country"
                  name="shipping_country"
                  id="shipping_country"
                  placeholder="Insert">
                </InputText>
              </div>
              <div class="col-span-2">
                <InputLabel for="shipping_state" value="Province / State" />
                <InputText type="text" class="w-full" v-model="form.shipping_state" name="shipping_state" placeholder="Insert"></InputText>
              </div>
              <div class="col-span-2">
                <InputLabel for="shipping_city" value="City" />
                <InputText type="text" class="w-full" v-model="form.shipping_city" name="shipping_city" placeholder="Insert"></InputText>
              </div>
              <div class="col-span-2">
                <InputLabel for="shipping_postal_code" value="Postal/Zip Code" />
                <InputText
                  type="text"
                  class="w-full"
                  v-model="form.shipping_postal_code"
                  name="shipping_postal_code"
                  placeholder="Insert"></InputText>
              </div>
              <div class="col-span-6">
                <a href="javascript:;" class="text-blue-500" @click="cleanShippingForm"> Clear Address </a>
              </div>
            </div>
            <div class="w-full px-6 py-3">
              <InputLabel for="shipping_phone" value="Phone" />
              <InputText
                type="tel"
                class="w-full col-span-2"
                name="shipping_phone"
                placeholder="Phone"
                v-model="form.shipping_phone"></InputText>
            </div>
            <div class="w-full px-6 py-3">
              <InputLabel for="shipping_delivery_instructions" value="Delivery Instructions" />
              <Textarea
                class="w-full col-span-2 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50 filter--value"
                rows="4"
                name="shipping_delivery_instructions"
                v-model="form.shipping_delivery_instructions"
                placeholder="Insert"></Textarea>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <section class="flex flex-row justify-around p-3">
      <Button class="w-full mx-2" type="reset" @click="cleanForm">Reset</Button>
      <Button class="w-full mx-2" @click="onFormSubmit">Save</Button>
    </section>
  </form>
</template>

<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { Button, InputText, Message, Textarea, useToast } from "primevue";
import Card from "primevue/card";
import { computed, inject, onMounted, ref } from "vue";

const dialogRef = inject("dialogRef");
const toast = useToast();

// Refs for form
const customerForm = ref(null);

// Arrays to control contacts and extra phones
const personal_info_content = ref([]);
const phone_optional = ref([]);
const nameInvalid = ref(false);
const customer = ref(null);

// Computed properties
const formType = computed(() => (customer.value ? "Edit" : "Create"));
const requestUrl = computed(() => {
  return customer.value ? route("customer.update", customer.value.id) : route("customer.store");
});

// onMounted lifecycle hook
onMounted(() => {
  let contact = "";
  customer.value = dialogRef.value?.data?.customer ?? null;
  if (customer.value !== null) {
    form.customer_name = customer.value.customer;
    form.first_name = customer.value.first_name;
    form.last_name = customer.value.last_name;
    form.email = customer.value.email.split(", ");
    form.personal_phone = customer.value.phone.split(", ");
    form.optional_number = customer.value.phone_optional != null ? customer.value.phone_optional : [];
    form.accnumber = customer.value.account_number;
    form.website = customer.value.website;
    form.note = customer.value.notes;
    form.billing_currency = customer.value.currency;
    form.billing_address = customer.value.billing_address;
    form.billing_address_optional = customer.value.billing_address_optional;
    form.billing_country = customer.value.billing_address_country;
    form.billing_state = customer.value.billing_address_state;
    form.billing_city = customer.value.billing_address_city;
    form.billing_postal_code = customer.value.billing_address_postal;
    form.shipto = customer.value.ship_name;
    form.shipping_address = customer.value.shipping_address;
    form.shipping_address_optional = customer.value.shipping_address_optional;
    form.shipping_country = customer.value.shipping_address_country;
    form.shipping_state = customer.value.shipping_address_state;
    form.shipping_city = customer.value.shipping_address_city;
    form.shipping_postal_code = customer.value.shipping_address_postal;
    form.shipping_phone = customer.value.shipping_phone;
    form.shipping_delivery_instructions = customer.value.delivery_instructions;
    form.credit = customer.value.credit;

    // Add entries to personal_info_content for each contact
    customer.value.first_name.forEach(() => {
      personal_info_content.value.push(contact);
    });

    // Adjust size of phone_optional if empty
    if (form.optional_number.length == 0) {
      customer.value.first_name.forEach(() => {
        phone_optional.value.push([]);
        form.optional_number.push([]);
      });
    } else {
      phone_optional.value = form.optional_number;
    }
  } else {
    personal_info_content.value.push(contact);
    phone_optional.value.push([]);
    form.optional_number.push([]);
  }
});

// Functions
function cleanForm() {
  form.customer_name = "";
  form.first_name = [];
  form.last_name = [];
  form.email = [];
  form.personal_phone = [];
  form.optional_number = [];
  form.accnumber = "";
  form.website = "";
  form.note = "";
  form.billing_currency = "";
  form.shipto = "";
  form.shipping_phone = "";
  form.shipping_delivery_instructions = "";
  form.credit = null;
  cleanBillingForm();
  cleanShippingForm();
}

function cleanBillingForm() {
  form.billing_address = "";
  form.billing_address_optional = "";
  form.billing_country = "";
  form.billing_state = "";
  form.billing_city = "";
  form.billing_postal_code = "";
}

function cleanShippingForm() {
  form.shipping_address = "";
  form.shipping_address_optional = "";
  form.shipping_country = "";
  form.shipping_state = "";
  form.shipping_city = "";
  form.shipping_postal_code = "";
}

const form = useForm({
  customer_name: "",
  first_name: [],
  last_name: [],
  email: [],
  personal_phone: [],
  optional_number: [],
  accnumber: "",
  website: "",
  note: "",
  billing_currency: "",
  billing_address: "",
  billing_address_optional: "",
  billing_country: "",
  billing_state: "",
  billing_city: "",
  billing_postal_code: "",
  shipto: "",
  shipping_address: "",
  shipping_address_optional: "",
  shipping_country: "",
  shipping_state: "",
  shipping_city: "",
  shipping_postal_code: "",
  shipping_phone: "",
  shipping_delivery_instructions: "",
  credit: 0,
  personal_phone_optional: [],
});

async function onFormSubmit(event) {
  event.preventDefault();
  if (form.customer_name.trim() == "") {
    nameInvalid.value = true;
    window.scrollTo(0, 0);
    return;
  }

  if (phone_optional.value.length > 0) {
    phone_optional.value.forEach((phones) => {
      form.personal_phone_optional.push(phones);
    });
  }

  try {
    let response;
    if (formType.value === "Edit") {
      response = await axios.put(requestUrl.value, form);
    } else {
      response = await axios.post(requestUrl.value, form);
    }

    if (response.status >= 200 && response.status < 400) {
      const newCustomer = response.data;
      cleanForm();
      toast.add({ severity: "success", summary: "Success", detail: "Customer created succesfully!", life: 3000 });
      dialogRef.value.close(newCustomer);
    }
  } catch (error) {
    console.error(error);
    toast.add({ severity: "error", summary: "Error", detail: error.response?.data || error.message, life: 4000 });
  }
}

function addPhoneField(index) {
  phone_optional[index].push("");
}

function removePhoneField(index, re_index) {
  phone_optional[index].splice(re_index, 1);
}

function addContactDetails() {
  let contact = "";
  phone_optional.push([]);
  form.optional_number.push([]);
  personal_info_content.push(contact);
}

function removeContact(index) {
  form.first_name.splice(index, 1);
  form.last_name.splice(index, 1);
  form.email.splice(index, 1);
  form.personal_phone.splice(index, 1);
  phone_optional.value.splice(index, 1);
  form.optional_number.splice(index, 1);
  personal_info_content.value.splice(index, 1);
}
</script>

<style scoped>
.card {
  width: 90%;
  margin: 0 auto;
}
</style>
