<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
        <!--
      - min-h-screen: Makes the container at least the full height of the viewport.
      - flex: Enables Flexbox layout.
      - items-center: Vertically centers the content within the flex container.
      - justify-center: Horizontally centers the content within the flex container.
      - bg-gray-100: Adds a light gray background to the page area.
      - p-4: Adds some padding around the container.
    -->
        <section class="text-center">
            <!--
          - text-center: Centers the text within the section.
        -->
            <h1 class="text-3xl md:text-4xl font-semibold text-gray-600">
                <!--
              - text-3xl / md:text-4xl: Sets a large text size, slightly bigger on medium screens and up.
              - font-semibold: Makes the text semi-bold.
              - text-gray-600: Uses a medium-dark gray color for the text.
            -->
                This user doesn't belong to any store
            </h1>
        </section>
    </div>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });
</script>