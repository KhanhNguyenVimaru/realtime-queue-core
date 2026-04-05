<script setup lang="ts">
type UserRow = {
  id: number
  name: string
  email: string
  role: 'admin' | 'user'
  created_at: string | null
  updated_at: string | null
}

type UserPayload = {
  name: string
  email: string
  password: string
  role: 'admin' | 'user'
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  user: UserRow | null
  pending?: boolean
  error?: string
}>()

const emit = defineEmits<{
  submit: [payload: UserPayload]
}>()

const roleOptions = [
  { label: 'Admin', value: 'admin' },
  { label: 'User', value: 'user' },
]

const form = reactive<UserPayload>({
  name: '',
  email: '',
  password: '',
  role: 'user',
})

watch(
  () => props.user,
  (user) => {
    form.name = user?.name || ''
    form.email = user?.email || ''
    form.password = ''
    form.role = user?.role || 'user'
  },
  { immediate: true }
)

function submitForm() {
  emit('submit', {
    name: form.name,
    email: form.email,
    password: form.password,
    role: form.role,
  })
}
</script>

<template>
  <UModal v-model:open="open" title="Edit user">
    <template #body>
      <form class="space-y-4" @submit.prevent="submitForm">
        <UFormField label="Name" name="edit-name">
          <UInput v-model="form.name" class="w-full" />
        </UFormField>

        <UFormField label="Email" name="edit-email">
          <UInput v-model="form.email" type="email" class="w-full" />
        </UFormField>

        <UFormField label="Password" name="edit-password">
          <UInput v-model="form.password" type="password" placeholder="Leave blank to keep current password" class="w-full" />
        </UFormField>

        <UFormField label="Role" name="edit-role">
          <USelect v-model="form.role" :items="roleOptions" value-key="value" class="w-full" />
        </UFormField>

        <UAlert
          v-if="error"
          color="error"
          variant="subtle"
          title="Update failed"
          :description="error"
        />

        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Cancel
          </UButton>
          <UButton type="submit" :loading="pending">
            Save changes
          </UButton>
        </div>
      </form>
    </template>
  </UModal>
</template>
