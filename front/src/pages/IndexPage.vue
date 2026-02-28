<template>
  <q-page class="q-pa-md dashboard-page">
    <div class="row items-center q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold">Inicio - Torneo Ya</div>
        <div class="text-caption text-grey-6">Resumen de tu cuenta y actividad reciente</div>
      </div>
      <q-space />
      <q-btn color="primary" no-caps icon="refresh" label="Actualizar" :loading="loading" @click="loadDashboard" />
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-6 col-md-3" v-for="card in metricCards" :key="card.key">
        <q-card flat bordered class="metric-card">
          <q-card-section class="row items-center q-gutter-sm">
            <q-avatar :color="card.color" text-color="white" icon="insights" />
            <div>
              <div class="text-caption text-grey-7">{{ card.label }}</div>
              <div class="text-h6 text-weight-bold">{{ card.value }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-lg-6">
        <q-card flat bordered>
          <q-card-section class="text-subtitle1 text-weight-bold">Distribucion Por Deporte</q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart type="donut" height="300" :options="deporteChartOptions" :series="deporteSeries" />
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-lg-6">
        <q-card flat bordered>
          <q-card-section class="text-subtitle1 text-weight-bold">Campeonatos Creados (6 meses)</q-card-section>
          <q-separator />
          <q-card-section>
            <apexchart type="bar" height="300" :options="mesChartOptions" :series="mesSeries" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <div class="col-12 col-lg-6">
        <q-card flat bordered>
          <q-card-section class="text-subtitle1 text-weight-bold">Ultimos Campeonatos</q-card-section>
          <q-separator />
          <q-list separator>
            <q-item v-for="c in recientesCampeonatos" :key="c.id">
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ c.nombre }}</q-item-label>
                <q-item-label caption>
                  {{ c.tipo }} | {{ c.codigo }} | {{ deporteNombre(c.deporte) }} | {{ formatDate(c.created_at) }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="!recientesCampeonatos.length"><q-item-section class="text-grey-6">Sin registros</q-item-section></q-item>
          </q-list>
        </q-card>
      </div>
      <div class="col-12 col-lg-6">
        <q-card flat bordered>
          <q-card-section class="text-subtitle1 text-weight-bold">Ultimos Partidos</q-card-section>
          <q-separator />
          <q-list separator>
            <q-item v-for="p in recientesPartidos" :key="p.id">
              <q-item-section>
                <q-item-label class="text-weight-medium">
                  {{ p.local?.nombre || 'Pendiente' }} {{ score(p) }} {{ p.visita?.nombre || 'Pendiente' }}
                </q-item-label>
                <q-item-label caption>
                  {{ p.fase?.nombre || '-' }} | {{ p.estado || 'no_realizado' }} | {{ p.programado_at ? formatDateTime(p.programado_at) : 'Sin fecha' }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="!recientesPartidos.length"><q-item-section class="text-grey-6">Sin registros</q-item-section></q-item>
          </q-list>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'IndexPage',
  data () {
    return {
      loading: false,
      totals: {},
      incidencias: {},
      deportes: [],
      porMes: [],
      recientesCampeonatos: [],
      recientesPartidos: []
    }
  },
  computed: {
    metricCards () {
      return [
        { key: 'campeonatos', label: 'Campeonatos', value: this.totals.campeonatos || 0, color: 'indigo' },
        { key: 'categorias', label: 'Categorias', value: this.totals.categorias || 0, color: 'deep-orange' },
        { key: 'equipos', label: 'Equipos', value: this.totals.equipos || 0, color: 'teal' },
        { key: 'jugadores', label: 'Jugadores', value: this.totals.jugadores || 0, color: 'green' },
        { key: 'partidos', label: 'Partidos', value: this.totals.partidos || 0, color: 'blue' },
        { key: 'mensajes', label: 'Mensajes', value: this.totals.mensajes || 0, color: 'purple' },
        { key: 'incidencias', label: 'Incidencias', value: this.totals.incidencias || 0, color: 'brown' }
      ]
    },
    deporteSeries () {
      return (this.deportes || []).map(d => d.total)
    },
    deporteChartOptions () {
      return {
        labels: (this.deportes || []).map(d => d.nombre),
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
      }
    },
    mesSeries () {
      return [{ name: 'Campeonatos', data: (this.porMes || []).map(m => m.value) }]
    },
    mesChartOptions () {
      return {
        xaxis: { categories: (this.porMes || []).map(m => m.label) },
        chart: { toolbar: { show: false } },
        dataLabels: { enabled: false }
      }
    }
  },
  mounted () {
    this.loadDashboard()
  },
  methods: {
    loadDashboard () {
      this.loading = true
      this.$axios.get('dashboard')
        .then(r => {
          this.totals = r.data?.totals || {}
          this.incidencias = r.data?.incidencias || {}
          this.deportes = r.data?.deportes || []
          this.porMes = r.data?.campeonatos_por_mes || []
          this.recientesCampeonatos = r.data?.recientes_campeonatos || []
          this.recientesPartidos = r.data?.recientes_partidos || []
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar dashboard'))
        .finally(() => { this.loading = false })
    },
    score (p) {
      if (p.goles_local === null || p.goles_visita === null) return 'vs'
      return `${p.goles_local}:${p.goles_visita}`
    },
    formatDate (v) {
      if (!v) return '-'
      return new Date(v).toLocaleDateString()
    },
    formatDateTime (v) {
      if (!v) return '-'
      return new Date(v).toLocaleString()
    },
    deporteNombre (key) {
      if (!key) return 'Sin deporte'
      const d = (this.deportes || []).find(x => x.deporte === key)
      return d?.nombre || key
    }
  }
}
</script>

<style scoped>
.metric-card {
  min-height: 86px;
}
</style>
