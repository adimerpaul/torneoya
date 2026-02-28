<template>
  <q-layout class="login-layout">
    <q-page-container>
      <q-page class="full-height">
        <div class="login-bg-overlay"></div>

        <q-form @submit="login" class="login-wrap">
          <q-card flat bordered class="login-card">
            <q-card-section class="q-pt-lg text-center">
              <q-img src="logo.png" width="110px" class="q-mb-sm" ratio="1" fit="contain" />
              <br>
              <div class="text-subtitle2 text-grey-7 brand-chip">
                <b>Torneo Ya</b>
              </div>
            </q-card-section>

            <q-separator spaced />

            <q-card-section class="q-pt-none">
              <div class="text-h6 text-bold q-mb-xs">Iniciar sesion</div>
              <div class="text-body2 text-grey-7 q-mb-md">
                Accede al panel de administracion usando tus credenciales.
              </div>

              <div class="q-mb-sm text-caption text-grey-7">Nombre de usuario</div>
              <q-input
                v-model="username"
                outlined
                dense
                placeholder="Nombre de usuario"
                :rules="[v => !!v || 'Ingrese su nombre de usuario']"
                class="q-mb-md"
              >
                <template #prepend><q-icon name="account_circle" size="18px" /></template>
              </q-input>

              <div class="q-mb-sm text-caption text-grey-7">Contrasena</div>
              <q-input
                v-model="password"
                outlined
                dense
                :type="showPassword ? 'text' : 'password'"
                placeholder="Contrasena"
                :rules="[v => !!v || 'Ingrese su contrasena']"
              >
                <template #prepend><q-icon name="lock" size="18px" /></template>
                <template #append>
                  <q-icon
                    :name="showPassword ? 'visibility' : 'visibility_off'"
                    size="18px"
                    class="cursor-pointer"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </q-input>

              <div class="row items-center q-mt-sm q-mb-md">
                <q-checkbox v-model="rememberMe" label="Recordarme" color="primary" dense />
                <q-space />
                <q-btn flat dense no-caps class="text-weight-medium link-muted" label="Olvide mi contrasena" />
              </div>

              <q-btn
                color="primary"
                label="Iniciar sesion"
                class="full-width btnLogin"
                no-caps
                unelevated
                size="16px"
                :loading="loading"
                type="submit"
              />
            </q-card-section>

            <q-card-section class="q-pt-none text-center">
              <div class="text-caption text-grey-6">
                (c) {{ year }} Torneo Ya. Todos los derechos reservados.
              </div>
            </q-card-section>
          </q-card>
        </q-form>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)
const loading = ref(false)
const year = computed(() => new Date().getFullYear())

function login() {
  loading.value = true
  proxy.$axios.post('/login', { username: username.value, password: password.value })
    .then(res => {
      const { user, token } = res.data
      proxy.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
      proxy.$store.isLogged = true
      proxy.$store.user = user
      proxy.$store.permissions = (user.permissions || []).map(p => typeof p === 'string' ? p : p?.name)
      localStorage.setItem('tokenTorneoya', token)
      localStorage.setItem('user', JSON.stringify(user))
      proxy.$alert.success('Bienvenido', user.name)
      proxy.$router.push('/')
    })
    .catch(err => {
      proxy.$alert.error(err?.response?.data?.message || 'Error de autenticacion', 'Error')
    })
    .finally(() => {
      loading.value = false
    })
}
</script>

<style scoped>
.login-layout {
  background-image: url('./../bg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  min-height: 100vh;
}

.full-height {
  min-height: 100vh;
  position: relative;
}

.login-bg-overlay {
  position: absolute;
  inset: 0;
  backdrop-filter: blur(5px);
  background: radial-gradient(1200px 800px at 70% 40%, rgba(0, 0, 0, 0.12), rgba(0, 0, 0, 0.25));
}

.login-wrap {
  position: relative;
  z-index: 1;
  max-width: 520px;
  margin: 0 auto;
  padding: 24px 12px;
  display: flex;
  align-items: center;
  min-height: 100vh;
}

.login-card {
  width: 100%;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.05);
}

.brand-chip {
  display: inline-block;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.06);
}

.link-muted {
  color: #6b7280 !important;
}

.link-muted:hover {
  color: var(--q-primary) !important;
}

.btnLogin {
  height: 42px;
  border-radius: 10px;
  transition: all 0.25s ease;
}

.btnLogin:hover {
  background-color: #fff !important;
  color: var(--q-primary) !important;
  outline: 1px solid var(--q-primary) !important;
}

@media (max-width: 768px) {
  .login-wrap {
    max-width: 92vw;
    padding: 16px 8px;
  }

  .login-card {
    border-radius: 14px;
  }
}
</style>
