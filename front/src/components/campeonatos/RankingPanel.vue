<template>
  <div class="ranking-root" :class="{ 'mode-light': isLightMode }">
    <q-card flat bordered class="bg-dark-card q-mb-md" :class="{ 'text-white': !isLightMode }">
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-md-4">
          <q-select
            v-model="metric"
            dense
            outlined
            emit-value
            map-options
            option-value="value"
            option-label="label"
            :options="metricOptions"
            label="Ordenar por"
          />
        </div>
        <div class="col-12 col-md-4">
          <q-select
            v-model="equipoFilter"
            dense
            outlined
            emit-value
            map-options
            option-value="id"
            option-label="nombre"
            :options="equipoOptions"
            label="Filtrar equipo"
          />
        </div>
        <div class="col-12 col-md-4">
          <q-input v-model="search" dense outlined label="Buscar jugador">
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
      </q-card-section>
      <q-separator :dark="!isLightMode" />
      <q-card-section class="row q-col-gutter-sm">
        <div class="col-6 col-md-2" v-for="chip in resumenChips" :key="chip.key">
          <q-chip class="full-width justify-center" dense :color="chip.color" text-color="white" :icon="chip.icon">
            {{ chip.label }}: {{ resumen?.[chip.key] || 0 }}
          </q-chip>
        </div>
      </q-card-section>
    </q-card>

    <q-table
      :rows="sortedRows"
      :columns="columns"
      row-key="key"
      flat
      bordered
      dense
      :loading="loading"
      :rows-per-page-options="[0]"
      no-data-label="Sin datos de ranking"
      class="bg-dark-card"
      :class="{ 'text-white': !isLightMode }"
    >
      <template #body-cell-pos="props">
        <q-td :props="props" class="text-center">
          <q-badge color="indigo" rounded>{{ props.pageIndex + 1 }}</q-badge>
        </q-td>
      </template>
      <template #body-cell-jugador="props">
        <q-td :props="props">
          <div class="text-weight-medium">{{ props.row.jugador || 'Sin jugador' }}</div>
          <div class="text-caption text-grey-5">{{ props.row.equipo || 'Sin equipo' }}</div>
        </q-td>
      </template>
      <template #body-cell-total="props">
        <q-td :props="props" class="text-center">
          <q-chip dense color="primary" text-color="white">{{ props.row.total }}</q-chip>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script>
export default {
  name: 'RankingPanel',
  props: {
    code: { type: String, required: true },
    campeonato: { type: Object, default: () => ({}) },
    isLightMode: { type: Boolean, default: false }
  },
  data () {
    return {
      loading: false,
      rows: [],
      resumen: {},
      search: '',
      metric: 'goles',
      equipoFilter: 0,
      metricOptions: [
        { label: 'Goles', value: 'goles' },
        { label: 'Faltas', value: 'faltas' },
        { label: 'Amarillas', value: 'amarillas' },
        { label: 'Rojas', value: 'rojas' },
        { label: 'Sustituciones', value: 'sustituciones' },
        { label: 'Porteros', value: 'porteros' },
        { label: 'Total', value: 'total' }
      ],
      columns: [
        { name: 'pos', label: '#', align: 'center', field: 'pos' },
        { name: 'jugador', label: 'Jugador', align: 'left', field: 'jugador' },
        { name: 'goles', label: 'Goles', align: 'center', field: 'goles' },
        { name: 'faltas', label: 'Faltas', align: 'center', field: 'faltas' },
        { name: 'amarillas', label: 'Amarillas', align: 'center', field: 'amarillas' },
        { name: 'rojas', label: 'Rojas', align: 'center', field: 'rojas' },
        { name: 'sustituciones', label: 'Sustit.', align: 'center', field: 'sustituciones' },
        { name: 'porteros', label: 'Portero', align: 'center', field: 'porteros' },
        { name: 'total', label: 'Total', align: 'center', field: 'total' }
      ]
    }
  },
  computed: {
    equipoOptions () {
      const base = [{ id: 0, nombre: 'Todos' }]
      const equipos = (this.campeonato?.equipos || []).map(e => ({ id: Number(e.id), nombre: e.nombre }))
      return base.concat(equipos)
    },
    filteredRows () {
      const txt = (this.search || '').toLowerCase().trim()
      return (this.rows || []).filter(r => {
        if (this.equipoFilter && Number(r.equipo_id) !== Number(this.equipoFilter)) return false
        if (!txt) return true
        const hay = `${r.jugador || ''} ${r.equipo || ''}`.toLowerCase()
        return hay.includes(txt)
      })
    },
    sortedRows () {
      const key = this.metric || 'goles'
      return [...this.filteredRows]
        .sort((a, b) => {
          if ((a[key] || 0) !== (b[key] || 0)) return (b[key] || 0) - (a[key] || 0)
          if ((a.total || 0) !== (b.total || 0)) return (b.total || 0) - (a.total || 0)
          return (a.jugador || '').localeCompare(b.jugador || '')
        })
        .map((r, idx) => ({ ...r, key: `${r.jugador_id || 0}-${r.equipo_id || 0}-${idx}` }))
    },
    resumenChips () {
      return [
        { key: 'goles', label: 'Goles', color: 'green', icon: 'sports_soccer' },
        { key: 'faltas', label: 'Faltas', color: 'blue-grey', icon: 'sports' },
        { key: 'amarillas', label: 'Amarillas', color: 'amber-8', icon: 'style' },
        { key: 'rojas', label: 'Rojas', color: 'red', icon: 'warning' },
        { key: 'sustituciones', label: 'Sustit.', color: 'indigo', icon: 'swap_horiz' },
        { key: 'porteros', label: 'Porteros', color: 'teal', icon: 'sports_handball' }
      ]
    }
  },
  mounted () {
    this.loadRanking()
  },
  watch: {
    code () {
      this.loadRanking()
    }
  },
  methods: {
    loadRanking () {
      this.loading = true
      this.$axios.get(`public/campeonatos/${this.code}/ranking`)
        .then(r => {
          this.rows = r.data?.rows || []
          this.resumen = r.data?.resumen || {}
        })
        .catch(e => {
          this.rows = []
          this.resumen = {}
          this.$alert.error(e.response?.data?.message || 'No se pudo cargar ranking')
        })
        .finally(() => { this.loading = false })
    }
  }
}
</script>

<style scoped>
.bg-dark-card {
  background: #0b1220;
  border-color: rgba(148, 163, 184, 0.25);
}
.mode-light .bg-dark-card {
  background: #ffffff;
  border-color: #dbe4f0;
}
</style>

