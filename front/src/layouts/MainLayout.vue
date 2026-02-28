<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-toolbar-title>
          Torneo Ya
        </q-toolbar-title>

        <div v-if="auth.user" class="row items-center q-gutter-sm">
          <q-chip color="white" text-color="primary" dense>
            {{ auth.user.name }}
          </q-chip>
          <q-btn flat icon="logout" label="Salir" @click="logout" />
        </div>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/example-store'
import { api } from 'src/boot/axios'

const router = useRouter()
const auth = useAuthStore()

async function logout() {
  try {
    await api.post('/logout')
  } catch (error) {
    // No-op: limpiamos sesión local aunque el backend responda error.
  } finally {
    auth.clearSession()
    delete api.defaults.headers.common.Authorization
    await router.push('/login')
  }
}
</script>
