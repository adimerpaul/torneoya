<template>
  <q-layout view="lhh Lpr lFf">
    <q-header :class="['public-header', isLightMode ? 'header-light text-dark' : 'header-dark text-white']" bordered>
      <q-toolbar>
        <div class="text-subtitle1 text-weight-medium header-title">Torneo Ya - Vive tus deportes</div>
        <q-space />
        <q-btn
          flat
          round
          dense
          :color="isLightMode ? 'dark' : 'white'"
          :icon="isLightMode ? 'dark_mode' : 'light_mode'"
          @click="toggleTheme"
        >
          <q-tooltip>{{ isLightMode ? 'Modo oscuro' : 'Modo claro' }}</q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view :is-light-mode="isLightMode" />
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: 'PublicCampeonatoLayout',
  data () {
    return {
      isLightMode: false
    }
  },
  mounted () {
    const saved = localStorage.getItem('torneo_public_theme')
    if (saved === 'light' || saved === 'dark') {
      this.isLightMode = saved === 'light'
    } else {
      this.isLightMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches
    }
    this.applyTheme()
  },
  methods: {
    toggleTheme () {
      this.isLightMode = !this.isLightMode
      localStorage.setItem('torneo_public_theme', this.isLightMode ? 'light' : 'dark')
      this.applyTheme()
    },
    applyTheme () {
      const root = document.documentElement
      root.classList.toggle('public-light', this.isLightMode)
    }
  }
}
</script>

<style scoped>
.public-header {
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}
.header-dark {
  background: #0b1220;
  border-bottom: 1px solid rgba(148, 163, 184, 0.3);
}
.header-light {
  background: #f8fafc;
  border-bottom: 1px solid #cbd5e1;
}
.header-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 72vw;
}
</style>
