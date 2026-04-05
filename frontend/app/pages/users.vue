<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'admin',
})

useSeoMeta({
  title: 'Users',
  description: 'Protected users page'
})

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

const auth = useAuth()

const users = ref<UserRow[]>([])
const pending = ref(false)
const pageError = ref('')
const formError = ref('')
const deleteError = ref('')

const isDetailOpen = ref(false)
const isEditOpen = ref(false)

const selectedUser = ref<UserRow | null>(null)
const editingUser = ref<UserRow | null>(null)

const headers = [
  'ID',
  'Name',
  'Email',
  'Role',
  'Created',
  'Actions',
]

const skeletonRows = Array.from({ length: 4 }, (_, index) => `skeleton-${index}`)

function formatDate(value: string | null) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function readError(error: unknown, fallback: string) {
  if (!error || typeof error !== 'object') {
    return fallback
  }

  const fetchError = error as {
    data?: { message?: string, errors?: Record<string, string[]> }
    message?: string
    statusMessage?: string
  }

  const errors = fetchError.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]
    if (Array.isArray(first) && first[0]) {
      return first[0]
    }
  }

  return fetchError.data?.message || fetchError.statusMessage || fetchError.message || fallback
}

async function fetchUsers() {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ users: UserRow[] }>('/admin/users')
    users.value = response.users
  } catch (error) {
    pageError.value = readError(error, 'Unable to load users.')
  } finally {
    pending.value = false
  }
}

async function openDetail(userId: number) {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ user: UserRow }>(`/admin/users/${userId}`)
    selectedUser.value = response.user
    isDetailOpen.value = true
  } catch (error) {
    pageError.value = readError(error, 'Unable to load user details.')
  } finally {
    pending.value = false
  }
}

function openEditModal(user: UserRow) {
  formError.value = ''
  editingUser.value = user
  isEditOpen.value = true
}

async function updateUser(payload: UserPayload) {
  if (!editingUser.value) {
    return
  }

  pending.value = true
  formError.value = ''

  try {
    await auth.request(`/admin/users/${editingUser.value.id}`, {
      method: 'PUT',
      body: payload,
    })

    isEditOpen.value = false
    editingUser.value = null
    await fetchUsers()
  } catch (error) {
    formError.value = readError(error, 'Unable to update user.')
  } finally {
    pending.value = false
  }
}

async function deleteUser(user: UserRow) {
  deleteError.value = ''

  const confirmed = window.confirm(`Delete user "${user.email}"?`)
  if (!confirmed) {
    return
  }

  pending.value = true

  try {
    await auth.request(`/admin/users/${user.id}`, {
      method: 'DELETE',
    })

    await fetchUsers()
  } catch (error) {
    deleteError.value = readError(error, 'Unable to delete user.')
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await fetchUsers()
})
</script>

<template>
  <div class="space-y-6">
    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
    />

    <UAlert
      v-if="deleteError"
      color="error"
      variant="subtle"
      title="Delete failed"
      :description="deleteError"
    />

    <UCard class="overflow-hidden shadow-sm ring-1 ring-default">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-default">
          <thead class="bg-muted/40">
            <tr>
              <th
                v-for="header in headers"
                :key="header"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted"
              >
                {{ header }}
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-default">
            <tr v-if="pending" v-for="row in skeletonRows" :key="row" class="align-top">
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-10" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-36" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-48" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-6 w-16 rounded-full" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-32" />
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap gap-2">
                  <USkeleton class="h-8 w-20 rounded-md" />
                  <USkeleton class="h-8 w-16 rounded-md" />
                  <USkeleton class="h-8 w-20 rounded-md" />
                </div>
              </td>
            </tr>

            <tr v-else v-for="user in users" :key="user.id" class="align-top">
              <td class="px-4 py-4 text-sm text-toned">
                {{ user.id }}
              </td>
              <td class="px-4 py-4 text-sm font-medium text-highlighted">
                {{ user.name }}
              </td>
              <td class="px-4 py-4 text-sm text-toned">
                {{ user.email }}
              </td>
              <td class="px-4 py-4 text-sm">
                <UBadge :color="user.role === 'admin' ? 'primary' : 'neutral'" variant="soft">
                  {{ user.role }}
                </UBadge>
              </td>
              <td class="px-4 py-4 text-sm text-toned">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap gap-2">
                  <UButton
                    color="neutral"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-eye"
                    @click="openDetail(user.id)"
                  >
                    Detail
                  </UButton>

                  <UButton
                    color="primary"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-pencil"
                    @click="openEditModal(user)"
                  >
                    Edit
                  </UButton>

                  <UButton
                    color="error"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-trash-2"
                    @click="deleteUser(user)"
                  >
                    Delete
                  </UButton>
                </div>
              </td>
            </tr>

            <tr v-if="!users.length && !pending">
              <td colspan="6" class="px-4 py-10 text-center text-sm text-muted">
                No users found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </UCard>
  </div>

  <UserDetailModal v-model:open="isDetailOpen" :user="selectedUser" />
  <UserEditModal
    v-model:open="isEditOpen"
    :user="editingUser"
    :pending="pending"
    :error="formError"
    @submit="updateUser"
  />
</template>
