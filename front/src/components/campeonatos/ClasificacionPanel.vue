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
            <q-btn v-if="canEdit" color="indigo" icon="add_circle" no-caps label="Nueva fase" @click="abrirFaseDialog" :loading="btnLoading.fase" />
          </div>
        </q-card-section>
      </q-card>

      <template v-if="isEliminatoria">
        <q-card
          v-for="group in tabla"
          :key="group.grupo"
          flat
          bordered
          class="bg-dark-card q-mb-md"
          :class="{ 'text-white': !isLightMode }"
        >
          <q-card-section class="text-subtitle1 text-weight-bold">{{ group.grupo }}</q-card-section>
          <q-separator :dark="!isLightMode" />
          <q-list separator :dark="!isLightMode">
            <q-item v-for="r in group.rows" :key="r.equipo_id">
              <q-item-section avatar>
                <q-avatar rounded size="36px">
                  <q-img :src="equipoLogo(r.equipo_id)" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ r.equipo }}</q-item-label>
                <q-item-label caption>
                  Ganados: {{ r.pg }} | Empatados: {{ r.pe }} | Perdidos: {{ r.pp }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-chip dense color="indigo-2" text-color="indigo-10">PJ {{ r.pj }}</q-chip>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>

      <template v-else>
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
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, idx) in group.rows" :key="r.equipo_id">
                <td class="text-left">{{ idx + 1 }}</td>
                <td class="text-left">
                  <div class="row items-center no-wrap q-gutter-xs">
                    <q-avatar rounded size="24px">
                      <q-img :src="equipoLogo(r.equipo_id)" />
                    </q-avatar>
                    <span>{{ r.equipo }}</span>
                  </div>
                </td>
                <td class="text-center">{{ r.pts }}</td>
                <td class="text-center">{{ r.pj }}</td>
                <td class="text-center">{{ r.pg }}</td>
                <td class="text-center">{{ r.pe }}</td>
                <td class="text-center">{{ r.pp }}</td>
                <td class="text-center">{{ r.gf }}</td>
                <td class="text-center">{{ r.gc }}</td>
                <td class="text-center">{{ r.dif }}</td>
                <td class="text-center">{{ r.porcentaje }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card>
      </template>
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
            style="min-width: 160px"
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
              icon="auto_awesome"
              label="Generar partidos"
              @click="genDialog = true"
              :loading="btnLoading.generar"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-btn
              v-if="canEdit"
              class="full-width"
              color="teal"
              no-caps
              icon="sports_soccer"
              label="Agregar partido"
              @click="abrirPartidoDialog"
              :loading="btnLoading.partido"
            />
          </div>
        </q-card-section>

        <q-separator :dark="!isLightMode" />
        <div class="match-list q-pa-sm">
          <q-card
            v-for="p in activePartidos"
            :key="p.id"
            flat
            bordered
            class="q-mb-sm match-card"
            :class="{ editable: canEdit }"
            @dblclick="openEditPartido(p)"
          >
            <q-card-section class="q-pa-sm">
              <div class="row items-center q-col-gutter-sm">
                <div class="col-5 text-center">
                  <q-avatar rounded size="46px">
                    <q-img :src="equipoLogo(p.local_equipo_id, p.local?.imagen)" />
                  </q-avatar>
                  <div class="text-caption q-mt-xs">{{ p.local?.nombre || 'Pendiente' }}</div>
                </div>
                <div class="col-2 text-center">
                  <div class="match-score text-weight-bold">{{ formatScore(p) }}</div>
                  <div class="match-mid-card q-mt-xs" :class="estadoClass(p.estado)">
                    <div class="text-caption text-weight-medium">{{ estadoLabel(p.estado) }}</div>
                    <div class="text-caption">{{ p.grupo_nombre || 'General' }}</div>
                    <div class="text-caption">Sin fecha</div>
                  </div>
                </div>
                <div class="col-5 text-center">
                  <q-avatar rounded size="46px">
                    <q-img :src="equipoLogo(p.visita_equipo_id, p.visita?.imagen)" />
                  </q-avatar>
                  <div class="text-caption q-mt-xs">{{ p.visita?.nombre || 'Pendiente' }}</div>
                </div>
              </div>
              <div class="row items-center q-gutter-xs q-mt-sm">
                <q-space />
                <q-btn
                  v-if="canEdit"
                  dense
                  flat
                  round
                  icon="edit"
                  color="primary"
                  @click="openEditPartido(p)"
                  :loading="btnLoading.editar"
                />
              </div>
            </q-card-section>
          </q-card>
          <div v-if="!activePartidos.length" class="text-caption text-grey-6 q-pa-sm">No hay partidos en esta fase</div>
        </div>
      </q-card>
    </div>

    <div class="col-12">
      <q-card flat bordered class="bg-dark-card" :class="{ 'text-white': !isLightMode }">
        <q-card-section class="text-subtitle1 text-weight-bold">Comentarios</q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-10">
              <q-input
                v-model="newMessage"
                type="textarea"
                autogrow
                outlined
                :dark="!isLightMode"
                label="Escribe un comentario"
              />
            </div>
            <div class="col-12 col-md-2 flex flex-center">
              <q-btn color="deep-orange" no-caps icon="send" label="Enviar" class="full-width" @click="sendMessage" :loading="sendingMessage" />
            </div>
          </div>
        </q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-list separator :dark="!isLightMode">
          <q-item v-for="m in mensajes" :key="m.id">
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ m.user?.name || m.user?.username || m.autor_nombre || 'Publico' }}</q-item-label>
              <q-item-label caption>{{ formatDateTime(m.created_at) }}</q-item-label>
              <q-item-label class="q-mt-xs">{{ m.mensaje }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!mensajes?.length">
            <q-item-section class="text-grey-6">Sin comentarios aun</q-item-section>
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
          <q-btn color="primary" no-caps label="Continuar" @click="generarPartidos" :loading="btnLoading.generar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="faseDialog" persistent>
      <q-card style="width: 440px; max-width: 95vw">
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
          <q-btn color="primary" no-caps label="Guardar" @click="guardarFase" :loading="btnLoading.fase" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="partidoDialog" persistent>
      <q-card style="width: 620px; max-width: 95vw">
        <q-card-section class="text-h6">Agregar partido</q-card-section>
        <q-card-section class="row q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.campeonato_fase_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="fases"
              label="Fase"
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
              v-model="partidoForm.estado"
              dense
              outlined
              emit-value
              map-options
              option-value="value"
              option-label="label"
              :options="estadoOptions"
              label="Estado"
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
              :options="filteredTeamOptionsCreate"
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
              :options="filteredTeamOptionsCreate"
              label="Visita"
            />
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="partidoDialog = false" />
          <q-btn color="primary" no-caps label="Guardar" @click="guardarPartido" :loading="btnLoading.partido" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="editPartidoDialog" persistent>
      <q-card style="width: 680px; max-width: 95vw">
        <q-card-section class="row items-center">
          <div class="text-h6">Editar partido</div>
          <q-space />
          <q-btn dense flat round icon="close" @click="editPartidoDialog = false" />
        </q-card-section>
        <q-card-section class="row q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-select
              v-model="editPartidoForm.grupo_nombre"
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
              v-model="editPartidoForm.local_equipo_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="filteredTeamOptionsEdit"
              label="Local"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="editPartidoForm.visita_equipo_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="filteredTeamOptionsEdit"
              label="Visita"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-input v-model.number="editPartidoForm.goles_local" dense outlined type="number" min="0" label="Goles local" />
          </div>
          <div class="col-12 col-md-4">
            <q-input v-model.number="editPartidoForm.goles_visita" dense outlined type="number" min="0" label="Goles visita" />
          </div>
          <div class="col-12 col-md-4">
            <q-select
              v-model="editPartidoForm.estado"
              dense
              outlined
              emit-value
              map-options
              option-value="value"
              option-label="label"
              :options="estadoOptions"
              label="Estado"
            />
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            v-if="canEdit && editPartidoForm.id"
            flat
            no-caps
            color="negative"
            icon="delete"
            label="Eliminar"
            @click="eliminarPartido"
          />
          <q-space />
          <q-btn flat no-caps label="Cancelar" @click="editPartidoDialog = false" />
          <q-btn color="primary" no-caps icon="save" label="Guardar" @click="guardarEdicionPartido" :loading="btnLoading.editar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
const PHASE_NAMES = [
  'Primera fase',
  'Segunda fase',
  'Tercera fase',
  'Cuarta fase',
  'Quinta fase',
  'Sexta fase',
  'Septima fase',
  'Octava fase',
  'Novena fase',
  'Decima fase'
]

export default {
  name: 'ClasificacionPanel',
  props: {
    code: { type: String, required: true },
    campeonato: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
    isLightMode: { type: Boolean, default: false },
    mensajes: { type: Array, default: () => [] }
  },
  data () {
    return {
      fases: [],
      faseId: null,
      genDialog: false,
      genMode: 'ida',
      faseDialog: false,
      partidoDialog: false,
      editPartidoDialog: false,
      faseForm: {
        nombre: 'Primera fase',
        tipo: 'liga'
      },
      partidoForm: {
        campeonato_fase_id: null,
        campeonato_fecha_id: null,
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null,
        estado: 'no_realizado'
      },
      editPartidoForm: {
        id: null,
        campeonato_fecha_id: null,
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null,
        goles_local: null,
        goles_visita: null,
        estado: 'no_realizado'
      },
      estadoOptions: [
        { label: 'No realizado', value: 'no_realizado' },
        { label: 'En vivo', value: 'en_vivo' },
        { label: 'Finalizado', value: 'finalizado' }
      ],
      btnLoading: {
        fase: false,
        generar: false,
        partido: false,
        editar: false
      },
      newMessage: '',
      sendingMessage: false
    }
  },
  computed: {
    activeFase () {
      return this.fases.find(f => Number(f.id) === Number(this.faseId)) || null
    },
    tabla () {
      return this.activeFase?.tabla || []
    },
    isEliminatoria () {
      return this.activeFase?.tipo === 'eliminatoria'
    },
    activePartidos () {
      return this.activeFase?.partidos || []
    },
    allTeamOptions () {
      return (this.campeonato?.equipos || []).map(e => ({
        id: e.id,
        nombre: e.nombre,
        imagen: e.imagen || 'torneoImagen.jpg',
        grupo_nombre: e.grupo?.nombre || e.grupo_nombre || null
      }))
    },
    filteredTeamOptionsCreate () {
      if (!this.partidoForm.grupo_nombre) return this.allTeamOptions
      return this.allTeamOptions.filter(t => (t.grupo_nombre || 'Sin grupo') === this.partidoForm.grupo_nombre)
    },
    filteredTeamOptionsEdit () {
      if (!this.editPartidoForm.grupo_nombre) return this.allTeamOptions
      return this.allTeamOptions.filter(t => (t.grupo_nombre || 'Sin grupo') === this.editPartidoForm.grupo_nombre)
    },
    groupOptions () {
      return (this.campeonato?.grupos || []).map(g => ({ value: g.nombre, label: g.nombre }))
    }
  },
  watch: {
    code () {
      this.cargar()
    },
    faseId (val) {
      this.partidoForm.campeonato_fase_id = val
    },
    'partidoForm.grupo_nombre' () {
      this.cleanInvalidTeams()
    },
    'editPartidoForm.grupo_nombre' () {
      this.cleanInvalidTeamsEdit()
    }
  },
  mounted () {
    this.cargar()
  },
  methods: {
    imageSrc (name) {
      return `${this.$url}../../images/${name || 'torneoImagen.jpg'}`
    },
    equipoLogo (equipoId, fallback = null) {
      const eq = (this.campeonato?.equipos || []).find(e => Number(e.id) === Number(equipoId))
      return this.imageSrc(eq?.imagen || fallback || 'torneoImagen.jpg')
    },
    formatScore (partido) {
      if (partido.goles_local === null || partido.goles_visita === null) return 'vs'
      return `${partido.goles_local} - ${partido.goles_visita}`
    },
    formatDateTime (value) {
      if (!value) return ''
      return new Date(value).toLocaleString()
    },
    estadoLabel (estado) {
      const map = {
        no_realizado: 'No realizado',
        en_vivo: 'En vivo',
        finalizado: 'Finalizado',
        jugado: 'Finalizado',
        pendiente: 'No realizado'
      }
      return map[estado] || 'No realizado'
    },
    estadoClass (estado) {
      if (estado === 'en_vivo') return 'estado-vivo'
      if (estado === 'finalizado' || estado === 'jugado') return 'estado-finalizado'
      return 'estado-no-realizado'
    },
    estadoColor (estado) {
      if (estado === 'en_vivo') return 'amber-6'
      if (estado === 'finalizado' || estado === 'jugado') return 'positive'
      return 'grey-5'
    },
    estadoTextColor (estado) {
      return estado === 'en_vivo' ? 'black' : 'white'
    },
    defaultFaseName () {
      const index = this.fases.length
      if (PHASE_NAMES[index]) return PHASE_NAMES[index]
      return `Fase ${index + 1}`
    },
    cleanInvalidTeams () {
      const validIds = this.filteredTeamOptionsCreate.map(t => Number(t.id))
      if (!validIds.includes(Number(this.partidoForm.local_equipo_id))) {
        this.partidoForm.local_equipo_id = null
      }
      if (!validIds.includes(Number(this.partidoForm.visita_equipo_id))) {
        this.partidoForm.visita_equipo_id = null
      }
    },
    cleanInvalidTeamsEdit () {
      const validIds = this.filteredTeamOptionsEdit.map(t => Number(t.id))
      if (!validIds.includes(Number(this.editPartidoForm.local_equipo_id))) {
        this.editPartidoForm.local_equipo_id = null
      }
      if (!validIds.includes(Number(this.editPartidoForm.visita_equipo_id))) {
        this.editPartidoForm.visita_equipo_id = null
      }
    },
    abrirFaseDialog () {
      this.faseForm = {
        nombre: this.defaultFaseName(),
        tipo: 'liga'
      }
      this.faseDialog = true
    },
    abrirPartidoDialog () {
      this.partidoForm = {
        campeonato_fase_id: this.activeFase?.id || this.faseId,
        campeonato_fecha_id: null,
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null,
        estado: 'no_realizado'
      }
      this.partidoDialog = true
    },
    openEditPartido (p) {
      if (!this.canEdit) return
      this.editPartidoForm = {
        id: p.id,
        campeonato_fecha_id: p.campeonato_fecha_id || null,
        local_equipo_id: p.local_equipo_id || null,
        visita_equipo_id: p.visita_equipo_id || null,
        grupo_nombre: p.grupo_nombre || null,
        goles_local: p.goles_local,
        goles_visita: p.goles_visita,
        estado: p.estado || 'no_realizado'
      }
      this.editPartidoDialog = true
    },
    cargar () {
      this.$axios.get(`public/campeonatos/${this.code}/clasificacion`)
        .then(r => {
          const prevId = this.faseId
          this.fases = r.data || []
          if (!this.fases.length) return
          const exists = this.fases.some(f => Number(f.id) === Number(prevId))
          this.faseId = exists ? prevId : this.fases[0].id
          this.partidoForm.campeonato_fase_id = this.faseId
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
      const nombre = (this.faseForm.nombre || '').trim()
      if (!nombre) {
        this.$alert.error('Nombre de fase requerido')
        return
      }
      this.btnLoading.fase = true
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases`, {
        nombre,
        tipo: this.faseForm.tipo,
        agrupar_por_grupo: true
      })
        .then(() => {
          this.faseDialog = false
          this.$alert.success('Fase creada')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo crear fase'))
        .finally(() => { this.btnLoading.fase = false })
    },
    generarPartidos () {
      if (!this.activeFase) return
      this.btnLoading.generar = true
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}/generar-partidos`, {
        modo: this.genMode
      })
        .then(() => {
          this.genDialog = false
          this.$alert.success('Partidos generados')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo generar partidos'))
        .finally(() => { this.btnLoading.generar = false })
    },
    guardarPartido () {
      const faseId = this.partidoForm.campeonato_fase_id
      if (!faseId) {
        this.$alert.error('Selecciona una fase')
        return
      }
      if (this.partidoForm.local_equipo_id && this.partidoForm.local_equipo_id === this.partidoForm.visita_equipo_id) {
        this.$alert.error('Local y visita no pueden ser el mismo equipo')
        return
      }
      const payload = {
        campeonato_fecha_id: null,
        local_equipo_id: this.partidoForm.local_equipo_id,
        visita_equipo_id: this.partidoForm.visita_equipo_id,
        grupo_nombre: this.partidoForm.grupo_nombre,
        estado: this.partidoForm.estado
      }
      this.btnLoading.partido = true
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${faseId}/partidos`, payload)
        .then(() => {
          this.partidoDialog = false
          this.$alert.success('Partido agregado')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo agregar partido'))
        .finally(() => { this.btnLoading.partido = false })
    },
    guardarEdicionPartido () {
      if (!this.editPartidoForm.id) return
      if (this.editPartidoForm.local_equipo_id && this.editPartidoForm.local_equipo_id === this.editPartidoForm.visita_equipo_id) {
        this.$alert.error('Local y visita no pueden ser el mismo equipo')
        return
      }
      this.btnLoading.editar = true
      this.$axios.put(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}`, {
        campeonato_fecha_id: null,
        local_equipo_id: this.editPartidoForm.local_equipo_id,
        visita_equipo_id: this.editPartidoForm.visita_equipo_id,
        grupo_nombre: this.editPartidoForm.grupo_nombre,
        goles_local: this.editPartidoForm.goles_local,
        goles_visita: this.editPartidoForm.goles_visita,
        estado: this.editPartidoForm.estado || 'no_realizado'
      })
        .then(() => {
          this.editPartidoDialog = false
          this.$alert.success('Partido actualizado')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar partido'))
        .finally(() => { this.btnLoading.editar = false })
    },
    eliminarPartido () {
      if (!this.editPartidoForm.id) return
      this.$alert.dialog('Se eliminara este partido. Deseas continuar?')
        .onOk(() => {
          this.btnLoading.editar = true
          this.$axios.delete(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}`)
            .then(() => {
              this.editPartidoDialog = false
              this.$alert.success('Partido eliminado')
              this.cargar()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar partido'))
            .finally(() => { this.btnLoading.editar = false })
        })
    },
    sendMessage () {
      if (!this.newMessage || !this.newMessage.trim()) {
        this.$alert.error('Escribe un comentario')
        return
      }
      this.sendingMessage = true
      this.$axios.post(`public/campeonatos/${this.code}/mensajes`, {
        mensaje: this.newMessage.trim()
      })
        .then(() => {
          this.newMessage = ''
          this.$emit('refresh-mensajes')
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo enviar comentario'))
        .finally(() => { this.sendingMessage = false })
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
  max-height: 620px;
  overflow-y: auto;
}
.match-card {
  background: rgba(15, 23, 42, 0.45);
  border-color: rgba(148, 163, 184, 0.22);
}
.match-mid-card {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 8px;
  padding: 3px 4px;
  background: linear-gradient(180deg, #c79218 0%, #9b6b06 100%);
  color: #ffffff;
  line-height: 1.1;
}
.match-score {
  font-size: 40px;
  line-height: 1;
  color: #ffffff;
  letter-spacing: 0.5px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
}
.estado-no-realizado {
  border-color: #9ca3af;
  background: linear-gradient(180deg, #6b7280 0%, #4b5563 100%);
}
.estado-vivo {
  border-color: #ef4444;
  background: linear-gradient(180deg, #ef4444 0%, #b91c1c 100%);
}
.estado-finalizado {
  border-color: #22c55e;
  background: linear-gradient(180deg, #22c55e 0%, #15803d 100%);
}
.match-card.editable {
  cursor: pointer;
}
.clasif-root.mode-light .bg-dark-card {
  background: #ffffff;
  color: #0f172a !important;
  border-color: #dbe4f0;
}
.clasif-root.mode-light .table-standings {
  color: #0f172a !important;
}
.clasif-root.mode-light .match-card {
  background: #f8fafc;
  border-color: #dbe4f0;
}
.clasif-root.mode-light .match-mid-card {
  color: #ffffff;
}
.clasif-root.mode-light .match-score {
  color: #334155;
  text-shadow: none;
  font-weight: 800;
}
</style>
