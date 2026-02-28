<template>
  <q-page class="q-pa-sm misped-page">
    <q-card flat bordered class="hero-card q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <div class="text-subtitle2 text-grey-7">Panel administrativo</div>
          <div class="text-h6 text-weight-bold">Digitador factura</div>
        </div>
        <div class="col-12 col-md-2">
          <q-input v-model="fechaInicio" type="date" dense outlined label="Fecha inicio" />
        </div>
        <div class="col-12 col-md-2">
          <q-input v-model="fechaFin" type="date" dense outlined label="Fecha fin" />
        </div>
        <div class="col-12 col-md-2">
          <q-toggle v-model="soloFactura" label="Solo factura" color="primary" />
        </div>
        <div class="col-12 col-md-2">
          <q-btn color="primary" icon="search" no-caps class="full-width" label="Consulta" :loading="loading" @click="cargarVentas" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-6 col-md-2">
          <q-chip square color="blue-8" text-color="white" class="full-width justify-center">Ventas: {{ stats.total_ventas || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="indigo-8" text-color="white" class="full-width justify-center">Monto Bs: {{ Number(stats.monto_total_ventas || 0).toFixed(2) }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="green-8" text-color="white" class="full-width justify-center">Facturadas: {{ stats.ventas_facturadas || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="orange-8" text-color="white" class="full-width justify-center">No facturadas: {{ stats.ventas_no_facturadas || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="deep-orange-9" text-color="white" class="full-width justify-center">Pendientes: {{ stats.pendientes_factura || 0 }}</q-chip>
        </div>
        <div class="col-6 col-md-2">
          <q-chip square color="grey-8" text-color="white" class="full-width justify-center">Sin venta: {{ stats.pedidos_sin_venta || 0 }}</q-chip>
        </div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-btn
              color="deep-orange"
              icon="receipt_long"
              label="Generar factura de todos"
              no-caps
              size="lg"
              class="full-width text-weight-bold"
              :loading="loadingFacturar"
              @click="generarFacturaTodos"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-btn
              color="primary"
              icon="print"
              label="Imprimir todas facturas"
              no-caps
              size="lg"
              class="full-width text-weight-bold"
              :loading="loadingPrintFacturas"
              @click="imprimirFacturasMasivo"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-btn
              color="teal"
              icon="receipt"
              label="Imprimir todos vouchers"
              no-caps
              size="lg"
              class="full-width text-weight-bold"
              :loading="loadingPrintVouchers"
              @click="imprimirVouchersMasivo"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined label="Buscar comanda/cliente/vendedor/producto" debounce="250">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-md-3">
          <q-chip color="indigo-7" text-color="white">Registros: {{ ventasFiltradas.length }}</q-chip>
        </div>
      </q-card-section>
      <q-markup-table flat dense wrap-cells>
        <thead>
          <tr class="bg-grey-2">
            <th>Opciones</th>
            <th>Comanda</th>
            <th>Vendedor</th>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>Fec/Hora</th>
            <th>Pago</th>
            <th>Factura</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="venta in ventasFiltradas" :key="venta.venta_id">
            <td>
              <q-btn-dropdown color="primary" label="Opciones" dense no-caps size="10px">
                <q-item clickable v-close-popup @click="abrirEdicion(venta)" :disable="isBloqueada(venta)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section>{{ isBloqueada(venta) ? 'Edicion bloqueada' : 'Editar' }}</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="verificarImpuestos(venta)" v-if="venta.cuf">
                  <q-item-section avatar><q-icon name="fact_check" /></q-item-section>
                  <q-item-section>Verificar impuestos</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="imprimirImpuestos(venta)" v-if="venta.cuf">
                  <q-item-section avatar><q-icon name="receipt_long" /></q-item-section>
                  <q-item-section>Imprimir impuesto</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="imprimirFacturaIndividual(venta)" v-if="venta.cuf && venta.factura_estado === 'FACTURADO'">
                  <q-item-section avatar><q-icon name="print" /></q-item-section>
                  <q-item-section>Imprimir factura</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="imprimirVoucherIndividual(venta)">
                  <q-item-section avatar><q-icon name="description" /></q-item-section>
                  <q-item-section>Imprimir voucher</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="mandarWhatsappFactura(venta)" v-if="venta.cuf && venta.factura_estado === 'FACTURADO'">
                  <q-item-section avatar><q-icon name="chat" /></q-item-section>
                  <q-item-section>WhatsApp factura</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="mandarWhatsappVoucher(venta)">
                  <q-item-section avatar><q-icon name="chat_bubble" /></q-item-section>
                  <q-item-section>WhatsApp voucher</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="anularVenta(venta)">
                  <q-item-section avatar><q-icon name="delete" /></q-item-section>
                  <q-item-section>Anular</q-item-section>
                </q-item>
              </q-btn-dropdown>
            </td>
            <td>{{ venta.comanda || '-' }}</td>
            <td>{{ venta.vendedor || '-' }}</td>
            <td>{{ venta.cliente || '-' }}</td>
            <td>
              <q-chip
                v-for="tipo in (venta.tipo || [])"
                :key="`${venta.venta_id}-${tipo}`"
                dense
                text-color="white"
                :color="tipoColor(tipo)"
                class="q-mr-xs q-mb-xs"
              >
                {{ tipo }}
              </q-chip>
            </td>
            <td>
              <ul style="padding:0;margin:0;list-style:none;">
                <li v-for="p in (venta.productos || [])" :key="`${venta.venta_id}-${p.id}`" style="font-size:0.9em;border-bottom:1px solid #eee;padding:0;">
                  {{ p.nombre }} x {{ p.cantidad }}
                </li>
              </ul>
            </td>
            <td>{{ venta.fecha }} {{ venta.hora || '' }}</td>
            <td>{{ venta.pago || '-' }}</td>
            <td>
              <q-chip dense :color="venta.facturado ? 'green-8' : 'negative'" text-color="white">
                {{ venta.facturado ? 'SI' : 'NO' }}
              </q-chip>
              <q-chip dense :color="venta.online ? 'green-7' : 'blue-8'" text-color="white" class="q-ml-xs">
                {{ venta.factura_estado || 'SIN_GESTION' }}
              </q-chip>
              <q-chip dense :color="venta.online ? 'green' : 'red'" text-color="white" class="q-ml-xs">
                {{ venta.online ? 'ONLINE' : 'OFFLINE' }}
              </q-chip>
              <q-chip
                v-if="venta.factura_error"
                dense
                color="negative"
                text-color="white"
                class="q-ml-xs"
              >
                Error
                <q-tooltip style="max-width: 360px; white-space: normal;">{{ venta.factura_error }}</q-tooltip>
              </q-chip>
            </td>
          </tr>
          <tr v-if="ventasFiltradas.length === 0">
            <td colspan="9" class="text-center text-grey-7">Sin datos disponibles</td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="text-subtitle2">
        Pedidos sin venta generada: {{ pedidosSinVenta.length }}
      </q-card-section>
      <q-markup-table flat dense wrap-cells>
        <thead>
          <tr class="bg-grey-2">
            <th>Comanda</th>
            <th>Vendedor</th>
            <th>Cliente</th>
            <th>Fec/Hora</th>
            <th>Factura</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pedidosSinVenta" :key="`sv-${p.comanda}`">
            <td>{{ p.comanda }}</td>
            <td>{{ p.vendedor || '-' }}</td>
            <td>{{ p.cliente || '-' }}</td>
            <td>{{ p.fecha }} {{ p.hora || '' }}</td>
            <td>{{ p.facturado ? 'SI' : 'NO' }}</td>
            <td>{{ p.estado || '-' }}</td>
          </tr>
          <tr v-if="pedidosSinVenta.length === 0">
            <td colspan="6" class="text-center text-grey-7">Sin pendientes</td>
          </tr>
        </tbody>
      </q-markup-table>
    </q-card>

    <q-dialog v-model="dialogEdit" maximized>
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Editar venta #{{ editForm.venta_id }}</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pt-sm">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-3">
              <q-select v-model="editForm.tipo_pago" :options="tipoPagoVentaOptions" dense outlined emit-value map-options label="Tipo pago" />
            </div>
            <div class="col-12 col-md-2">
              <q-toggle v-model="editForm.facturado" label="Facturado" />
            </div>
            <div class="col-12 col-md-3">
              <q-select v-model="editForm.factura_estado" :options="facturaEstadoOptions" dense outlined emit-value map-options label="Estado factura" />
            </div>
            <div class="col-12 col-md-4">
              <q-input v-model="editForm.observaciones" dense outlined label="Observacion" maxlength="600" />
            </div>
          </div>
          <q-markup-table dense flat bordered class="q-mt-sm">
            <thead>
              <tr>
                <th>Cod</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in editForm.productos" :key="`ed-${d.id}-${i}`">
                <td>{{ d.codigo }}</td>
                <td>{{ d.nombre }}</td>
                <td>{{ d.tipo }}</td>
                <td><input v-model.number="d.cantidad" type="number" step="0.01" min="0" style="width: 80px" /></td>
                <td><input v-model.number="d.precio" type="number" step="0.01" min="0" style="width: 90px" /></td>
                <td>{{ (Number(d.cantidad || 0) * Number(d.precio || 0)).toFixed(2) }}</td>
              </tr>
              <tr v-if="editForm.productos.length === 0">
                <td colspan="6" class="text-grey-7">Sin detalles</td>
              </tr>
            </tbody>
          </q-markup-table>
          <div class="text-h6 q-mt-sm">Total: {{ totalEdit.toFixed(2) }} Bs.</div>
        </q-card-section>
        <q-card-actions align="between" class="q-pa-md">
          <q-btn flat color="negative" label="Cerrar" v-close-popup />
          <q-btn color="green" no-caps icon="save" label="Guardar cambios" :loading="saving" @click="guardarEdicion" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'DigitadorFactura',
  data() {
    const today = new Date().toISOString().slice(0, 10)
    return {
      fechaInicio: today,
      fechaFin: today,
      loading: false,
      loadingFacturar: false,
      loadingPrintFacturas: false,
      loadingPrintVouchers: false,
      saving: false,
      search: '',
      soloFactura: false,
      ventas: [],
      pedidosSinVenta: [],
      stats: {
        total_ventas: 0,
        monto_total_ventas: 0,
        ventas_facturadas: 0,
        ventas_no_facturadas: 0,
        pendientes_factura: 0,
        pedidos_sin_venta: 0,
      },
      dialogEdit: false,
      editForm: {
        venta_id: null,
        tipo_pago: 'Efectivo',
        facturado: false,
        factura_estado: 'SIN_GESTION',
        observaciones: '',
        productos: [],
      },
      tipoPagoVentaOptions: [
        { label: 'Efectivo', value: 'Efectivo' },
        { label: 'QR', value: 'QR' },
        { label: 'Contado', value: 'Contado' },
        { label: 'Credito', value: 'Credito' },
        { label: 'Boleta anterior', value: 'Boleta anterior' },
      ],
      facturaEstadoOptions: [
        { label: 'Sin gestion', value: 'SIN_GESTION' },
        { label: 'Pendiente', value: 'PENDIENTE' },
        { label: 'Facturado', value: 'FACTURADO' },
      ],
    }
  },
  computed: {
    ventasFiltradas() {
      const term = String(this.search || '').trim().toLowerCase()
      if (!term) return this.ventas
      return this.ventas.filter((v) => {
        const productos = (v.productos || []).map((x) => `${x.nombre} ${x.codigo}`).join(' ').toLowerCase()
        const s = `${v.comanda || ''} ${v.vendedor || ''} ${v.cliente || ''} ${v.venta_id || ''} ${productos}`.toLowerCase()
        return s.includes(term)
      })
    },
    totalEdit() {
      return (this.editForm.productos || []).reduce((acc, d) => acc + (Number(d.cantidad || 0) * Number(d.precio || 0)), 0)
    },
  },
  mounted() {
    this.cargarVentas()
  },
  methods: {
    tipoColor(tipo) {
      const t = String(tipo || 'NORMAL').toUpperCase()
      if (t === 'POLLO') return 'orange'
      if (t === 'RES') return 'red'
      if (t === 'CERDO') return 'brown'
      return 'primary'
    },
    isBloqueada(venta) {
      const facturado = !!(venta && venta.facturado)
      const emitida = String((venta && venta.factura_estado) || '').toUpperCase() === 'FACTURADO'
      const activa = String((venta && venta.estado) || '').toUpperCase() === 'ACTIVO'
      return facturado && emitida && activa
    },
    async cargarVentas() {
      this.loading = true
      try {
        const res = await this.$axios.get('/digitador-factura/pedidos', {
          params: {
            fecha_inicio: this.fechaInicio,
            fecha_fin: this.fechaFin,
            solo_factura: this.soloFactura,
          },
        })
        const data = res && res.data ? res.data : {}
        this.ventas = Array.isArray(data.data) ? data.data : []
        this.pedidosSinVenta = Array.isArray(data.pedidos_sin_venta) ? data.pedidos_sin_venta : []
        this.stats = data.stats || this.stats
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo cargar ventas de digitador factura')
      } finally {
        this.loading = false
      }
    },
    abrirEdicion(venta) {
      if (this.isBloqueada(venta)) {
        this.$alert.error('La factura ya fue generada. Anule la venta para volver a editar.')
        return
      }
      const detalleEdit = venta && venta.detalle_edit ? venta.detalle_edit : {}
      this.editForm = {
        venta_id: venta.venta_id,
        tipo_pago: detalleEdit.tipo_pago || venta.pago || 'Efectivo',
        facturado: !!(typeof detalleEdit.facturado !== 'undefined' ? detalleEdit.facturado : venta.facturado),
        factura_estado: detalleEdit.factura_estado || venta.factura_estado || 'SIN_GESTION',
        observaciones: detalleEdit.observaciones || venta.observaciones || '',
        productos: (detalleEdit.productos || venta.productos || []).map((p) => ({
          id: p.id,
          codigo: p.codigo,
          nombre: p.nombre,
          tipo: p.tipo,
          cantidad: Number(p.cantidad || 0),
          precio: Number(p.precio || 0),
        })),
      }
      this.dialogEdit = true
    },
    async guardarEdicion() {
      if (!this.editForm.venta_id) return
      try {
        this.saving = true
        await this.$axios.put(`/digitador-factura/ventas/${this.editForm.venta_id}`, {
          tipo_pago: this.editForm.tipo_pago,
          facturado: !!this.editForm.facturado,
          factura_estado: this.editForm.factura_estado,
          observaciones: this.editForm.observaciones || null,
          productos: (this.editForm.productos || []).map((d) => ({
            id: d.id,
            cantidad: Number(d.cantidad || 0),
            precio: Number(d.precio || 0),
          })),
        })
        this.$alert.success('Venta actualizada')
        this.dialogEdit = false
        await this.cargarVentas()
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo actualizar venta')
      } finally {
        this.saving = false
      }
    },
    async verificarImpuestos(venta) {
      if (!(venta && venta.cuf)) return
      try {
        const res = await this.$axios.post(`/verificarImpuestos/${venta.cuf}`)
        this.$q.dialog({
          title: 'Verificacion de impuestos',
          fullWidth: true,
          message: `<pre>${JSON.stringify(res.data, null, 2)}</pre>`,
          html: true,
          ok: true,
        })
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo verificar en impuestos')
      }
    },
    imprimirImpuestos(venta) {
      if (!(venta && venta.cuf)) return
      window.open(`${this.$store.env.url2}consulta/QR?nit=${this.$store.env.nit}&cuf=${venta.cuf}&numero=${venta.venta_id}&t=2`, '_blank')
    },
    async anularVenta(venta) {
      const ok = await new Promise((resolve) => {
        this.$alert.dialog('Desea anular la venta?')
          .onOk(() => resolve(true))
          .onCancel(() => resolve(false))
      })
      if (!ok) return

      try {
        await this.$axios.put(`/ventasAnular/${venta.venta_id}`)
        this.$alert.success('Venta anulada')
        await this.cargarVentas()
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo anular la venta')
      }
    },
    async generarFacturaTodos() {
      const ok = await new Promise((resolve) => {
        this.$alert.dialog('Seguro que quiere facturar todas las ventas pendientes del rango?')
          .onOk(() => resolve(true))
          .onCancel(() => resolve(false))
      })
      if (!ok) return

      this.loadingFacturar = true
      try {
        const res = await this.$axios.post('/digitador-factura/generar-factura-todos', {
          fecha_inicio: this.fechaInicio,
          fecha_fin: this.fechaFin,
        })
        const okCount = Number((res && res.data && res.data.facturadas) || 0)
        const errCount = Number((res && res.data && res.data.errores) || 0)
        this.$alert.success(`${(res && res.data && res.data.message) || 'Proceso ejecutado'} | Facturadas: ${okCount} | Errores: ${errCount}`)
        await this.cargarVentas()
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo marcar facturacion masiva')
      } finally {
        this.loadingFacturar = false
      }
    },
    async descargarPdf(url, loadingKey) {
      this[loadingKey] = true
      try {
        const res = await this.$axios.get(url, {
          params: {
            fecha_inicio: this.fechaInicio,
            fecha_fin: this.fechaFin,
          },
          responseType: 'blob',
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const disposition = (res && res.headers && res.headers['content-disposition']) || ''
        const match = disposition.match(/filename="?([^"]+)"?/)
        const fileName = (match && match[1]) || 'reporte.pdf'
        const link = document.createElement('a')
        const fileUrl = window.URL.createObjectURL(blob)
        link.href = fileUrl
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(fileUrl)
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo generar el PDF')
      } finally {
        this[loadingKey] = false
      }
    },
    async descargarPdfIndividual(url) {
      try {
        const res = await this.$axios.get(url, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const disposition = (res && res.headers && res.headers['content-disposition']) || ''
        const match = disposition.match(/filename="?([^"]+)"?/)
        const fileName = (match && match[1]) || 'documento.pdf'
        const link = document.createElement('a')
        const fileUrl = window.URL.createObjectURL(blob)
        link.href = fileUrl
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(fileUrl)
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo generar el PDF')
      }
    },
    async imprimirFacturaIndividual(venta) {
      await this.descargarPdfIndividual(`/digitador-factura/reportes/facturas/${venta.venta_id}`)
    },
    async imprimirVoucherIndividual(venta) {
      await this.descargarPdfIndividual(`/digitador-factura/reportes/vouchers/${venta.venta_id}`)
    },
    async compartirPdf(url, fallbackName) {
      try {
        const res = await this.$axios.get(url, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const disposition = (res && res.headers && res.headers['content-disposition']) || ''
        const match = disposition.match(/filename="?([^"]+)"?/)
        const fileName = (match && match[1]) || fallbackName

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
        this.$alert.error('Tu navegador no permite compartir PDF directo. Se descargo el archivo para enviarlo por WhatsApp.')
      } catch (e) {
        this.$alert.error((e && e.response && e.response.data && e.response.data.message) || 'No se pudo preparar el PDF para compartir')
      }
    },
    async mandarWhatsappFactura(venta) {
      await this.compartirPdf(`/digitador-factura/reportes/facturas/${venta.venta_id}`, `factura_venta_${venta.venta_id}.pdf`)
    },
    async mandarWhatsappVoucher(venta) {
      await this.compartirPdf(`/digitador-factura/reportes/vouchers/${venta.venta_id}`, `voucher_venta_${venta.venta_id}.pdf`)
    },
    async imprimirFacturasMasivo() {
      await this.descargarPdf('/digitador-factura/reportes/facturas', 'loadingPrintFacturas')
    },
    async imprimirVouchersMasivo() {
      await this.descargarPdf('/digitador-factura/reportes/vouchers', 'loadingPrintVouchers')
    },
  },
}
</script>

<style scoped>
.misped-page {
  background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 30%, #ffffff 100%);
}
.hero-card {
  border: 1px solid #dbe7ff;
  background:
    radial-gradient(1200px 280px at 10% 0%, rgba(30, 136, 229, 0.12), transparent 70%),
    radial-gradient(1000px 240px at 95% 20%, rgba(102, 187, 106, 0.12), transparent 70%),
    #fff;
}
</style>

