import { defineStore } from 'pinia'

type AuthUser = {
  id: number
  name: string
  email: string
  role: 'admin' | 'user'
}

type AuthResponse = {
  access_token: string
  token_type: string
  expires_at: string
  user: AuthUser
}

type RegisterPayload = {
  name: string
  email: string
  password: string
  password_confirmation: string
}

type LoginPayload = {
  email: string
  password: string
}

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const accessToken = ref<string | null>(null)
  const expiresAt = ref<string | null>(null)
  const currentUser = ref<AuthUser | null>(null)
  const lastError = ref<string | null>(null)
  const initialized = ref(false)
  const pending = ref(false)

  const isAuthenticated = computed(() => Boolean(accessToken.value && currentUser.value))

  function setSession(payload: AuthResponse) {
    accessToken.value = payload.access_token
    expiresAt.value = payload.expires_at
    currentUser.value = payload.user
  }

  function clearSession() {
    accessToken.value = null
    expiresAt.value = null
    currentUser.value = null
  }

  function normalizeError(error: unknown): string {
    const fallback = 'Request failed.'

    if (!error || typeof error !== 'object') {
      return fallback
    }

    const fetchError = error as {
      data?: { message?: string, errors?: Record<string, string[]> }
      statusMessage?: string
      message?: string
    }

    if (fetchError.data?.errors) {
      const firstErrorGroup = Object.values(fetchError.data.errors)[0]
      if (Array.isArray(firstErrorGroup) && firstErrorGroup[0]) {
        return firstErrorGroup[0]
      }
    }

    return fetchError.data?.message || fetchError.statusMessage || fetchError.message || fallback
  }

  async function request<T>(path: string, options: Parameters<typeof $fetch<T>>[1] = {}, retryOnUnauthorized = true): Promise<T> {
    const headers = new Headers(options.headers as HeadersInit | undefined)

    headers.set('Accept', 'application/json')
    headers.set('X-Requested-With', 'XMLHttpRequest')

    if (accessToken.value) {
      headers.set('Authorization', `Bearer ${accessToken.value}`)
    }

    try {
      return await $fetch<T>(`${config.public.apiBase}${path}`, {
        ...options,
        headers,
        credentials: 'include',
      })
    } catch (error) {
      const statusCode = typeof error === 'object' && error && 'statusCode' in error
        ? Number((error as { statusCode?: number }).statusCode)
        : null

      if (retryOnUnauthorized && statusCode === 401 && path !== '/auth/refresh-token') {
        const refreshed = await refreshToken()

        if (refreshed) {
          return request<T>(path, options, false)
        }
      }

      throw error
    }
  }

  async function login(payload: LoginPayload) {
    pending.value = true
    lastError.value = null

    try {
      const response = await request<AuthResponse>('/auth/login', {
        method: 'POST',
        body: payload,
      }, false)

      setSession(response)
      return response
    } catch (error) {
      clearSession()
      lastError.value = normalizeError(error)
      throw error
    } finally {
      pending.value = false
    }
  }

  async function register(payload: RegisterPayload) {
    pending.value = true
    lastError.value = null

    try {
      const response = await request<AuthResponse>('/auth/register', {
        method: 'POST',
        body: payload,
      }, false)

      setSession(response)
      return response
    } catch (error) {
      clearSession()
      lastError.value = normalizeError(error)
      throw error
    } finally {
      pending.value = false
    }
  }

  async function refreshToken() {
    return refreshTokenWithOptions()
  }

  async function refreshTokenWithOptions(options: { silent?: boolean } = {}) {
    try {
      const response = await $fetch<AuthResponse>(`${config.public.apiBase}/auth/refresh-token`, {
        method: 'POST',
        credentials: 'include',
      })

      setSession(response)
      if (!options.silent) {
        lastError.value = null
      }

      return true
    } catch (error) {
      clearSession()

      if (!options.silent) {
        lastError.value = normalizeError(error)
      }

      return false
    }
  }

  async function fetchMe() {
    pending.value = true
    lastError.value = null

    try {
      const response = await request<{ user: AuthUser }>('/auth/me')
      currentUser.value = response.user
      return response.user
    } catch (error) {
      lastError.value = normalizeError(error)
      throw error
    } finally {
      pending.value = false
    }
  }

  async function logout() {
    pending.value = true
    lastError.value = null

    try {
      await request('/auth/logout', {
        method: 'POST',
      }, false)
    } catch (error) {
      lastError.value = normalizeError(error)
    } finally {
      clearSession()
      pending.value = false
    }
  }

  async function initialize() {
    if (initialized.value) {
      return
    }

    if (accessToken.value) {
      try {
        await fetchMe()
        initialized.value = true
        return
      } catch {
        clearSession()
      }
    }

    await refreshTokenWithOptions({ silent: true })
    initialized.value = true
  }

  return {
    accessToken,
    currentUser,
    expiresAt,
    initialized,
    isAuthenticated,
    lastError,
    pending,
    fetchMe,
    initialize,
    login,
    logout,
    refreshToken,
    register,
    request,
  }
})
