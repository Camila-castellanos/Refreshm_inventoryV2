<template>
    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden  sm:rounded-lg">
                    <Button class="mb-12 ml-12" label="Back to Index" @click="router.visit(route('customers.index'))" icon="pi pi-chevron-left"></Button>
                    <form method="post" ref="customerForm" @submit.prevent="onFormSubmit" class="flex flex-col gap-4">
                        <h2 class="mb-4 text-4xl ml-12 font-bold">
                            {{ formType }} Customer
                        </h2>
                        <Card class="card">
                            <template #title>
                            Basic Information
                        </template>
                        <template #content class="px-6 py-3">
                            <InputLabel for="customer" value="Customer name" />
                            <InputText type="text" class="w-full" id="customer" name="customer_name"
                                v-model="customer_name" placeholder="Insert"></InputText>
                            <p class="block my-2 text-sm font-normal">
                                Name of a business or person.
                            </p>
                            <Message v-if="nameInvalid" severity="error" size="small" variant="simple">
                                Name is required</Message>
                        </template>
                        </Card>
                        <Card class="card">  
                            <template #title>Primary Contact</template>
                        <template #content  class="w-full">
                            <div class="grid grid-cols-6 gap-4 px-6 pt-6 pb-8 mb-4 rounded" v-for="(
                                    personal_info, index
                                ) in personal_info_content" :key="index">
                                <div class="col-span-6 text-right" v-if="index != 0">
                                    <a href="javascript:;" class="text-red-500 removecontact"
                                        @click="removeContact(index)">
                                        <i class="mr-1 pi pi-trash"></i>
                                        Remove
                                    </a>
                                </div>
                                <div class="col-span-3">
                                    <InputLabel for="first_name" value="First name" />
                                    <InputText type="text" class="w-full" id="first_name" name="first_name[]"
                                        v-model="first_name[index]" placeholder="Insert"></InputText>
                                </div>
                                <div class="col-span-3">
                                    <InputLabel for="last_name" value="Last name" />
                                    <InputText type="text" class="w-full col-span-2" name="last_name[]" id="last_name"
                                        placeholder="Insert" v-model="last_name[index]"></InputText>
                                </div>
                                <div class="col-span-3">
                                    <InputLabel for="email" value="Email" />
                                    <InputText type="email" class="w-full col-span-2" name="email[]" id="email"
                                        placeholder="Insert" v-model="email[index]"></InputText>
                                </div>
                                <div class="col-span-3">
                                    <InputLabel for="personal_phone" value="Phone" />
                                    <InputText type="tel" class="w-full col-span-2" id="personal_phone"
                                        placeholder="Insert" name="personal_phone[]" v-model="personal_phone[index]">
                                    </InputText>
                                </div>
                                <div v-if="phone_optional[index].length != 0"
                                    class="grid grid-cols-6 col-span-6 gap-4 px-6 py-3">
                                    <div class="col-span-2 mb-6" id="phone_optional" v-for="(
personal_phone, p_index
                ) in phone_optional[index]" :key="p_index">
                                        <div class="flex justify-between col-span-2">
                                            <label class="mb-2 text-sm font-bold text-gray-700"
                                                for="personal_phone_optional">
                                                Phone (Optional)</label>
                                            <a href="javascript:;" class="text-red-500" @click="
                                                removePhoneField(
                                                    index,
                                                    p_index
                                                )
                                                ">
                                                <i class="pi pi-times"></i>
                                            </a>
                                        </div>
                                        <InputText type="tel" class="w-full col-span-2" :name="'personal_phone_optional[' +
                                            index +
                                            '][' +
                                            p_index +
                                            ']'
                                            " id="personal_phone_optional" placeholder="Phone Number (Optional)" v-model="optional_number[index][p_index]
                        "></InputText>
                                    </div>
                                </div>
                                <div v-else>
                                    <InputText type="hidden" :name="'personal_phone_optional[' +
                                        index +
                                        ']'
                                        " :value="[]"></InputText>
                                </div>
                                <div class="col-span-6 text-center">
                                    <Button href="javascript:;" class="text-blue-500 w-full"
                                        @click="addPhoneField(index)">
                                        <i class="pi pi-plus-circle"></i>
                                        Add Phone
                                    </Button>
                                </div>
                            </div>
                            <div class="col-span-6 text-center">
                                <Button href="javascript:;" class="text-blue-500 w-full" @click="addContactDetails"><i
                                        class="pi pi-plus-circle"></i>
                                    Add Contact
                                </Button>
                            </div>

                            <div class="grid w-full grid-cols-6 py-5">
                            <div class="col-span-2 px-6 py-3">
                                <InputLabel for="accnumber" value="Account number" />
                                <InputText type="number" class="w-full col-span-2 bg-transparent" name="accnumber"
                                    placeholder="Insert" v-model="accnumber"></InputText>
                            </div>
                            <div class="col-span-2 px-6 py-3">
                                <InputLabel for="website" value="Website" />
                                <InputText class="w-full col-span-2" name="website" placeholder="Insert"
                                    v-model="website" type="url"></InputText>
                            </div>
                            <div class="col-span-2 px-6 py-3">
                                <InputLabel for="credit" value="Credit ($)" />
                                <InputText class="w-full col-span-2" name="credit" placeholder="Insert" v-model="credit"
                                    type="number"></InputText>
                            </div>
                            <div class="col-span-6 px-6 py-3">
                                <InputLabel for="note" value="Note" />
                                <Textarea
                                    class="w-full col-span-2 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50 filter--value"
                                    rows="3" name="note" placeholder="Insert"    v-model="note"></Textarea>
                            </div>
                        </div>

                        </template>
                        </Card>
                     
                        <Card class="card">
                            <template #title>Billing</template>
                        <template #content class="col-span-3 px-6 py-3">
                            <InputLabel for="billing_currency" value="Currency" />
                            <InputText type="text" class="w-full" v-model="billing_currency" name="billing_currency"
                                placeholder="Currency"></InputText>
                        </template>
                        </Card>
                        
                        <Card class="card">
                            <template #title class="my-2 text-xl font-medium">
                            Billing Address
                        </template>
                        <template #content >
                            <div class="grid w-full grid-cols-8 gap-4 px-6 py-3">
                                <div class="col-span-4">
                                <InputLabel for="billing_address" value="Address" />
                                <InputText type="text" class="w-full" v-model="billing_address" name="billing_address"
                                    id="billing_address" placeholder="Insert"></InputText>
                            </div>
                            <div class="col-span-4">
                                <InputLabel for="billing_address_optional" value="Address 2 (optional)" />
                                <InputText type="text" class="w-full" v-model="billing_address_optional"
                                    name="billing_address_optional" id="billing_address_optional" placeholder="Insert">
                                </InputText>
                            </div>
                            <div class="col-span-2">
                                <InputLabel for="billing_country" value="Country" />
                                <InputText type="text" class="w-full" v-model="billing_country" name="billing_country"
                                    id="billing_country" placeholder="Insert"></InputText>
                            </div>
                            <div class="col-span-2">
                                <InputLabel for="billing_state" value="Province / State" />
                                <InputText type="text" class="w-full" v-model="billing_state" name="billing_state"
                                    placeholder="Insert"></InputText>
                            </div>
                            <div class="col-span-2">
                                <InputLabel for="billing_city" value="City" />
                                <InputText type="text" class="w-full" v-model="billing_city" name="billing_city"
                                    placeholder="Insert"></InputText>
                            </div>
                            <div class="col-span-2">
                                <InputLabel for="billing_postal_code" value="Postal/Zip Code" />
                                <InputText type="text" class="w-full" v-model="billing_postal_code"
                                    name="billing_postal_code" placeholder="Insert"></InputText>
                            </div>
                            <div class="col-span-6">
                                <a href="javascript:;" class="text-blue-500" @click="cleanBillingForm">
                                    Clear Address
                                </a>
                            </div>
                            </div>
                        </template>
                        </Card>
                    
                        <Card class="card">
                            <template #title class="pt-4 text-xl font-bold">Shipping</template>
                            <template #content >
                              <div class="w-full px-6 py-3">
                                <InputLabel for="shipto" value="Ship to" />
                                <InputText type="text" class="w-full col-span-2" name="shipto" placeholder="Insert"
                                    v-model="shipto"></InputText>
                              </div>

                              <h3 class="my-2 text-xl font-medium">
                            Shipping Address
                        </h3>
                        <div class="w-full col-span-4">
                            <div class="rounded-md">
                                <div class="grid w-full grid-cols-8 gap-4 px-6 py-3">
                                    <div class="col-span-4">
                                        <InputLabel for="shipping_address" value="Address" />
                                        <InputText type="text" class="w-full" v-model="shipping_address"
                                            name="shipping_address" id="shipping_address" placeholder="Insert">
                                        </InputText>
                                    </div>
                                    <div class="col-span-4">
                                        <InputLabel for="shipping_address_optional" value="Address 2 (optional)" />
                                        <InputText type="text" class="w-full" v-model="shipping_address_optional"
                                            name="shipping_address_optional" id="shipping_address_optional"
                                            placeholder="Insert"></InputText>
                                    </div>
                                    <div class="col-span-2">
                                        <InputLabel for="shipping_country" value="Country" />
                                        <InputText type="text" class="w-full" v-model="shipping_country"
                                            name="shipping_country" id="shipping_country" placeholder="Insert">
                                        </InputText>
                                    </div>
                                    <div class="col-span-2">
                                        <InputLabel for="shipping_state" value="Province / State" />
                                        <InputText type="text" class="w-full" v-model="shipping_state"
                                            name="shipping_state" placeholder="Insert"></InputText>
                                    </div>
                                    <div class="col-span-2">
                                        <InputLabel for="shipping_city" value="City" />
                                        <InputText type="text" class="w-full" v-model="shipping_city"
                                            name="shipping_city" placeholder="Insert"></InputText>
                                    </div>
                                    <div class="col-span-2">
                                        <InputLabel for="shipping_postal_code" value="Postal/Zip Code" />
                                        <InputText type="text" class="w-full" v-model="shipping_postal_code"
                                            name="shipping_postal_code" placeholder="Insert"></InputText>
                                    </div>
                                    <div class="col-span-6">
                                        <a href="javascript:;" class="text-blue-500" @click="cleanShippingForm">
                                            Clear Address
                                        </a>
                                    </div>
                                </div>
                                <div class="w-full px-6 py-3">
                                    <InputLabel for="shipping_phone" value="Phone" />
                                    <InputText type="tel" class="w-full col-span-2" name="shipping_phone"
                                        placeholder="Phone" v-model="shipping_phone"></InputText>
                                </div>
                                <div class="w-full px-6 py-3">
                                    <InputLabel for="shipping_delivery_instructions" value="Delivery Instructions" />
                                    <Textarea
                                        class="w-full col-span-2 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50 filter--value"
                                        rows="4" name="shipping_delivery_instructions"
                                        v-model="shipping_delivery_instructions" placeholder="Insert"></Textarea>
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

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";
import { Button, Textarea, InputText, Message } from "primevue";
import { computed, onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import Card from 'primevue/card';


// Props
const props = defineProps({
    customerEdit: {
        type: Object,
        required: false,
        default: null,
    },
});

// Refs for data
const customer_name = ref("");
const first_name = ref([]);
const last_name = ref([]);
const email = ref([]);
const personal_phone = ref([]);
const optional_number = ref([]);
const accnumber = ref("");
const website = ref("");
const note = ref("");
const billing_currency = ref("");
const billing_address = ref("");
const billing_address_optional = ref("");
const billing_country = ref("");
const billing_state = ref("");
const billing_city = ref("");
const billing_postal_code = ref("");
const shipto = ref("");
const shipping_address = ref("");
const shipping_address_optional = ref("");
const shipping_country = ref("");
const shipping_state = ref("");
const shipping_city = ref("");
const shipping_postal_code = ref("");
const shipping_phone = ref("");
const shipping_delivery_instructions = ref("");
const credit = ref(null);

// Refs for form
const customerForm = ref(null);

// Arrays to control contacts and extra phones
const personal_info_content = ref([]);
const phone_optional = ref([]);
const nameInvalid = ref(false);

// Computed properties
const formType = computed(() => (props.customerEdit ? "Edit" : "Create"));
const requestUrl = computed(() => {
    return props.customerEdit
        ? route("customers.update", props.customerEdit.id)
        : route("customers.store");
});

// onMounted lifecycle hook
onMounted(() => {
    let contact = "";
    if (props.customerEdit !== null) {
        customer_name.value = props.customerEdit.customer;
        first_name.value = props.customerEdit.first_name;
        last_name.value = props.customerEdit.last_name;
        email.value = props.customerEdit.email;
        personal_phone.value = props.customerEdit.phone;
        optional_number.value =
            props.customerEdit.phone_optional != null
                ? props.customerEdit.phone_optional
                : [];
        accnumber.value = props.customerEdit.account_number;
        website.value = props.customerEdit.website;
        note.value = props.customerEdit.notes;
        billing_currency.value = props.customerEdit.currency;
        billing_address.value = props.customerEdit.billing_address;
        billing_address_optional.value =
            props.customerEdit.billing_address_optional;
        billing_country.value = props.customerEdit.billing_address_country;
        billing_state.value = props.customerEdit.billing_address_state;
        billing_city.value = props.customerEdit.billing_address_city;
        billing_postal_code.value = props.customerEdit.billing_address_postal;
        shipto.value = props.customerEdit.ship_name;
        shipping_address.value = props.customerEdit.shipping_address;
        shipping_address_optional.value =
            props.customerEdit.shipping_address_optional;
        shipping_country.value = props.customerEdit.shipping_address_country;
        shipping_state.value = props.customerEdit.shipping_address_state;
        shipping_city.value = props.customerEdit.shipping_address_city;
        shipping_postal_code.value = props.customerEdit.shipping_address_postal;
        shipping_phone.value = props.customerEdit.shipping_phone;
        shipping_delivery_instructions.value =
            props.customerEdit.delivery_instructions;
        credit.value = props.customerEdit.credit;

        // Add entries to personal_info_content for each contact
        props.customerEdit.first_name.forEach(() => {
            personal_info_content.value.push(contact);
        });

        // Adjust size of phone_optional if empty
        if (optional_number.value.length == 0) {
            props.customerEdit.first_name.forEach(() => {
                phone_optional.value.push([]);
                optional_number.value.push([]);
            });
        } else {
            phone_optional.value = optional_number.value;
        }
    } else {
        personal_info_content.value.push(contact);
        phone_optional.value.push([]);
        optional_number.value.push([]);
    }
});

// Functions
function cleanForm() {
    customer_name.value = "";
    first_name.value = [];
    last_name.value = [];
    email.value = [];
    personal_phone.value = [];
    optional_number.value = [];
    accnumber.value = "";
    website.value = "";
    note.value = "";
    billing_currency.value = "";
    shipto.value = "";
    shipping_phone.value = "";
    shipping_delivery_instructions.value = "";
    credit.value = null;
    cleanBillingForm();
    cleanShippingForm();
}

function cleanBillingForm() {
    billing_address.value = "";
    billing_address_optional.value = "";
    billing_country.value = "";
    billing_state.value = "";
    billing_city.value = "";
    billing_postal_code.value = "";
}

function cleanShippingForm() {
    shipping_address.value = "";
    shipping_address_optional.value = "";
    shipping_country.value = "";
    shipping_state.value = "";
    shipping_city.value = "";
    shipping_postal_code.value = "";
}

async function onFormSubmit(event) {
    event.preventDefault();
    if (customer_name.value.trim() == "") {
        nameInvalid.value = true;
        window.scrollTo(0, 0);
        return;
    }

    const payload = {
        customer_name: customer_name.value,
        first_name: first_name.value,
        last_name: last_name.value,
        email: email.value,
        personal_phone: personal_phone.value,
        optional_number: optional_number.value,
        accnumber: accnumber.value,
        website: website.value,
        note: note.value,
        billing_currency: billing_currency.value,
        billing_address: billing_address.value,
        billing_address_optional: billing_address_optional.value,
        billing_country: billing_country.value,
        billing_state: billing_state.value,
        billing_city: billing_city.value,
        billing_postal_code: billing_postal_code.value,
        shipto: shipto.value,
        shipping_address: shipping_address.value,
        shipping_address_optional: shipping_address_optional.value,
        shipping_country: shipping_country.value,
        shipping_state: shipping_state.value,
        shipping_city: shipping_city.value,
        shipping_postal_code: shipping_postal_code.value,
        shipping_phone: shipping_phone.value,
        shipping_delivery_instructions: shipping_delivery_instructions.value,
        credit: credit.value,
        personal_phone_optional: [],
    };

    if (formType.value === "Edit") {
        payload._method = "put";
    }

    if (phone_optional.value.length > 0) {
        phone_optional.value.forEach((phones) => {
            payload.personal_phone_optional.push(phones);
        });
    }

    try {
        const response = await axios.post(requestUrl.value, payload, {
            headers: {
                "Content-Type": "application/json",
            },
        });

        if (response.status >= 200 && response.status < 400) {
            if (sessionStorage.getItem("sale-data")) {
                sessionStorage.setItem("customer-redirect", true);
                window.location.href = sessionStorage.getItem("sale-redirect");
            }

            if (sessionStorage.getItem("invoice-data")) {
                sessionStorage.setItem("customer-redirect", true);
                window.location.href = sessionStorage.getItem("invoice-redirect");
            }

            window.location.href = route("customers.index");
            cleanForm();
        }
    } catch (error) {
        console.error(error);
    }
}

function addPhoneField(index) {
    phone_optional.value[index].push("");
}

function removePhoneField(index, re_index) {
    phone_optional.value[index].splice(re_index, 1);
}

function addContactDetails() {
    let contact = "";
    phone_optional.value.push([]);
    optional_number.value.push([]);
    personal_info_content.value.push(contact);
}

function removeContact(index) {
    first_name.value.splice(index, 1);
    last_name.value.splice(index, 1);
    email.value.splice(index, 1);
    personal_phone.value.splice(index, 1);
    phone_optional.value.splice(index, 1);
    optional_number.value.splice(index, 1);
    personal_info_content.value.splice(index, 1);
}
</script>

<style scoped>
.card {
    width: 90%;
    margin: 0 auto;
}
</style>
