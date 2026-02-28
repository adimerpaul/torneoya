<template>
  <q-page class="q-pa-xs mapa-page">
    <q-card flat bordered class="q-mb-xs">
      <q-card-section class="q-pa-sm row q-col-gutter-sm items-center">
        <div class="col-12 col-md-2">
          <q-input v-model="fecha" dense outlined type="date" label="Fecha" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="info" icon="search" label="Consultar" no-caps class="full-width" :loading="loading" @click="loadData" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="green" icon="local_shipping" label="Asignar" no-caps class="full-width" :disable="selectedRows.length === 0" @click="openAsignar" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn-dropdown color="primary" icon="print" label="Reportes" no-caps class="full-width">
            <q-list>
              <q-item clickable v-close-popup @click="exportarReportePedidos">
                <q-item-section avatar><q-icon name="description" /></q-item-section>
                <q-item-section>Reporte pedidos</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="dialogReporteZona = true">
                <q-item-section avatar><q-icon name="local_shipping" /></q-item-section>
                <q-item-section>Reporte por zona/vehiculo</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="exportarReporteProductosTotales">
                <q-item-section avatar><q-icon name="inventory_2" /></q-item-section>
                <q-item-section>Productos totales</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </div>
        <div class="col-12 col-md-3">
          <q-select v-model="vendedorId" :options="vendedoresOptions" dense outlined emit-value map-options label="Vendedor" />
        </div>
        <div class="col-12 col-md-1">
          <q-select v-model="tipo" :options="tipoOptions" dense outlined emit-value map-options label="Tipo" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="row q-col-gutter-xs q-pa-sm">
        <div class="col-6 col-md-2">
          <q-chip square color="blue-8" text-color="white" icon="groups" class="full-width justify-center">
            Clientes: {{ stats.total_clientes || 0 }}
          </q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="indigo-8" text-color="white" icon="payments" class="full-width justify-center">
            Bs: {{ Number(stats.monto_total || 0).toFixed(2) }}
          </q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="orange-7" text-color="white" icon="egg" class="full-width justify-center">
            Pollo: {{ stats.tipo_pollo || 0 }}
          </q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="red-7" text-color="white" icon="set_meal" class="full-width justify-center">
            Res: {{ stats.tipo_res || 0 }}
          </q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="brown-7" text-color="white" icon="restaurant" class="full-width justify-center">
            Cerdo: {{ stats.tipo_cerdo || 0 }}
          </q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="blue-grey-7" text-color="white" icon="inventory_2" class="full-width justify-center">
            Normal: {{ stats.tipo_normal || 0 }}
          </q-chip>
        </div>
      </q-card-section>

      <q-card-section class="row q-col-gutter-xs q-pa-sm">
        <div class="col-12 col-md-2" v-for="cam in statsCamiones" :key="cam.nombre">
          <q-chip square color="grey-8" text-color="white" class="full-width justify-center">
            {{ cam.nombre }} : {{ cam.cantidad }}
          </q-chip>
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-xs">
      <div ref="mapRef" class="map-container" />
    </q-card>

    <q-card flat bordered>
      <q-card-section class="row q-col-gutter-sm items-center q-py-sm">
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined label="Buscar cliente/zona/vendedor" debounce="350" @update:model-value="loadData">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-3">
          <q-select
            v-model="zonaFiltroTabla"
            :options="zonaFiltroOptions"
            dense
            outlined
            emit-value
            map-options
            label="Zona (tabla)"
          />
        </div>
        <div class="col-12 col-md-3">
          <q-select
            v-model="vendedorFiltroTabla"
            :options="vendedorFiltroTablaOptions"
            dense
            outlined
            emit-value
            map-options
            label="Vendedor (tabla)"
          />
        </div>
        <div class="col-12 col-md-2">
          <q-chip color="teal" text-color="white">Seleccionados: {{ selectedRows.length }}</q-chip>
        </div>
      </q-card-section>

      <q-table
        dense
        flat
        bordered
        row-key="id"
        :rows="rowsFiltradasTabla"
        :columns="columns"
        v-model:selected="selectedRows"
        selection="multiple"
        :rows-per-page-options="[0]"
      >
        <template #body="props">
          <q-tr :props="props" :class="rowClass(props.row)">
            <q-td auto-width>
              <q-checkbox v-model="props.selected" dense />
            </q-td>
            <q-td key="op" :props="props">
              <q-btn dense flat color="primary" icon="my_location" @click.stop="focusRow(props.row)" />
            </q-td>
            <q-td key="num" :props="props">{{ props.row.num }}</q-td>
            <q-td key="cliente_codcli" :props="props">{{ props.row.cliente_codcli || '-' }}</q-td>
            <q-td key="cliente_nombre" :props="props">{{ props.row.cliente_nombre || '-' }}</q-td>
            <q-td key="direccion" :props="props">{{ props.row.direccion || '-' }}</q-td>
            <q-td key="importe" :props="props">{{ Number(props.row.importe || 0).toFixed(2) }}</q-td>
            <q-td key="vendedor" :props="props">{{ props.row.vendedor || '-' }}</q-td>
            <q-td key="zona" :props="props">
              <q-chip
                dense
                :style="{ backgroundColor: props.row.zona_color || '#9e9e9e', color: textColor(props.row.zona_color || '#9e9e9e') }"
              >
                {{ props.row.zona || 'SIN ZONA' }}
              </q-chip>
            </q-td>
            <q-td key="usuario_camion" :props="props">{{ props.row.usuario_camion || '-' }}</q-td>
            <q-td key="en_ruta" :props="props">
              <q-chip dense :color="props.row.usuario_camion ? 'green-7' : 'red-7'" text-color="white">
                {{ props.row.usuario_camion ? 'EN RUTA' : 'SIN RUTA' }}
              </q-chip>
            </q-td>
          </q-tr>
        </template>
        <template #body-cell-zona="props">
          <q-td :props="props">
            <q-chip
              dense
              :style="{ backgroundColor: props.row.zona_color || '#9e9e9e', color: textColor(props.row.zona_color || '#9e9e9e') }"
            >
              {{ props.row.zona || 'SIN ZONA' }}
            </q-chip>
          </q-td>
        </template>
        <template #body-cell-en_ruta="props">
          <q-td :props="props">
            <q-chip dense :color="props.row.usuario_camion ? 'green-7' : 'red-7'" text-color="white">
              {{ props.row.usuario_camion ? 'EN RUTA' : 'SIN RUTA' }}
            </q-chip>
          </q-td>
        </template>
        <template #body-cell-op="props">
          <q-td :props="props">
            <q-btn dense flat color="primary" icon="my_location" @click="focusRow(props.row)" />
          </q-td>
        </template>
      </q-table>

      <q-separator />
      <q-card-section class="row q-col-gutter-sm items-center q-py-sm">
        <div class="col-12 col-md-2">
          <q-btn
            color="green"
            icon="local_shipping"
            label="Asignar"
            no-caps
            class="full-width"
            :disable="selectedRows.length === 0"
            @click="openAsignar"
          />
        </div>
        <div class="col-12 col-md-3">
          <q-btn-dropdown color="primary" icon="print" label="Reportes" no-caps class="full-width">
            <q-list>
              <q-item clickable v-close-popup @click="exportarReportePedidos">
                <q-item-section avatar><q-icon name="description" /></q-item-section>
                <q-item-section>Reporte pedidos</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="dialogReporteZona = true">
                <q-item-section avatar><q-icon name="local_shipping" /></q-item-section>
                <q-item-section>Reporte por zona/vehiculo</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="exportarReporteProductosTotales">
                <q-item-section avatar><q-icon name="inventory_2" /></q-item-section>
                <q-item-section>Productos totales</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dialogAsignar" persistent>
      <q-card style="width: 420px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Asignar camion / zona</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="asignarSeleccion">
            <q-select
              v-model="asignacion.usuario_camion_id"
              :options="camionesOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              dense
              outlined
              label="Camion"
              :rules="[v => !!v || 'Campo requerido']"
            />
            <q-select
              class="q-mt-sm"
              v-model="asignacion.pedido_zona_id"
              :options="zonasOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              dense
              outlined
              label="Zona / color"
              :rules="[v => !!v || 'Campo requerido']"
            >
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <div class="zona-option" :style="{ backgroundColor: scope.opt.color, color: textColor(scope.opt.color) }">
                      {{ scope.opt.label }}
                    </div>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-card-actions align="right" class="q-px-none q-pt-md">
              <q-btn flat no-caps color="grey-8" label="Cancelar" v-close-popup />
              <q-btn color="primary" no-caps label="Asignar" type="submit" :loading="assigning" />
            </q-card-actions>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogReporteZona">
      <q-card style="width: 380px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Reporte por vehiculo</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-select
            v-model="reporteZonaCamionId"
            :options="camionesOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            dense
            outlined
            label="Camion"
            :rules="[v => !!v || 'Campo requerido']"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat color="grey-8" no-caps label="Cancelar" v-close-popup />
          <q-btn color="primary" no-caps label="Generar" :loading="loadingReport" @click="exportarReporteZonaVehiculo" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const { proxy } = getCurrentInstance()

const mapRef = ref(null)
const map = ref(null)
const markersLayer = ref(null)

const fecha = ref(new Date().toISOString().slice(0, 10))
const vendedorId = ref(null)
const tipo = ref('TODOS')
const search = ref('')
const loading = ref(false)
const assigning = ref(false)
const loadingReport = ref(false)
const dialogAsignar = ref(false)
const dialogReporteZona = ref(false)
const zonaFiltroTabla = ref('TODAS')
const vendedorFiltroTabla = ref('TODOS')
const reporteZonaCamionId = ref(null)

const rows = ref([])
const selectedRows = ref([])
const stats = ref({})
const vendedores = ref([])
const camiones = ref([])
const zonas = ref([])

const asignacion = ref({
  usuario_camion_id: null,
  pedido_zona_id: null,
})

const tipoOptions = [
  { label: 'Todos', value: 'TODOS' },
  { label: 'Normal', value: 'NORMAL' },
  { label: 'Pollo', value: 'POLLO' },
  { label: 'Res', value: 'RES' },
  { label: 'Cerdo', value: 'CERDO' },
]

const columns = [
  { name: 'op', label: 'Op', field: 'op', align: 'left' },
  { name: 'num', label: '#', field: 'num', align: 'center' },
  { name: 'cliente_codcli', label: 'CINIT', field: 'cliente_codcli', align: 'left' },
  { name: 'cliente_nombre', label: 'Cliente', field: 'cliente_nombre', align: 'left' },
  { name: 'direccion', label: 'Direccion', field: 'direccion', align: 'left' },
  { name: 'importe', label: 'Importe', field: row => Number(row.importe || 0).toFixed(2), align: 'right' },
  { name: 'vendedor', label: 'Vendedor', field: 'vendedor', align: 'left' },
  { name: 'zona', label: 'Zona', field: 'zona', align: 'left' },
  { name: 'usuario_camion', label: 'Camion', field: row => row.usuario_camion || '-', align: 'left' },
  { name: 'en_ruta', label: 'Ruta', field: 'en_ruta', align: 'left' },
]

const vendedoresOptions = computed(() => [
  { label: 'Todos', value: null },
  ...vendedores.value.map(v => ({ label: v.name, value: v.id })),
])

const camionesOptions = computed(() => camiones.value.map(c => ({
  label: `${c.name}${c.placa ? ` (${c.placa})` : ''}`,
  value: c.id,
})))

const zonasOptions = computed(() => zonas.value.map(z => ({
  label: z.nombre,
  value: z.id,
  color: z.color,
})))

const statsCamiones = computed(() => Array.isArray(stats.value?.camiones) ? stats.value.camiones : [])
const zonaFiltroOptions = computed(() => {
  const set = new Set((rows.value || []).map(r => r.zona || 'SIN ZONA'))
  return [
    { label: 'Todas', value: 'TODAS' },
    ...Array.from(set).sort().map(z => ({ label: z, value: z })),
  ]
})

const vendedorFiltroTablaOptions = computed(() => {
  const set = new Set((rows.value || []).map(r => r.vendedor || 'SIN VENDEDOR'))
  return [
    { label: 'Todos', value: 'TODOS' },
    ...Array.from(set).sort().map(v => ({ label: v, value: v })),
  ]
})

const rowsFiltradasTabla = computed(() => {
  return rows.value.filter((r) => {
    const okZona = zonaFiltroTabla.value === 'TODAS' || (r.zona || 'SIN ZONA') === zonaFiltroTabla.value
    const okVendedor = vendedorFiltroTabla.value === 'TODOS' || (r.vendedor || 'SIN VENDEDOR') === vendedorFiltroTabla.value
    return okZona && okVendedor
  })
})

function textColor (bg) {
  const hex = String(bg || '').replace('#', '')
  if (hex.length !== 6) return '#ffffff'
  const r = parseInt(hex.slice(0, 2), 16)
  const g = parseInt(hex.slice(2, 4), 16)
  const b = parseInt(hex.slice(4, 6), 16)
  const yiq = (r * 299 + g * 587 + b * 114) / 1000
  return yiq >= 140 ? '#111827' : '#ffffff'
}

function rowClass (row) {
  return row?.usuario_camion ? 'row-en-ruta' : 'row-sin-ruta'
}

function initMap () {
  if (!mapRef.value || map.value) return
  map.value = L.map(mapRef.value, { center: [-17.969721, -67.114493], zoom: 12 })
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map.value)
  markersLayer.value = L.layerGroup().addTo(map.value)
}

function markerIcon (row) {
  const color = row.zona_color || '#607d8b'
  return L.divIcon({
    className: '',
    html: `
      <div style="
        width:26px;height:26px;border-radius:13px;
        background:${color};color:${textColor(color)};
        border:2px solid #fff;display:flex;align-items:center;justify-content:center;
        font-size:11px;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,.35);
      ">${row.num}</div>
    `,
    iconSize: [26, 26],
    iconAnchor: [13, 13],
  })
}

function tooltipHtml (row) {
  const estadoRuta = row.usuario_camion ? 'EN RUTA' : 'SIN RUTA'
  return `
    <div style="font-size:12px;line-height:1.25;">
      <div style="font-weight:700;">${row.cliente_nombre || '-'}</div>
      <div><b>${estadoRuta}</b></div>
      <div><b>Territorio:</b> ${row.territorio || '-'}</div>
      <div><b>Importe:</b> ${Number(row.importe || 0).toFixed(2)} Bs</div>
      <div><b>Vendedor:</b> ${row.vendedor || '-'}</div>
      <div><b>Direccion:</b> ${row.direccion || '-'}</div>
      <div><b>Camion:</b> ${row.usuario_camion || 'Sin asignar'}</div>
    </div>
  `
}

function renderMarkers () {
  if (!markersLayer.value || !map.value) return
  markersLayer.value.clearLayers()
  const bounds = []

  rowsFiltradasTabla.value.forEach((row) => {
    const lat = Number(row.latitud)
    const lng = Number(row.longitud)
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return

    const marker = L.marker([lat, lng], { icon: markerIcon(row) }).addTo(markersLayer.value)
    marker.bindTooltip(tooltipHtml(row), { sticky: true, direction: 'top' })
    marker.on('click', () => {
      const already = selectedRows.value.some(r => r.id === row.id)
      if (already) {
        selectedRows.value = selectedRows.value.filter(r => r.id !== row.id)
      } else {
        selectedRows.value = [...selectedRows.value, row]
      }
      marker.openTooltip()
    })
    bounds.push([lat, lng])
  })

  if (bounds.length > 0) {
    map.value.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 })
  }
}

function focusRow (row) {
  const lat = Number(row.latitud)
  const lng = Number(row.longitud)
  if (!Number.isFinite(lat) || !Number.isFinite(lng) || !map.value) return
  map.value.flyTo([lat, lng], 16)
}

async function loadData () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('/mapa-clientes', {
      params: {
        fecha: fecha.value,
        vendedor_id: vendedorId.value,
        tipo: tipo.value,
        search: search.value,
      },
    })
    rows.value = Array.isArray(res.data?.data) ? res.data.data : []
    zonaFiltroTabla.value = 'TODAS'
    vendedorFiltroTabla.value = 'TODOS'
    vendedores.value = Array.isArray(res.data?.vendedores) ? res.data.vendedores : []
    camiones.value = Array.isArray(res.data?.camiones) ? res.data.camiones : []
    zonas.value = Array.isArray(res.data?.zonas) ? res.data.zonas : []
    stats.value = res.data?.stats || {}
    selectedRows.value = []
    renderMarkers()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo cargar mapa cliente')
  } finally {
    loading.value = false
  }
}

function openAsignar () {
  if (selectedRows.value.length === 0) return
  asignacion.value = { usuario_camion_id: null, pedido_zona_id: null }
  dialogAsignar.value = true
}

async function asignarSeleccion () {
  const pedidoIds = Array.from(new Set(selectedRows.value.flatMap(r => r.pedido_ids || [])))
  if (pedidoIds.length === 0) {
    proxy.$alert.error('No hay pedidos para asignar')
    return
  }

  assigning.value = true
  try {
    await proxy.$axios.post('/mapa-clientes/asignar', {
      pedido_ids: pedidoIds,
      usuario_camion_id: asignacion.value.usuario_camion_id,
      pedido_zona_id: asignacion.value.pedido_zona_id,
    })
    dialogAsignar.value = false
    proxy.$alert.success('Asignacion aplicada')
    await loadData()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo asignar')
  } finally {
    assigning.value = false
  }
}

function selectedZonaId () {
  if (zonaFiltroTabla.value === 'TODAS') return null
  const zone = zonas.value.find(z => z.nombre === zonaFiltroTabla.value)
  return zone?.id || null
}

function baseReportParams () {
  return {
    fecha: fecha.value,
    vendedor_id: vendedorId.value,
    tipo: tipo.value,
    pedido_zona_id: selectedZonaId(),
  }
}

async function descargarPdf (url, params, defaultFileName) {
  loadingReport.value = true
  try {
    const res = await proxy.$axios.get(url, {
      params,
      responseType: 'blob',
    })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const disposition = res?.headers?.['content-disposition'] || ''
    const match = disposition.match(/filename="?([^"]+)"?/)
    const fileName = match?.[1] || defaultFileName

    const fileUrl = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = fileUrl
    a.download = fileName
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(fileUrl)
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo generar reporte')
  } finally {
    loadingReport.value = false
  }
}

async function exportarReportePedidos () {
  await descargarPdf(
    '/mapa-clientes/reportes/pedidos',
    baseReportParams(),
    `reporte_pedidos_${fecha.value}.pdf`,
  )
}

async function exportarReporteZonaVehiculo () {
  if (!reporteZonaCamionId.value) {
    proxy.$alert.error('Debes seleccionar un camion')
    return
  }
  await descargarPdf(
    '/mapa-clientes/reportes/zona-vehiculo',
    {
      ...baseReportParams(),
      usuario_camion_id: reporteZonaCamionId.value,
    },
    `reporte_zona_vehiculo_${fecha.value}.pdf`,
  )
  dialogReporteZona.value = false
}

async function exportarReporteProductosTotales () {
  await descargarPdf(
    '/mapa-clientes/reportes/productos-totales',
    baseReportParams(),
    `reporte_productos_totales_${fecha.value}.pdf`,
  )
}

onMounted(() => {
  initMap()
  loadData()
})

watch([zonaFiltroTabla, vendedorFiltroTabla], () => {
  selectedRows.value = selectedRows.value.filter((s) => rowsFiltradasTabla.value.some((r) => r.id === s.id))
  renderMarkers()
})

onBeforeUnmount(() => {
  if (map.value) {
    map.value.remove()
    map.value = null
  }
})
</script>

<style scoped>
.mapa-page {
  background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 30%, #ffffff 100%);
}

.map-container {
  height: 52vh;
  min-height: 430px;
}

.zona-option {
  padding: 4px 10px;
  border-radius: 8px;
  font-weight: 600;
}

:deep(.row-en-ruta) {
  background: #e8f5e9;
}

:deep(.row-sin-ruta) {
  background: #fff;
}
</style>
