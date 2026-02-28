<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="public-header text-white" bordered>
      <q-toolbar>
        <q-btn v-if="$store?.isLogged" flat dense round color="white" icon="arrow_back" @click="$router.push('/login')" />
        <div class="text-subtitle1 text-weight-medium q-ml-sm">Torneo Ya - Vive tus deportes</div>
        <q-space />
        <q-badge color="white" text-color="indigo-10">Codigo {{ code }}</q-badge>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: 'PublicCampeonatoLayout',
  computed: {
    code () {
      return (this.$route.params.code || '').toString().toUpperCase()
    }
  }
}
</script>

<style scoped>
.public-header {
  background: #0b1220;
  border-bottom: 1px solid rgba(148, 163, 184, 0.3);
}
</style>
