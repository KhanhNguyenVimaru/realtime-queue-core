<script setup lang="ts">
definePageMeta({
  layout: false,
})

const auth = useAuthStore()
const hasSubmitted = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

onMounted(async () => {
  auth.lastError = null
  await auth.initialize()

  if (auth.isAuthenticated) {
    await navigateTo('/')
  }
})

async function handleRegister() {
  hasSubmitted.value = true

  try {
    await auth.register(form)
    await navigateTo('/')
  } catch {
    // Error state is already exposed by the auth composable.
  }
}
</script>

<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,#f5f7fb_0%,#eef2f7_35%,#e8edf4_100%)] px-4 py-10 dark:bg-[radial-gradient(circle_at_top,#17202b_0%,#111827_45%,#0b1220_100%)]">
    <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-5xl items-center justify-center">
      <div class="grid w-full max-w-4xl overflow-hidden rounded-xl border border-default bg-default shadow-xl shadow-black/5 lg:grid-cols-[0.95fr_1.05fr]">
        <div class="hidden border-r border-default bg-muted/30 p-10 lg:flex lg:flex-col lg:justify-between">
          <div class="space-y-4">
            <p class="text-sm font-medium text-muted">
              Realtime Queue Core
            </p>
            <h1 class="text-4xl font-semibold tracking-tight text-highlighted">
              Create account
            </h1>
            <p class="max-w-sm text-sm leading-6 text-toned">
              Register a new user, then continue directly into the internal dashboard.
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
                Create account
              </h1>
            </div>

            <form class="space-y-5" @submit.prevent="handleRegister">
              <UFormField label="Name" name="name">
                <UInput v-model="form.name" size="xl" autocomplete="name" class="w-full" />
              </UFormField>

              <UFormField label="Email" name="email">
                <UInput v-model="form.email" type="email" size="xl" autocomplete="email" class="w-full" />
              </UFormField>

              <UFormField label="Password" name="password">
                <UInput
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  size="xl"
                  autocomplete="new-password"
                  class="w-full"
                >
                  <template #trailing>
                    <UButton
                      type="button"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                      :aria-label="showPassword ? 'Hide password' : 'Show password'"
                      @click="showPassword = !showPassword"
                    />
                  </template>
                </UInput>
              </UFormField>

              <UFormField label="Confirm password" name="password_confirmation">
                <UInput
                  v-model="form.password_confirmation"
                  :type="showPasswordConfirmation ? 'text' : 'password'"
                  size="xl"
                  autocomplete="new-password"
                  class="w-full"
                >
                  <template #trailing>
                    <UButton
                      type="button"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      :icon="showPasswordConfirmation ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                      :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'"
                      @click="showPasswordConfirmation = !showPasswordConfirmation"
                    />
                  </template>
                </UInput>
              </UFormField>

              <UAlert
                v-if="hasSubmitted && auth.lastError"
                color="error"
                variant="subtle"
                title="Registration failed"
                :description="auth.lastError"
              />

              <div class="space-y-3 pt-2">
                <UButton type="submit" block size="xl" color="primary" :loading="auth.pending">
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
