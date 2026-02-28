<template>
  <q-page class="q-pa-md flex flex-center">
    <q-spinner color="primary" size="40px" />
  </q-page>
</template>

<script>
export default {
  mounted() {
    this.$router.replace('/campeonatos')
  }
}
</script>
