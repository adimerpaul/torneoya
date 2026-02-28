<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-table :rows="rows" :columns="columns" row-key="id" :loading="loading">
        <template #top-left>
          <div class="text-h6">Campeonatos simples</div>
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

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 760px; max-width: 96vw">
        <q-card-section class="row items-center">
          <div class="text-subtitle1 text-weight-bold">{{ form.id ? 'Editar campeonato' : 'Nuevo campeonato' }}</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="dialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit="saveRow" class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.name" label="Nombre" :rules="[v => !!v || 'Requerido']" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="form.sport_id" :options="sports" option-value="id" option-label="name" emit-value map-options label="Deporte" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="form.format_default_id" :options="formats" option-value="id" option-label="name" emit-value map-options label="Formato" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="form.points_scheme_id" :options="pointsSchemes" option-value="id" option-label="name" emit-value map-options label="Sistema de puntos" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.start_date" type="date" label="Fecha inicio" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.inscription_deadline" type="date" label="Fecha limite inscripcion" />
            </div>
            <div class="col-12">
              <q-input outlined dense v-model="form.description" type="textarea" autogrow label="Descripcion" />
            </div>
            <div class="col-12 text-right">
              <q-btn flat no-caps label="Cancelar" @click="dialog = false" />
              <q-btn color="primary" no-caps label="Guardar" type="submit" :loading="saving" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'CampeonatosSimplesPage',
  data() {
    return {
      loading: false,
      saving: false,
      rows: [],
      sports: [],
      formats: [],
      pointsSchemes: [],
      columns: [
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'name', label: 'Nombre', field: 'name', align: 'left' },
        { name: 'status', label: 'Estado', field: 'status', align: 'left' },
        { name: 'start_date', label: 'Inicio', field: 'start_date', align: 'left' },
        { name: 'actions', label: 'Acciones', align: 'right' }
      ],
      dialog: false,
      form: this.emptyForm()
    }
  },
  mounted() {
    this.loadCatalogs()
    this.loadRows()
  },
  methods: {
    emptyForm() {
      return {
        id: null,
        name: '',
        description: '',
        sport_id: null,
        format_default_id: null,
        points_scheme_id: null,
        start_date: '',
        inscription_deadline: '',
        status: 'draft'
      }
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
        const res = await this.$axios.get('/championship-single')
        this.rows = res.data?.data || []
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo cargar campeonatos')
      } finally {
        this.loading = false
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
          await this.$axios.put(`/championship-single/${this.form.id}`, this.form)
        } else {
          await this.$axios.post('/championship-single', this.form)
        }
        this.dialog = false
        await this.loadRows()
        this.$alert.success('Campeonato guardado')
      } catch (e) {
        this.$alert.error(e?.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.saving = false
      }
    },
    removeRow(row) {
      this.$alert.dialog(`Eliminar campeonato ${row.name}?`)
        .onOk(async () => {
          this.saving = true
          try {
            await this.$axios.delete(`/championship-single/${row.id}`)
            await this.loadRows()
            this.$alert.success('Campeonato eliminado')
          } catch (e) {
            this.$alert.error(e?.response?.data?.message || 'No se pudo eliminar')
          } finally {
            this.saving = false
          }
        })
    }
  }
}
</script>
