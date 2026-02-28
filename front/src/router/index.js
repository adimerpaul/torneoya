import { defineRouter } from '#q-app/wrappers'
import { createMemoryHistory, createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import routes from './routes'
import { useAuthStore } from 'src/stores/example-store'

export default defineRouter(({ store }) => {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE)
  })

  router.beforeEach((to, _from, next) => {
    const auth = useAuthStore(store)

    if (to.meta.requiresAuth && !auth.isLoggedIn()) {
      next('/login')
      return
    }

    if (to.path === '/login' && auth.isLoggedIn()) {
      next('/')
      return
    }

    next()
  })

  return router
})
