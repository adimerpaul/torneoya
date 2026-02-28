<template>
  <q-page class="q-pa-md">
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-title">Campeonatos</div>
          <div class="text-caption text-grey-7">Crea tus campeonatos</div>
        </div>
        <q-space />
        <q-input v-model="filter" label="Buscar" dense outlined debounce="300" style="width: 280px">
          <template #append><q-icon name="search" /></template>
        </q-input>
      </q-card-section>
    </q-card>

    <q-table
      :rows="campeonatos"
      :columns="columns"
      row-key="id"
      dense
      flat
      bordered
      wrap-cells
      :filter="filter"
      :rows-per-page-options="[0]"
      :loading="loading"
      loading-label="Cargando..."
      no-data-label="Sin registros"
    >
      <template #top-right>
        <q-btn color="positive" label="Nuevo campeonato" no-caps icon="add_circle_outline" class="q-mr-sm" @click="nuevo" />
        <q-btn color="primary" label="Actualizar" no-caps icon="refresh" :loading="loading" @click="listar" />
      </template>

      <template #body-cell-tipo="props">
        <q-td :props="props">
          <q-chip :label="props.row.tipo" dense text-color="white" :color="props.row.tipo === 'categorias' ? 'deep-orange' : 'primary'" />
        </q-td>
      </template>

      <template #body-cell-imagen="props">
        <q-td :props="props">
          <q-avatar rounded size="42px">
            <q-img :src="imageSrc(props.row.imagen || 'torneoImagen.jpg')" />
          </q-avatar>
        </q-td>
      </template>

      <template #body-cell-deporte="props">
        <q-td :props="props">
          <div class="row items-center q-gutter-xs">
            <q-icon v-if="props.row.deporte_icono" :name="props.row.deporte_icono" size="18px" />
            <span>{{ deporteNombre(props.row.deporte) || '-' }}</span>
          </div>
        </q-td>
      </template>

      <template #body-cell-codigo="props">
        <q-td :props="props">
          <q-chip dense outline color="indigo">{{ props.row.codigo }}</q-chip>
        </q-td>
      </template>

      <template #body-cell-categorias="props">
        <q-td :props="props">{{ props.row.categorias_count || 0 }}</q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-center">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable v-close-popup @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="abrirPublico(props.row)">
                <q-item-section avatar><q-icon name="public" /></q-item-section>
                <q-item-section><q-item-label>Ver publico</q-item-label></q-item-section>
              </q-item>
              <q-item v-if="props.row.tipo === 'categorias'" clickable v-close-popup @click="abrirCategorias(props.row)">
                <q-item-section avatar><q-icon name="category" /></q-item-section>
                <q-item-section><q-item-label>Gestionar categorias</q-item-label></q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="eliminar(props.row)">
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 720px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">{{ form.id ? 'Editar campeonato' : 'Nuevo campeonato' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dialog = false" />
        </q-card-section>

        <q-card-section>
          <q-form @submit.prevent="guardar">
            <q-input v-model="form.nombre" label="Nombre del campeonato" dense outlined class="q-mb-sm" :rules="[req]" />

            <div class="q-mb-sm text-caption text-grey-7">Tipo de campeonato</div>
            <q-option-group
              v-model="form.tipo"
              :options="[
                { label: 'Unico', value: 'unico' },
                { label: 'Multiples categorias', value: 'categorias' }
              ]"
              type="radio"
              inline
              class="q-mb-sm"
            />

            <q-input v-model="form.descripcion" type="textarea" autogrow dense outlined label="Descripcion (opcional)" class="q-mb-sm" />
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-12 col-md-6">
                <q-input v-model="form.fecha_inicio" type="date" dense outlined label="Fecha inicio" />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="form.fecha_fin" type="date" dense outlined label="Fecha fin" />
              </div>
            </div>

            <div v-if="form.tipo === 'unico'" class="q-mb-sm">
              <div class="text-caption text-grey-7 q-mb-xs">Deporte</div>
              <div class="row q-col-gutter-sm">
                <div v-for="d in deportes" :key="d.key" class="col-6 col-sm-4 col-md-3">
                  <q-card flat bordered class="cursor-pointer sport-card" :class="{ 'sport-selected': form.deporte === d.key }" @click="form.deporte = d.key">
                    <q-card-section class="q-pa-sm text-center">
                      <q-icon :name="d.icono" size="24px" />
                      <div class="text-caption q-mt-xs">{{ d.nombre }}</div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </div>

            <q-file
              v-model="form.imagen"
              dense
              outlined
              label="Imagen (opcional)"
              accept="image/*"
              class="q-mb-sm"
              @update:model-value="onImageSelected"
            />
            <q-file
              v-model="form.banner"
              dense
              outlined
              label="Banner (opcional)"
              accept="image/*"
              class="q-mb-sm"
              @update:model-value="onBannerSelected"
            />

            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-12 col-md-6">
                <q-card flat bordered>
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-grey-7 q-mb-xs">Vista previa imagen</div>
                    <q-img :src="form.imagePreview || imageSrc(form.imagen_actual || 'torneoImagen.jpg')" ratio="1" />
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-md-6">
                <q-card flat bordered>
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-grey-7 q-mb-xs">Vista previa banner</div>
                    <q-img :src="form.bannerPreview || imageSrc(form.banner_actual || 'torneoBanner.jpg')" :ratio="16 / 9" />
                  </q-card-section>
                </q-card>
              </div>
            </div>
            <q-banner dense rounded class="bg-indigo-1 text-indigo-9 q-mb-sm">
              El codigo publico se genera automaticamente con 6 caracteres aleatorios.
            </q-banner>

            <div class="row justify-end q-gutter-sm">
              <q-btn color="negative" label="Cancelar" no-caps flat @click="dialog = false" :disable="loading" />
              <q-btn color="primary" label="Guardar" no-caps type="submit" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="categoriasDialog" persistent>
      <q-card style="width: 860px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">Categorias de {{ campeonatoPadre.nombre }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="categoriasDialog = false" />
        </q-card-section>
        <q-card-section>
          <div class="row justify-end q-mb-md">
            <q-btn
              color="primary"
              no-caps
              label="Agregar categoria"
              icon="add"
              @click="openCategoriaEditor()"
            />
          </div>

          <q-table
            :rows="categorias"
            :columns="categoriaColumns"
            row-key="id"
            dense
            flat
            bordered
            :loading="loadingCategorias"
            :rows-per-page-options="[0]"
            :pagination="{ rowsPerPage: 0 }"
          >
            <template #body-cell-deporte="props">
              <q-td :props="props">
                <div class="row items-center q-gutter-xs">
                  <q-icon :name="deporteIcono(props.row.deporte)" size="18px" />
                  <span>{{ deporteNombre(props.row.deporte) }}</span>
                </div>
              </q-td>
            </template>
            <template #body-cell-imagen="props">
              <q-td :props="props">
                <q-avatar rounded size="42px">
                  <q-img :src="imageSrc(props.row.imagen || 'torneoImagen.jpg')" />
                </q-avatar>
              </q-td>
            </template>
            <template #body-cell-codigo="props">
              <q-td :props="props"><q-chip dense outline color="indigo">{{ props.row.codigo }}</q-chip></q-td>
            </template>
            <template #body-cell-actions="props">
              <q-td :props="props" class="text-left">
                <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
                  <q-list>
                    <q-item clickable v-close-popup @click="editarCategoria(props.row)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section><q-item-label>Modificar</q-item-label></q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="eliminarCategoria(props.row)">
                      <q-item-section avatar><q-icon name="delete" /></q-item-section>
                      <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="categoriaEditorDialog" persistent>
      <q-card style="width: 620px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ categoriaForm.id ? 'Modificar categoria' : 'Nueva categoria' }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense @click="closeCategoriaEditor" />
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-12 col-md-6">
              <q-input v-model="categoriaForm.nombre" dense outlined label="Nombre categoria" />
            </div>
            <div class="col-12 col-md-6">
              <q-select
                v-model="categoriaForm.deporte"
                dense
                outlined
                emit-value
                map-options
                option-value="key"
                option-label="nombre"
                :options="deportes"
                label="Deporte"
              >
                <template #selected-item="scope">
                  <div class="row items-center q-gutter-xs">
                    <q-icon :name="scope.opt.icono || 'emoji_events'" size="18px" />
                    <span>{{ scope.opt.nombre }}</span>
                  </div>
                </template>
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section avatar>
                      <q-icon :name="scope.opt.icono || 'emoji_events'" />
                    </q-item-section>
                    <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
            <div class="col-12 col-md-6">
              <q-file
                v-model="categoriaForm.imagen"
                dense
                outlined
                label="Imagen (opcional)"
                accept="image/*"
                @update:model-value="onCategoriaImageSelected"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-card flat bordered>
                <q-card-section class="q-pa-sm">
                  <div class="text-caption text-grey-7 q-mb-xs">Vista previa categoria</div>
                  <q-img
                    :src="categoriaForm.imagePreview || imageSrc(categoriaForm.imagen_actual || 'torneoImagen.jpg')"
                    :ratio="1"
                    style="max-height: 110px"
                  />
                </q-card-section>
              </q-card>
            </div>
          </div>

          <div class="row justify-end q-gutter-sm">
            <q-btn flat color="grey-8" no-caps label="Cancelar" @click="closeCategoriaEditor" />
            <q-btn
              color="primary"
              no-caps
              :label="categoriaForm.id ? 'Actualizar categoria' : 'Agregar categoria'"
              @click="guardarCategoria"
              :loading="loadingCategorias"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'CampeonatosPage',
  data () {
    return {
      loading: false,
      loadingCategorias: false,
      filter: '',
      dialog: false,
      categoriasDialog: false,
      categoriaEditorDialog: false,
      campeonatos: [],
      categorias: [],
      deportes: [],
      campeonatoPadre: {},
      form: {
        id: null,
        nombre: '',
        tipo: 'unico',
        deporte: null,
        descripcion: '',
        fecha_inicio: '',
        fecha_fin: '',
        imagen: null,
        banner: null,
        imagen_actual: 'torneoImagen.jpg',
        banner_actual: 'torneoBanner.jpg',
        imagePreview: null,
        bannerPreview: null
      },
      categoriaForm: {
        id: null,
        nombre: '',
        deporte: null,
        imagen: null,
        imagePreview: null,
        imagen_actual: 'torneoImagen.jpg'
      },
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'imagen', label: 'Imagen', align: 'left', field: 'imagen' },
        { name: 'nombre', label: 'Nombre', align: 'left', field: 'nombre' },
        { name: 'tipo', label: 'Tipo', align: 'left', field: 'tipo' },
        { name: 'deporte', label: 'Deporte', align: 'left', field: 'deporte' },
        { name: 'codigo', label: 'Codigo', align: 'left', field: 'codigo' },
        { name: 'categorias', label: 'Categorias', align: 'center', field: 'categorias_count' }
      ],
      categoriaColumns: [
        { name: 'actions', label: 'Opciones', align: 'left' },
        { name: 'imagen', label: 'Imagen', align: 'left', field: 'imagen' },
        { name: 'nombre', label: 'Nombre', align: 'left', field: 'nombre' },
        { name: 'deporte', label: 'Deporte', align: 'left', field: 'deporte' },
        { name: 'codigo', label: 'Codigo', align: 'left', field: 'codigo' }
      ]
    }
  },
  mounted () {
    this.cargarDeportes()
    this.listar()
  },
  methods: {
    req (v) {
      return !!v || 'Campo requerido'
    },
    deporteNombre (key) {
      return this.deportes.find(d => d.key === key)?.nombre || null
    },
    deporteIcono (key) {
      return this.deportes.find(d => d.key === key)?.icono || 'emoji_events'
    },
    imageSrc (name) {
      return `${this.$url}../../images/${name || 'torneoImagen.jpg'}`
    },
    onImageSelected (file) {
      if (this.form.imagePreview) URL.revokeObjectURL(this.form.imagePreview)
      this.form.imagePreview = file ? URL.createObjectURL(file) : null
    },
    onBannerSelected (file) {
      if (this.form.bannerPreview) URL.revokeObjectURL(this.form.bannerPreview)
      this.form.bannerPreview = file ? URL.createObjectURL(file) : null
    },
    onCategoriaImageSelected (file) {
      if (this.categoriaForm.imagePreview) URL.revokeObjectURL(this.categoriaForm.imagePreview)
      this.categoriaForm.imagePreview = file ? URL.createObjectURL(file) : null
    },
    resetCategoriaForm () {
      if (this.categoriaForm.imagePreview) URL.revokeObjectURL(this.categoriaForm.imagePreview)
      this.categoriaForm = {
        id: null,
        nombre: '',
        deporte: null,
        imagen: null,
        imagePreview: null,
        imagen_actual: 'torneoImagen.jpg'
      }
    },
    openCategoriaEditor () {
      this.resetCategoriaForm()
      this.categoriaEditorDialog = true
    },
    closeCategoriaEditor () {
      this.categoriaEditorDialog = false
      this.resetCategoriaForm()
    },
    cargarDeportes () {
      this.$axios.get('deportes')
        .then(r => { this.deportes = r.data || [] })
        .catch(() => { this.deportes = [] })
    },
    listar () {
      this.loading = true
      this.$axios.get('campeonatos')
        .then(r => { this.campeonatos = r.data || [] })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar campeonatos'))
        .finally(() => { this.loading = false })
    },
    resetForm () {
      this.form = {
        id: null,
        nombre: '',
        tipo: 'unico',
        deporte: null,
        descripcion: '',
        fecha_inicio: '',
        fecha_fin: '',
        imagen: null,
        banner: null,
        imagen_actual: 'torneoImagen.jpg',
        banner_actual: 'torneoBanner.jpg',
        imagePreview: null,
        bannerPreview: null
      }
    },
    nuevo () {
      this.resetForm()
      this.dialog = true
    },
    editar (row) {
      this.form = {
        id: row.id,
        nombre: row.nombre,
        tipo: row.tipo === 'categoria_item' ? 'unico' : row.tipo,
        deporte: row.deporte,
        descripcion: row.descripcion || '',
        fecha_inicio: row.fecha_inicio || '',
        fecha_fin: row.fecha_fin || '',
        imagen: null,
        banner: null,
        imagen_actual: row.imagen || 'torneoImagen.jpg',
        banner_actual: row.banner || 'torneoBanner.jpg',
        imagePreview: null,
        bannerPreview: null
      }
      this.dialog = true
    },
    guardar () {
      if (this.form.tipo === 'unico' && !this.form.deporte) {
        this.$alert.error('Debes elegir un deporte para campeonato unico')
        return
      }

      this.loading = true
      const fd = new FormData()
      fd.append('nombre', this.form.nombre)
      fd.append('tipo', this.form.tipo)
      fd.append('descripcion', this.form.descripcion || '')
      if (this.form.fecha_inicio) fd.append('fecha_inicio', this.form.fecha_inicio)
      if (this.form.fecha_fin) fd.append('fecha_fin', this.form.fecha_fin)
      if (this.form.deporte) fd.append('deporte', this.form.deporte)
      if (this.form.imagen) fd.append('imagen', this.form.imagen)
      if (this.form.banner) fd.append('banner', this.form.banner)

      const req = this.form.id
        ? this.$axios.post(`campeonatos/${this.form.id}?_method=PUT`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        : this.$axios.post('campeonatos', fd, { headers: { 'Content-Type': 'multipart/form-data' } })

      req
        .then(() => {
          this.$alert.success('Campeonato guardado')
          this.dialog = false
          this.listar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar'))
        .finally(() => { this.loading = false })
    },
    eliminar (row) {
      this.$alert.dialog(`Eliminar campeonato ${row.nombre}?`)
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`campeonatos/${row.id}`)
            .then(() => {
              this.$alert.success('Campeonato eliminado')
              this.listar()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar'))
            .finally(() => { this.loading = false })
        })
    },
    abrirPublico (row) {
      this.$router.push(`/c/${row.codigo}`)
    },
    abrirCategorias (row) {
      this.campeonatoPadre = row
      this.resetCategoriaForm()
      this.categoriasDialog = true
      this.cargarCategorias()
    },
    editarCategoria (row) {
      this.categoriaForm = {
        id: row.id,
        nombre: row.nombre,
        deporte: row.deporte,
        imagen: null,
        imagePreview: null,
        imagen_actual: row.imagen || 'torneoImagen.jpg'
      }
      this.categoriaEditorDialog = true
    },
    cargarCategorias () {
      if (!this.campeonatoPadre.id) return
      this.loadingCategorias = true
      this.$axios.get(`campeonatos/${this.campeonatoPadre.id}/categorias`)
        .then(r => { this.categorias = r.data || [] })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar categorias'))
        .finally(() => { this.loadingCategorias = false })
    },
    guardarCategoria () {
      if (!this.categoriaForm.nombre || !this.categoriaForm.deporte) {
        this.$alert.error('Nombre y deporte son obligatorios')
        return
      }

      this.loadingCategorias = true
      const fd = new FormData()
      fd.append('nombre', this.categoriaForm.nombre)
      fd.append('deporte', this.categoriaForm.deporte)
      if (this.categoriaForm.imagen) fd.append('imagen', this.categoriaForm.imagen)

      const req = this.categoriaForm.id
        ? this.$axios.post(`campeonatos/${this.campeonatoPadre.id}/categorias/${this.categoriaForm.id}?_method=PUT`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        : this.$axios.post(`campeonatos/${this.campeonatoPadre.id}/categorias`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

      req
        .then(() => {
          this.$alert.success(this.categoriaForm.id ? 'Categoria actualizada' : 'Categoria agregada')
          this.closeCategoriaEditor()
          this.cargarCategorias()
          this.listar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo agregar categoria'))
        .finally(() => { this.loadingCategorias = false })
    },
    eliminarCategoria (row) {
      this.$alert.dialog(`Eliminar categoria ${row.nombre}?`)
        .onOk(() => {
          this.loadingCategorias = true
          this.$axios.delete(`campeonatos/${this.campeonatoPadre.id}/categorias/${row.id}`)
            .then(() => {
              this.$alert.success('Categoria eliminada')
              this.cargarCategorias()
              this.listar()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar categoria'))
            .finally(() => { this.loadingCategorias = false })
        })
    }
  }
}
</script>

<style scoped>
.sport-card {
  transition: all 0.2s ease;
}
.sport-selected {
  border-color: var(--q-primary) !important;
  box-shadow: 0 0 0 1px var(--q-primary) inset;
}
</style>
