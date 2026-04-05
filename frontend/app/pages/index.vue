<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Home',
  description: 'Sample homepage'
})
</script>

<template>
  <UCard class="shadow-sm ring-1 ring-default">
    <template #header>
      <h1 class="text-2xl font-semibold text-highlighted">
        Sample Home Page
      </h1>
    </template>

    <div class="space-y-4">
      <p class="text-sm leading-6 text-toned">
        This page is rendered inside the shared dashboard layout.
      </p>

      <div class="flex gap-3">
        <UButton to="/users" icon="i-lucide-users">
          Go to users
        </UButton>
      </div>
    </div>
  </UCard>
 </template>
