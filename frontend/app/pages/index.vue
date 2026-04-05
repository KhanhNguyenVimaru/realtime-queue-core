<script setup lang="ts">
const auth = useAuth()
const loading = ref(true)

const accessTokenPreview = computed(() => {
  if (!auth.accessToken.value) {
    return 'No access token in memory.'
  }

  return `${auth.accessToken.value.slice(0, 48)}...`
})

async function handleLogout() {
  await auth.logout()
  await navigateTo('/login')
}

onMounted(async () => {
  await auth.initialize()

  if (!auth.isAuthenticated.value) {
    await navigateTo('/login')
    return
  }

  loading.value = false
})
</script>

<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,#f5f7fb_0%,#eef2f7_35%,#e8edf4_100%)] px-4 py-10 dark:bg-[radial-gradient(circle_at_top,#17202b_0%,#111827_45%,#0b1220_100%)]">
    <div class="fixed right-4 top-4 z-20 flex items-center gap-2 sm:right-6 sm:top-6">
      <UColorModeButton />
      <UButton color="error" variant="outline" icon="i-lucide-log-out" @click="handleLogout">
        Logout
      </UButton>
    </div>

    <UContainer class="py-10 sm:py-14">
      <div v-if="loading" class="mx-auto grid max-w-5xl gap-6 md:grid-cols-3">
        <USkeleton class="h-40 rounded-2xl" />
        <USkeleton class="h-40 rounded-2xl" />
        <USkeleton class="h-40 rounded-2xl" />
      </div>

      <div v-else class="mx-auto max-w-5xl space-y-6">
        <div class="space-y-2">
          <p class="text-sm font-medium text-muted">
            Internal dashboard
          </p>
          <h1 class="text-3xl font-semibold tracking-tight text-highlighted">
            Session information
          </h1>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
          <UCard class="shadow-sm ring-1 ring-default">
            <p class="text-sm text-muted">
              Name
            </p>
            <p class="mt-3 text-lg font-semibold text-highlighted">
              {{ auth.currentUser.value?.name || '-' }}
            </p>
          </UCard>

          <UCard class="shadow-sm ring-1 ring-default">
            <p class="text-sm text-muted">
              Email
            </p>
            <p class="mt-3 break-all text-lg font-semibold text-highlighted">
              {{ auth.currentUser.value?.email || '-' }}
            </p>
          </UCard>

          <UCard class="shadow-sm ring-1 ring-default">
            <p class="text-sm text-muted">
              User ID
            </p>
            <p class="mt-3 text-lg font-semibold text-highlighted">
              {{ auth.currentUser.value?.id || '-' }}
            </p>
          </UCard>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
          <UCard class="shadow-sm ring-1 ring-default">
            <template #header>
              <h2 class="text-base font-semibold text-highlighted">
                Access token
              </h2>
            </template>

            <p class="break-all font-mono text-sm leading-6 text-toned">
              {{ accessTokenPreview }}
            </p>
          </UCard>

          <UCard class="shadow-sm ring-1 ring-default">
            <template #header>
              <h2 class="text-base font-semibold text-highlighted">
                Token expiry
              </h2>
            </template>

            <p class="text-sm leading-6 text-toned">
              {{ auth.expiresAt.value || 'N/A' }}
            </p>
          </UCard>
        </div>

        <UAlert
          v-if="auth.lastError.value"
          color="error"
          variant="subtle"
          title="Request error"
          :description="auth.lastError.value"
        />
      </div>
    </UContainer>
  </div>
</template>
