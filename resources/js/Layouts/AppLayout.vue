<template>
    <div>
        <header>
            <h1>Inventory Layout</h1>
        </header>
        <main>
            <slot /> <!-- Renderiza el contenido de la página -->
        </main>
    </div>
</template>

<script>
export default {
    name: "InventoryLayout",
};
</script>

<style scoped>
header {
    background-color: #4B5563;
    color: white;
    padding: 1rem;
}
</style>
