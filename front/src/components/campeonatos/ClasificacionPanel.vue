<template>
  <div class="row q-col-gutter-md clasif-root" :class="{ 'mode-light': isLightMode }">
    <div class="col-12 col-lg-8">
      <q-card flat bordered class="bg-dark-card q-mb-md" :class="{ 'text-white': !isLightMode }">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-select
              v-model="faseId"
              dense
              outlined
              :dark="!isLightMode"
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="fases"
              label="Fase"
            />
          </div>
          <div class="col-12 col-md-4 row items-center q-gutter-sm">
            <span>Clasificacion por grupo</span>
            <q-toggle
              :model-value="activeFase?.agrupar_por_grupo"
              color="positive"
              :disable="!canEdit || !activeFase"
              @update:model-value="toggleAgrupar"
            />
          </div>
          <div class="col-12 col-md-4 text-right">
            <q-btn v-if="canEdit" color="indigo" icon="add" no-caps label="Nueva fase" @click="faseDialog = true" />
          </div>
        </q-card-section>
      </q-card>

      <q-card
        v-for="group in tabla"
        :key="group.grupo"
        flat
        bordered
        class="bg-dark-card q-mb-md"
        :class="{ 'text-white': !isLightMode }"
      >
        <q-card-section class="text-center text-subtitle1 text-weight-bold">{{ group.grupo }}</q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-markup-table dense flat :dark="!isLightMode" class="table-standings">
          <thead>
            <tr>
              <th class="text-left">Pos</th>
              <th class="text-left">Equipos</th>
              <th class="text-center">Pts</th>
              <th class="text-center">J</th>
              <th class="text-center">G</th>
              <th class="text-center">E</th>
              <th class="text-center">P</th>
              <th class="text-center">GF</th>
              <th class="text-center">GC</th>
              <th class="text-center">DIF</th>
              <th class="text-center">%</th>
              <th class="text-center">PE</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, idx) in group.rows" :key="r.equipo_id">
              <td class="text-left">{{ idx + 1 }}</td>
              <td class="text-left">{{ r.equipo }}</td>
              <td class="text-center">{{ r.pts }}</td>
              <td class="text-center">{{ r.pj }}</td>
              <td class="text-center">{{ r.pg }}</td>
              <td class="text-center">{{ r.pe }}</td>
              <td class="text-center">{{ r.pp }}</td>
              <td class="text-center">{{ r.gf }}</td>
              <td class="text-center">{{ r.gc }}</td>
              <td class="text-center">{{ r.dif }}</td>
              <td class="text-center">{{ r.porcentaje }}</td>
              <td class="text-center">{{ r.pe }}</td>
            </tr>
          </tbody>
        </q-markup-table>
      </q-card>
    </div>

    <div class="col-12 col-lg-4">
      <q-card flat bordered class="bg-dark-card" :class="{ 'text-white': !isLightMode }">
        <q-card-section class="row items-center">
          <div class="text-h6 text-weight-bold">Juegos</div>
          <q-space />
          <q-select
            v-model="faseId"
            dense
            outlined
            :dark="!isLightMode"
            emit-value
            map-options
            option-value="id"
            option-label="nombre"
            :options="fases"
            style="min-width: 140px"
          />
        </q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-card-section class="row q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-btn
              v-if="canEdit"
              class="full-width"
              color="primary"
              no-caps
              label="Generar partidos"
              @click="genDialog = true"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-btn
              v-if="canEdit"
              class="full-width"
              color="deep-purple"
              no-caps
              label="Agregar fecha"
              @click="agregarFecha"
            />
          </div>
          <div class="col-12">
            <q-btn
              v-if="canEdit"
              class="full-width"
              color="teal"
              no-caps
              label="Agregar partido"
              @click="partidoDialog = true"
            />
          </div>
        </q-card-section>

        <q-separator :dark="!isLightMode" />
        <q-list separator :dark="!isLightMode" class="match-list">
          <q-item v-for="p in activePartidos" :key="p.id">
            <q-item-section>
              <q-item-label>{{ p.local?.nombre || '?' }} vs {{ p.visita?.nombre || '?' }}</q-item-label>
              <q-item-label caption>{{ p.grupo_nombre || 'General' }} - {{ p.fecha?.nombre || 'Sin fecha' }}</q-item-label>
            </q-item-section>
            <q-item-section side class="row items-center q-gutter-xs" v-if="canEdit">
              <q-input v-model.number="p.goles_local" dense outlined :dark="!isLightMode" type="number" min="0" style="width:56px" />
              <span>-</span>
              <q-input v-model.number="p.goles_visita" dense outlined :dark="!isLightMode" type="number" min="0" style="width:56px" />
              <q-btn dense flat color="positive" icon="save" @click="guardarResultado(p)" />
            </q-item-section>
            <q-item-section side v-else>
              <q-chip dense color="grey-8">{{ p.goles_local ?? '-' }} - {{ p.goles_visita ?? '-' }}</q-chip>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-dialog v-model="genDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw">
        <q-card-section class="text-h6">Generar partidos</q-card-section>
        <q-card-section>
          <q-option-group
            v-model="genMode"
            type="radio"
            :options="[
              { label: 'Solo ida', value: 'ida' },
              { label: 'Ida y vuelta', value: 'ida_vuelta' }
            ]"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="genDialog = false" />
          <q-btn color="primary" no-caps label="Continuar" @click="generarPartidos" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="faseDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw">
        <q-card-section class="text-h6">Nueva fase</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input v-model="faseForm.nombre" dense outlined label="Nombre fase" />
          <q-select
            v-model="faseForm.tipo"
            dense
            outlined
            emit-value
            map-options
            option-value="value"
            option-label="label"
            :options="[
              { label: 'Todos contra todos', value: 'liga' },
              { label: 'Eliminatoria', value: 'eliminatoria' }
            ]"
            label="Tipo"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="faseDialog = false" />
          <q-btn color="primary" no-caps label="Guardar" @click="guardarFase" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="partidoDialog" persistent>
      <q-card style="width: 560px; max-width: 95vw">
        <q-card-section class="text-h6">Agregar partido</q-card-section>
        <q-card-section class="row q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.campeonato_fecha_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="activeFase?.fechas || []"
              label="Fecha (opcional)"
              clearable
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.grupo_nombre"
              dense
              outlined
              emit-value
              map-options
              option-value="value"
              option-label="label"
              :options="groupOptions"
              label="Grupo"
              clearable
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.local_equipo_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="teamOptions"
              label="Local"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.visita_equipo_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="teamOptions"
              label="Visita"
            />
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="partidoDialog = false" />
          <q-btn color="primary" no-caps label="Guardar" @click="guardarPartido" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
export default {
  name: 'ClasificacionPanel',
  props: {
    code: { type: String, required: true },
    campeonato: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
    isLightMode: { type: Boolean, default: false }
  },
  data () {
    return {
      fases: [],
      faseId: null,
      genDialog: false,
      genMode: 'ida',
      faseDialog: false,
      partidoDialog: false,
      faseForm: {
        nombre: 'Nueva fase',
        tipo: 'liga'
      },
      partidoForm: {
        campeonato_fecha_id: null,
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null
      }
    }
  },
  computed: {
    activeFase () {
      return this.fases.find(f => Number(f.id) === Number(this.faseId)) || null
    },
    tabla () {
      return this.activeFase?.tabla || []
    },
    activePartidos () {
      return this.activeFase?.partidos || []
    },
    teamOptions () {
      return (this.campeonato?.equipos || []).map(e => ({ id: e.id, nombre: e.nombre }))
    },
    groupOptions () {
      return (this.campeonato?.grupos || []).map(g => ({ value: g.nombre, label: g.nombre }))
    }
  },
  watch: {
    code () {
      this.cargar()
    }
  },
  mounted () {
    this.cargar()
  },
  methods: {
    cargar () {
      this.$axios.get(`public/campeonatos/${this.code}/clasificacion`)
        .then(r => {
          const prevId = this.faseId
          this.fases = r.data || []
          if (!this.fases.length) return
          const exists = this.fases.some(f => Number(f.id) === Number(prevId))
          this.faseId = exists ? prevId : this.fases[0].id
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar clasificacion'))
    },
    toggleAgrupar (value) {
      if (!this.activeFase || !this.canEdit) return
      this.$axios.put(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}`, {
        nombre: this.activeFase.nombre,
        tipo: this.activeFase.tipo,
        agrupar_por_grupo: !!value
      })
        .then(() => this.cargar())
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar fase'))
    },
    guardarFase () {
      if (!this.faseForm.nombre.trim()) {
        this.$alert.error('Nombre de fase requerido')
        return
      }
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases`, {
        nombre: this.faseForm.nombre.trim(),
        tipo: this.faseForm.tipo,
        agrupar_por_grupo: true
      })
        .then(() => {
          this.faseDialog = false
          this.$alert.success('Fase creada')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo crear fase'))
    },
    agregarFecha () {
      if (!this.activeFase) return
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}/fechas`)
        .then(() => {
          this.$alert.success('Fecha agregada')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo agregar fecha'))
    },
    generarPartidos () {
      if (!this.activeFase) return
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}/generar-partidos`, {
        modo: this.genMode
      })
        .then(() => {
          this.genDialog = false
          this.$alert.success('Partidos generados')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo generar partidos'))
    },
    guardarPartido () {
      if (!this.activeFase) return
      if (this.partidoForm.local_equipo_id && this.partidoForm.local_equipo_id === this.partidoForm.visita_equipo_id) {
        this.$alert.error('Local y visita no pueden ser el mismo equipo')
        return
      }
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}/partidos`, this.partidoForm)
        .then(() => {
          this.partidoDialog = false
          this.partidoForm = {
            campeonato_fecha_id: null,
            local_equipo_id: null,
            visita_equipo_id: null,
            grupo_nombre: null
          }
          this.$alert.success('Partido agregado')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo agregar partido'))
    },
    guardarResultado (p) {
      this.$axios.put(`campeonatos/${this.campeonato.id}/partidos/${p.id}`, {
        campeonato_fecha_id: p.campeonato_fecha_id,
        local_equipo_id: p.local_equipo_id,
        visita_equipo_id: p.visita_equipo_id,
        grupo_nombre: p.grupo_nombre,
        goles_local: p.goles_local,
        goles_visita: p.goles_visita,
        estado: (p.goles_local !== null && p.goles_visita !== null) ? 'jugado' : 'pendiente'
      })
        .then(() => {
          this.$alert.success('Resultado guardado')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar resultado'))
    }
  }
}
</script>

<style scoped>
.bg-dark-card {
  background: #0b1220;
  border-color: rgba(148, 163, 184, 0.25);
}
.table-standings th,
.table-standings td {
  font-size: 12px;
}
.match-list {
  max-height: 560px;
  overflow-y: auto;
}
.clasif-root.mode-light .bg-dark-card {
  background: #ffffff;
  color: #0f172a !important;
  border-color: #dbe4f0;
}
.clasif-root.mode-light .table-standings {
  color: #0f172a !important;
}
</style>
