<template>
  <q-page class="q-pa-sm rutas-page">
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-2">
          <q-input v-model="fecha" type="date" dense outlined label="Fecha" @update:model-value="loadRutas" />
        </div>
        <div class="col-12 col-md-6">
          <q-input v-model="search" dense outlined label="Buscar cliente/comanda/producto" debounce="250">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-4">
          <q-btn color="primary" icon="search" no-caps class="full-width" label="Buscar" :loading="loading" @click="loadRutas" />
        </div>
      </q-card-section>
      <q-card-section class="row q-col-gutter-sm q-pt-none">
        <div class="col-6 col-md-3"><q-chip color="blue-8" text-color="white">Comandas: {{ stats.total_entregas || 0 }}</q-chip></div>
        <div class="col-6 col-md-3"><q-chip color="indigo-8" text-color="white">Total Bs: {{ Number(stats.monto_total || 0).toFixed(2) }}</q-chip></div>
        <div class="col-6 col-md-3"><q-chip color="green-8" text-color="white">Cobrado Bs: {{ Number(stats.monto_cobrado || 0).toFixed(2) }}</q-chip></div>
        <div class="col-6 col-md-3"><q-chip color="orange-8" text-color="white">Saldo Bs: {{ Number(stats.saldo_total || 0).toFixed(2) }}</q-chip></div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-sm">
      <div ref="mapRef" class="map-container" />
    </q-card>

    <q-card flat bordered>
      <q-table
        dense
        flat
        row-key="cliente_key"
        :rows="rowsClientes"
        :columns="columns"
        :pagination="{ rowsPerPage: 0 }"
        @row-click="(_, row) => openClienteDialog(row)"
      >
        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip dense :color="estadoColor(props.row.despacho_estado)" text-color="white">{{ props.row.despacho_estado }}</q-chip>
          </q-td>
        </template>
        <template #body-cell-total="props"><q-td :props="props">{{ money(props.row.total) }}</q-td></template>
        <template #body-cell-pagado="props"><q-td :props="props">{{ money(props.row.pagado) }}</q-td></template>
        <template #body-cell-saldo="props"><q-td :props="props">{{ money(props.row.saldo) }}</q-td></template>
      </q-table>
    </q-card>

    <q-dialog v-model="dialogCliente" full-width>
      <q-card style="max-width: 1280px; margin: 0 auto;">
        <q-card-section class="row items-center">
          <div class="col">
            <div class="text-subtitle1 text-weight-bold">{{ selectedCliente?.cliente || '-' }}</div>
            <div class="text-caption text-grey-7">{{ selectedCliente?.direccion || '-' }} · {{ selectedCliente?.telefono || '-' }}</div>
          </div>
          <q-btn color="blue-7" icon="map" no-caps label="Abrir mapa" @click="openGoogleMaps(selectedCliente)" />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <q-markup-table dense flat bordered wrap-cells>
            <thead>
              <tr>
                <th>Acciones</th>
                <th>Comanda</th>
                <th>Importe</th>
                <th>Pagado</th>
                <th>Saldo</th>
                <th>Tipo pago</th>
                <th>Estado</th>
                <th>Cobrado</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(p, idx) in dialogPedidos" :key="p.pedido_id">
                <tr>
                  <td style="white-space: nowrap;">
                    <q-btn-dropdown
                      dense
                      color="primary"
                      no-caps
                      label="Opciones"
                      size="sm"
                      :loading="!!actionLoading[p.pedido_id]"
                      :disable="loadingSaveAll || loadingEstado"
                    >
                      <q-list>
                        <q-item clickable v-close-popup @click="toggleDetallePedido(p.pedido_id)">
                          <q-item-section avatar><q-icon :name="expandedPedidos[p.pedido_id] ? 'visibility_off' : 'visibility'" /></q-item-section>
                          <q-item-section>{{ expandedPedidos[p.pedido_id] ? 'Ocultar detalle' : 'Ver detalle' }}</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="cobrarPedido(p)" :disable="!p.venta_id || Number(p.saldo || 0) <= 0 || loadingSaveAll || loadingEstado || !!actionLoading[p.pedido_id]">
                          <q-item-section avatar><q-icon name="payments" /></q-item-section>
                          <q-item-section>Cobrar</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="guardarYSiguiente(p, idx)" :disable="!p.venta_id || Number(p.saldo || 0) <= 0 || loadingSaveAll || loadingEstado || !!actionLoading[p.pedido_id]">
                          <q-item-section avatar><q-icon name="arrow_downward" /></q-item-section>
                          <q-item-section>Siguiente</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="modificarCobro(p)" :disable="!ultimoPagoId(p) || loadingSaveAll || loadingEstado || !!actionLoading[p.pedido_id]">
                          <q-item-section avatar><q-icon name="edit" /></q-item-section>
                          <q-item-section>Modificar cobro</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="imprimirVoucherVenta(p)" :disable="!p.venta_id">
                          <q-item-section avatar><q-icon name="description" /></q-item-section>
                          <q-item-section>Voucher</q-item-section>
                        </q-item>
                        <q-item clickable v-close-popup @click="mandarWhatsappVoucher(p)" :disable="!p.venta_id">
                          <q-item-section avatar><q-icon name="chat" /></q-item-section>
                          <q-item-section>WhatsApp voucher</q-item-section>
                        </q-item>
                      </q-list>
                    </q-btn-dropdown>
                  </td>
                  <td>#{{ p.comanda }}</td>
                  <td>{{ money(p.total) }}</td>
                  <td>{{ money(p.pagado) }}</td>
                  <td>{{ money(p.saldo) }}</td>
                  <td>{{ p.tipo_pago_venta || '-' }}</td>
                  <td><q-chip dense :color="estadoColor(p.despacho_estado)" text-color="white">{{ p.despacho_estado }}</q-chip></td>
                  <td style="width: 140px;">
                    <q-input
                      :ref="(el) => setPagoInputRef(el, idx)"
                      v-model.number="p.pagoMonto"
                      dense
                      outlined
                      type="number"
                      min="0"
                      step="0.01"
                      :disable="loadingSaveAll || loadingEstado || !!actionLoading[p.pedido_id]"
                      @keyup.enter="guardarYSiguiente(p, idx)"
                    />
                  </td>
                </tr>
                <tr v-show="expandedPedidos[p.pedido_id]">
                  <td colspan="8" class="bg-grey-1">
                    <div v-for="(d, i) in (p.productos || [])" :key="`${p.pedido_id}-${i}`" class="q-py-xs">
                      <b>Codigo:</b> {{ d.codigo || '-' }} <b>Producto:</b> {{ d.nombre || '-' }} <b>Cant.:</b> {{ Number(d.cantidad || 0).toFixed(2) }}
                    </div>
                    <div class="q-mt-xs">
                      <div class="text-caption text-grey-7 text-weight-bold">Pagos registrados:</div>
                      <div v-if="!(p.pagos || []).length" class="text-caption text-grey-7">Sin pagos</div>
                      <div v-for="pg in (p.pagos || [])" :key="`pg-${p.pedido_id}-${pg.id}`" class="text-caption">
                        - Pago #{{ pg.id }}: {{ money(pg.monto) }}
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </q-markup-table>
        </q-card-section>
        <q-separator />
        <q-card-actions align="right">
          <q-btn color="primary" no-caps label="Guardar todo y bloquear" :loading="loadingSaveAll" :disable="loadingEstado" @click="guardarTodoYBloquear" />
          <q-btn v-if="mostrarAccionesEstado" color="positive" no-caps label="Entregado" :loading="loadingEstado" :disable="loadingSaveAll" @click="actualizarEstadosCliente('ENTREGADO')" />
          <q-btn v-if="mostrarAccionesEstado" color="amber-8" no-caps label="Volver mas tarde" :loading="loadingEstado" :disable="loadingSaveAll" @click="actualizarEstadosCliente('NO ENTREGADO')" />
          <q-btn v-if="mostrarAccionesEstado" color="negative" no-caps label="Rechazado" :loading="loadingEstado" :disable="loadingSaveAll" @click="actualizarEstadosCliente('RECHAZADO')" />
          <q-btn flat no-caps color="grey-8" label="Cerrar" v-close-popup />
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
const fecha = ref(new Date().toISOString().slice(0, 10))
const search = ref('')
const loading = ref(false)
const rows = ref([])
const stats = ref({})
const dialogCliente = ref(false)
const selectedCliente = ref(null)
const dialogPedidos = ref([])
const expandedPedidos = ref({})
const pagoInputRefs = ref({})
const actionLoading = ref({})
const loadingSaveAll = ref(false)
const loadingEstado = ref(false)

const mapRef = ref(null)
const map = ref(null)
const layer = ref(null)
let searchTimer = null

const columns = [
  { name: 'pedidos_count', label: 'Pedidos', field: 'pedidos_count', align: 'left' },
  { name: 'cliente', label: 'Cliente', field: 'cliente', align: 'left' },
  { name: 'tipo_pago_venta', label: 'Tipo pago', field: 'tipo_pago_venta', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'despacho_estado', align: 'left' },
  { name: 'total', label: 'Total', field: 'total', align: 'right' },
  { name: 'pagado', label: 'Cobrado', field: 'pagado', align: 'right' },
  { name: 'saldo', label: 'Saldo', field: 'saldo', align: 'right' },
]

const estadosFinales = ['ENTREGADO', 'NO ENTREGADO', 'RECHAZADO']
const mostrarAccionesEstado = computed(() => {
  if (!Array.isArray(dialogPedidos.value) || dialogPedidos.value.length === 0) return true
  return dialogPedidos.value.some((p) => !estadosFinales.includes(String(p.despacho_estado || '').toUpperCase()))
})

const rowsClientes = computed(() => {
  const map = new Map()
  rows.value.forEach((r) => {
    const key = r?.cliente_id ? `cli-${r.cliente_id}` : `ped-${r.pedido_id}`
    const base = map.get(key) || {
      cliente_key: key,
      cliente_id: r.cliente_id,
      source_pedido_ids: [],
      cliente: r.cliente,
      telefono: r.telefono,
      direccion: r.direccion,
      latitud: r.latitud,
      longitud: r.longitud,
      pedidos_count: 0,
      total: 0,
      pagado: 0,
      saldo: 0,
      tipo_pago_venta: r.tipo_pago_venta || '',
      despacho_estado: r.despacho_estado || 'PENDIENTE',
    }
    base.pedidos_count += 1
    base.source_pedido_ids.push(r.pedido_id)
    base.total = Number(base.total) + Number(r.total || 0)
    base.pagado = Number(base.pagado) + Number(r.pagado || 0)
    base.saldo = Number(base.saldo) + Number(r.saldo || 0)
    if (String(base.tipo_pago_venta || '').toUpperCase().includes('CRED') || String(r.tipo_pago_venta || '').toUpperCase().includes('CRED')) {
      base.tipo_pago_venta = 'MIXTO/CREDITO'
    }
    base.despacho_estado = mergeEstado(base.despacho_estado, r.despacho_estado)
    map.set(key, base)
  })
  return Array.from(map.values()).map((x) => ({
    ...x,
    total: Number(x.total.toFixed(2)),
    pagado: Number(x.pagado.toFixed(2)),
    saldo: Number(x.saldo.toFixed(2)),
  }))
})

function mergeEstado(a, b) {
  const rank = {
    'PENDIENTE': 0,
    'PARCIAL': 1,
    'NO ENTREGADO': 2,
    'RECHAZADO': 3,
    'ENTREGADO': 4,
  }
  const ea = String(a || 'PENDIENTE').toUpperCase()
  const eb = String(b || 'PENDIENTE').toUpperCase()
  return (rank[eb] ?? 0) > (rank[ea] ?? 0) ? eb : ea
}

function money(v) {
  const n = Number(v || 0)
  return `Bs ${n.toFixed(2)}`
}

function estadoColor(estado) {
  const e = String(estado || 'PENDIENTE').toUpperCase()
  if (e === 'ENTREGADO') return 'green-7'
  if (e === 'PARCIAL') return 'orange-7'
  if (e === 'NO ENTREGADO') return 'amber-8'
  if (e === 'RECHAZADO') return 'negative'
  return 'blue-7'
}

function initMap() {
  if (map.value || !mapRef.value) return
  map.value = L.map(mapRef.value, { center: [-17.969721, -67.114493], zoom: 12 })
  const googleRoad = L.tileLayer('https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data © Google' })
  const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data © Google' })
  const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data © Google' })
  googleRoad.addTo(map.value)
  L.control.layers({ 'Google Calle': googleRoad, 'Google Satelite': googleSat, 'Google Hibrido': googleHybrid }, {}, { collapsed: true }).addTo(map.value)
  layer.value = L.layerGroup().addTo(map.value)
}

function renderMap() {
  if (!layer.value) return
  layer.value.clearLayers()
  const bounds = []
  const byClient = new Map()
  rows.value.forEach((r) => {
    if (!r.cliente_id || !Number.isFinite(Number(r.latitud)) || !Number.isFinite(Number(r.longitud))) return
    if (!byClient.has(r.cliente_id)) byClient.set(r.cliente_id, r)
  })
  byClient.forEach((r) => {
    const lat = Number(r.latitud)
    const lng = Number(r.longitud)
    const mk = L.marker([lat, lng]).addTo(layer.value)
    mk.bindTooltip(`${r.cliente || ''}`, { sticky: true })
    mk.on('click', () => openClienteDialog(r))
    bounds.push([lat, lng])
  })
  if (bounds.length) map.value.fitBounds(bounds, { padding: [25, 25], maxZoom: 15 })
}

async function loadRutas() {
  loading.value = true
  try {
    const res = await proxy.$axios.get('/despachador/rutas', { params: { fecha: fecha.value, search: search.value || undefined } })
    rows.value = Array.isArray(res.data?.data)
      ? res.data.data.map((r) => ({ ...r, pagoMonto: initialCobroMonto(r) }))
      : []
    stats.value = res.data?.stats || {}
    renderMap()
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo cargar rutas')
  } finally {
    loading.value = false
  }
}

function openClienteDialog(row) {
  selectedCliente.value = row
  const ids = Array.isArray(row?.source_pedido_ids) ? row.source_pedido_ids : []
  dialogPedidos.value = rows.value
    .filter((x) => {
      if (ids.length) return ids.includes(x.pedido_id)
      return String(x.cliente_id) === String(row.cliente_id)
    })
    .map((x) => ({ ...x, pagoMonto: initialCobroMonto(x) }))
  expandedPedidos.value = {}
  pagoInputRefs.value = {}
  dialogCliente.value = true
}

function toggleDetallePedido(pedidoId) {
  expandedPedidos.value[pedidoId] = !expandedPedidos.value[pedidoId]
}

function openGoogleMaps(row) {
  if (!row) return
  const lat = Number(row.latitud)
  const lng = Number(row.longitud)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    proxy.$alert.error('El cliente no tiene ubicacion registrada')
    return
  }
  window.open(`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`, '_blank')
}

function getCurrentLocation() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) return resolve(null)
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ latitud: pos.coords.latitude, longitud: pos.coords.longitude }),
      () => resolve(null),
      { enableHighAccuracy: true, timeout: 5000, maximumAge: 30000 }
    )
  })
}

async function cobrarPedido(p, reload = true) {
  const pedidoKey = p?.pedido_id
  actionLoading.value[pedidoKey] = true
  try {
    const monto = Number(Number(p.pagoMonto || 0).toFixed(2))
    if (!Number.isFinite(monto) || monto <= 0) {
      proxy.$alert.error('Monto invalido')
      return false
    }
    const pagoId = ultimoPagoId(p)
    if (pagoId) {
      await proxy.$axios.put(`/despachador/pagos/${pagoId}`, { monto })
    } else {
      const geo = await getCurrentLocation()
      const tipo = String((p.tipo_pago_venta || 'CONTADO')).toUpperCase().includes('CRED') ? 'CREDITO' : 'CONTADO'
      await proxy.$axios.post('/despachador/pagos', {
        venta_id: p.venta_id,
        monto,
        tipo_pago: tipo,
        metodo_pago: 'EFECTIVO',
        observacion: null,
        latitud: geo?.latitud,
        longitud: geo?.longitud,
      })
    }
    if (reload) {
      proxy.$alert.success('Cobro guardado')
      await loadRutas()
      if (selectedCliente.value) {
        const pick = rows.value.find((x) => String(x.cliente_id) === String(selectedCliente.value.cliente_id))
        if (pick) openClienteDialog(pick)
      }
    }
    return true
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo registrar cobro')
    return false
  } finally {
    actionLoading.value[pedidoKey] = false
  }
}

function ultimoPagoId(p) {
  const pagos = Array.isArray(p?.pagos) ? p.pagos : []
  if (!pagos.length) return null
  return pagos[pagos.length - 1]?.id || null
}

function ultimoPagoMonto(p) {
  const pagos = Array.isArray(p?.pagos) ? p.pagos : []
  if (!pagos.length) return 0
  return Number(Number(pagos[pagos.length - 1]?.monto || 0).toFixed(2))
}

function initialCobroMonto(row) {
  const ultimo = ultimoPagoMonto(row)
  if (ultimo > 0) return ultimo
  const pagado = Number(Number(row?.pagado || 0).toFixed(2))
  if (pagado > 0) return pagado
  return Number(Number(row?.saldo || 0).toFixed(2))
}

async function modificarCobro(p) {
  const pagoId = ultimoPagoId(p)
  if (!pagoId) {
    proxy.$alert.error('No hay pago previo para modificar')
    return
  }
  const montoActual = ultimoPagoMonto(p)
  const result = await new Promise((resolve) => {
    proxy.$q.dialog({
      title: 'Modificar cobro',
      message: `Comanda #${p.comanda}`,
      prompt: {
        model: String(montoActual),
        type: 'number',
      },
      cancel: true,
      persistent: true,
    }).onOk((val) => resolve(val)).onCancel(() => resolve(null))
  })

  if (result === null) return
  const monto = Number(result)
  if (!Number.isFinite(monto) || monto <= 0) {
    proxy.$alert.error('Monto invalido')
    return
  }

  try {
    await proxy.$axios.put(`/despachador/pagos/${pagoId}`, { monto: Number(monto.toFixed(2)) })
    proxy.$alert.success('Cobro modificado')
    await loadRutas()
    if (selectedCliente.value) {
      const pick = rows.value.find((x) => String(x.cliente_id) === String(selectedCliente.value.cliente_id))
      if (pick) openClienteDialog(pick)
    }
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo modificar el cobro')
  }
}

function setPagoInputRef(el, idx) {
  if (!el) return
  pagoInputRefs.value[idx] = el
}

async function guardarYSiguiente(p, idx) {
  const ok = await cobrarPedido(p, true)
  if (!ok) return
  const next = pagoInputRefs.value[idx + 1]
  if (next && typeof next.focus === 'function') next.focus()
}

async function guardarTodoYBloquear() {
  loadingSaveAll.value = true
  try {
    for (const p of dialogPedidos.value) {
      const montoEscrito = Number(Number(p.pagoMonto || 0).toFixed(2))
      const yaTienePago = !!ultimoPagoId(p)
      if (montoEscrito <= 0 && yaTienePago) continue
      if (montoEscrito <= 0 && !yaTienePago) continue
      const ok = await cobrarPedido(p, false)
      if (!ok) return
    }
    await loadRutas()
    if (selectedCliente.value) {
      const pick = rows.value.find((x) => String(x.cliente_id) === String(selectedCliente.value.cliente_id))
      if (pick) {
        openClienteDialog(pick)
        for (const p of dialogPedidos.value) {
          const saldo = Number(Number(p.saldo || 0).toFixed(2))
          p.pagoMonto = saldo > 0 ? initialCobroMonto(p) : 0
        }
      }
    }
    await actualizarEstadosCliente('ENTREGADO')
  } finally {
    loadingSaveAll.value = false
  }
}

async function actualizarEstadosCliente(estado) {
  loadingEstado.value = true
  try {
    await Promise.all(dialogPedidos.value.map((p) => proxy.$axios.put(`/despachador/pedidos/${p.pedido_id}/estado`, { estado })))
    proxy.$alert.success('Estado actualizado')
    await loadRutas()
    dialogCliente.value = false
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo actualizar estado')
  } finally {
    loadingEstado.value = false
  }
}

async function imprimirVoucherVenta(p) {
  try {
    const res = await proxy.$axios.get(`/despachador/reportes/vouchers/${p.venta_id}`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const link = document.createElement('a')
    const fileUrl = window.URL.createObjectURL(blob)
    link.href = fileUrl
    link.download = `voucher_venta_${p.venta_id}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(fileUrl)
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo generar el voucher')
  }
}

async function mandarWhatsappVoucher(p) {
  try {
    const res = await proxy.$axios.get(`/despachador/reportes/vouchers/${p.venta_id}`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const fileName = `voucher_venta_${p.venta_id}.pdf`
    const file = new File([blob], fileName, { type: 'application/pdf' })
    if (navigator.canShare && navigator.canShare({ files: [file] })) {
      await navigator.share({ files: [file], title: fileName })
      return
    }
    const link = document.createElement('a')
    const fileUrl = window.URL.createObjectURL(blob)
    link.href = fileUrl
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(fileUrl)
    proxy.$alert.error('Tu navegador no permite compartir PDF directo. Se descargo el voucher.')
  } catch (e) {
    proxy.$alert.error(e?.response?.data?.message || 'No se pudo preparar el voucher')
  }
}

onMounted(() => {
  initMap()
  loadRutas()
})

watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadRutas(), 350)
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
})
</script>

<style scoped>
.rutas-page { background: #f4f7fc; }
.map-container { height: 46vh; min-height: 360px; }
</style>
