<template>
  <q-page class="q-pa-md">
    <div class="text-center q-mb-md">
      <q-btn
        color="primary"
        text-color="white"
        no-caps
        label="Nuevo campeonato"
        class="btn-nuevo"
        @click="dialogTipo = true"
      />
    </div>

    <q-list bordered separator class="bg-grey-1 rounded-borders">
      <q-item v-for="item in campeonatos" :key="item.id" class="q-py-sm">
        <q-item-section side>
          <q-btn-dropdown flat dense icon="more_vert" no-caps>
            <q-list>
              <q-item clickable v-close-popup @click="editarCampeonato(item)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="eliminarCampeonato(item)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section class="text-negative">Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-item-section>

        <q-item-section avatar>
          <q-avatar size="44px" color="white">
            <q-icon :name="iconoCampeonato(item)" color="green-7" />
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium">{{ item.nombre }}</q-item-label>
          <q-item-label caption>
            {{ item.tipo === 'unico' ? (item.deporte?.nombre || 'Sin deporte') : `${(item.categorias || []).length} categorias` }}
          </q-item-label>
        </q-item-section>
        <q-item-section side class="text-blue-4 text-caption">
          0 seguidores
        </q-item-section>
        <q-item-section side>
          <q-btn flat dense icon="content_copy" @click="copiarLink(item)" />
        </q-item-section>
      </q-item>
      <q-item v-if="!campeonatos.length">
        <q-item-section>No hay campeonatos creados todavia.</q-item-section>
      </q-item>
    </q-list>

    <q-dialog v-model="dialogTipo">
      <q-card style="width: 650px; max-width: 95vw">
        <q-card-section class="q-pt-lg">
          <q-item clickable @click="seleccionarTipo('unico')" class="rounded-borders">
            <q-item-section avatar><q-icon name="emoji_events" color="green-6" /></q-item-section>
            <q-item-section>
              <q-item-label class="text-h5 text-weight-bold">Campeonato unico</q-item-label>
              <q-item-label caption>Campeonato de una sola modalidad con una sola categoria</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator class="q-my-sm" />
          <q-item clickable @click="seleccionarTipo('categorias')" class="rounded-borders">
            <q-item-section avatar><q-icon name="hub" color="green-6" /></q-item-section>
            <q-item-section>
              <q-item-label class="text-h5 text-weight-bold">Campeonato con categorias</q-item-label>
              <q-item-label caption>Campeonato con mas de una categoria o diferentes deportes</q-item-label>
            </q-item-section>
          </q-item>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogCrear" persistent>
      <q-card style="width: 700px; max-width: 95vw">
        <q-card-section>
          <q-form @submit="guardarCampeonato">
            <q-input
              v-model="form.nombre"
              outlined
              dense
              label="Nombre del campeonato"
              class="q-mb-sm"
              :rules="[v => !!v || 'Ingrese un nombre']"
            />
            <q-separator class="q-mt-sm" />

            <div class="q-mt-md" v-if="form.tipo === 'unico'">
              <div class="text-subtitle1 text-primary">Fases del campeonato</div>
              <q-option-group
                v-model="form.fase_formato"
                :options="faseOptions"
                color="primary"
                type="radio"
              />
            </div>

            <div class="q-mt-md" v-if="form.tipo === 'unico'">
              <q-select
                v-model="form.deporte_id"
                outlined
                dense
                label="Tipo de juego"
                emit-value
                map-options
                :options="deportes"
                option-value="id"
                option-label="nombre"
                behavior="dialog"
              >
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section avatar>
                      <q-avatar size="30px" color="grey-3">
                        <q-icon :name="scope.opt.icono || 'sports'" />
                      </q-avatar>
                    </q-item-section>
                    <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                  </q-item>
                </template>
                <template #selected-item="scope">
                  <q-chip dense square color="grey-3" text-color="black" class="q-my-xs">
                    <q-icon :name="scope.opt.icono || 'sports'" size="18px" class="q-mr-xs" />
                    {{ scope.opt.nombre }}
                  </q-chip>
                </template>
              </q-select>
            </div>

            <div class="q-mt-md" v-if="form.tipo === 'categorias'">
              <div class="row items-center">
                <div class="text-subtitle1 text-primary">Categorias</div>
                <q-space />
                <q-btn flat color="primary" icon="add" no-caps label="Agregar" @click="agregarCategoria" />
              </div>
              <q-list bordered separator class="q-mt-sm">
                <q-item v-for="(cat, idx) in form.categorias" :key="idx">
                  <q-item-section>
                    <q-input v-model="cat.nombre" dense outlined label="Nombre categoria" />
                  </q-item-section>
                  <q-item-section>
                    <q-select
                      v-model="cat.deporte_id"
                      dense
                      outlined
                      label="Deporte"
                      emit-value
                      map-options
                      :options="deportes"
                      option-value="id"
                      option-label="nombre"
                    >
                      <template #option="scope">
                        <q-item v-bind="scope.itemProps">
                          <q-item-section avatar>
                            <q-avatar size="28px" color="grey-3">
                              <q-icon :name="scope.opt.icono || 'sports'" />
                            </q-avatar>
                          </q-item-section>
                          <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                        </q-item>
                      </template>
                      <template #selected-item="scope">
                        <q-chip dense square color="grey-3" text-color="black" class="q-my-xs">
                          <q-icon :name="scope.opt.icono || 'sports'" size="16px" class="q-mr-xs" />
                          {{ scope.opt.nombre }}
                        </q-chip>
                      </template>
                    </q-select>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn flat color="negative" icon="delete" @click="eliminarCategoria(idx)" />
                  </q-item-section>
                </q-item>
              </q-list>
            </div>

            <div class="text-right q-mt-md">
              <q-btn flat no-caps label="Cancelar" @click="dialogCrear = false" />
              <q-btn color="primary" no-caps label="Crear campeonato" type="submit" :loading="guardando" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'CampeonatosPage',
  data() {
    return {
      deportes: [],
      campeonatos: [],
      dialogTipo: false,
      dialogCrear: false,
      guardando: false,
      editandoId: null,
      faseOptions: [
        { label: 'Todos contra todos', value: 'todos_contra_todos' },
        { label: 'Todos contra todos + Eliminatoria', value: 'todos_contra_todos_eliminatoria' },
        { label: 'Eliminatoria', value: 'eliminatoria' }
      ],
      form: this.formVacio()
    }
  },
  mounted() {
    this.cargarDatos()
  },
  methods: {
    formVacio() {
      return {
        nombre: '',
        tipo: 'unico',
        fase_formato: 'todos_contra_todos',
        deporte_id: null,
        categorias: []
      }
    },
    async cargarDatos() {
      try {
        const [depRes, campRes] = await Promise.all([
          this.$axios.get('/deportes'),
          this.$axios.get('/campeonatos')
        ])
        this.deportes = depRes.data || []
        this.campeonatos = campRes.data || []
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo cargar campeonatos')
      }
    },
    seleccionarTipo(tipo) {
      this.form = this.formVacio()
      this.editandoId = null
      this.form.tipo = tipo
      if (tipo === 'categorias') {
        this.form.fase_formato = null
        this.form.deporte_id = null
        this.form.categorias = [{ nombre: '', deporte_id: null }]
      }
      this.dialogTipo = false
      this.dialogCrear = true
    },
    agregarCategoria() {
      this.form.categorias.push({ nombre: '', deporte_id: null })
    },
    eliminarCategoria(idx) {
      this.form.categorias.splice(idx, 1)
    },
    async guardarCampeonato() {
      this.guardando = true
      try {
        if (this.editandoId) {
          await this.$axios.put(`/campeonatos/${this.editandoId}`, this.form)
        } else {
          await this.$axios.post('/campeonatos', this.form)
        }
        this.dialogCrear = false
        this.$alert.success(this.editandoId ? 'Campeonato actualizado correctamente' : 'Campeonato creado correctamente')
        this.form = this.formVacio()
        this.editandoId = null
        await this.cargarDatos()
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo guardar campeonato')
      } finally {
        this.guardando = false
      }
    },
    copiarLink(item) {
      const text = `${window.location.origin}/#/campeonatos/${item.id}`
      navigator.clipboard.writeText(text)
      this.$alert.success('Enlace copiado')
    },
    editarCampeonato(item) {
      this.editandoId = item.id
      this.form = {
        nombre: item.nombre || '',
        tipo: item.tipo || 'unico',
        fase_formato: item.fase_formato || 'todos_contra_todos',
        deporte_id: item.deporte_id || null,
        categorias: (item.categorias || []).map((c) => ({
          nombre: c.nombre,
          deporte_id: c.deporte_id
        }))
      }
      if (this.form.tipo === 'categorias' && this.form.categorias.length === 0) {
        this.form.categorias = [{ nombre: '', deporte_id: null }]
      }
      this.dialogCrear = true
    },
    eliminarCampeonato(item) {
      this.$alert.dialog(`Desea eliminar el campeonato "${item.nombre}"?`)
        .onOk(async () => {
          this.guardando = true
          try {
            await this.$axios.delete(`/campeonatos/${item.id}`)
            this.$alert.success('Campeonato eliminado')
            await this.cargarDatos()
          } catch (e) {
            this.$alert.error(e?.response?.data?.message || 'No se pudo eliminar campeonato')
          } finally {
            this.guardando = false
          }
        })
    },
    iconoCampeonato(item) {
      if (item.tipo === 'unico') {
        return item.deporte?.icono || 'emoji_events'
      }
      return 'category'
    }
  }
}
</script>

<style scoped>
.btn-nuevo {
  min-width: 380px;
  border-radius: 14px;
}
</style>
