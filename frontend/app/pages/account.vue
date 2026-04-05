<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Account',
  description: 'Account page'
})
</script>

<template>
  <UCard class="shadow-sm ring-1 ring-default">
    <template #header>
      <h1 class="text-2xl font-semibold text-highlighted">
        Account
      </h1>
    </template>

    <p class="text-sm leading-6 text-toned">
      Sample account page inside the shared layout.
    </p>
  </UCard>
</template>
