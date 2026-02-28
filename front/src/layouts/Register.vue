<template>
  <q-layout class="register-layout">
    <q-page-container>
      <q-page class="full-height">
        <div class="register-bg-overlay"></div>

        <div class="register-wrap">
          <q-card flat bordered class="register-card">
            <q-card-section class="q-pt-lg text-center">
              <q-img src="logo.png" width="96px" ratio="1" fit="contain" />
              <div class="text-subtitle2 text-grey-7 brand-chip q-mt-sm">
                <b>Torneo Ya</b> - Crea tus torneos
              </div>
            </q-card-section>

            <q-separator spaced />

            <q-card-section>
              <div class="text-h6 text-bold q-mb-sm">Registro</div>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-8">
                  <q-form @submit.prevent="register">
                    <q-input v-model="form.name" outlined dense label="Nombre" :rules="[req]" class="q-mb-sm" />
                    <q-input v-model="form.username" outlined dense label="Username" :rules="[req]" class="q-mb-sm" />
                    <q-input v-model="form.email" outlined dense type="email" label="Gmail (opcional)" class="q-mb-sm" />

                    <q-input
                      v-model="form.password"
                      outlined
                      dense
                      :type="showPassword ? 'text' : 'password'"
                      label="Clave"
                      :rules="[req]"
                      class="q-mb-sm"
                    >
                      <template v-slot:append>
                        <q-icon
                          :name="showPassword ? 'visibility' : 'visibility_off'"
                          class="cursor-pointer"
                          @click="showPassword = !showPassword"
                        />
                      </template>
                    </q-input>

                    <q-input
                      v-model="form.telefono_contacto_1"
                      outlined
                      dense
                      label="Telefono de contacto"
                      :rules="[req]"
                      class="q-mb-sm"
                    />

                    <q-file
                      v-model="form.avatar"
                      outlined
                      dense
                      label="Avatar"
                      accept="image/jpeg,image/png,image/webp"
                      @update:model-value="onAvatarSelected"
                      class="q-mb-sm"
                    >
                      <template v-slot:prepend>
                        <q-icon name="image" />
                      </template>
                    </q-file>

                    <q-banner dense class="bg-blue-1 text-blue-9 q-mb-md" rounded>
                      Avatar recomendado: hasta 2 MB. Se guarda comprimido en WEBP (256x256).
                    </q-banner>

                    <div class="row q-gutter-sm">
                      <q-btn color="primary" unelevated no-caps :loading="loading" label="Crear cuenta" type="submit" />
                      <q-btn outline color="primary" no-caps label="Volver a login" @click="$router.push('/login')" />
                    </div>
                  </q-form>
                </div>

                <div class="col-12 col-md-4">
                  <q-card flat bordered class="preview-card">
                    <q-card-section>
                      <div class="text-subtitle2 text-weight-medium q-mb-sm">Vista previa del avatar</div>
                      <div class="row justify-center q-mb-sm">
                        <q-avatar size="110px" class="shadow-2">
                          <q-img v-if="avatarPreview" :src="avatarPreview" />
                          <q-icon v-else name="person" size="42px" />
                        </q-avatar>
                      </div>
                      <div class="text-caption text-grey-8">Peso: {{ avatarInfo.sizeText }}</div>
                      <div class="text-caption text-grey-8">Dimensiones: {{ avatarInfo.dimensionsText }}</div>
                      <div class="text-caption text-grey-8">Formato: {{ avatarInfo.typeText }}</div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </q-card-section>

            <q-card-section class="text-center q-pt-none">
              <div class="text-caption text-grey-6">(c) {{ year }} Torneo Ya</div>
            </q-card-section>
          </q-card>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: 'RegisterTorneoYa',
  data () {
    return {
      loading: false,
      showPassword: false,
      avatarPreview: null,
      avatarInfo: {
        sizeText: 'Sin archivo',
        dimensionsText: '-',
        typeText: '-'
      },
      form: {
        name: '',
        username: '',
        email: '',
        password: '',
        telefono_contacto_1: '',
        avatar: null
      }
    }
  },
  computed: {
    year () {
      return new Date().getFullYear()
    }
  },
  beforeUnmount () {
    if (this.avatarPreview) URL.revokeObjectURL(this.avatarPreview)
  },
  methods: {
    req (v) {
      return !!v || 'Campo requerido'
    },
    formatBytes (bytes) {
      if (!bytes) return '0 B'
      const units = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(1024))
      const value = bytes / Math.pow(1024, i)
      return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[i]}`
    },
    async onAvatarSelected (file) {
      if (this.avatarPreview) {
        URL.revokeObjectURL(this.avatarPreview)
        this.avatarPreview = null
      }

      if (!file) {
        this.avatarInfo = {
          sizeText: 'Sin archivo',
          dimensionsText: '-',
          typeText: '-'
        }
        return
      }

      const maxBytes = 2 * 1024 * 1024
      if (file.size > maxBytes) {
        this.$alert?.error?.('La imagen supera 2 MB. Elige una mas liviana.')
        this.form.avatar = null
        this.avatarInfo = {
          sizeText: this.formatBytes(file.size),
          dimensionsText: '-',
          typeText: file.type || '-'
        }
        return
      }

      this.avatarPreview = URL.createObjectURL(file)
      this.avatarInfo.sizeText = this.formatBytes(file.size)
      this.avatarInfo.typeText = file.type || '-'

      const img = new Image()
      img.onload = () => {
        this.avatarInfo.dimensionsText = `${img.width} x ${img.height}px`
      }
      img.onerror = () => {
        this.avatarInfo.dimensionsText = 'No disponible'
      }
      img.src = this.avatarPreview
    },
    register () {
      this.loading = true
      const formData = new FormData()
      formData.append('name', this.form.name)
      formData.append('username', this.form.username)
      formData.append('password', this.form.password)
      formData.append('telefono_contacto_1', this.form.telefono_contacto_1)

      if (this.form.email) formData.append('email', this.form.email)
      if (this.form.avatar) formData.append('avatar', this.form.avatar)

      this.$axios.post('/register', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then(res => {
          const { user, token } = res.data
          this.$axios.defaults.headers.common.Authorization = `Bearer ${token}`

          if (this.$store) {
            this.$store.isLogged = true
            this.$store.user = user
            this.$store.permissions = (user.permissions || []).map(p => p.name)
          }

          localStorage.setItem('tokenTorneoya', token)
          localStorage.setItem('user', JSON.stringify(user))

          this.$alert?.success?.('Registro exitoso. Bienvenido.')
          this.$router.push('/')
        })
        .catch(err => {
          const data = err?.response?.data
          const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null
          const msg = firstError || data?.message || 'No se pudo registrar el usuario'
          this.$alert?.error?.(msg, 'Error')
        })
        .finally(() => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped>
.register-layout {
  background-image: url('./../bg.jpg');
  background-size: cover;
  background-position: center;
  min-height: 100vh;
}
.full-height { min-height: 100vh; position: relative; }
.register-bg-overlay {
  position: absolute;
  inset: 0;
  backdrop-filter: blur(3px);
  background: radial-gradient(1200px 800px at 70% 40%, rgba(0,0,0,0.10), rgba(0,0,0,0.26));
}
.register-wrap {
  position: relative;
  z-index: 1;
  max-width: 900px;
  margin: 0 auto;
  padding: 20px 12px;
  min-height: 100vh;
  display: flex;
  align-items: center;
}
.register-card {
  width: 100%;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.84);
  border: 1px solid rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(12px);
}
.brand-chip {
  display: inline-block;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.06);
}
.preview-card {
  background: rgba(255, 255, 255, 0.75);
  border-radius: 12px;
}
</style>

