<template>
  <q-page class="public-page">
    <div class="hero" :style="{ backgroundImage: `url(${imageSrc(campeonato.banner || 'torneoBanner.jpg')})` }">
      <div class="hero-overlay">
        <div class="hero-content">
          <q-avatar rounded size="76px" class="hero-logo">
            <q-img :src="configImagePreview || imageSrc(campeonato.imagen || 'torneoImagen.jpg')" />
          </q-avatar>

          <div class="hero-main">
            <div class="text-h5 text-weight-bold text-white">{{ campeonato.nombre || 'Campeonato' }}</div>
            <div class="text-blue-1 q-mt-xs">{{ rangoFechas(campeonato.fecha_inicio, campeonato.fecha_fin) }}</div>
            <div class="row items-center q-gutter-sm q-mt-sm">
              <q-chip dense color="indigo-2" text-color="indigo-10" icon="shield">Codigo: {{ campeonato.codigo }}</q-chip>
              <q-chip dense color="teal-2" text-color="teal-10" icon="person">
                Creador: {{ creadorNombre }}
              </q-chip>
              <q-chip
                v-if="campeonato?.user?.telefono_contacto_1"
                dense
                color="green-2"
                text-color="green-10"
                icon="phone"
              >
                {{ campeonato.user.telefono_contacto_1 }}
              </q-chip>
            </div>
          </div>

          <q-space />

          <div class="column q-gutter-sm actions-col">
            <q-btn
              color="white"
              text-color="indigo-9"
              no-caps
              icon="share"
              label="Compartir"
              @click="sharePublic"
            />
            <q-btn
              v-if="creatorWhatsappLink"
              color="positive"
              no-caps
              icon="chat"
              label="WhatsApp al creador"
              @click="openWhatsApp"
            />
          </div>
        </div>
      </div>
    </div>

    <q-card flat bordered class="q-ma-md panel-shell">
      <q-tabs
        v-model="tab"
        dense
        align="left"
        no-caps
        class="pub-tabs"
      >
        <q-tab
          name="inicio"
          label="Inicio"
          :icon="tabIcon('inicio')"
          :class="tabClass('inicio')"
        />
        <q-tab
          name="clasificacion"
          label="Clasificacion"
          :icon="tabIcon('clasificacion')"
          :class="tabClass('clasificacion')"
        />
        <q-tab
          name="ranking"
          label="Ranking"
          :icon="tabIcon('ranking')"
          :class="tabClass('ranking')"
        />
        <q-tab
          name="configuracion"
          label="Configuracion"
          :icon="tabIcon('configuracion')"
          :class="tabClass('configuracion')"
        />
      </q-tabs>

      <q-tab-panels v-model="tab" animated class="panels">
        <q-tab-panel name="inicio">
          <div class="text-subtitle1 text-weight-medium q-mb-sm text-cyan-2">Resumen</div>
          <q-card flat bordered class="q-mb-md bg-dark-card text-blue-1">
            <q-card-section>{{ campeonato.descripcion || 'Sin descripcion' }}</q-card-section>
          </q-card>

          <div v-if="campeonato.tipo === 'unico'" class="row items-center q-gutter-sm text-white q-mb-md">
            <q-icon :name="campeonato.deporte_icono || 'emoji_events'" color="amber-4" />
            <span>{{ deporteNombre(campeonato.deporte) || campeonato.deporte || 'Deporte' }}</span>
          </div>

          <div v-else class="q-mb-md">
            <div class="text-subtitle2 text-cyan-2 q-mb-sm">Categorias</div>
            <div class="row q-col-gutter-sm">
              <div v-for="cat in (campeonato.categorias || [])" :key="cat.id" class="col-12 col-md-4">
                <q-card flat bordered class="bg-dark-card text-white">
                  <q-card-section class="row items-center q-gutter-sm">
                    <q-avatar rounded size="40px"><q-img :src="imageSrc(cat.imagen || 'torneoImagen.jpg')" /></q-avatar>
                    <div>
                      <div class="text-weight-medium">{{ cat.nombre }}</div>
                      <div class="text-caption text-blue-2">
                        <q-icon :name="cat.deporte_icono || 'emoji_events'" size="14px" />
                        {{ deporteNombre(cat.deporte) || cat.deporte }}
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>

          <div class="row items-center q-mb-sm">
            <div class="text-subtitle1 text-weight-medium text-green-3">Equipos</div>
            <q-space />
            <q-btn
              v-if="canEdit"
              color="green-7"
              no-caps
              icon="groups"
              label="Gestionar equipos"
              @click="openEquiposDialog"
            />
          </div>

          <div v-if="!equipos.length" class="text-grey-5 q-mb-md">
            Aun no hay equipos registrados.
          </div>

          <div v-else class="row q-col-gutter-sm q-mb-md">
            <div v-for="eq in equipos" :key="eq.id" class="col-12 col-md-6">
              <q-card flat bordered class="bg-dark-card text-white">
                <q-card-section class="row items-center q-gutter-sm">
                  <q-avatar rounded size="44px">
                    <q-img :src="imageSrc(eq.imagen || 'torneoImagen.jpg')" />
                  </q-avatar>
                  <div class="col">
                    <div class="text-weight-bold">{{ eq.nombre }}</div>
                    <div class="text-caption text-blue-2">Entrenador: {{ eq.entrenador || 'Sin definir' }}</div>
                  </div>
                  <q-chip v-if="eq.grupo_nombre" dense color="teal-2" text-color="teal-10">
                    {{ eq.grupo_nombre }}
                  </q-chip>
                </q-card-section>
                <q-separator dark />
                <q-card-section class="text-caption text-grey-4">
                  Jugadores: {{ (eq.jugadores || []).length }}
                </q-card-section>
              </q-card>
            </div>
          </div>

          <div class="text-subtitle1 text-weight-medium q-mb-sm text-deep-orange-3">Mensajes</div>

          <q-list bordered separator class="rounded-borders messages-list q-mb-md">
            <q-item v-for="m in mensajes" :key="m.id" class="items-start">
              <q-item-section>
                <q-item-label class="text-white">
                  <b>{{ m.user?.name || m.user?.username || m.autor_nombre || 'Publico' }}</b>
                  <q-badge v-if="!m.visible" color="orange" class="q-ml-sm">Oculto</q-badge>
                </q-item-label>
                <q-item-label caption class="text-blue-2">{{ formatDateTime(m.created_at) }}</q-item-label>
                <q-item-label class="q-mt-xs text-grey-3">{{ m.mensaje }}</q-item-label>
              </q-item-section>
              <q-item-section side v-if="canModerate">
                <q-btn
                  dense
                  flat
                  :color="m.visible ? 'negative' : 'positive'"
                  :label="m.visible ? 'Ocultar' : 'Mostrar'"
                  :icon="m.visible ? 'visibility_off' : 'visibility'"
                  @click="toggleVisible(m)"
                />
              </q-item-section>
            </q-item>
          </q-list>

          <q-card flat bordered class="bg-dark-card">
            <q-card-section class="row q-col-gutter-sm">
              <div class="col-12 col-md-10">
                <q-input
                  v-model="newMessage"
                  type="textarea"
                  autogrow
                  outlined
                  dark
                  color="teal-3"
                  label="Escribe tu mensaje"
                  hint="Tu mensaje se publica al presionar Enviar"
                />
              </div>
              <div class="col-12 col-md-2 flex flex-center">
                <q-btn
                  color="deep-orange"
                  no-caps
                  class="full-width"
                  label="Enviar"
                  icon="send"
                  @click="sendMessage"
                  :loading="sendingMessage"
                />
              </div>
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <q-tab-panel name="clasificacion" class="panel-light-blue">
          <q-banner dense rounded class="bg-white text-indigo-10">
            <q-icon name="construction" class="q-mr-xs" />
            Clasificacion en construccion.
          </q-banner>
        </q-tab-panel>

        <q-tab-panel name="ranking" class="panel-light-amber">
          <q-banner dense rounded class="bg-white text-brown-9">
            <q-icon name="construction" class="q-mr-xs" />
            Ranking en construccion (goles y asistencias).
          </q-banner>
        </q-tab-panel>

        <q-tab-panel name="configuracion">
          <q-banner v-if="!canEdit" dense rounded class="bg-orange-2 text-orange-10 q-mb-sm">
            Solo el creador o administrador logueado puede modificar este campeonato.
          </q-banner>

          <q-form @submit.prevent="saveConfig()">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-6">
                <q-input v-model="config.nombre" dense outlined dark color="cyan-3" label="Nombre" :disable="!canEdit" />
              </div>
              <div class="col-12 col-md-6">
                <q-option-group
                  v-model="config.tipo"
                  :options="[
                    { label: 'Unico', value: 'unico' },
                    { label: 'Multiples categorias', value: 'categorias' }
                  ]"
                  type="radio"
                  inline
                  :disable="!canEdit"
                  color="cyan-3"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.fecha_inicio" dense outlined dark type="date" color="cyan-3" label="Fecha inicio" :disable="!canEdit" />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.fecha_fin" dense outlined dark type="date" color="cyan-3" label="Fecha fin" :disable="!canEdit" />
              </div>
              <div class="col-12 col-md-6" v-if="config.tipo === 'unico'">
                <q-select
                  v-model="config.deporte"
                  dense
                  outlined
                  dark
                  color="cyan-3"
                  emit-value
                  map-options
                  option-value="key"
                  option-label="nombre"
                  :options="deportes"
                  label="Deporte"
                  :disable="!canEdit"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-file
                  v-model="config.imagen"
                  dense
                  outlined
                  dark
                  color="cyan-3"
                  label="Logo/imagen"
                  accept="image/*"
                  :disable="!canEdit"
                  @update:model-value="onConfigFileChange('imagen', $event)"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-file
                  v-model="config.banner"
                  dense
                  outlined
                  dark
                  color="cyan-3"
                  label="Banner/fondo"
                  accept="image/*"
                  :disable="!canEdit"
                  @update:model-value="onConfigFileChange('banner', $event)"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-card flat bordered class="bg-dark-card">
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-blue-2 q-mb-xs">Vista previa logo</div>
                    <q-img :src="configImagePreview || imageSrc(campeonato.imagen || 'torneoImagen.jpg')" :ratio="1" />
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-md-6">
                <q-card flat bordered class="bg-dark-card">
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-blue-2 q-mb-xs">Vista previa banner</div>
                    <q-img :src="configBannerPreview || imageSrc(campeonato.banner || 'torneoBanner.jpg')" :ratio="16 / 9" />
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12">
                <q-input
                  v-model="config.descripcion"
                  dense
                  outlined
                  dark
                  color="cyan-3"
                  type="textarea"
                  autogrow
                  label="Descripcion"
                  :disable="!canEdit"
                />
              </div>
            </div>

            <div class="row justify-end q-mt-sm" v-if="canEdit">
              <q-btn color="primary" no-caps label="Guardar cambios" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-tab-panel>
      </q-tab-panels>

      <q-dialog v-model="equiposDialog" persistent>
        <q-card style="width: 980px; max-width: 98vw" class="bg-grey-1">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">Equipos y categorias</div>
            <q-space />
            <q-btn flat round dense icon="close" @click="equiposDialog = false" />
          </q-card-section>

          <q-card-section>
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-md-3">
                <q-input v-model="grupoForm.nombre" dense outlined label="Categoria/Grupo" />
              </div>
              <div class="col-12 col-md-9 row q-gutter-sm items-center">
                <q-btn color="indigo" no-caps label="Agregar categoria" icon="add" @click="guardarGrupo" />
                <q-btn color="teal" no-caps label="Crear A/B/C" icon="auto_awesome" @click="crearGruposDefault" />
              </div>
            </div>

            <div class="row q-gutter-xs q-mb-md">
              <q-chip
                v-for="g in grupos"
                :key="g.id"
                clickable
                color="teal-2"
                text-color="teal-10"
                :label="g.nombre"
                :outline="teamForm.campeonato_grupo_id !== g.id"
                @click="teamForm.campeonato_grupo_id = g.id"
              />
              <q-chip
                clickable
                color="grey-4"
                text-color="black"
                :outline="!!teamForm.campeonato_grupo_id"
                label="Sin categoria"
                @click="teamForm.campeonato_grupo_id = null"
              />
            </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-md-4">
                <q-input v-model="teamForm.nombre" dense outlined label="Nombre del equipo" />
              </div>
              <div class="col-12 col-md-3">
                <q-input v-model="teamForm.entrenador" dense outlined label="Entrenador" />
              </div>
              <div class="col-12 col-md-3">
                <q-file
                  v-model="teamForm.imagen"
                  dense
                  outlined
                  label="Imagen equipo"
                  accept="image/*"
                  @update:model-value="onTeamImageChange"
                />
              </div>
              <div class="col-12 col-md-2">
                <q-btn
                  class="full-width"
                  color="primary"
                  no-caps
                  :label="teamForm.id ? 'Actualizar' : 'Agregar'"
                  @click="guardarEquipo"
                />
              </div>
            </div>

            <q-list bordered separator>
              <q-item v-for="eq in equipos" :key="eq.id">
                <q-item-section avatar>
                  <q-avatar rounded size="34px">
                    <q-img :src="imageSrc(eq.imagen || 'torneoImagen.jpg')" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ eq.nombre }}</q-item-label>
                  <q-item-label caption>
                    {{ eq.entrenador || 'Sin entrenador' }} | {{ eq.grupo_nombre || 'Sin categoria' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side class="row q-gutter-xs items-center">
                  <q-btn dense flat color="primary" icon="edit" @click="editarEquipo(eq)" />
                  <q-btn dense flat color="deep-purple" icon="person_add" @click="openJugadorDialog(eq)" />
                  <q-btn dense flat color="negative" icon="delete" @click="eliminarEquipo(eq)" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </q-dialog>

      <q-dialog v-model="jugadorDialog" persistent>
        <q-card style="width: 840px; max-width: 98vw">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">Jugadores de {{ selectedEquipo?.nombre }}</div>
            <q-space />
            <q-btn flat round dense icon="close" @click="jugadorDialog = false" />
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.nombre" dense outlined label="Nombre" /></div>
              <div class="col-12 col-md-2"><q-input v-model="jugadorForm.abreviado" dense outlined label="Abreviado" /></div>
              <div class="col-12 col-md-2"><q-input v-model="jugadorForm.posicion" dense outlined label="Posicion" /></div>
              <div class="col-12 col-md-2"><q-input v-model="jugadorForm.numero_camiseta" dense outlined label="Nro camiseta" /></div>
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.fecha_nacimiento" type="date" dense outlined label="Nacimiento" /></div>
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.documento" dense outlined label="Documento" /></div>
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.celular" dense outlined label="Celular" /></div>
              <div class="col-12 col-md-4">
                <q-file v-model="jugadorForm.foto" dense outlined label="Foto" accept="image/*" />
              </div>
              <div class="col-12 col-md-2">
                <q-btn
                  class="full-width"
                  color="primary"
                  no-caps
                  :label="jugadorForm.id ? 'Actualizar' : 'Agregar'"
                  @click="guardarJugador"
                />
              </div>
            </div>

            <q-list bordered separator>
              <q-item v-for="j in (selectedEquipo?.jugadores || [])" :key="j.id">
                <q-item-section avatar>
                  <q-avatar rounded size="34px">
                    <q-img :src="imageSrc(j.foto || 'torneoImagen.jpg')" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ j.nombre }}</q-item-label>
                  <q-item-label caption>
                    {{ j.posicion || '-' }} | #{{ j.numero_camiseta || '-' }} | {{ j.celular || '-' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side class="row q-gutter-xs items-center">
                  <q-btn dense flat color="primary" icon="edit" @click="editarJugador(j)" />
                  <q-btn dense flat color="negative" icon="delete" @click="eliminarJugador(j)" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </q-dialog>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'CampeonatoPublicoPage',
  data () {
    return {
      loading: false,
      saving: false,
      sendingMessage: false,
      tab: 'inicio',
      campeonato: {},
      deportes: [],
      mensajes: [],
      grupos: [],
      equipos: [],
      newMessage: '',
      equiposDialog: false,
      jugadorDialog: false,
      selectedEquipo: null,
      grupoForm: {
        nombre: ''
      },
      teamPreview: null,
      teamForm: {
        id: null,
        nombre: '',
        entrenador: '',
        campeonato_grupo_id: null,
        imagen: null
      },
      jugadorForm: {
        id: null,
        nombre: '',
        abreviado: '',
        posicion: '',
        numero_camiseta: '',
        fecha_nacimiento: '',
        documento: '',
        celular: '',
        foto: null
      },
      configImagePreview: null,
      configBannerPreview: null,
      config: {
        id: null,
        nombre: '',
        tipo: 'unico',
        deporte: null,
        descripcion: '',
        fecha_inicio: '',
        fecha_fin: '',
        imagen: null,
        banner: null
      }
    }
  },
  computed: {
    canEdit () {
      const user = this.$store?.user || {}
      if (!this.$store?.isLogged || !user?.id || !this.campeonato?.id) return false
      return user.role === 'Administrador' || Number(user.id) === Number(this.campeonato.user_id)
    },
    canModerate () {
      return this.canEdit
    },
    creadorNombre () {
      return this.campeonato?.user?.name || this.campeonato?.user?.username || 'Sin creador'
    },
    creatorWhatsappLink () {
      const raw = (this.campeonato?.user?.telefono_contacto_1 || '').toString()
      const digits = raw.replace(/\D/g, '')
      if (!digits) return ''
      return `https://wa.me/${digits}`
    }
  },
  mounted () {
    this.cargarDeportes()
    this.cargarCampeonato()
    this.cargarMensajes()
  },
  beforeUnmount () {
    if (this.configImagePreview) URL.revokeObjectURL(this.configImagePreview)
    if (this.configBannerPreview) URL.revokeObjectURL(this.configBannerPreview)
    if (this.teamPreview) URL.revokeObjectURL(this.teamPreview)
  },
  methods: {
    imageSrc (name) {
      return `${this.$url}../../images/${name || 'torneoImagen.jpg'}`
    },
    deporteNombre (key) {
      return this.deportes.find(d => d.key === key)?.nombre || key
    },
    rangoFechas (inicio, fin) {
      if (!inicio && !fin) return 'Sin fechas definidas'
      if (inicio && fin) return `${inicio} - ${fin}`
      return inicio || fin
    },
    formatDateTime (value) {
      if (!value) return ''
      return new Date(value).toLocaleString()
    },
    tabIcon (name) {
      const active = this.tab === name
      const base = {
        inicio: 'home',
        clasificacion: 'leaderboard',
        ranking: 'emoji_events',
        configuracion: 'settings'
      }
      const outline = {
        inicio: 'o_home',
        clasificacion: 'o_leaderboard',
        ranking: 'o_emoji_events',
        configuracion: 'o_settings'
      }
      return active ? outline[name] : base[name]
    },
    tabClass (name) {
      return {
        'tab-active': this.tab === name,
        [`tab-${name}`]: true
      }
    },
    sharePublic () {
      const url = window.location.href
      if (navigator.share) {
        navigator.share({
          title: this.campeonato?.nombre || 'Torneo Ya',
          text: `Mira este campeonato: ${this.campeonato?.nombre || ''}`,
          url
        }).catch(() => {})
        return
      }
      navigator.clipboard.writeText(url)
        .then(() => this.$alert.success('Enlace copiado'))
        .catch(() => this.$alert.error('No se pudo copiar el enlace'))
    },
    openWhatsApp () {
      if (!this.creatorWhatsappLink) return
      window.open(this.creatorWhatsappLink, '_blank')
    },
    onConfigFileChange (field, file) {
      if (field === 'imagen') {
        if (this.configImagePreview) URL.revokeObjectURL(this.configImagePreview)
        this.configImagePreview = file ? URL.createObjectURL(file) : null
      } else {
        if (this.configBannerPreview) URL.revokeObjectURL(this.configBannerPreview)
        this.configBannerPreview = file ? URL.createObjectURL(file) : null
      }

      if (file && this.canEdit) {
        this.saveConfig(true)
      }
    },
    resetTeamForm () {
      if (this.teamPreview) URL.revokeObjectURL(this.teamPreview)
      this.teamPreview = null
      this.teamForm = {
        id: null,
        nombre: '',
        entrenador: '',
        campeonato_grupo_id: null,
        imagen: null
      }
    },
    resetJugadorForm () {
      this.jugadorForm = {
        id: null,
        nombre: '',
        abreviado: '',
        posicion: '',
        numero_camiseta: '',
        fecha_nacimiento: '',
        documento: '',
        celular: '',
        foto: null
      }
    },
    onTeamImageChange (file) {
      if (this.teamPreview) URL.revokeObjectURL(this.teamPreview)
      this.teamPreview = file ? URL.createObjectURL(file) : null
    },
    cargarDeportes () {
      this.$axios.get('public/deportes')
        .then(r => { this.deportes = r.data || [] })
        .catch(() => { this.deportes = [] })
    },
    cargarCampeonato () {
      this.loading = true
      this.$axios.get(`public/campeonatos/${this.$route.params.code}`)
        .then(r => {
          this.campeonato = r.data || {}
          this.grupos = this.campeonato.grupos || []
          this.equipos = this.campeonato.equipos || []
          this.config = {
            id: this.campeonato.id,
            nombre: this.campeonato.nombre || '',
            tipo: this.campeonato.tipo || 'unico',
            deporte: this.campeonato.deporte || null,
            descripcion: this.campeonato.descripcion || '',
            fecha_inicio: this.campeonato.fecha_inicio || '',
            fecha_fin: this.campeonato.fecha_fin || '',
            imagen: null,
            banner: null
          }
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar el campeonato'))
        .finally(() => { this.loading = false })
    },
    openEquiposDialog () {
      if (!this.canEdit) return
      this.equiposDialog = true
      this.resetTeamForm()
      this.grupoForm.nombre = ''
      this.loadGestionData()
    },
    loadGestionData () {
      this.$axios.get(`campeonatos/${this.campeonato.id}/grupos`)
        .then(r => { this.grupos = r.data || [] })
        .catch(() => { this.grupos = [] })
      this.$axios.get(`campeonatos/${this.campeonato.id}/equipos`)
        .then(r => { this.equipos = r.data || [] })
        .catch(() => { this.equipos = [] })
    },
    guardarGrupo () {
      const nombre = (this.grupoForm.nombre || '').trim()
      if (!nombre) {
        this.$alert.error('Ingresa el nombre de la categoria/grupo')
        return
      }
      this.$axios.post(`campeonatos/${this.campeonato.id}/grupos`, { nombre })
        .then(() => {
          this.grupoForm.nombre = ''
          this.$alert.success('Categoria/grupo agregado')
          this.loadGestionData()
          this.cargarCampeonato()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo agregar categoria/grupo'))
    },
    crearGruposDefault () {
      this.$axios.post(`campeonatos/${this.campeonato.id}/grupos/defaults`)
        .then(() => {
          this.$alert.success('Categorias A/B/C aplicadas')
          this.loadGestionData()
          this.cargarCampeonato()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo crear categorias por defecto'))
    },
    guardarEquipo () {
      if (!this.teamForm.nombre || !this.teamForm.nombre.trim()) {
        this.$alert.error('Nombre de equipo requerido')
        return
      }

      const fd = new FormData()
      fd.append('nombre', this.teamForm.nombre.trim())
      fd.append('entrenador', this.teamForm.entrenador || '')
      if (this.teamForm.campeonato_grupo_id) fd.append('campeonato_grupo_id', this.teamForm.campeonato_grupo_id)
      if (this.teamForm.imagen) fd.append('imagen', this.teamForm.imagen)

      const req = this.teamForm.id
        ? this.$axios.post(`campeonatos/${this.campeonato.id}/equipos/${this.teamForm.id}?_method=PUT`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        : this.$axios.post(`campeonatos/${this.campeonato.id}/equipos`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

      req
        .then(() => {
          this.$alert.success(this.teamForm.id ? 'Equipo actualizado' : 'Equipo creado')
          this.resetTeamForm()
          this.loadGestionData()
          this.cargarCampeonato()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar equipo'))
    },
    editarEquipo (eq) {
      this.teamForm = {
        id: eq.id,
        nombre: eq.nombre || '',
        entrenador: eq.entrenador || '',
        campeonato_grupo_id: eq.campeonato_grupo_id || null,
        imagen: null
      }
    },
    eliminarEquipo (eq) {
      this.$alert.dialog(`Eliminar equipo ${eq.nombre}?`)
        .onOk(() => {
          this.$axios.delete(`campeonatos/${this.campeonato.id}/equipos/${eq.id}`)
            .then(() => {
              this.$alert.success('Equipo eliminado')
              this.loadGestionData()
              this.cargarCampeonato()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar equipo'))
        })
    },
    openJugadorDialog (eq) {
      this.selectedEquipo = eq
      this.jugadorDialog = true
      this.resetJugadorForm()
    },
    guardarJugador () {
      if (!this.selectedEquipo?.id) return
      if (!this.jugadorForm.nombre || !this.jugadorForm.nombre.trim()) {
        this.$alert.error('Nombre del jugador requerido')
        return
      }

      const fd = new FormData()
      fd.append('nombre', this.jugadorForm.nombre.trim())
      fd.append('abreviado', this.jugadorForm.abreviado || '')
      fd.append('posicion', this.jugadorForm.posicion || '')
      fd.append('numero_camiseta', this.jugadorForm.numero_camiseta || '')
      fd.append('fecha_nacimiento', this.jugadorForm.fecha_nacimiento || '')
      fd.append('documento', this.jugadorForm.documento || '')
      fd.append('celular', this.jugadorForm.celular || '')
      if (this.jugadorForm.foto) fd.append('foto', this.jugadorForm.foto)

      const req = this.jugadorForm.id
        ? this.$axios.post(`campeonatos/${this.campeonato.id}/equipos/${this.selectedEquipo.id}/jugadores/${this.jugadorForm.id}?_method=PUT`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        : this.$axios.post(`campeonatos/${this.campeonato.id}/equipos/${this.selectedEquipo.id}/jugadores`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

      req
        .then(() => {
          this.$alert.success(this.jugadorForm.id ? 'Jugador actualizado' : 'Jugador agregado')
          this.resetJugadorForm()
          this.loadGestionData()
          this.cargarCampeonato()
          const refreshed = this.equipos.find(e => e.id === this.selectedEquipo.id)
          if (refreshed) this.selectedEquipo = refreshed
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar jugador'))
    },
    editarJugador (j) {
      this.jugadorForm = {
        id: j.id,
        nombre: j.nombre || '',
        abreviado: j.abreviado || '',
        posicion: j.posicion || '',
        numero_camiseta: j.numero_camiseta || '',
        fecha_nacimiento: j.fecha_nacimiento || '',
        documento: j.documento || '',
        celular: j.celular || '',
        foto: null
      }
    },
    eliminarJugador (j) {
      if (!this.selectedEquipo?.id) return
      this.$alert.dialog(`Eliminar jugador ${j.nombre}?`)
        .onOk(() => {
          this.$axios.delete(`campeonatos/${this.campeonato.id}/equipos/${this.selectedEquipo.id}/jugadores/${j.id}`)
            .then(() => {
              this.$alert.success('Jugador eliminado')
              this.loadGestionData()
              this.cargarCampeonato()
              const refreshed = this.equipos.find(e => e.id === this.selectedEquipo.id)
              if (refreshed) this.selectedEquipo = refreshed
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar jugador'))
        })
    },
    saveConfig (silent = false) {
      if (!this.canEdit) return

      this.saving = true
      const fd = new FormData()
      fd.append('nombre', this.config.nombre || '')
      fd.append('tipo', this.config.tipo || 'unico')
      fd.append('descripcion', this.config.descripcion || '')
      if (this.config.deporte) fd.append('deporte', this.config.deporte)
      if (this.config.fecha_inicio) fd.append('fecha_inicio', this.config.fecha_inicio)
      if (this.config.fecha_fin) fd.append('fecha_fin', this.config.fecha_fin)
      if (this.config.imagen) fd.append('imagen', this.config.imagen)
      if (this.config.banner) fd.append('banner', this.config.banner)

      this.$axios.post(`campeonatos/${this.config.id}?_method=PUT`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then(() => {
          if (!silent) this.$alert.success('Configuracion actualizada')
          this.cargarCampeonato()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar'))
        .finally(() => { this.saving = false })
    },
    cargarMensajes () {
      this.$axios.get(`public/campeonatos/${this.$route.params.code}/mensajes`)
        .then(r => { this.mensajes = r.data || [] })
        .catch(() => { this.mensajes = [] })
    },
    sendMessage () {
      if (!this.newMessage || !this.newMessage.trim()) {
        this.$alert.error('Escribe un mensaje')
        return
      }
      this.sendingMessage = true
      this.$axios.post(`public/campeonatos/${this.$route.params.code}/mensajes`, {
        mensaje: this.newMessage.trim()
      })
        .then(() => {
          this.newMessage = ''
          this.cargarMensajes()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo enviar mensaje'))
        .finally(() => { this.sendingMessage = false })
    },
    toggleVisible (m) {
      this.$axios.patch(`campeonatos/${this.campeonato.id}/mensajes/${m.id}/visible`)
        .then(() => this.cargarMensajes())
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cambiar visibilidad'))
    }
  }
}
</script>

<style scoped>
.public-page {
  background: linear-gradient(180deg, #0f172a 0%, #111827 45%, #0b1220 100%);
}
.hero {
  min-height: 240px;
  background-size: cover;
  background-position: center;
}
.hero-overlay {
  background: linear-gradient(100deg, rgba(2, 6, 23, 0.92), rgba(30, 41, 59, 0.8));
  min-height: 240px;
  display: flex;
  align-items: center;
}
.hero-content {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
}
.hero-main {
  min-width: 220px;
}
.hero-logo {
  border: 2px solid rgba(255, 255, 255, 0.7);
}
.actions-col {
  min-width: 180px;
}
.panel-shell {
  border-radius: 14px;
  overflow: hidden;
  background: #111827;
  border-color: rgba(148, 163, 184, 0.25);
  color: #e5e7eb;
}
.pub-tabs {
  padding: 10px 10px 6px;
  background: #0b1220;
}
.pub-tabs :deep(.q-tab) {
  margin-right: 8px;
  border-radius: 10px;
  color: #cbd5e1;
  padding: 0 12px;
}
.pub-tabs :deep(.q-tab__label) {
  text-transform: none;
  letter-spacing: 0;
}
.tab-active {
  color: #fff !important;
  border: 1px solid rgba(255, 255, 255, 0.32);
}
.tab-inicio.tab-active {
  background: rgba(37, 99, 235, 0.5);
}
.tab-clasificacion.tab-active {
  background: rgba(14, 116, 144, 0.5);
}
.tab-ranking.tab-active {
  background: rgba(180, 83, 9, 0.5);
}
.tab-configuracion.tab-active {
  background: rgba(79, 70, 229, 0.5);
}
.panels {
  background: #111827;
}
.bg-dark-card {
  background: #0b1220;
  border-color: rgba(148, 163, 184, 0.25);
}
.messages-list {
  background: #0b1220;
  border-color: rgba(148, 163, 184, 0.25);
}
.panel-light-blue {
  background: rgba(30, 64, 175, 0.15);
}
.panel-light-amber {
  background: rgba(180, 83, 9, 0.18);
}
</style>
