<script setup lang="ts">
const auth = useAuth()
const colorMode = useColorMode()

const isDark = computed(() => colorMode.value === 'dark')

async function handleLogout() {
  await auth.logout()
  await navigateTo('/login')
}

function toggleColorMode() {
  colorMode.preference = isDark.value ? 'light' : 'dark'
}
</script>

<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,#f5f7fb_0%,#eef2f7_35%,#e8edf4_100%)] dark:bg-[radial-gradient(circle_at_top,#17202b_0%,#111827_45%,#0b1220_100%)]">
    <header class="border-b border-default/70 bg-white/80 backdrop-blur dark:bg-neutral-950/70">
      <UContainer class="max-w-5xl px-4 py-4">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary">
              Realtime Queue Core
            </p>
            <h1 class="text-lg font-semibold text-highlighted">
              Event Signup
            </h1>
          </div>

          <div class="flex items-center gap-2">
            <UButton
              to="/"
              color="neutral"
              variant="ghost"
              icon="i-lucide-house"
            >
              Home
            </UButton>

            <UButton
              to="/users"
              color="neutral"
              variant="ghost"
              icon="i-lucide-users"
            >
              Users
            </UButton>

            <UButton
              to="/events"
              color="neutral"
              variant="ghost"
              icon="i-lucide-calendar-days"
            >
              Events
            </UButton>

            <UButton
              to="/account"
              color="neutral"
              variant="ghost"
              icon="i-lucide-user-round"
            >
              Account
            </UButton>

            <UButton
              color="error"
              variant="soft"
              icon="i-lucide-log-out"
              :loading="auth.pending.value"
              @click="handleLogout"
            >
              Logout
            </UButton>

            <UButton
              color="neutral"
              variant="ghost"
              :icon="isDark ? 'i-lucide-sun' : 'i-lucide-moon'"
              @click="toggleColorMode"
            >
              {{ isDark ? 'Light' : 'Dark' }}
            </UButton>
          </div>
        </div>
      </UContainer>
    </header>

    <main>
      <UContainer class="max-w-5xl px-4 py-8 sm:py-10">
        <slot />
      </UContainer>
    </main>
  </div>
</template>
