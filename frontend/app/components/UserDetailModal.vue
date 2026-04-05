<script setup lang="ts">
type UserRow = {
  id: number
  name: string
  email: string
  role: 'admin' | 'user'
  created_at: string | null
  updated_at: string | null
}

const open = defineModel<boolean>('open', { default: false })

defineProps<{
  user: UserRow | null
}>()

function formatDate(value: string | null) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}
</script>

<template>
  <UModal v-model:open="open" title="User detail">
    <template #body>
      <template v-if="user">
        <div class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <UCard>
              <p class="text-xs uppercase tracking-wide text-muted">
                Name
              </p>
              <p class="mt-2 text-sm font-medium text-highlighted">
                {{ user.name }}
              </p>
            </UCard>

            <UCard>
              <p class="text-xs uppercase tracking-wide text-muted">
                Role
              </p>
              <p class="mt-2 text-sm font-medium text-highlighted">
                {{ user.role }}
              </p>
            </UCard>

            <UCard>
              <p class="text-xs uppercase tracking-wide text-muted">
                Email
              </p>
              <p class="mt-2 text-sm font-medium text-highlighted">
                {{ user.email }}
              </p>
            </UCard>

            <UCard>
              <p class="text-xs uppercase tracking-wide text-muted">
                Created at
              </p>
              <p class="mt-2 text-sm font-medium text-highlighted">
                {{ formatDate(user.created_at) }}
              </p>
            </UCard>
          </div>
        </div>
      </template>
    </template>
  </UModal>
</template>
