import { defineStore, acceptHMRUpdate } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('tokenTorneoYa') || null,
    user: (() => {
      const raw = localStorage.getItem('userTorneoYa')
      return raw ? JSON.parse(raw) : null
    })()
  }),

  actions: {
    isLoggedIn() {
      return !!this.token
    },
    setSession({ token, user }) {
      this.token = token
      this.user = user
      localStorage.setItem('tokenTorneoYa', token)
      localStorage.setItem('userTorneoYa', JSON.stringify(user))
    },
    clearSession() {
      this.token = null
      this.user = null
      localStorage.removeItem('tokenTorneoYa')
      localStorage.removeItem('userTorneoYa')
    }
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useAuthStore, import.meta.hot))
}
