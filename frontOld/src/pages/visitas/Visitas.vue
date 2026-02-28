<template>
  <q-page class="q-pa-xs">
    <q-card flat bordered>
      <div ref="mapRef" class="visitas-map" />

      <div class="map-toolbar-left">
        <q-btn
          color="orange"
          icon="format_list_bulleted"
          :outline="!showAllDays"
          @click="setDayFilter(false)"
          no-caps
          label="Del dia"
        />
        <q-btn
          color="primary"
          icon="calendar_month"
          :outline="showAllDays"
          @click="setDayFilter(true)"
          no-caps
          label="Todos"
          class="q-ml-xs"
        />
      </div>

      <div class="map-toolbar-right">
        <q-btn color="negative" icon="my_location" @click="locateMe" round dense />
      </div>
    </q-card>

    <q-card flat bordered class="q-mt-xs">
      <q-card-section class="row items-center q-col-gutter-sm q-py-sm">
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined label="Buscar cliente" debounce="350" @update:model-value="cargarClientes">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-auto">
          <q-chip color="teal" text-color="white">Clientes: {{ clientes.length }}</q-chip>
        </div>
        <div class="col-12 col-md-auto">
          <q-chip color="primary" text-color="white">Dia: {{ dayLabel }}</q-chip>
        </div>
      </q-card-section>

      <q-markup-table dense flat wrap-cells>
        <thead>
        <tr>
          <th>Accion</th>
          <th>Cliente</th>
          <th>Direccion</th>
          <th>Telefono</th>
          <th>Estado</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="c in clientesOrdenados" :key="c.id" :class="[selectedCliente?.id === c.id ? 'row-selected' : '', rowClassByCliente(c)]" @click="openAcciones(c)">
          <td>
            <q-btn color="purple" icon="visibility" dense round @click="openAcciones(c)" />
          </td>
          <td>
            {{ c.codcli }} -{{ c.nombre }}
          </td>
          <td>{{ c.direccion || '-' }}</td>
          <td>{{ c.telefono || '-' }}</td>
          <td>
            <q-chip dense :color="statusColor(clienteStatus(c.id))" text-color="white">
              {{ clienteStatus(c.id) }}
            </q-chip>
          </td>
        </tr>
        </tbody>
      </q-markup-table>
    </q-card>

    <q-dialog v-model="dialogAcciones">
      <q-card style="min-width: 900px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ selectedCliente?.codcli || selectedCliente?.id }} {{ selectedCliente?.nombre }}</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <div><b>Cel:</b> {{ selectedCliente?.telefono || '-' }}</div>
              <div><b>Direccion:</b> {{ selectedCliente?.direccion || '-' }}</div>
              <div class="q-mt-sm">
                Estado para pedidos:
                <q-chip dense :color="statusColor(clienteStatus(selectedCliente?.id))" text-color="white">
                  {{ clienteStatus(selectedCliente?.id) }}
                </q-chip>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="comentario" label="Comentario" outlined dense type="textarea" autogrow />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="stretch" class="row q-col-gutter-sm q-px-md q-pb-md">
          <div class="col-12 col-md-6">
            <q-btn color="green" icon="shopping_cart" no-caps class="full-width" label="Realizar pedido" @click="accionSeleccionada('REALIZAR_PEDIDO')" :loading="loadingAccion === 'REALIZAR_PEDIDO'" :disable="Boolean(loadingAccion)" />
          </div>
          <div class="col-12 col-md-6">
            <q-btn color="warning" icon="history" no-caps class="full-width" label="Retornar" @click="accionSeleccionada('RETORNAR')" :loading="loadingAccion === 'RETORNAR'" :disable="Boolean(loadingAccion)" />
          </div>
          <div class="col-12 col-md-6">
            <q-btn color="negative" icon="close" no-caps class="full-width" label="No pedido" @click="accionSeleccionada('NO_PEDIDO')" :loading="loadingAccion === 'NO_PEDIDO'" :disable="Boolean(loadingAccion)" />
          </div>
          <div class="col-12 col-md-6">
            <q-btn color="purple" icon="map" no-caps class="full-width" label="Generar ruta" @click="accionSeleccionada('GENERAR_RUTA')" :loading="loadingAccion === 'GENERAR_RUTA'" :disable="Boolean(loadingAccion)" />
          </div>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogPedido" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ selectedCliente?.codcli || selectedCliente?.id }} {{ selectedCliente?.nombre }}</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="dialogPedido = false" />
        </q-card-section>

        <q-card-section class="q-pt-sm">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-4">
              <q-option-group
                v-model="tipoPago"
                :options="tiposPago"
                type="radio"
                color="primary"
                inline
              />
            </div>
            <div class="col-12 col-md-2">
              <q-toggle v-model="facturadoPedido" label="Facturado" />
            </div>
            <div class="col-12 col-md-3">
              <q-input v-model="fechaPedido" type="date" label="Fecha" dense outlined />
            </div>
            <div class="col-12 col-md-3">
              <q-select
                v-model="horaPedido"
                :options="horariosPedido"
                label="Horario"
                dense
                outlined
                emit-value
                map-options
                clearable
              />
            </div>
            <div class="col-12 col-md-12">
              <q-input v-model="comentario" label="Comentario" dense outlined />
            </div>
            <div class="col-12 col-md-10">
              <q-select
                v-model="productoSeleccionado"
                :options="productos"
                option-label="label"
                option-value="id"
                emit-value
                map-options
                dense
                outlined
                label="Productos (solo stock de compras)"
                use-input
                input-debounce="350"
                @filter="filtrarProductos"
              >
                <template #selected-item="scope">
                  <div class="row items-center no-wrap q-gutter-xs">
                    <q-avatar rounded size="24px">
                      <q-img :src="productImageUrl(scope?.opt?.imagen)" />
                    </q-avatar>
                    <span class="ellipsis">{{ scope?.opt?.label || '' }}</span>
                  </div>
                </template>
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section avatar>
                      <q-avatar rounded size="28px">
                        <q-img :src="productImageUrl(scope.opt.imagen)" />
                      </q-avatar>
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>{{ scope.opt.label }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
            <div class="col-12 col-md-2">
              <q-btn color="negative" icon="add" class="full-width" @click="agregarProducto" />
            </div>
          </div>

          <q-markup-table dense flat bordered class="q-mt-sm">
            <thead>
            <tr>
              <th>Detalle</th>
              <th>Subtotal</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Cod</th>
              <th>Nombre</th>
              <th>Obs</th>
<!--              <th></th>-->
            </tr>
            </thead>
            <tbody>
            <tr v-for="(p, index) in pedidoItems" :key="`${p.producto_id}-${index}`">
              <td>
<!--                <q-btn flat dense round icon="tune" color="purple" @click="openDetalleDialog(p, index)" />-->
                <q-btn-dropdown dense :label="'Op(' + p.tipo + ')'" :color="normalizeTipoProducto(p.tipo) === 'RES' ? 'red' : (normalizeTipoProducto(p.tipo) === 'CERDO' ? 'brown' : (normalizeTipoProducto(p.tipo) === 'POLLO' ? 'orange' : 'primary'))" no-caps size="10px">
                  <q-list>
                    <q-item clickable v-ripple @click="openDetalleDialog(p, index)" v-close-popup>
                      <q-item-section avatar><q-icon name="tune" color="purple" /></q-item-section>
                      <q-item-section>Editar detalle</q-item-section>
                    </q-item>
                    <q-item clickable v-ripple @click="pedidoItems.splice(index, 1)" v-close-popup>
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section>Eliminar producto</q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
<!--                <pre>{{p.tipo}}</pre>-->
              </td>
              <td>{{ (Number(p.cantidad) * Number(p.precio)).toFixed(2) }}</td>
              <td>
<!--                <q-input v-model.number="p.cantidad" dense outlined type="number" min="1" style="min-width:90px" />-->
                <input type="text" v-model.number="p.cantidad" style="width: 40px" @input="p.cantidad = p.cantidad < 1 ? 1 : p.cantidad" />
              </td>
              <td>
<!--                <q-input v-model.number="p.precio" dense outlined type="number" min="0" step="0.01" style="min-width:110px" />-->
                <input type="text" v-model.number="p.precio" style="width: 50px" @input="p.precio = p.precio < 0 ? 0 : p.precio" />
              </td>
              <td>{{ p.codigo || p.producto_id }}</td>
              <td>
                <div class="row items-center no-wrap q-gutter-sm">
                  <q-avatar rounded size="30px">
                    <q-img :src="productImageUrl(p.imagen)" />
                  </q-avatar>
                  <div>{{ p.nombre }}</div>
                </div>
              </td>
              <td>{{ p.observacion || '-' }}</td>
<!--              <td>-->
<!--                <q-btn-->
<!--                  flat-->
<!--                  dense-->
<!--                  round-->
<!--                  icon="delete"-->
<!--                  color="negative"-->
<!--                  @click="pedidoItems.splice(index, 1)"-->
<!--                />-->
<!--              </td>-->
            </tr>
            <tr v-if="pedidoItems.length === 0">
              <td colspan="8" class="text-grey-7">Sin datos disponibles</td>
            </tr>
            </tbody>
          </q-markup-table>

          <div class="text-h6 q-mt-sm">Total: {{ totalPedido.toFixed(2) }} Bs.</div>
        </q-card-section>

        <q-card-actions align="between" class="q-pa-md">
          <q-btn flat color="negative" label="Cerrar" @click="dialogPedido = false" />
          <q-btn color="green" no-caps icon="send" label="Realizar pedido" :loading="loadingPedido" :disable="loadingPedido || Boolean(loadingAccion)" @click="guardarPedido" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogDetalle">
      <q-card style="width: 450px; max-width: 96vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Pedido {{ detalleTipoLabel }}</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-sm" v-if="detalleTipo === 'NORMAL'">
            <div class="col-12">
              <q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" />
            </div>
          </div>

          <div class="row q-col-gutter-sm" v-else-if="detalleTipo === 'RES'">
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.precio_res" dense outlined label="Precio RES" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.res_trozado" dense outlined label="Res trozado" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.res_entero" dense outlined label="Res entero" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.res_pierna" dense outlined label="Res pierna" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.res_brazo" dense outlined label="Res brazo" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>

          <div class="row q-col-gutter-sm" v-else-if="detalleTipo === 'CERDO'">
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.cerdo_precio_total" dense outlined label="Precio total" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.cerdo_entero" dense outlined label="Cerdo entero" /></div>
            <div class="col-12 col-md-4"><q-input v-model="detalleEdit.cerdo_kilo" dense outlined label="Cerdo kilo" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.cerdo_desmembrado" dense outlined label="Cerdo desmembrado" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.cerdo_corte" dense outlined label="Cerdo corte" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>

          <div class="row q-col-gutter-sm" v-else>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_b5" dense outlined label="Cja b5" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_b5" dense outlined label="Uni b5" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_b6" dense outlined label="Cja b6" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_b6" dense outlined label="Uni b6" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_104" dense outlined label="Cja-104 (1.5-1.7)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_104" dense outlined label="Unid-104" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_105" dense outlined label="Cja-105 (1.7-1.9)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_105" dense outlined label="Unid-105" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_106" dense outlined label="Cja-106 (1.9-2.2)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_106" dense outlined label="Unid-106" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_107" dense outlined label="Cja-107 (2.2-2.5)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_107" dense outlined label="Unid-107" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_108" dense outlined label="Cja-108 (2.6-2.7)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_108" dense outlined label="Unid-108" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cja_109" dense outlined label="Cja-109 (2.8-2.9)" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_uni_109" dense outlined label="Unid-109" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.pollo_rango_unidades" dense outlined label="Rango pollo (unidades)" /></div>

            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_ala" dense outlined label="Ala" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_ala_unidad" :options="unidadesPollo" dense outlined label="Unidad ala" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cadera" dense outlined label="Cadera" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_cadera_unidad" :options="unidadesPollo" dense outlined label="Unidad cadera" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_pecho" dense outlined label="Pecho" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_pecho_unidad" :options="unidadesPollo" dense outlined label="Unidad pecho" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_pi_mu" dense outlined label="Pi/Mu" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_pi_mu_unidad" :options="unidadesPollo" dense outlined label="Unidad pi/mu" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_filete" dense outlined label="Filete" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_filete_unidad" :options="unidadesPollo" dense outlined label="Unidad filete" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_cuello" dense outlined label="Cuello" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_cuello_unidad" :options="unidadesPollo" dense outlined label="Unidad cuello" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_hueso" dense outlined label="Hueso" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_hueso_unidad" :options="unidadesPollo" dense outlined label="Unidad hueso" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_menudencia" dense outlined label="Menudencia" /></div>
            <div class="col-12 col-md-6"><q-select v-model="detalleEdit.pollo_menudencia_unidad" :options="unidadesPollo" dense outlined label="Unidad menudencia" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_bs" dense outlined label="BS" /></div>
            <div class="col-12 col-md-6"><q-input v-model="detalleEdit.pollo_bs2" dense outlined label="BS2" /></div>
            <div class="col-12"><q-input v-model="detalleEdit.observacion" dense outlined label="Observacion detalle" /></div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat color="negative" label="Cerrar" v-close-popup />
          <q-btn color="primary" label="Guardar detalle" @click="saveDetalleDialog" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

const ORURO_CENTER = [-17.967, -67.106]
const DAY_MAP = ['do', 'lu', 'ma', 'mi', 'ju', 'vi', 'sa']
const TIPOS_OBLIGATORIOS = ['POLLO', 'RES', 'CERDO']

export default {
  name: 'VisitasPage',
  data () {
    return {
      loading: false,
      loadingPedido: false,
      loadingAccion: '',
      map: null,
      markersLayer: null,
      meMarker: null,
      search: '',
      showAllDays: false,
      clientes: [],
      visitasByCliente: {},
      selectedCliente: null,
      comentario: '',
      dialogAcciones: false,
      dialogPedido: false,
      tiposPago: [
        { label: 'Contado', value: 'Contado' },
        { label: 'Pago QR', value: 'QR' },
        { label: 'Credito', value: 'Credito' },
        { label: 'Boleta anterior', value: 'Boleta anterior' }
      ],
      tipoPago: 'Contado',
      facturadoPedido: false,
      fechaPedido: new Date().toISOString().slice(0, 10),
      horariosPedido: [
        { label: 'Sin horario', value: null },
        { label: '06:00-07:30', value: '06:00-07:30' },
        { label: '07:30-09:00', value: '07:30-09:00' },
        { label: '09:00-10:30', value: '09:00-10:30' },
        { label: '10:30-12:00', value: '10:30-12:00' },
        { label: 'SEGUNDA VUELTA', value: 'SEGUNDA VUELTA' },
        { label: 'SE RECOGE', value: 'SE RECOGE' },
      ],
      horaPedido: null,
      productos: [],
      productosSource: [],
      productoSeleccionado: null,
      pedidoItems: [],
      dialogDetalle: false,
      detalleEditIndex: -1,
      detalleEdit: {},
      detalleTipo: 'NORMAL',
      unidadesPollo: ['KG', 'CAJA', 'UNIDAD'],
      isAlive: true
    }
  },
  computed: {
    dayCode () {
      return DAY_MAP[new Date().getDay()]
    },
    dayLabel () {
      const labels = { lu: 'Lunes', ma: 'Martes', mi: 'Miercoles', ju: 'Jueves', vi: 'Viernes', sa: 'Sabado', do: 'Domingo' }
      return labels[this.dayCode]
    },
    totalPedido () {
      return this.pedidoItems.reduce((acc, p) => acc + (Number(p.cantidad) * Number(p.precio)), 0)
    },
    clientesOrdenados () {
      const prioridad = {
        ACTIVO: 1,
        RETORNAR: 2,
        NO_PEDIDO: 3,
        REALIZAR_PEDIDO: 4,
      }

      return [...this.clientes].sort((a, b) => {
        const sa = this.clienteStatus(a?.id)
        const sb = this.clienteStatus(b?.id)
        const pa = prioridad[sa] || 99
        const pb = prioridad[sb] || 99

        if (pa !== pb) return pa - pb

        const na = String(a?.nombre || '').toLowerCase()
        const nb = String(b?.nombre || '').toLowerCase()
        return na.localeCompare(nb, 'es')
      })
    },
    detalleTipoLabel () {
      if (this.detalleTipo === 'RES') return 'Res'
      if (this.detalleTipo === 'CERDO') return 'Cerdo'
      if (this.detalleTipo === 'POLLO') return 'Pollo'
      return 'Normal'
    }
  },
  mounted () {
    this.initMap()
    this.cargarClientes()
    this.cargarProductos()
  },
  beforeUnmount () {
    this.isAlive = false
    try {
      if (this.map) {
        this.map.off()
        this.map.remove()
      }
    } catch (_) {}
    this.map = null
    this.markersLayer = null
    this.meMarker = null
  },
  methods: {
    markerColor (status) {
      const s = this.normalizeStatus(status)
      if (s === 'REALIZAR_PEDIDO') return '#16a34a'
      if (s === 'RETORNAR') return '#f4b400'
      if (s === 'NO_PEDIDO') return '#e53935'
      if (s === 'GENERAR_RUTA') return '#7e22ce'
      return '#1e88e5'
    },
    statusColor (status) {
      const s = this.normalizeStatus(status)
      if (s === 'RETORNAR') return 'warning'
      if (s === 'NO_PEDIDO') return 'negative'
      if (s === 'REALIZAR_PEDIDO') return 'positive'
      if (s === 'GENERAR_RUTA') return 'purple'
      return 'primary'
    },
    normalizeStatus (status) {
      return String(status || 'ACTIVO').trim().toUpperCase()
    },
    normalizeTipoProducto (tipo) {
      const t = String(tipo || 'NORMAL').trim().toUpperCase()
      if (t === 'RES' || t === 'CERDO' || t === 'POLLO') return t
      return 'NORMAL'
    },
    detalleDefaultsByTipo (tipo) {
      if (tipo === 'RES') {
        return {
          precio_res: '',
          res_trozado: '',
          res_entero: '',
          res_pierna: '',
          res_brazo: '',
          observacion: '',
        }
      }
      if (tipo === 'CERDO') {
        return {
          cerdo_precio_total: '',
          cerdo_entero: '',
          cerdo_desmembrado: '',
          cerdo_corte: '',
          cerdo_kilo: '',
          observacion: '',
        }
      }
      if (tipo === 'POLLO') {
        return {
          pollo_cja_b5: '',
          pollo_uni_b5: '',
          pollo_cja_b6: '',
          pollo_uni_b6: '',
          pollo_cja_104: '',
          pollo_uni_104: '',
          pollo_cja_105: '',
          pollo_uni_105: '',
          pollo_cja_106: '',
          pollo_uni_106: '',
          pollo_cja_107: '',
          pollo_uni_107: '',
          pollo_cja_108: '',
          pollo_uni_108: '',
          pollo_cja_109: '',
          pollo_uni_109: '',
          pollo_rango_unidades: '',
          pollo_ala: '',
          pollo_ala_unidad: 'KG',
          pollo_cadera: '',
          pollo_cadera_unidad: 'KG',
          pollo_pecho: '',
          pollo_pecho_unidad: 'KG',
          pollo_pi_mu: '',
          pollo_pi_mu_unidad: 'KG',
          pollo_filete: '',
          pollo_filete_unidad: 'KG',
          pollo_cuello: '',
          pollo_cuello_unidad: 'KG',
          pollo_hueso: '',
          pollo_hueso_unidad: 'KG',
          pollo_menudencia: '',
          pollo_menudencia_unidad: 'KG',
          pollo_bs: '',
          pollo_bs2: '',
          observacion: '',
        }
      }
      return { observacion: '' }
    },
    openDetalleDialog (item, index) {
      this.detalleEditIndex = index
      this.detalleTipo = this.normalizeTipoProducto(item?.tipo)
      const defaults = this.detalleDefaultsByTipo(this.detalleTipo)
      this.detalleEdit = { ...defaults, ...(item?.detalle_extra || {}) }
      this.dialogDetalle = true
    },
    saveDetalleDialog () {
      if (this.detalleEditIndex < 0 || !this.pedidoItems[this.detalleEditIndex]) return
      const current = this.pedidoItems[this.detalleEditIndex]
      current.detalle_extra = { ...this.detalleEdit }
      current.observacion = this.detalleEdit.observacion || current.observacion || ''
      this.dialogDetalle = false
      this.detalleEditIndex = -1
      this.detalleEdit = {}
    },
    rowClassByCliente (cliente) {
      const status = this.clienteStatus(cliente?.id)
      if (status === 'RETORNAR') return 'cliente-retornar'
      if (status === 'NO_PEDIDO') return 'cliente-no-pedido'
      if (status === 'REALIZAR_PEDIDO') return 'cliente-pedido'
      return ''
    },
    clienteStatus (clienteId) {
      if (!clienteId) return 'ACTIVO'
      return this.normalizeStatus(this.visitasByCliente[clienteId]?.tipo_visita || 'ACTIVO')
    },
    mapReady () {
      return !!(this.map && this.map._loaded && this.map.getPane && this.map.getPane('mapPane'))
    },
    initMap () {
      if (!this.$refs.mapRef || !this.isAlive) return
      this.map = L.map(this.$refs.mapRef, { center: ORURO_CENTER, zoom: 13 })

      const googleRoad = L.tileLayer('https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data © Google' })
      const googleSat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data © Google' })
      const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 21, attribution: 'Map data © Google' })
      const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' })

      googleRoad.addTo(this.map)
      L.control.layers({
        'Google Calle': googleRoad,
        'Google Satelite': googleSat,
        'Google Hibrido': googleHybrid,
        OpenStreetMap: osm
      }).addTo(this.map)

      this.markersLayer = L.layerGroup().addTo(this.map)
    },
    async cargarClientes () {
      this.loading = true
      try {
        const res = await this.$axios.get('clientes', {
          params: {
            search: this.search,
            per_page: 500,
            solo_mios: 1,
            solo_dia: this.showAllDays ? 0 : 1,
            dia: this.dayCode
          }
        })
        if (!this.isAlive) return
        this.clientes = res.data?.data || []
        await this.cargarVisitas()
        this.renderMarkers()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar clientes de visitas')
      } finally {
        if (this.isAlive) this.loading = false
      }
    },
    renderMarkers () {
      if (!this.mapReady() || !this.markersLayer) return
      this.markersLayer.clearLayers()
      const bounds = []

      this.clientes.forEach(c => {
        if (!Number.isFinite(Number(c.latitud)) || !Number.isFinite(Number(c.longitud))) return
        const lat = Number(c.latitud)
        const lng = Number(c.longitud)
        const idLabel = c.codcli || c.id
        const status = this.clienteStatus(c.id)
        const color = this.markerColor(status)
        const marker = L.circleMarker([lat, lng], {
          radius: 9,
          fillColor: color,
          color: '#fff',
          weight: 3,
          fillOpacity: 0.95
        }).addTo(this.markersLayer)
        marker.bindTooltip(String(idLabel), { permanent: true, direction: 'top', className: 'cliente-id-tooltip' })
        marker.on('click', () => this.openAcciones(c))
        bounds.push([lat, lng])
      })

      if (bounds.length > 0 && this.mapReady()) {
        try {
          this.map.fitBounds(bounds, { padding: [35, 35], maxZoom: 16 })
        } catch (_) {}
      }
    },
    setDayFilter (allDays) {
      this.showAllDays = allDays
      this.cargarClientes()
    },
    async cargarVisitas () {
      try {
        const res = await this.$axios.get('visitas', {
          params: {
            solo_mios: 1,
            all_days: 0,
            latest_per_cliente: 1,
            fecha: new Date().toISOString().slice(0, 10),
          }
        })
        const data = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        const byCliente = {}
        data.forEach(v => {
          if (!v?.cliente_id) return
          byCliente[v.cliente_id] = v
        })
        this.visitasByCliente = byCliente
      } catch (_) {
        this.visitasByCliente = {}
      }
    },
    locateMe () {
      if (!this.mapReady()) return
      if (!navigator.geolocation) {
        this.$alert.error('Geolocalizacion no disponible')
        return
      }
      navigator.geolocation.getCurrentPosition((pos) => {
        if (!this.mapReady()) return
        const lat = Number(pos.coords.latitude.toFixed(7))
        const lng = Number(pos.coords.longitude.toFixed(7))

        if (this.meMarker) this.map.removeLayer(this.meMarker)
        this.meMarker = L.circleMarker([lat, lng], {
          radius: 10,
          fillColor: '#ff1744',
          color: '#fff',
          weight: 3,
          fillOpacity: 0.95
        }).addTo(this.map)
        this.meMarker.bindPopup('Aqui estoy yo').openPopup()
        try {
          this.map.flyTo([lat, lng], 16)
        } catch (_) {}
      }, () => this.$alert.error('No se pudo obtener tu ubicacion'))
    },
    openAcciones (cliente) {
      this.selectedCliente = cliente
      this.loadingAccion = ''
      this.dialogAcciones = true
    },
    async accionSeleccionada (accion) {
      if (!this.selectedCliente) return
      if (accion === 'REALIZAR_PEDIDO') {
        this.dialogAcciones = false
        this.loadingPedido = false
        this.pedidoItems = []
        this.facturadoPedido = false
        this.fechaPedido = new Date().toISOString().slice(0, 10)
        this.horaPedido = null
        this.dialogPedido = true
        return
      }
      if (accion === 'GENERAR_RUTA') {
        this.generarRuta()
      }
      this.guardarAccion(accion)
    },
    async guardarAccion (accion) {
      if (!this.selectedCliente) return
      this.loadingAccion = accion
      this.loading = true
      try {
        await this.$axios.post('pedidos', {
          tipo_pedido: accion,
          cliente_id: this.selectedCliente.id,
          comentario_visita: this.comentario || '',
          observaciones: this.comentario || '',
          productos: []
        })
        this.$alert.success('Accion registrada')
        this.dialogAcciones = false
        this.comentario = ''
        await this.cargarVisitas()
        this.renderMarkers()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo registrar la accion')
      } finally {
        this.loadingAccion = ''
        this.loading = false
      }
    },
    generarRuta () {
      if (!this.selectedCliente) return
      const lat = Number(this.selectedCliente.latitud)
      const lng = Number(this.selectedCliente.longitud)
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        this.$alert.error('El cliente no tiene coordenadas')
        return
      }
      window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank')
    },
    mapStockOption (p) {
      return {
        id: p.id,
        label: `${p.codigo || p.id}-${p.nombre} ${Number(p.precio1 || 0).toFixed(2)}Bs ${Number(p.stock || 0).toFixed(2)}U`,
        nombre: p.nombre,
        codigo: p.codigo,
        imagen: p.imagen || 'uploads/default.png',
        precio: Number(p.precio1 || 0),
        stock: Number(p.stock || 0),
        tipo: this.normalizeTipoProducto(p.tipo),
      }
    },
    productImageUrl (path) {
      const safe = path || 'uploads/default.png'
      return `${this.$url}../${safe}`
    },
    async cargarProductos () {
      try {
        const [resStock, resAll] = await Promise.all([
          this.$axios.get('productosStock', { params: { per_page: 500 } }),
          this.$axios.get('productosAll')
        ])

        const stockData = resStock.data?.data || []
        const allData = Array.isArray(resAll.data) ? resAll.data : []

        const extraTipos = allData.filter(p => TIPOS_OBLIGATORIOS.includes(String(p?.tipo || '').toUpperCase()))
        const mergedById = new Map()
        ;[...stockData, ...extraTipos].forEach(p => {
          if (!p?.id) return
          mergedById.set(p.id, p)
        })

        const prioridadTipo = { POLLO: 1, RES: 2, CERDO: 3 }
        this.productosSource = Array.from(mergedById.values())
          .sort((a, b) => {
            const ta = String(a?.tipo || '').toUpperCase()
            const tb = String(b?.tipo || '').toUpperCase()
            const pa = prioridadTipo[ta] || 99
            const pb = prioridadTipo[tb] || 99
            if (pa !== pb) return pa - pb
            return String(a?.nombre || '').localeCompare(String(b?.nombre || ''), 'es')
          })
        this.productos = this.productosSource.map(this.mapStockOption)
      } catch (_) {
        this.productos = []
        this.productosSource = []
      }
    },
    filtrarProductos (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase()
        if (!needle) {
          this.productos = this.productosSource.map(this.mapStockOption)
          return
        }
        this.productos = this.productosSource
          .filter(p => `${p.nombre || ''} ${p.codigo || ''}`.toLowerCase().includes(needle))
          .map(this.mapStockOption)
      })
    },
    agregarProducto () {
      if (!this.productoSeleccionado) return
      const p = this.productos.find(x => x.id === this.productoSeleccionado)
      if (!p) return

      const ex = this.pedidoItems.find(x => x.producto_id === p.id)
      if (ex) {
        ex.cantidad += 1
      } else {
        this.pedidoItems.push({
          producto_id: p.id,
          codigo: p.codigo,
          nombre: p.nombre,
          imagen: p.imagen || 'uploads/default.png',
          cantidad: 1,
          precio: Number(p.precio || 0),
          observacion: '',
          tipo: this.normalizeTipoProducto(p.tipo),
          detalle_extra: this.detalleDefaultsByTipo(this.normalizeTipoProducto(p.tipo)),
        })
      }
      this.productoSeleccionado = null
    },
    async guardarPedido () {
      if (!this.selectedCliente) return
      if (this.pedidoItems.length === 0) {
        this.$alert.error('Debe agregar al menos un producto')
        return
      }

      const productos = this.pedidoItems.map(p => ({
        producto_id: p.producto_id,
        cantidad: Number(p.cantidad || 0),
        precio: Number(p.precio || 0),
        observacion: p.observacion || '',
        detalle_extra: p.detalle_extra || null,
      })).filter(p => p.cantidad > 0 && p.precio >= 0)

      if (productos.length === 0) {
        this.$alert.error('Revise cantidades y precios')
        return
      }

      this.loadingPedido = true
      this.loading = true
      try {
        await this.$axios.post('pedidos', {
          tipo_pedido: 'REALIZAR_PEDIDO',
          tipo_pago: this.tipoPago,
          facturado: this.facturadoPedido,
          fecha: this.fechaPedido,
          hora: this.horaPedido,
          cliente_id: this.selectedCliente.id,
          comentario_visita: this.comentario || '',
          observaciones: this.comentario || '',
          productos
        })
        this.$alert.success('Pedido registrado')
        this.dialogPedido = false
        this.comentario = ''
        this.pedidoItems = []
        await this.cargarVisitas()
        this.renderMarkers()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo registrar pedido')
      } finally {
        this.loadingPedido = false
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.visitas-map {
  height: 48vh;
  min-height: 330px;
  border-radius: 10px;
}
.map-toolbar-left {
  position: absolute;
  left: 16px;
  bottom: 12px;
  z-index: 500;
}
.map-toolbar-right {
  position: absolute;
  right: 16px;
  bottom: 12px;
  z-index: 500;
}
.row-selected {
  background: #f0f8ff;
}
.cliente-link {
  justify-content: flex-start;
  width: 100%;
  text-align: left;
}
.cliente-pedido {
  background: #dcfce7;
}
.cliente-retornar {
  background: #fff2b3;
}
.cliente-no-pedido {
  background: #ffd6d6;
}
:deep(.cliente-id-tooltip) {
  background: #22b8cf;
  color: #fff;
  border: 0;
  border-radius: 10px;
  padding: 2px 8px;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}
:deep(.cliente-id-tooltip:before) {
  border-top-color: #22b8cf;
}
</style>
