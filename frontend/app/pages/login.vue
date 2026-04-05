<script setup lang="ts">
definePageMeta({
  layout: false,
})

const auth = useAuth()
const hasSubmitted = ref(false)

const form = reactive({
  email: '',
  password: ''
})

onMounted(async () => {
  auth.lastError.value = null
  await auth.initialize()

  if (auth.isAuthenticated.value) {
    await navigateTo('/')
  }
})

async function handleLogin() {
  hasSubmitted.value = true

  try {
    await auth.login(form)
    await navigateTo('/')
  } catch {
    // Error state is already exposed by the auth composable.
  }
}
</script>

<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,#f5f7fb_0%,#eef2f7_35%,#e8edf4_100%)] px-4 py-10 dark:bg-[radial-gradient(circle_at_top,#17202b_0%,#111827_45%,#0b1220_100%)]">
    <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-5xl items-center justify-center">
      <div class="grid w-full max-w-4xl overflow-hidden rounded-xl border border-default bg-default shadow-xl shadow-black/5 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="hidden border-r border-default bg-muted/30 p-10 lg:flex lg:flex-col lg:justify-between">
          <div class="space-y-4">
            <p class="text-sm font-medium text-muted">
              Realtime Queue Core
            </p>
            <h1 class="text-4xl font-semibold tracking-tight text-highlighted">
              Sign in
            </h1>
            <p class="max-w-sm text-sm leading-6 text-toned">
              Access the internal dashboard and manage your current authenticated session.
            </p>
          </div>
        </div>

        <div class="p-6 sm:p-10">
          <div class="mx-auto max-w-md">
            <div class="mb-8 space-y-2 lg:hidden">
              <p class="text-sm font-medium text-muted">
                Realtime Queue Core
              </p>
              <h1 class="text-3xl font-semibold tracking-tight text-highlighted">
                Sign in
              </h1>
            </div>

            <form class="space-y-5" @submit.prevent="handleLogin">
              <UFormField label="Email" name="email">
                <UInput v-model="form.email" type="email" size="xl" autocomplete="email" class="w-full" />
              </UFormField>

              <UFormField label="Password" name="password">
                <UInput v-model="form.password" type="password" size="xl" autocomplete="current-password" class="w-full" />
              </UFormField>

              <UAlert
                v-if="hasSubmitted && auth.lastError.value"
                color="error"
                variant="subtle"
                title="Login failed"
                :description="auth.lastError.value"
              />

              <div class="space-y-3 pt-2">
                <UButton type="submit" block size="xl" color="primary" :loading="auth.pending.value">
                  Sign in
                </UButton>
                <UButton to="/register" block size="xl" color="neutral" variant="soft">
                  Create account
                </UButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
