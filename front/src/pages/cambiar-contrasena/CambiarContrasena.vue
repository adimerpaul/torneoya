<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row justify-center">
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section class="row items-center q-py-sm">
            <div>
              <div class="text-h6 text-weight-bold">Cambiar contrasena</div>
              <div class="text-caption text-grey-7">
                Actualiza tu acceso de forma segura.
              </div>
            </div>
            <q-space />
            <q-icon name="vpn_key" color="primary" size="28px" />
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-md">
            <q-form @submit.prevent="openConfirmDialog">
              <q-input
                v-model="form.current_password"
                dense
                outlined
                :type="showCurrent ? 'text' : 'password'"
                label="Contrasena actual"
                autocomplete="current-password"
                class="q-mb-sm"
                :rules="[v => !!v || 'Requerido']"
              >
                <template #append>
                  <q-btn
                    dense
                    flat
                    round
                    type="button"
                    :icon="showCurrent ? 'visibility' : 'visibility_off'"
                    @click="showCurrent = !showCurrent"
                  />
                </template>
              </q-input>

              <q-input
                v-model="form.password"
                dense
                outlined
                :type="showNew ? 'text' : 'password'"
                label="Nueva contrasena"
                autocomplete="new-password"
                class="q-mb-sm"
                :rules="[v => (v && v.length >= 6) || 'Minimo 6 caracteres']"
              >
                <template #append>
                  <q-btn
                    dense
                    flat
                    round
                    type="button"
                    :icon="showNew ? 'visibility' : 'visibility_off'"
                    @click="showNew = !showNew"
                  />
                </template>
              </q-input>

              <q-input
                v-model="form.password_confirmation"
                dense
                outlined
                :type="showConfirm ? 'text' : 'password'"
                label="Confirmar nueva contrasena"
                autocomplete="new-password"
                class="q-mb-md"
                :rules="[
                  v => !!v || 'Requerido',
                  v => v === form.password || 'No coincide'
                ]"
              >
                <template #append>
                  <q-btn
                    dense
                    flat
                    round
                    type="button"
                    :icon="showConfirm ? 'visibility' : 'visibility_off'"
                    @click="showConfirm = !showConfirm"
                  />
                </template>
              </q-input>

              <q-banner dense class="bg-grey-1 q-mb-md" rounded>
                Se guardara en password (encriptado) y en clave. Luego cerraras sesion por seguridad.
              </q-banner>

              <div class="row q-col-gutter-sm">
                <div class="col-12 col-sm-6">
                  <q-btn
                    class="full-width"
                    no-caps
                    color="primary"
                    icon="save"
                    label="Cambiar contrasena"
                    type="submit"
                    :loading="loading"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <q-btn
                    class="full-width"
                    no-caps
                    flat
                    color="grey-8"
                    icon="arrow_back"
                    label="Volver"
                    @click="$router.push('/')"
                    :disable="loading"
                  />
                </div>
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="confirmDialog" persistent>
      <q-card style="min-width: 340px; max-width: 460px; width: 92vw;">
        <q-card-section class="row items-center q-pb-sm">
          <q-icon name="lock_reset" color="primary" size="24px" class="q-mr-sm" />
          <div class="text-subtitle1 text-weight-medium">Cambiar contrasena</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-body2 q-mb-sm">
            Estas por actualizar tu contrasena.
          </div>
          <div class="text-caption text-grey-7">
            Se guardara en password y clave. Despues se cerrara tu sesion para que vuelvas a entrar.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" color="grey-8" v-close-popup :disable="loading" />
          <q-btn unelevated no-caps label="Confirmar cambio" color="primary" :loading="loading" @click="submit" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'CambiarContrasena',
  data () {
    return {
      loading: false,
      confirmDialog: false,
      showCurrent: false,
      showNew: false,
      showConfirm: false,
      form: {
        current_password: '',
        password: '',
        password_confirmation: ''
      }
    }
  },
  methods: {
    openConfirmDialog () {
      if (!this.form.current_password || !this.form.password || !this.form.password_confirmation) {
        this.$alert.error('Completa todos los campos')
        return
      }
      if (this.form.password !== this.form.password_confirmation) {
        this.$alert.error('La confirmacion no coincide')
        return
      }
      this.confirmDialog = true
    },
    async submit () {
      this.loading = true
      try {
        await this.$axios.post('/me/password/update', this.form)

        this.$alert.success('Contrasena actualizada. Inicia sesion nuevamente.')
        this.confirmDialog = false

        this.$store.isLogged = false
        this.$store.user = {}
        this.$store.permissions = []
        localStorage.removeItem('tokenTicket')
        localStorage.removeItem('user')

        this.$router.push('/login')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cambiar la contrasena')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
