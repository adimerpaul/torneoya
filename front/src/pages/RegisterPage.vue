<template>
  <q-layout class="login-layout">
    <q-page-container>
      <q-page class="full-height">
        <div class="login-bg-overlay"></div>

        <q-form @submit="registerUser" class="login-wrap">
          <q-card flat bordered class="login-card">
            <q-card-section class="q-pt-lg text-center">
              <q-img src="logo.png" width="110px" class="q-mb-sm" ratio="1" fit="contain" />
              <div class="text-subtitle2 text-grey-7 brand-chip">
                <b>Torneo Ya</b>
              </div>
            </q-card-section>

            <q-separator spaced />

            <q-card-section class="q-pt-none">
              <div class="text-h6 text-bold q-mb-xs">Crear cuenta</div>
              <div class="text-body2 text-grey-7 q-mb-md">
                Completa tus datos basicos para registrarte.
              </div>

              <q-input
                v-model="form.name"
                outlined
                dense
                label="Nombre completo"
                :rules="[v => !!v || 'El nombre es obligatorio']"
                class="q-mb-md"
              />

              <q-input
                v-model="form.email"
                outlined
                dense
                label="Correo"
                type="email"
                :rules="[v => !!v || 'El correo es obligatorio']"
                class="q-mb-md"
              />

              <q-input
                v-model="form.username"
                outlined
                dense
                label="Usuario"
                :rules="[v => !!v || 'El usuario es obligatorio']"
                class="q-mb-md"
              />

              <q-input
                v-model="form.password"
                outlined
                dense
                label="Contrasena"
                :type="showPassword ? 'text' : 'password'"
                :rules="[v => !!v || 'La contrasena es obligatoria']"
                class="q-mb-md"
              >
                <template #append>
                  <q-icon
                    :name="showPassword ? 'visibility' : 'visibility_off'"
                    class="cursor-pointer"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </q-input>

              <q-input
                v-model="form.password_confirmation"
                outlined
                dense
                label="Confirmar contrasena"
                :type="showPassword ? 'text' : 'password'"
                :rules="[v => !!v || 'Debe confirmar la contrasena']"
                class="q-mb-md"
              />

              <div class="avatar-box q-mb-md">
                <div class="row items-center q-col-gutter-md">
                  <div class="col-auto">
                    <q-avatar size="70px" color="grey-3" text-color="grey-8">
                      <q-img v-if="avatarPreview" :src="avatarPreview" />
                      <q-icon v-else name="person" size="36px" />
                    </q-avatar>
                  </div>
                  <div class="col">
                    <q-file
                      v-model="form.avatar"
                      outlined
                      dense
                      label="Fotografia (opcional)"
                      accept=".jpg,.jpeg,.png,.webp"
                      clearable
                    >
                      <template #prepend>
                        <q-icon name="photo_camera" />
                      </template>
                    </q-file>
                    <div class="text-caption text-grey-7 q-mt-xs">
                      Si no subes foto, se usara avatar.png por defecto.
                    </div>
                  </div>
                </div>
              </div>

              <q-btn
                color="deep-orange"
                label="Registrarme"
                class="full-width btnLogin"
                no-caps
                unelevated
                size="16px"
                :loading="loading"
                type="submit"
              />
              <q-btn
                flat
                color="primary"
                label="Ya tengo cuenta"
                class="full-width q-mt-sm"
                no-caps
                @click="$router.push('/login')"
              />
            </q-card-section>
          </q-card>
        </q-form>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { getCurrentInstance, onBeforeUnmount, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

const loading = ref(false)
const showPassword = ref(false)
const form = ref({
  name: '',
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
  avatar: null
})

const avatarPreview = ref('')

watch(() => form.value.avatar, (file) => {
  if (avatarPreview.value) {
    URL.revokeObjectURL(avatarPreview.value)
    avatarPreview.value = ''
  }
  if (file) {
    avatarPreview.value = URL.createObjectURL(file)
  }
})

onBeforeUnmount(() => {
  if (avatarPreview.value) {
    URL.revokeObjectURL(avatarPreview.value)
  }
})

function registerUser() {
  loading.value = true
  const payload = new FormData()
  payload.append('name', form.value.name)
  payload.append('email', form.value.email)
  payload.append('username', form.value.username)
  payload.append('password', form.value.password)
  payload.append('password_confirmation', form.value.password_confirmation)
  if (form.value.avatar) {
    payload.append('avatar', form.value.avatar)
  }

  proxy.$axios.post('/register', payload, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })
    .then(res => {
      const { user, token } = res.data
      proxy.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
      proxy.$store.isLogged = true
      proxy.$store.user = user
      proxy.$store.permissions = (user.permissions || []).map(p => typeof p === 'string' ? p : p?.name)
      localStorage.setItem('tokenTorneoya', token)
      localStorage.setItem('user', JSON.stringify(user))
      proxy.$alert.success('Cuenta creada', user.name)
      proxy.$router.push('/')
    })
    .catch(err => {
      proxy.$alert.error(err?.response?.data?.message || 'No se pudo registrar la cuenta', 'Error')
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

.avatar-box {
  padding: 10px;
  border: 1px dashed rgba(0, 0, 0, 0.15);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.4);
}
</style>
