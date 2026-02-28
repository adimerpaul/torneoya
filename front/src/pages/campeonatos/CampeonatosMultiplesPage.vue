<template>
  <q-page class="q-pa-md">
    <q-card flat bordered class="q-mb-md">
      <q-table :rows="rows" :columns="columns" row-key="id" :loading="loading" selection="single" v-model:selected="selected">
        <template #top-left>
          <div class="text-h6">Campeonatos multiples</div>
        </template>
        <template #top-right>
          <q-btn color="primary" no-caps label="Nuevo" icon="add" @click="openCreate" class="q-mr-sm" />
          <q-btn flat icon="refresh" @click="loadRows" :loading="loading" />
        </template>
        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn dense flat icon="edit" @click="openEdit(props.row)" />
            <q-btn dense flat icon="delete" color="negative" @click="removeRow(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div class="text-subtitle1 text-weight-bold">Categorias</div>
        <q-space />
        <q-btn
          color="primary"
          no-caps
          icon="add"
          label="Nueva categoria"
          :disable="!selectedChampionship"
          @click="openCategoryCreate"
        />
      </q-card-section>
      <q-separator />
      <q-table :rows="categories" :columns="categoryColumns" row-key="id" :loading="loadingCategories">
        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn dense flat icon="edit" @click="openCategoryEdit(props.row)" />
            <q-btn dense flat icon="delete" color="negative" @click="removeCategory(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 720px; max-width: 96vw">
        <q-card-section class="row items-center">
          <div class="text-subtitle1 text-weight-bold">{{ form.id ? 'Editar multiple' : 'Nuevo multiple' }}</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="dialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit="saveRow" class="row q-col-gutter-md">
            <div class="col-12 col-md-6"><q-input outlined dense v-model="form.name" label="Nombre" :rules="[v => !!v || 'Requerido']" /></div>
            <div class="col-12 col-md-6"><q-input outlined dense v-model="form.start_date" type="date" label="Fecha inicio" /></div>
            <div class="col-12"><q-input outlined dense v-model="form.description" type="textarea" autogrow label="Descripcion" /></div>
            <div class="col-12 text-right">
              <q-btn flat no-caps label="Cancelar" @click="dialog = false" />
              <q-btn color="primary" no-caps label="Guardar" type="submit" :loading="saving" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogCategory" persistent>
      <q-card style="min-width: 680px; max-width: 96vw">
        <q-card-section class="row items-center">
          <div class="text-subtitle1 text-weight-bold">{{ categoryForm.id ? 'Editar categoria' : 'Nueva categoria' }}</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="dialogCategory = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit="saveCategory" class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="categoryForm.name" label="Nombre categoria" :rules="[v => !!v || 'Requerido']" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="categoryForm.sport_id" :options="sports" option-value="id" option-label="name" emit-value map-options label="Deporte" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="categoryForm.format_default_id" :options="formats" option-value="id" option-label="name" emit-value map-options label="Formato" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="categoryForm.points_scheme_id" :options="pointsSchemes" option-value="id" option-label="name" emit-value map-options label="Sistema puntos" />
            </div>
            <div class="col-12 text-right">
              <q-btn flat no-caps label="Cancelar" @click="dialogCategory = false" />
              <q-btn color="primary" no-caps label="Guardar categoria" type="submit" :loading="saving" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'CampeonatosMultiplesPage',
  data() {
    return {
      loading: false,
      loadingCategories: false,
      saving: false,
      rows: [],
      selected: [],
      categories: [],
      sports: [],
      formats: [],
      pointsSchemes: [],
      dialog: false,
      dialogCategory: false,
      form: this.emptyForm(),
      categoryForm: this.emptyCategory(),
      columns: [
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'name', label: 'Nombre', field: 'name', align: 'left' },
        { name: 'status', label: 'Estado', field: 'status', align: 'left' },
        { name: 'actions', label: 'Acciones', align: 'right' }
      ],
      categoryColumns: [
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'name', label: 'Categoria', field: 'name', align: 'left' },
        { name: 'sport_id', label: 'Deporte', field: 'sport_id', align: 'left' },
        { name: 'status', label: 'Estado', field: 'status', align: 'left' },
        { name: 'actions', label: 'Acciones', align: 'right' }
      ]
    }
  },
  computed: {
    selectedChampionship() {
      return this.selected?.[0] || null
    }
  },
  watch: {
    selectedChampionship() {
      this.loadCategories()
    }
  },
  mounted() {
    this.loadCatalogs()
    this.loadRows()
  },
  methods: {
    emptyForm() {
      return { id: null, name: '', description: '', start_date: '', status: 'draft' }
    },
    emptyCategory() {
      return { id: null, name: '', sport_id: null, format_default_id: null, points_scheme_id: null, status: 'draft' }
    },
    async loadCatalogs() {
      const [sports, formats, points] = await Promise.all([
        this.$axios.get('/catalogs/sports'),
        this.$axios.get('/catalogs/formats'),
        this.$axios.get('/catalogs/points-schemes')
      ])
      this.sports = sports.data || []
      this.formats = formats.data || []
      this.pointsSchemes = points.data || []
    },
    async loadRows() {
      this.loading = true
      try {
        const res = await this.$axios.get('/championship-multi')
        this.rows = res.data?.data || []
        if (this.rows.length && !this.selectedChampionship) {
          this.selected = [this.rows[0]]
        }
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo cargar campeonatos multiples')
      } finally {
        this.loading = false
      }
    },
    async loadCategories() {
      if (!this.selectedChampionship) {
        this.categories = []
        return
      }
      this.loadingCategories = true
      try {
        const res = await this.$axios.get(`/championship-multi/${this.selectedChampionship.id}/categories`)
        this.categories = res.data || []
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudieron cargar categorias')
      } finally {
        this.loadingCategories = false
      }
    },
    openCreate() {
      this.form = this.emptyForm()
      this.dialog = true
    },
    openEdit(row) {
      this.form = { ...this.emptyForm(), ...row }
      this.dialog = true
    },
    async saveRow() {
      this.saving = true
      try {
        if (this.form.id) {
          await this.$axios.put(`/championship-multi/${this.form.id}`, this.form)
        } else {
          await this.$axios.post('/championship-multi', this.form)
        }
        this.dialog = false
        await this.loadRows()
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo guardar campeonato multiple')
      } finally {
        this.saving = false
      }
    },
    removeRow(row) {
      this.$alert.dialog(`Eliminar campeonato ${row.name}?`)
        .onOk(async () => {
          this.saving = true
          try {
            await this.$axios.delete(`/championship-multi/${row.id}`)
            await this.loadRows()
          } catch (e) {
            this.$alert.error(e?.response?.data?.message || 'No se pudo eliminar')
          } finally {
            this.saving = false
          }
        })
    },
    openCategoryCreate() {
      this.categoryForm = this.emptyCategory()
      this.dialogCategory = true
    },
    openCategoryEdit(row) {
      this.categoryForm = { ...this.emptyCategory(), ...row }
      this.dialogCategory = true
    },
    async saveCategory() {
      if (!this.selectedChampionship) {
        return
      }
      this.saving = true
      try {
        if (this.categoryForm.id) {
          await this.$axios.put(`/championship-categories/${this.categoryForm.id}`, this.categoryForm)
        } else {
          await this.$axios.post(`/championship-multi/${this.selectedChampionship.id}/categories`, this.categoryForm)
        }
        this.dialogCategory = false
        await this.loadCategories()
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo guardar categoria')
      } finally {
        this.saving = false
      }
    },
    removeCategory(row) {
      this.$alert.dialog(`Eliminar categoria ${row.name}?`)
        .onOk(async () => {
          this.saving = true
          try {
            await this.$axios.delete(`/championship-categories/${row.id}`)
            await this.loadCategories()
          } catch (e) {
            this.$alert.error(e?.response?.data?.message || 'No se pudo eliminar categoria')
          } finally {
            this.saving = false
          }
        })
    }
  }
}
</script>
