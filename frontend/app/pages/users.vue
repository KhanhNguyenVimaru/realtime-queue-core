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

type PaginationMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const auth = useAuthStore()
const toast = useToast()

const users = ref<UserRow[]>([])
const pending = ref(false)
const pageError = ref('')
const formError = ref('')
const deleteError = ref('')

const isEditOpen = ref(false)

const editingUser = ref<UserRow | null>(null)

const search = ref('')
const role = ref<'admin' | 'user' | null>(null)
const sortBy = ref<'latest' | 'oldest'>('latest')
const perPage = 10
const currentPage = ref(1)
const lastPage = ref(1)
const totalUsers = ref(0)

const roleOptions = [
  { label: 'All roles', value: null },
  { label: 'Admin', value: 'admin' },
  { label: 'User', value: 'user' },
]

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

function buildQuery() {
  const params = new URLSearchParams()

  const term = search.value.trim()
  if (term) {
    params.set('search', term)
  }

  if (role.value) {
    params.set('role', role.value)
  }

  if (sortBy.value) {
    params.set('sort_by', sortBy.value)
  }

  params.set('per_page', String(perPage))
  params.set('page', String(currentPage.value))

  const query = params.toString()
  return query ? `/admin/users?${query}` : '/admin/users'
}

function toggleSort() {
  sortBy.value = sortBy.value === 'latest' ? 'oldest' : 'latest'
}

async function fetchUsers() {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ users: UserRow[], meta: PaginationMeta }>(buildQuery())
    users.value = response.users
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    totalUsers.value = response.meta.total
  } catch (error) {
    pageError.value = readError(error, 'Unable to load users.')
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

function confirmDelete(user: UserRow) {
  return new Promise<boolean>((resolve) => {
    let resolved = false
    let timeoutId: ReturnType<typeof setTimeout> | null = null

    const finalize = (value: boolean) => {
      if (resolved) {
        return
      }
      resolved = true
      if (timeoutId) {
        clearTimeout(timeoutId)
      }
      resolve(value)
    }

    const toastItem = toast.add({
      title: 'Delete user?',
      description: `Delete user "${user.email}"?`,
      color: 'warning',
      duration: 5000,
      actions: [
        {
          label: 'Cancel',
          color: 'neutral',
          variant: 'ghost',
          onClick: () => {
            finalize(false)
            toast.remove(toastItem.id)
          },
        },
        {
          label: 'OK',
          color: 'error',
          onClick: () => {
            finalize(true)
            toast.remove(toastItem.id)
          },
        },
      ],
    })

    timeoutId = setTimeout(() => {
      toast.remove(toastItem.id)
      finalize(false)
    }, 5200)
  })
}

async function deleteUser(user: UserRow) {
  deleteError.value = ''

  const confirmed = await confirmDelete(user)
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

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchUsers()
  }, 350)
})

watch([role, sortBy], () => {
  currentPage.value = 1
  fetchUsers()
})

function goToPage(page: number) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) {
    return
  }

  currentPage.value = page
  fetchUsers()
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-end">
      <div class="flex w-full flex-col gap-3 md:w-1/2 md:flex-row md:justify-end">
        <UFormField name="search-users" class="w-full">
          <UInput v-model="search" placeholder="Search by id, name, email" class="w-full" />
        </UFormField>

        <UFormField name="filter-role" class="w-full md:max-w-[180px]">
          <USelect v-model="role" :items="roleOptions" value-key="value" class="w-full" />
        </UFormField>
      </div>

      <UButton
        color="primary"
        variant="solid"
        class="w-fit"
        icon="i-lucide-arrow-up-down"
        @click="toggleSort"
      >
        {{ sortBy === 'latest' ? 'Newest' : 'Oldest' }}
      </UButton>
    </div>

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

      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-default px-4 py-3 text-sm text-muted">
        <div>
          Page {{ currentPage }} / {{ lastPage }} · Total {{ totalUsers }}
        </div>
        <div class="flex gap-2">
          <UButton
            color="neutral"
            variant="soft"
            size="sm"
            :disabled="currentPage <= 1 || pending"
            @click="goToPage(currentPage - 1)"
          >
            Previous
          </UButton>
          <UButton
            color="neutral"
            variant="soft"
            size="sm"
            :disabled="currentPage >= lastPage || pending"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </UButton>
        </div>
      </div>
    </UCard>
    <UserEditModal
      v-model:open="isEditOpen"
      :user="editingUser"
      :pending="pending"
      :error="formError"
      @submit="updateUser"
    />
  </div>
</template>
