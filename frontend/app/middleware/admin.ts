export default defineNuxtRouteMiddleware(async () => {
  if (import.meta.server) {
    return
  }

  const auth = useAuthStore()
  const toast = useToast()

  await auth.initialize()

  if (!auth.isAuthenticated) {
    return navigateTo('/login')
  }

  if (auth.currentUser?.role !== 'admin') {
    toast.add({
      title: 'Access denied',
      description: 'You do not have permission to access this page.',
      color: 'error',
    })

    return navigateTo('/')
  }
})
