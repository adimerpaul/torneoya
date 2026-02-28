<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-card-section>
        <div class="text-h6">Portal publico</div>
        <div class="text-caption text-grey-7">Abre el campeonato por codigo publico</div>
      </q-card-section>
      <q-card-section>
        <div class="row q-col-gutter-sm items-end">
          <div class="col-12 col-md-4">
            <q-input v-model="code" dense outlined label="Codigo (6 caracteres)" maxlength="8" />
          </div>
          <div class="col-12 col-md-auto">
            <q-btn color="primary" no-caps label="Abrir portal" @click="openByCode" />
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'PortalPublicoPage',
  data () {
    return {
      code: ''
    }
  },
  methods: {
    openByCode () {
      const c = (this.code || '').trim().toUpperCase()
      if (!c) {
        this.$alert.error('Ingresa un codigo')
        return
      }
      this.$router.push(`/c/${c}`)
    }
  }
}
</script>

