<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useSeoMeta({
  title: 'Activity Logs',
  description: 'Event participation history',
})

type LogRow = {
  id: number
  action: 'join' | 'leave' | string
  created_at: string | null
  user?: {
    id: number
    name: string
    email: string
  } | null
  event?: {
    id: number
    title: string
  } | null
}

type PaginationMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const auth = useAuthStore()
const toast = useToast()

const logs = ref<LogRow[]>([])
const pending = ref(false)
const pageError = ref('')

const search = ref('')
const actionFilter = ref<'all' | 'join' | 'leave'>('all')
const perPage = 15
const currentPage = ref(1)
const lastPage = ref(1)
const totalLogs = ref(0)

const isAdmin = computed(() => auth.currentUser?.role === 'admin')

const headers = [
  'ID',
  'Event',
  'User',
  'Action',
  'Time',
]

const skeletonRows = Array.from({ length: 5 }, (_, index) => `skeleton-${index}`)

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

  if (actionFilter.value && actionFilter.value !== 'all') {
    params.set('action', actionFilter.value)
  }

  params.set('per_page', String(perPage))
  params.set('page', String(currentPage.value))

  const query = params.toString()
  return query ? `/logs?${query}` : '/logs'
}

async function fetchLogs() {
  pending.value = true
  pageError.value = ''

  try {
    const response = await auth.request<{ logs: LogRow[], meta: PaginationMeta }>(buildQuery())
    logs.value = response.logs
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    totalLogs.value = response.meta.total
  } catch (error) {
    pageError.value = readError(error, 'Unable to load activity logs.')
  } finally {
    pending.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) {
    return
  }

  currentPage.value = page
  fetchLogs()
}

onMounted(async () => {
  await fetchLogs()
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchLogs()
  }, 350)
})

watch(actionFilter, () => {
  currentPage.value = 1
  fetchLogs()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
      <div>
        <h2 class="text-lg font-semibold text-highlighted">
          Activity Logs
        </h2>
        <p class="text-sm text-muted">
          {{ isAdmin ? 'All event participation history' : 'Your event participation history' }}
        </p>
      </div>

      <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
        <UFormField name="search-logs" class="w-full md:w-64">
          <UInput v-model="search" placeholder="Search event or user" class="w-full" />
        </UFormField>

        <USelect
          v-model="actionFilter"
          :items="[
            { label: 'All actions', value: 'all' },
            { label: 'Join', value: 'join' },
            { label: 'Leave', value: 'leave' },
          ]"
          class="w-full md:w-40"
        />
      </div>
    </div>

    <UAlert
      v-if="pageError"
      color="error"
      variant="subtle"
      title="Request failed"
      :description="pageError"
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
                <USkeleton class="h-5 w-48" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-32" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-16" />
              </td>
              <td class="px-4 py-4">
                <USkeleton class="h-5 w-28" />
              </td>
            </tr>

            <tr
              v-else
              v-for="log in logs"
              :key="log.id"
              class="align-top"
            >
              <td class="px-4 py-4 text-sm text-toned">
                {{ log.id }}
              </td>
              <td class="px-4 py-4 text-sm font-medium text-highlighted">
                {{ log.event?.title || 'Unknown event' }}
              </td>
              <td class="px-4 py-4 text-sm text-toned">
                <div class="flex flex-col">
                  <span>{{ log.user?.name || 'Unknown user' }}</span>
                  <span class="text-xs text-muted">{{ log.user?.email }}</span>
                </div>
              </td>
              <td class="px-4 py-4">
                <UBadge
                  :color="log.action === 'join' ? 'primary' : 'warning'"
                  variant="soft"
                  size="sm"
                >
                  {{ log.action === 'join' ? 'Join' : 'Leave' }}
                </UBadge>
              </td>
              <td class="px-4 py-4 text-sm text-toned">
                {{ formatDate(log.created_at) }}
              </td>
            </tr>

            <tr v-if="!logs.length && !pending">
              <td colspan="5" class="px-4 py-10 text-center text-sm text-muted">
                No logs found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-default px-4 py-3 text-sm text-muted">
        <div>
          Page {{ currentPage }} / {{ lastPage }} · Total {{ totalLogs }}
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
  </div>
</template>
