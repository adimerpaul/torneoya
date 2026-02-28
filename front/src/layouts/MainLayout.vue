<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-white text-black" bordered>
      <q-toolbar>
        <q-btn
          dense
          rounded
          color="primary"
          :icon="leftDrawerOpen ? 'keyboard_double_arrow_left' : 'keyboard_double_arrow_right'"
          aria-label="Menu"
          size="10px"
          @click="toggleLeftDrawer"
          unelevated
        />
        <span class="q-pa-xs text-bold">{{ version }}</span>
        <q-toolbar-title class="text-weight-bold text-white">
          Torneo Ya
        </q-toolbar-title>

        <q-btn-dropdown flat unelevated no-caps dropdown-icon="expand_more">
          <template #label>
            <q-avatar rounded size="34px">
              <q-img :src="avatarSrc" />
            </q-avatar>
            <div class="text-center q-ml-sm" style="line-height: 1">
              <div style="max-width: 130px; white-space: normal; overflow-wrap: break-word;">
                {{ $store.user.name }} <br>
                <q-chip color="red" dense size="xs" class="text-white">{{ $store.user.role }}</q-chip>
              </div>
            </div>
          </template>
          <q-item clickable v-ripple @click="logout" v-close-popup>
            <q-item-section avatar>
              <q-icon name="logout" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Salir</q-item-label>
            </q-item-section>
          </q-item>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      bordered
      show-if-above
      :width="220"
      :breakpoint="500"
      class="bg-primary text-white"
    >
      <q-list>
        <q-item-label header class="text-center">
          <q-avatar size="80px" class="bg-white" rounded>
            <q-img src="/logo.png" width="100px" />
          </q-avatar>
          <div class="q-mt-sm text-weight-bold text-white">Torneo Ya</div>
        </q-item-label>

        <template v-for="link in linksList" :key="link.title">
          <q-item
            clickable
            :to="link.link"
            exact
            class="text-white"
            active-class="menu"
            dense
            v-close-popup
            v-if="canSee(link)"
          >
            <q-item-section avatar>
              <q-icon
                :name="$route.path === link.link ? 'o_' + link.icon : link.icon"
                :class="$route.path === link.link ? 'text-black' : ''"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="$route.path === link.link ? 'text-black text-bold' : ''">
                {{ link.title }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <q-item clickable class="text-black" @click="logout" v-close-popup>
          <q-item-section avatar>
            <q-icon name="logout" class="text-white" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Salir</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container class="bg-grey-3">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: 'MainLayout',
  data() {
    return {
      leftDrawerOpen: false,
      version: import.meta.env.VITE_API_VERSION || '1.0.0'
    }
  },
  computed: {
    avatarSrc() {
      const user = this.$store && this.$store.user ? this.$store.user : {}
      const avatar = user.avatar || 'avatar.png'
      return this.$url + '../images/' + avatar
    },
    linksList() {
      return [
        { title: 'Principal', icon: 'home', link: '/', perm: 'Principal', always: true },
        { title: 'Usuarios', icon: 'people', link: '/usuarios', perm: 'Usuarios' },
        // { path: 'campeonatos', component: () => import('pages/campeonatos/CampeonatosPage.vue'), meta: { requiresAuth: true } },
        { title: 'Campeonatos', icon: 'emoji_events', link: '/campeonatos', perm: 'Campeonatos' },
      ]
    }
  },
  methods: {
    toggleLeftDrawer() {
      this.leftDrawerOpen = !this.leftDrawerOpen
    },
    logout() {
      this.$alert.dialog('¿Desea salir del sistema?')
        .onOk(() => {
          this.$axios.post('/logout')
            .finally(() => {
              this.$store.isLogged = false
              this.$store.user = {}
              this.$store.permissions = []
              localStorage.removeItem('tokenTorneoya')
              localStorage.removeItem('user')
              this.$alert.success('Sesión cerrada', 'Éxito')
              this.$router.push('/login')
            })
        })
    },
    canSee(link) {
      if (!link) {
        return false
      }

      if (link.always) {
        return true
      }

      const user = this.$store && this.$store.user ? this.$store.user : {}
      const role = String(user.role || '').toUpperCase()
      const isAdmin = role === 'ADMIN' || role === 'ADMINISTRADOR'
      if (isAdmin) {
        return true
      }

      const isCamion = !!user.es_camion
      const permissionsRaw = (this.$store && this.$store.permissions) ? this.$store.permissions : []
      const permissions = permissionsRaw
        .map(p => (typeof p === 'string' ? p : p && p.name))
        .filter(Boolean)

      if (permissions.includes(link.perm)) {
        return true
      }

      return !!link.esCamion && isCamion
    }
  }
}
</script>

<style>
.menu {
  background-color: #fff;
  color: #000 !important;
  border-radius: 10px;
  margin: 5px;
  padding: 5px;
}
</style>
