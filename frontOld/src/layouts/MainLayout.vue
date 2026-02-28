<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-white text-black" bordered >
      <q-toolbar>
        <!--        keyboard_double_arrow_left-->
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
        <span class="q-pa-xs text-bold">{{version}}</span>
        <q-toolbar-title>
        </q-toolbar-title>
        <div class="q-mr-sm">
          <q-btn flat round dense icon="notifications" @click="loadFallas">
            <q-badge v-if="fallasPendientes > 0" color="negative" floating>{{ fallasPendientes }}</q-badge>
            <q-tooltip>Fallas CUFD</q-tooltip>
            <q-menu>
              <q-list style="min-width: 360px; max-width: 420px;">
                <q-item-label header>
                  Alertas de impuestos
                </q-item-label>
                <q-item v-if="fallas.length === 0">
                  <q-item-section>Sin fallas pendientes</q-item-section>
                </q-item>
                <q-item v-for="falla in fallas.slice(0, 5)" :key="falla.id">
                  <q-item-section>
                    <q-item-label class="text-negative text-weight-bold">Fallo generación CUFD</q-item-label>
                    <q-item-label caption>{{ falla.mensaje }}</q-item-label>
                    <q-item-label caption>{{ falla.detalle?.error || '' }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator />
                <q-item clickable v-close-popup @click="$router.push('/impuestos')">
                  <q-item-section avatar><q-icon name="settings" /></q-item-section>
                  <q-item-section>Ir a Impuestos / Ajustes</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </div>
        <div>
          <q-btn-dropdown flat unelevated  no-caps dropdownIcon="expand_more">
            <template v-slot:label>
              <q-avatar rounded>
                <q-img :src="$url+ '../images/' + $store.user.avatar" v-if="$store.user.avatar" />
              </q-avatar>
              <div class="text-center" style="line-height: 1">
                <div style="width: 100px; white-space: normal; overflow-wrap: break-word;">
                  {{ $store.user.name }} <br>
                  <q-chip color="red" dense size="xs" class="text-white">{{$store.user.role}}</q-chip>
                  <q-chip :color="$store.user.es_camion ? 'teal' : 'grey'" dense size="xs" class="text-white q-ml-xs">
                    Camión: {{ $store.user.es_camion ? 'Sí' : 'No' }}
                  </q-chip>
                </div>
                <!--                <pre>{{$store.user}}</pre>-->
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
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      bordered
      show-if-above
      :width="200"
      :breakpoint="500"
      class="bg-primary text-white"
    >
      <q-list>
        <q-item-label
          header
          class="text-center"
        >
          <q-avatar size="80px" class="bg-white" rounded>
            <q-img src="/logo.png" width="100px" />
          </q-avatar>
        </q-item-label>

        <!--        <EssentialLink-->
        <!--          v-for="link in linksList"-->
        <!--          :key="link.title"-->
        <!--          v-bind="link"-->
        <!--        />-->
        <template v-for="link in linksList" :key="link.title">
          <!--          v-if="link.can === 'Todos' || $store.permissions.some(permission => permission.name === link.can)"-->
          <q-item  clickable :to="link.link" exact
                   class="text-black"
                   active-class="menu"
                   dense
                   v-close-popup
                   v-if="canSee(link)"
          >
            <q-item-section avatar>
              <q-icon :name="$route.path === link.link ? 'o_' + link.icon : link.icon"
                      :class="$route.path === link.link ? 'text-black' : ''"/>
            </q-item-section>
            <q-item-section>
              <q-item-label :class="$route.path === link.link ? `text-black text-bold ${link.color}` : ''">
                {{ link.title }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
        <q-item clickable class="text-black" @click="logout" v-close-popup>
          <q-item-section avatar>
            <q-icon name="logout" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Salir</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container class="bg-grey-3">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import {getCurrentInstance, onBeforeUnmount, onMounted, ref} from 'vue'
// import EssentialLink from 'components/EssentialLink.vue'
const {proxy} = getCurrentInstance()
const linksList = ref([])


const leftDrawerOpen = ref(false)
const fallasPendientes = ref(0)
const fallas = ref([])
let pollTimer = null

const version =import.meta.env.VITE_API_VERSION

onMounted(() => {
  const user = JSON.parse(localStorage.getItem('user')) || {}

  const baseLinks = [
    { title: 'Principal', icon: 'home', link: '/', always: true },
    { title: 'Usuarios', icon: 'people', link: '/usuarios', perm: 'Usuarios' },
    { title: 'Impuestos', icon: 'percent', link: '/impuestos', perm: 'Impuestos' },
    { title: 'Productos', icon: 'shopping_cart', link: '/productos', perm: 'Productos' },
    { title: 'Clientes', icon: 'groups', link: '/clientes', perm: 'Clientes' },
    { title: 'Nueva Cliente', icon: 'person_add', link: '/alta-cliente', perm: 'Clientes' },
    { title: 'Ventas', icon: 'shopping_bag', link: '/venta', perm: 'Ventas' },
    { title: 'Nueva Venta', icon: 'add_shopping_cart', link: '/ventaNuevo', perm: 'Nueva Venta' },
    { title: 'Proveedores', icon: 'manage_accounts', link: '/proveedores', perm: 'Proveedores'},
    { title: 'Compras', icon: 'storefront', link: '/compras', perm: 'Compras'},
    { title: 'Compras Nueva', icon: 'shopping_basket', link: '/compras-create', perm: 'Nueva Compra'},
    { title: 'Productos por vencer', icon: 'warning', link: '/productos-vencer', perm: 'Productos por vencer'},
    { title: 'Productos vencidos', icon: 'do_not_touch', link: '/productos-vencidos', perm: 'Productos vencidos'},
    { title: 'Pedidos', icon: 'real_estate_agent', link: '/pedidos', perm: 'Pedidos'},
    { title: 'Visitas', icon: 'map', link: '/visitas', perm: 'Pedidos'},
    { title: 'Mis pedidos', icon: 'playlist_add_check', link: '/mis-pedidos', perm: 'Pedidos'},
    { title: 'Mis pedidos totales', icon: 'summarize', link: '/mis-pedidos-totales', perm: 'Mis pedidos totales'},
    { title: 'Mapa cliente', icon: 'map', link: '/mapa-cliente', perm: 'Mapa cliente'},
    { title: 'Mapa zonas', icon: 'palette', link: '/mapa-zonas', perm: 'Mapa cliente zonas'},
    { title: 'Auxiliar de camara', icon: 'warehouse', link: '/auxiliar-camara', perm: 'Auxiliar de camara'},
    { title: 'Digitador factura', icon: 'receipt_long', link: '/digitador-factura', perm: 'Digitador factura'},
    { title: 'Cobranzas deudas', icon: 'payments', link: '/cobranzas', perm: 'Cobranzas' },
    { title: 'Historial cobranzas', icon: 'history', link: '/historial-cobranzas', perm: 'Cobranzas' },
    { title: 'Rutas', icon: 'route', link: '/rutas-camion', perm: 'Rutas', esCamion: true },
    { title: 'Despacho', icon: 'local_shipping', link: '/despacho-camion', perm: 'Despacho', esCamion: true },
    { title: 'Realizar pedido', icon: 'shopping_cart_checkout', link: '/pedidosCompra', perm: 'Nuevo Pedido'},
  ]
  linksList.value = baseLinks
  loadFallas()
  // pollTimer = setInterval(() => loadFallas(false), 60000)

  // const sucursalLinks = {
  //   'Ayacucho': { title: 'Ayacucho', icon: 'event', link: '/reservas', can: 'Todos', color: 'text-green' },
  //   'Oquendo': { title: 'Oquendo', icon: 'event', link: '/reservasOquendo', can: 'Todos', color: 'text-blue' },
  // }
  //
  // const altSucursal = user.sucursal === 'Ayacucho' ? 'Oquendo' : 'Ayacucho'
  //
  // linksList.value = [
  //   ...baseLinks.slice(0, 2),
  //   sucursalLinks[user.sucursal],
  //   ...baseLinks.slice(2),
  //   sucursalLinks[altSucursal]
  // ]
})

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
})

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
function logout() {
  proxy.$alert.dialog('¿Desea salir del sistema?')
    .onOk(() => {
      // proxy.$store.isLogged = false
      // proxy.$store.user = {}
      // localStorage.removeItem('tokenProvidencia')
      // proxy.$router.push('/login')
      proxy.$axios.post('/logout')
        .then(() => {
          proxy.$store.isLogged = false
          proxy.$store.user = {}
          localStorage.removeItem('tokenSofiaFactu')
          localStorage.removeItem('user')
          proxy.$alert.success('Sesión cerrada', 'Éxito')
          proxy.$router.push('/login')
        })
        .catch(() => {
          proxy.$store.isLogged = false
          proxy.$store.user = {}
          localStorage.removeItem('tokenSofiaFactu')
          localStorage.removeItem('user')
          proxy.$alert.success('Sesión cerrada', 'Éxito')
          proxy.$router.push('/login')
        })
    })
}
function canSee(link) {
  if (!link || !proxy.$store?.user) return false
  if (link.always) return true
  const isCamion = !!proxy.$store.user.es_camion
  const isCobrador = String(proxy.$store.user.role || '').toUpperCase() === 'COBRADOR'
  const perms = (proxy.$store.permissions || []).map(p => typeof p === 'string' ? p : p?.name).filter(Boolean)
  const requiredPerm = link.perm || link.title
  if (perms.includes(requiredPerm)) return true
  if (requiredPerm === 'Cobranzas' && isCobrador) return true
  return !!link.esCamion && isCamion
}
function loadFallas(showError = false) {
  proxy.$axios.get('/impuestos/fallas')
    .then((res) => {
      fallasPendientes.value = res.data?.pending || 0
      fallas.value = (res.data?.data || []).filter((x) => x.estado === 'pendiente')
    })
    .catch((err) => {
      if (showError) {
        proxy.$alert.error(err?.response?.data?.message || 'No se pudieron cargar fallas de impuestos')
      }
    })
}
</script>
<style>
.menu{
  background-color: #fff;
  color: #000 !important;
  border-radius: 10px;
  margin: 5px;
  padding: 5px
}
</style>
