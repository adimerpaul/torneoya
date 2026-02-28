import { defineBoot } from '#q-app/wrappers'
import axios from 'axios'
import { useAuthStore } from 'src/stores/example-store'

const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

const api = axios.create({
  baseURL: apiBaseUrl
})

export default defineBoot(({ app, router, store }) => {
  const auth = useAuthStore(store)

  if (auth.token) {
    api.defaults.headers.common.Authorization = `Bearer ${auth.token}`
  }

  api.interceptors.response.use(
    response => response,
    async error => {
      if (error?.response?.status === 401) {
        auth.clearSession()
        delete api.defaults.headers.common.Authorization
        await router.push('/login')
      }
      return Promise.reject(error)
    }
  )

  app.config.globalProperties.$axios = api
  app.config.globalProperties.$api = api
})

export { api }
