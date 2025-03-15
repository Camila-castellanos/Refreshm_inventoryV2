<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 p-button disabled:opacity-50 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
