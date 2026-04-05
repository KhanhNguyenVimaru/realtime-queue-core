<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Events',
  description: 'Events page'
})
</script>

<template>
  <UCard class="shadow-sm ring-1 ring-default">
    <template #header>
      <h1 class="text-2xl font-semibold text-highlighted">
        Events
      </h1>
    </template>

    <p class="text-sm leading-6 text-toned">
      Sample events page inside the shared layout.
    </p>
  </UCard>
</template>
