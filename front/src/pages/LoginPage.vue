<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="login-card q-pa-md" flat bordered>
      <q-card-section>
        <div class="text-h5 text-weight-bold">Iniciar sesión</div>
        <div class="text-grey-7">Torneo Ya - acceso al sistema</div>
      </q-card-section>

      <q-card-section>
        <q-form class="q-gutter-md" @submit="onLogin">
          <q-input
            v-model="username"
            label="Usuario"
            outlined
            dense
            :rules="[v => !!v || 'Usuario requerido']"
          />
          <q-input
            v-model="password"
            label="Contraseña"
            type="password"
            outlined
            dense
            :rules="[v => !!v || 'Contraseña requerida']"
          />
          <q-btn
            color="primary"
            label="Ingresar"
            class="full-width"
            :loading="loading"
            type="submit"
          />
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from 'src/boot/axios'
import { useAuthStore } from 'src/stores/example-store'

const $q = useQuasar()
const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const password = ref('')
const loading = ref(false)

async function onLogin() {
  loading.value = true
  try {
    const { data } = await api.post('/login', {
      username: username.value,
      password: password.value
    })

    auth.setSession({
      token: data.token,
      user: data.user
    })
    api.defaults.headers.common.Authorization = `Bearer ${data.token}`

    const me = await api.get('/me')
    auth.setSession({
      token: data.token,
      user: me.data.user
    })

    await router.push('/')
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error?.response?.data?.message || 'No se pudo iniciar sesión.'
    })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-card {
  width: min(420px, 92vw);
}
</style>
