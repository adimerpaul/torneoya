
<template>
  <q-page class="public-page" :class="{ 'mode-light': isLightMode }">
    <div class="hero" :style="{ backgroundImage: `url(${imageSrc(campeonato.banner || 'torneoBanner.jpg')})` }">
      <div class="hero-overlay">
        <div class="hero-content">
          <q-avatar rounded size="76px" class="hero-logo">
            <q-img :src="configImagePreview || imageSrc(campeonato.imagen || 'torneoImagen.jpg')" />
          </q-avatar>

          <div class="hero-main">
            <div class="text-h5 text-weight-bold text-white">{{ campeonato.nombre || 'Campeonato' }}</div>
            <div class="row items-center q-gutter-sm q-mt-xs">
              <div class="text-blue-1">{{ rangoFechas(campeonato.fecha_inicio, campeonato.fecha_fin) }}</div>
              <q-chip
                dense
                :color="esPadreCategorias ? 'deep-orange-2' : 'blue-2'"
                :text-color="esPadreCategorias ? 'deep-orange-10' : 'blue-10'"
              >
                {{ esPadreCategorias ? 'Tipo: categorias' : 'Tipo: unico' }}
              </q-chip>
            </div>
            <div class="row items-center q-gutter-sm q-mt-sm">
              <q-chip dense color="indigo-2" text-color="indigo-10" icon="shield">Codigo: {{ campeonato.codigo }}</q-chip>
              <q-chip dense color="teal-2" text-color="teal-10" icon="person">
                Creador: {{ creadorNombre }}
              </q-chip>
              <q-chip
                v-if="campeonato?.user?.telefono_contacto_1"
                dense
                color="green-2"
                text-color="green-10"
                icon="phone"
              >
                {{ campeonato.user.telefono_contacto_1 }}
              </q-chip>
            </div>
          </div>

          <q-space />

          <div class="column q-gutter-sm actions-col">
            <q-btn color="white" text-color="indigo-9" no-caps icon="share" label="Compartir" @click="sharePublic" />
            <q-btn v-if="creatorWhatsappLink" color="positive" no-caps icon="chat" label="WhatsApp al creador" @click="openWhatsApp" />
          </div>
        </div>
      </div>
    </div>

    <q-card flat bordered class="q-ma-md panel-shell">
      <q-tabs v-model="tab" dense align="left" no-caps class="pub-tabs">
        <q-tab name="inicio" label="Inicio" :icon="tabIcon('inicio')" :class="tabClass('inicio')" />
        <q-tab
          v-if="!esPadreCategorias"
          name="clasificacion"
          label="Clasificacion"
          :icon="tabIcon('clasificacion')"
          :class="tabClass('clasificacion')"
        />
        <q-tab name="ranking" label="Ranking" :icon="tabIcon('ranking')" :class="tabClass('ranking')" />
        <q-tab
          v-if="$store?.isLogged"
          name="configuracion"
          label="Configuracion"
          :icon="tabIcon('configuracion')"
          :class="tabClass('configuracion')"
        />
      </q-tabs>

      <q-tab-panels v-model="tab" animated class="panels">
        <q-tab-panel name="inicio">
          <q-banner v-if="showParentBanner" dense rounded class="bg-indigo-2 text-indigo-10 q-mb-md">
            Este campeonato pertenece a: <b>{{ campeonato.parent.nombre }}</b>
            <q-btn
              v-if="$store?.isLogged"
              color="indigo-9"
              text-color="white"
              class="q-ml-sm btn-parent-back"
              no-caps
              label="Volver al campeonato principal"
              @click="goToPadre"
            />
          </q-banner>

          <div class="text-subtitle1 text-weight-medium q-mb-sm text-cyan-2">Resumen</div>
          <q-card flat bordered class="q-mb-md bg-dark-card text-blue-1">
            <q-card-section>{{ campeonato.descripcion || 'Sin descripcion' }}</q-card-section>
          </q-card>

          <div v-if="esPadreCategorias" class="q-mb-md">
            <div class="text-subtitle1 text-green-3 q-mb-sm">Categorias del campeonato</div>
            <div class="row q-col-gutter-sm">
              <div v-for="cat in (campeonato.categorias || [])" :key="cat.id" class="col-12 col-md-4">
                <q-card flat bordered class="bg-dark-card text-white">
                  <q-card-section class="row items-center q-gutter-sm">
                    <q-avatar rounded size="44px"><q-img :src="imageSrc(cat.imagen || 'torneoImagen.jpg')" /></q-avatar>
                    <div class="col">
                      <div class="text-weight-bold">{{ cat.nombre }}</div>
                      <div class="text-caption text-blue-2">{{ deporteNombre(cat.deporte) || cat.deporte || 'Sin deporte' }}</div>
                    </div>
                    <q-btn dense color="primary" class="btn-enter-cat" no-caps label="Entrar" @click="entrarCategoria(cat)" />
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>

          <div v-else class="q-mb-md">
            <div class="row items-center q-mb-sm">
              <div class="text-subtitle1 text-weight-medium text-green-3">Equipos</div>
              <q-space />
              <q-btn v-if="canEdit" color="green-7" no-caps icon="groups" label="Gestionar equipos" @click="openEquiposDialog" />
            </div>

            <div v-if="!equipos.length" class="text-grey-5">Aun no hay equipos registrados</div>
            <div v-else class="row q-col-gutter-sm">
              <div v-for="eq in equipos" :key="eq.id" class="col-12 col-md-6">
                <q-card flat bordered class="bg-dark-card text-white">
                  <q-card-section class="row items-center q-gutter-sm">
                    <q-avatar rounded size="44px">
                      <q-img :src="imageSrc(eq.imagen || 'torneoImagen.jpg')" />
                    </q-avatar>
                    <div class="col">
                      <div class="text-weight-bold">{{ eq.nombre }}</div>
                      <div class="text-caption text-blue-2">{{ eq.entrenador || 'Sin entrenador' }}</div>
                    </div>
                    <q-chip dense color="teal-2" text-color="teal-10">{{ eq.grupo_nombre || 'Sin categoria' }}</q-chip>
                    <q-chip dense color="indigo-2" text-color="indigo-10">{{ (eq.jugadores || []).length }} jugadores</q-chip>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>

          <div class="text-subtitle1 text-weight-medium q-mb-sm text-deep-orange-3">Mensajes</div>
          <q-list bordered separator class="rounded-borders messages-list q-mb-md">
            <q-item v-for="m in mensajes" :key="m.id" class="items-start">
              <q-item-section>
                <q-item-label class="text-white">
                  <b>{{ m.user?.name || m.user?.username || m.autor_nombre || 'Publico' }}</b>
                  <q-badge v-if="!m.visible" color="orange" class="q-ml-sm">Oculto</q-badge>
                </q-item-label>
                <q-item-label caption class="text-blue-2">{{ formatDateTime(m.created_at) }}</q-item-label>
                <q-item-label class="q-mt-xs text-grey-3">{{ m.mensaje }}</q-item-label>
              </q-item-section>
              <q-item-section side v-if="canModerate">
                <q-btn
                  dense
                  flat
                  :color="m.visible ? 'negative' : 'positive'"
                  :label="m.visible ? 'Ocultar' : 'Mostrar'"
                  :icon="m.visible ? 'visibility_off' : 'visibility'"
                  @click="toggleVisible(m)"
                />
              </q-item-section>
            </q-item>
          </q-list>

          <q-card flat bordered class="bg-dark-card">
            <q-card-section class="row q-col-gutter-sm">
              <div class="col-12 col-md-10">
                <q-input
                  v-model="newMessage"
                  type="textarea"
                  autogrow
                  outlined
                  :dark="!isLightMode"
                  color="teal-3"
                  label="Escribe tu mensaje"
                  hint="Tu mensaje se publica al presionar Enviar"
                />
              </div>
              <div class="col-12 col-md-2 flex flex-center">
                <q-btn color="deep-orange" no-caps class="full-width" label="Enviar" icon="send" @click="sendMessage" :loading="sendingMessage" />
              </div>
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <q-tab-panel v-if="!esPadreCategorias" name="clasificacion" class="panel-light-blue">
          <ClasificacionPanel
            :code="$route.params.code"
            :campeonato="campeonato"
            :can-edit="canEdit"
            :is-light-mode="isLightMode"
            :mensajes="mensajes"
            @refresh-mensajes="cargarMensajes"
          />
        </q-tab-panel>

        <q-tab-panel name="ranking" class="panel-light-amber">
          <q-banner dense rounded class="bg-white text-brown-9">
            <q-icon name="construction" class="q-mr-xs" />
            Ranking en construccion (goles y asistencias).
          </q-banner>
        </q-tab-panel>

        <q-tab-panel name="configuracion" v-if="$store?.isLogged">
          <q-banner v-if="!canEdit" dense rounded class="bg-orange-2 text-orange-10 q-mb-sm">
            Solo el creador o administrador logueado puede modificar este campeonato.
          </q-banner>

          <q-form @submit.prevent="saveConfig()">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-6">
                <q-input v-model="config.nombre" dense outlined :dark="!isLightMode" color="cyan-3" label="Nombre" :disable="!canEdit" />
              </div>
              <div class="col-12 col-md-6">
                <q-option-group
                  v-model="config.tipo"
                  :options="[
                    { label: 'Unico', value: 'unico' },
                    { label: 'Multiples categorias', value: 'categorias' }
                  ]"
                  type="radio"
                  inline
                  :disable="!canEdit"
                  color="cyan-3"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.fecha_inicio" dense outlined :dark="!isLightMode" type="date" color="cyan-3" label="Fecha inicio" :disable="!canEdit" />
              </div>
              <div class="col-12 col-md-6">
                <q-input v-model="config.fecha_fin" dense outlined :dark="!isLightMode" type="date" color="cyan-3" label="Fecha fin" :disable="!canEdit" />
              </div>
              <div class="col-12 col-md-6" v-if="config.tipo === 'unico'">
                <q-select
                  v-model="config.deporte"
                  dense
                  outlined
                  :dark="!isLightMode"
                  color="cyan-3"
                  emit-value
                  map-options
                  option-value="key"
                  option-label="nombre"
                  :options="deportes"
                  label="Deporte"
                  :disable="!canEdit"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-file
                  v-model="config.imagen"
                  dense
                  outlined
                  :dark="!isLightMode"
                  color="cyan-3"
                  label="Logo/imagen"
                  accept="image/*"
                  :disable="!canEdit"
                  @update:model-value="onConfigFileChange('imagen', $event)"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-file
                  v-model="config.banner"
                  dense
                  outlined
                  :dark="!isLightMode"
                  color="cyan-3"
                  label="Banner/fondo"
                  accept="image/*"
                  :disable="!canEdit"
                  @update:model-value="onConfigFileChange('banner', $event)"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-card flat bordered class="bg-dark-card">
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-blue-2 q-mb-xs">Vista previa logo</div>
                    <q-img :src="configImagePreview || imageSrc(campeonato.imagen || 'torneoImagen.jpg')" :ratio="1" />
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-md-6">
                <q-card flat bordered class="bg-dark-card">
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-blue-2 q-mb-xs">Vista previa banner</div>
                    <q-img :src="configBannerPreview || imageSrc(campeonato.banner || 'torneoBanner.jpg')" :ratio="16 / 9" />
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12">
                <q-input v-model="config.descripcion" dense outlined :dark="!isLightMode" color="cyan-3" type="textarea" autogrow label="Descripcion" :disable="!canEdit" />
              </div>
            </div>

            <div class="row justify-end q-mt-sm" v-if="canEdit">
              <q-btn color="primary" no-caps label="Guardar cambios" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-tab-panel>
      </q-tab-panels>

      <q-dialog v-model="equiposDialog" persistent>
        <q-card style="width: 980px; max-width: 98vw" class="bg-grey-1">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">Gestionar equipos</div>
            <q-space />
            <q-btn flat round dense icon="close" @click="equiposDialog = false" />
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-12 col-md-6">
                <div class="text-subtitle2 q-mb-xs">Categorias existentes</div>
                <div class="row q-gutter-xs">
                  <q-chip
                    v-for="g in grupos"
                    :key="g.id"
                    dense
                    color="teal-2"
                    text-color="teal-10"
                    :label="g.nombre"
                  />
                  <q-chip v-if="!grupos.length" dense color="grey-4" text-color="black" label="Sin categorias" />
                </div>
              </div>
              <div class="col-12 col-md-6 row justify-end items-center q-gutter-sm">
                <q-btn color="indigo" no-caps icon="category" label="Crear categorias" @click="openGruposDialog" />
                <q-btn color="primary" no-caps icon="groups" label="Crear equipo" @click="openEquipoDialog()" />
              </div>
            </div>

            <q-table
              :rows="equipos"
              :columns="equiposAdminColumns"
              row-key="id"
              flat
              bordered
              dense
              :rows-per-page-options="[0]"
              no-data-label="No hay equipos"
            >
              <template #body-cell-imagen="props">
                <q-td :props="props">
                  <q-avatar rounded size="34px">
                    <q-img :src="imageSrc(props.row.imagen || 'torneoImagen.jpg')" />
                  </q-avatar>
                </q-td>
              </template>
              <template #body-cell-categoria="props">
                <q-td :props="props">{{ props.row.grupo_nombre || 'Sin categoria' }}</q-td>
              </template>
              <template #body-cell-jugadores_count="props">
                <q-td :props="props" class="text-right">
                  <q-chip dense color="indigo-2" text-color="indigo-10">
                    {{ (props.row.jugadores || []).length }}
                  </q-chip>
                </q-td>
              </template>
              <template #body-cell-actions="props">
                <q-td :props="props" class="text-left">
                  <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
                    <q-list>
                      <q-item clickable v-close-popup @click="openEquipoDialog(props.row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="openJugadorDialog(props.row)">
                        <q-item-section avatar><q-icon name="groups" /></q-item-section>
                        <q-item-section><q-item-label>Jugadores</q-item-label></q-item-section>
                      </q-item>
                      <q-separator />
                      <q-item clickable v-close-popup @click="eliminarEquipo(props.row)">
                        <q-item-section avatar><q-icon name="delete" /></q-item-section>
                        <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </q-dialog>

      <q-dialog v-model="gruposDialog" persistent>
        <q-card style="width: 620px; max-width: 95vw">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">Categorias del campeonato</div>
            <q-space />
            <q-btn flat round dense icon="close" @click="gruposDialog = false" />
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-md-6">
                <q-select
                  v-model="groupAutoCount"
                  dense
                  outlined
                  :options="groupAutoCountOptions"
                  label="Cuantas categorias quieres crear"
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-md-6 flex items-center">
                <q-btn color="teal" no-caps icon="auto_awesome" label="Generar grupos A/B/C..." @click="crearGruposAuto" />
              </div>
            </div>

            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-md-8">
                <q-input v-model="grupoForm.nombre" dense outlined label="Nombre de categoria" />
              </div>
              <div class="col-12 col-md-4">
                <q-btn class="full-width" color="indigo" no-caps :label="grupoForm.id ? 'Actualizar' : 'Agregar'" @click="guardarGrupo" />
              </div>
            </div>

            <q-list bordered separator>
              <q-item v-for="g in grupos" :key="g.id">
                <q-item-section>{{ g.nombre }}</q-item-section>
                <q-item-section side>
                  <q-btn dense flat color="primary" icon="edit" @click="editarGrupo(g)" />
                  <q-btn dense flat color="negative" icon="delete" @click="eliminarGrupo(g)" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </q-dialog>

      <q-dialog v-model="equipoDialog" persistent>
        <q-card style="width: 680px; max-width: 95vw">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">{{ teamForm.id ? 'Editar equipo' : 'Nuevo equipo' }}</div>
            <q-space />
            <q-btn flat round dense icon="close" @click="equipoDialog = false" />
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-6"><q-input v-model="teamForm.nombre" dense outlined label="Nombre del equipo" /></div>
              <div class="col-12 col-md-6"><q-input v-model="teamForm.entrenador" dense outlined label="Entrenador" /></div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="teamForm.campeonato_grupo_id"
                  dense
                  outlined
                  clearable
                  emit-value
                  map-options
                  :options="grupoSelectOptions"
                  option-value="id"
                  option-label="nombre"
                  label="Categoria (opcional)"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-file v-model="teamForm.imagen" dense outlined label="Imagen equipo" accept="image/*" @update:model-value="onTeamImageChange" />
              </div>
              <div class="col-12 col-md-6">
                <q-img :src="teamPreview || imageSrc(teamForm.imagen_actual || 'torneoImagen.jpg')" :ratio="1" />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat no-caps label="Cancelar" @click="equipoDialog = false" />
            <q-btn color="primary" no-caps :label="teamForm.id ? 'Actualizar' : 'Crear'" @click="guardarEquipo" />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <q-dialog v-model="jugadorDialog" persistent>
        <q-card style="width: 840px; max-width: 98vw">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">Jugadores de {{ selectedEquipo?.nombre }}</div>
            <q-chip dense color="indigo-2" text-color="indigo-10" class="q-ml-sm">
              {{ (selectedEquipo?.jugadores || []).length }} jugadores
            </q-chip>
            <q-space />
            <q-btn
              color="primary"
              no-caps
              icon="person_add"
              label="Agregar jugador"
              :loading="jugadorSaving"
              :disable="playersLoading || jugadorSaving"
              @click="openJugadorFormDialog()"
            />
            <q-btn flat round dense icon="close" @click="jugadorDialog = false" />
          </q-card-section>
          <q-card-section>
            <q-table
              :rows="selectedEquipo?.jugadores || []"
              :columns="jugadoresColumns"
              row-key="id"
              flat
              bordered
              dense
              :loading="playersLoading"
              :rows-per-page-options="[0]"
              no-data-label="No hay jugadores registrados"
            >
              <template #body-cell-actions="props">
                <q-td :props="props" class="text-left">
                  <q-btn-dropdown
                    label="Opciones"
                    no-caps
                    size="10px"
                    dense
                    color="primary"
                    :disable="jugadorSaving || playersLoading"
                  >
                    <q-list>
                      <q-item clickable v-close-popup @click="openJugadorFormDialog(props.row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="eliminarJugador(props.row)">
                        <q-item-section avatar><q-icon name="delete" /></q-item-section>
                        <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </q-td>
              </template>
              <template #body-cell-foto="props">
                <q-td :props="props">
                  <q-avatar rounded size="34px">
                    <q-img :src="imageSrc(props.row.foto || 'torneoImagen.jpg')" />
                  </q-avatar>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </q-dialog>

      <q-dialog v-model="jugadorFormDialog" persistent>
        <q-card style="width: 780px; max-width: 95vw">
          <q-card-section class="row items-center">
            <div class="text-subtitle1 text-weight-bold">{{ jugadorForm.id ? 'Editar jugador' : 'Nuevo jugador' }}</div>
            <q-space />
            <q-btn flat round dense icon="close" @click="jugadorFormDialog = false" />
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.nombre" dense outlined label="Nombre" /></div>
              <div class="col-12 col-md-2"><q-input v-model="jugadorForm.abreviado" dense outlined label="Abreviado" /></div>
              <div class="col-12 col-md-2"><q-input v-model="jugadorForm.posicion" dense outlined label="Posicion" /></div>
              <div class="col-12 col-md-2"><q-input v-model="jugadorForm.numero_camiseta" dense outlined label="Nro camiseta" /></div>
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.fecha_nacimiento" type="date" dense outlined label="Nacimiento" /></div>
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.documento" dense outlined label="Documento" /></div>
              <div class="col-12 col-md-3"><q-input v-model="jugadorForm.celular" dense outlined label="Celular" /></div>
              <div class="col-12 col-md-4">
                <q-file
                  v-model="jugadorForm.foto"
                  dense
                  outlined
                  label="Foto"
                  accept="image/*"
                  @update:model-value="onJugadorImageChange"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-card flat bordered>
                  <q-card-section class="q-pa-sm">
                    <div class="text-caption text-grey-7 q-mb-xs">Vista previa</div>
                    <q-img :src="jugadorPreview || imageSrc(jugadorForm.foto_actual || 'torneoImagen.jpg')" :ratio="1" />
                  </q-card-section>
                </q-card>
              </div>
            </div>
            <div class="row justify-end">
              <q-btn flat no-caps label="Cancelar" :disable="jugadorSaving" @click="jugadorFormDialog = false" />
              <q-btn
                color="primary"
                no-caps
                :label="jugadorForm.id ? 'Actualizar' : 'Agregar'"
                :loading="jugadorSaving"
                :disable="playersLoading"
                @click="guardarJugador"
              />
            </div>
          </q-card-section>
        </q-card>
      </q-dialog>
    </q-card>
  </q-page>
</template>

<script>
import ClasificacionPanel from 'components/campeonatos/ClasificacionPanel.vue'

export default {
  name: 'CampeonatoPublicoPage',
  components: {
    ClasificacionPanel
  },
  props: {
    isLightMode: { type: Boolean, default: false }
  },
  data () {
    return {
      loading: false,
      saving: false,
      sendingMessage: false,
      tab: 'inicio',
      campeonato: {},
      deportes: [],
      grupos: [],
      equipos: [],
      mensajes: [],
      newMessage: '',
      equiposDialog: false,
      gruposDialog: false,
      equipoDialog: false,
      jugadorDialog: false,
      jugadorFormDialog: false,
      playersLoading: false,
      jugadorSaving: false,
      selectedEquipo: null,
      groupAutoCount: 3,
      groupAutoCountOptions: [1, 2, 3, 4, 5, 6, 7, 8].map(v => ({ label: `${v}`, value: v })),
      grupoForm: { id: null, nombre: '' },
      teamPreview: null,
      jugadorPreview: null,
      teamForm: {
        id: null,
        nombre: '',
        entrenador: '',
        campeonato_grupo_id: null,
        imagen: null,
        imagen_actual: 'torneoImagen.jpg'
      },
      jugadorForm: {
        id: null,
        nombre: '',
        abreviado: '',
        posicion: '',
        numero_camiseta: '',
        fecha_nacimiento: '',
        documento: '',
        celular: '',
        foto: null,
        foto_actual: 'torneoImagen.jpg'
      },
      configImagePreview: null,
      configBannerPreview: null,
      config: {
        id: null,
        nombre: '',
        tipo: 'unico',
        deporte: null,
        descripcion: '',
        fecha_inicio: '',
        fecha_fin: '',
        imagen: null,
        banner: null
      },
      equiposColumns: [
        { name: 'imagen', label: '', field: 'imagen', align: 'left' },
        { name: 'nombre', label: 'Equipo', field: 'nombre', align: 'left' },
        { name: 'entrenador', label: 'Entrenador', field: 'entrenador', align: 'left' },
        { name: 'grupo_nombre', label: 'Categoria', field: 'grupo_nombre', align: 'left' }
      ],
      equiposAdminColumns: [
        { name: 'imagen', label: '', field: 'imagen', align: 'left' },
        { name: 'actions', label: 'Opciones', align: 'left' },
        { name: 'nombre', label: 'Equipo', field: 'nombre', align: 'left' },
        { name: 'entrenador', label: 'Entrenador', field: 'entrenador', align: 'left' },
        { name: 'categoria', label: 'Categoria', field: 'grupo_nombre', align: 'left' },
        { name: 'jugadores_count', label: 'Jugadores', align: 'right' }
      ],
      jugadoresColumns: [
        { name: 'actions', label: 'Opciones', align: 'left' },
        { name: 'foto', label: 'Foto', field: 'foto', align: 'left' },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
        { name: 'abreviado', label: 'Abrev.', field: 'abreviado', align: 'left' },
        { name: 'posicion', label: 'Posicion', field: 'posicion', align: 'left' },
        { name: 'numero_camiseta', label: '#', field: 'numero_camiseta', align: 'left' },
        { name: 'documento', label: 'Documento', field: 'documento', align: 'left' },
        { name: 'celular', label: 'Celular', field: 'celular', align: 'left' }
      ]
    }
  },
  computed: {
    canEdit () {
      const user = this.$store?.user || {}
      if (!this.$store?.isLogged || !user?.id || !this.campeonato?.id) return false
      return user.role === 'Administrador' || Number(user.id) === Number(this.campeonato.user_id)
    },
    canModerate () {
      return this.canEdit
    },
    esPadreCategorias () {
      return this.campeonato?.tipo === 'categorias'
    },
    showParentBanner () {
      return !!this.campeonato?.parent?.id && this.campeonato?.tipo === 'categoria_item'
    },
    creadorNombre () {
      return this.campeonato?.user?.name || this.campeonato?.user?.username || 'Sin creador'
    },
    creatorWhatsappLink () {
      const raw = (this.campeonato?.user?.telefono_contacto_1 || '').toString()
      const digits = raw.replace(/\D/g, '')
      if (!digits) return ''
      return `https://wa.me/${digits}`
    },
    grupoSelectOptions () {
      return this.grupos.map(g => ({ id: g.id, nombre: g.nombre }))
    }
  },
  mounted () {
    this.cargarDeportes()
    this.cargarCampeonato()
    this.cargarMensajes()
  },
  watch: {
    '$route.params.code' () {
      this.cargarCampeonato()
      this.cargarMensajes()
      this.tab = 'inicio'
      this.equiposDialog = false
      this.gruposDialog = false
      this.equipoDialog = false
      this.jugadorDialog = false
      this.jugadorFormDialog = false
    }
  },
  beforeUnmount () {
    if (this.configImagePreview) URL.revokeObjectURL(this.configImagePreview)
    if (this.configBannerPreview) URL.revokeObjectURL(this.configBannerPreview)
    if (this.teamPreview) URL.revokeObjectURL(this.teamPreview)
    if (this.jugadorPreview) URL.revokeObjectURL(this.jugadorPreview)
  },
  methods: {
    imageSrc (name) { return `${this.$url}../../images/${name || 'torneoImagen.jpg'}` },
    deporteNombre (key) { return this.deportes.find(d => d.key === key)?.nombre || key },
    rangoFechas (inicio, fin) {
      if (!inicio && !fin) return 'Sin fechas definidas'
      if (inicio && fin) return `${inicio} - ${fin}`
      return inicio || fin
    },
    formatDateTime (value) {
      if (!value) return ''
      return new Date(value).toLocaleString()
    },
    tabIcon (name) {
      const active = this.tab === name
      const base = { inicio: 'home', clasificacion: 'leaderboard', ranking: 'emoji_events', configuracion: 'settings' }
      const outline = { inicio: 'o_home', clasificacion: 'o_leaderboard', ranking: 'o_emoji_events', configuracion: 'o_settings' }
      return active ? outline[name] : base[name]
    },
    tabClass (name) { return { 'tab-active': this.tab === name, [`tab-${name}`]: true } },
    sharePublic () {
      const url = window.location.href
      if (navigator.share) {
        navigator.share({ title: this.campeonato?.nombre || 'Torneo Ya', text: `Mira este campeonato: ${this.campeonato?.nombre || ''}`, url }).catch(() => {})
        return
      }
      navigator.clipboard.writeText(url).then(() => this.$alert.success('Enlace copiado')).catch(() => this.$alert.error('No se pudo copiar el enlace'))
    },
    openWhatsApp () {
      if (!this.creatorWhatsappLink) return
      window.open(this.creatorWhatsappLink, '_blank')
    },
    goToPadre () {
      if (!this.campeonato?.parent?.codigo) return
      this.$router.push(`/c/${this.campeonato.parent.codigo}`)
    },
    entrarCategoria (cat) { this.$router.push(`/c/${cat.codigo}`) },
    onConfigFileChange (field, file) {
      if (field === 'imagen') {
        if (this.configImagePreview) URL.revokeObjectURL(this.configImagePreview)
        this.configImagePreview = file ? URL.createObjectURL(file) : null
      } else {
        if (this.configBannerPreview) URL.revokeObjectURL(this.configBannerPreview)
        this.configBannerPreview = file ? URL.createObjectURL(file) : null
      }
      if (file && this.canEdit) this.saveConfig(true)
    },
    onTeamImageChange (file) {
      if (this.teamPreview) URL.revokeObjectURL(this.teamPreview)
      this.teamPreview = file ? URL.createObjectURL(file) : null
    },
    onJugadorImageChange (file) {
      if (this.jugadorPreview) URL.revokeObjectURL(this.jugadorPreview)
      this.jugadorPreview = file ? URL.createObjectURL(file) : null
    },
    resetGrupoForm () { this.grupoForm = { id: null, nombre: '' } },
    resetTeamForm () {
      if (this.teamPreview) URL.revokeObjectURL(this.teamPreview)
      this.teamPreview = null
      this.teamForm = { id: null, nombre: '', entrenador: '', campeonato_grupo_id: null, imagen: null, imagen_actual: 'torneoImagen.jpg' }
    },
    resetJugadorForm () {
      if (this.jugadorPreview) URL.revokeObjectURL(this.jugadorPreview)
      this.jugadorPreview = null
      this.jugadorForm = {
        id: null,
        nombre: '',
        abreviado: '',
        posicion: '',
        numero_camiseta: '',
        fecha_nacimiento: '',
        documento: '',
        celular: '',
        foto: null,
        foto_actual: 'torneoImagen.jpg'
      }
    },
    cargarDeportes () { this.$axios.get('public/deportes').then(r => { this.deportes = r.data || [] }).catch(() => { this.deportes = [] }) },
    cargarCampeonato () {
      this.loading = true
      this.$axios.get(`public/campeonatos/${this.$route.params.code}`)
        .then(r => {
          this.campeonato = r.data || {}
          if (this.campeonato?.tipo === 'categorias' && this.tab === 'clasificacion') {
            this.tab = 'inicio'
          }
          this.grupos = this.campeonato.grupos || []
          this.equipos = this.campeonato.equipos || []
          this.config = {
            id: this.campeonato.id,
            nombre: this.campeonato.nombre || '',
            tipo: this.campeonato.tipo || 'unico',
            deporte: this.campeonato.deporte || null,
            descripcion: this.campeonato.descripcion || '',
            fecha_inicio: this.campeonato.fecha_inicio || '',
            fecha_fin: this.campeonato.fecha_fin || '',
            imagen: null,
            banner: null
          }
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar el campeonato'))
        .finally(() => { this.loading = false })
    },
    loadGestionData () {
      return Promise.all([
        this.$axios.get(`campeonatos/${this.campeonato.id}/grupos`)
          .then(r => { this.grupos = r.data || [] })
          .catch(() => { this.grupos = [] }),
        this.$axios.get(`campeonatos/${this.campeonato.id}/equipos`)
          .then(r => { this.equipos = r.data || [] })
          .catch(() => { this.equipos = [] })
      ])
    },
    openEquiposDialog () {
      if (!this.canEdit) return
      this.equiposDialog = true
      this.loadGestionData()
    },
    openGruposDialog () {
      this.gruposDialog = true
      this.resetGrupoForm()
    },
    crearGruposAuto () {
      this.$axios.post(`campeonatos/${this.campeonato.id}/grupos/defaults`, { cantidad: this.groupAutoCount })
        .then(() => {
          this.$alert.success('Grupos generados')
          this.loadGestionData()
          this.cargarCampeonato()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo generar categorias'))
    },
    guardarGrupo () {
      const nombre = (this.grupoForm.nombre || '').trim()
      if (!nombre) { this.$alert.error('Ingresa un nombre de categoria'); return }
      const req = this.grupoForm.id
        ? this.$axios.put(`campeonatos/${this.campeonato.id}/grupos/${this.grupoForm.id}`, { nombre })
        : this.$axios.post(`campeonatos/${this.campeonato.id}/grupos`, { nombre })

      req.then(() => {
        this.$alert.success(this.grupoForm.id ? 'Categoria actualizada' : 'Categoria agregada')
        this.resetGrupoForm()
        this.loadGestionData()
        this.cargarCampeonato()
      }).catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar categoria'))
    },
    editarGrupo (g) { this.grupoForm = { id: g.id, nombre: g.nombre || '' } },
    eliminarGrupo (g) {
      this.$alert.dialog(`Eliminar categoria ${g.nombre}?`)
        .onOk(() => {
          this.$axios.delete(`campeonatos/${this.campeonato.id}/grupos/${g.id}`)
            .then(() => {
              this.$alert.success('Categoria eliminada')
              this.loadGestionData()
              this.cargarCampeonato()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar categoria'))
        })
    },
    openEquipoDialog (eq = null) {
      if (eq) {
        this.teamForm = {
          id: eq.id,
          nombre: eq.nombre || '',
          entrenador: eq.entrenador || '',
          campeonato_grupo_id: eq.campeonato_grupo_id || null,
          imagen: null,
          imagen_actual: eq.imagen || 'torneoImagen.jpg'
        }
      } else {
        this.resetTeamForm()
      }
      this.equipoDialog = true
    },
    guardarEquipo () {
      if (!this.teamForm.nombre || !this.teamForm.nombre.trim()) { this.$alert.error('Nombre del equipo requerido'); return }
      const fd = new FormData()
      fd.append('nombre', this.teamForm.nombre.trim())
      fd.append('entrenador', this.teamForm.entrenador || '')
      if (this.teamForm.campeonato_grupo_id) fd.append('campeonato_grupo_id', this.teamForm.campeonato_grupo_id)
      if (this.teamForm.imagen) fd.append('imagen', this.teamForm.imagen)

      const req = this.teamForm.id
        ? this.$axios.post(`campeonatos/${this.campeonato.id}/equipos/${this.teamForm.id}?_method=PUT`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        : this.$axios.post(`campeonatos/${this.campeonato.id}/equipos`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })

      req.then(() => {
        this.$alert.success(this.teamForm.id ? 'Equipo actualizado' : 'Equipo creado')
        this.equipoDialog = false
        this.resetTeamForm()
        this.loadGestionData()
        this.cargarCampeonato()
      }).catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar equipo'))
    },
    eliminarEquipo (eq) {
      this.$alert.dialog(`Eliminar equipo ${eq.nombre}?`)
        .onOk(() => {
          this.$axios.delete(`campeonatos/${this.campeonato.id}/equipos/${eq.id}`)
            .then(() => {
              this.$alert.success('Equipo eliminado')
              this.loadGestionData()
              this.cargarCampeonato()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar equipo'))
        })
    },
    openJugadorDialog (eq) {
      this.selectedEquipo = eq
      this.resetJugadorForm()
      this.jugadorDialog = true
      this.playersLoading = true
      this.loadGestionData()
        .then(() => {
          const refreshed = this.equipos.find(e => e.id === eq.id)
          if (refreshed) this.selectedEquipo = refreshed
        })
        .finally(() => { this.playersLoading = false })
    },
    openJugadorFormDialog (j = null) {
      if (j) {
        this.editarJugador(j)
      } else {
        this.resetJugadorForm()
      }
      this.jugadorFormDialog = true
    },
    guardarJugador () {
      if (!this.selectedEquipo?.id) return
      if (!this.jugadorForm.nombre || !this.jugadorForm.nombre.trim()) { this.$alert.error('Nombre del jugador requerido'); return }
      this.jugadorSaving = true
      const fd = new FormData()
      fd.append('nombre', this.jugadorForm.nombre.trim())
      fd.append('abreviado', this.jugadorForm.abreviado || '')
      fd.append('posicion', this.jugadorForm.posicion || '')
      fd.append('numero_camiseta', this.jugadorForm.numero_camiseta || '')
      fd.append('fecha_nacimiento', this.jugadorForm.fecha_nacimiento || '')
      fd.append('documento', this.jugadorForm.documento || '')
      fd.append('celular', this.jugadorForm.celular || '')
      if (this.jugadorForm.foto) fd.append('foto', this.jugadorForm.foto)

      const req = this.jugadorForm.id
        ? this.$axios.post(`campeonatos/${this.campeonato.id}/equipos/${this.selectedEquipo.id}/jugadores/${this.jugadorForm.id}?_method=PUT`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        : this.$axios.post(`campeonatos/${this.campeonato.id}/equipos/${this.selectedEquipo.id}/jugadores`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })

      req.then(() => {
        this.$alert.success(this.jugadorForm.id ? 'Jugador actualizado' : 'Jugador agregado')
        this.jugadorFormDialog = false
        this.resetJugadorForm()
        this.playersLoading = true
        this.loadGestionData().then(() => {
          this.cargarCampeonato()
          const refreshed = this.equipos.find(e => e.id === this.selectedEquipo.id)
          if (refreshed) this.selectedEquipo = refreshed
        }).finally(() => { this.playersLoading = false })
      }).catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar jugador'))
        .finally(() => { this.jugadorSaving = false })
    },
    editarJugador (j) {
      if (this.jugadorPreview) URL.revokeObjectURL(this.jugadorPreview)
      this.jugadorPreview = null
      this.jugadorForm = {
        id: j.id,
        nombre: j.nombre || '',
        abreviado: j.abreviado || '',
        posicion: j.posicion || '',
        numero_camiseta: j.numero_camiseta || '',
        fecha_nacimiento: j.fecha_nacimiento || '',
        documento: j.documento || '',
        celular: j.celular || '',
        foto: null,
        foto_actual: j.foto || 'torneoImagen.jpg'
      }
    },
    eliminarJugador (j) {
      if (!this.selectedEquipo?.id) return
      this.$alert.dialog(`Eliminar jugador ${j.nombre}?`)
        .onOk(() => {
          this.playersLoading = true
          this.$axios.delete(`campeonatos/${this.campeonato.id}/equipos/${this.selectedEquipo.id}/jugadores/${j.id}`)
            .then(() => {
              this.$alert.success('Jugador eliminado')
              this.loadGestionData().then(() => {
                this.cargarCampeonato()
                const refreshed = this.equipos.find(e => e.id === this.selectedEquipo.id)
                if (refreshed) this.selectedEquipo = refreshed
              }).finally(() => { this.playersLoading = false })
            })
            .catch(e => {
              this.$alert.error(e.response?.data?.message || 'No se pudo eliminar jugador')
              this.playersLoading = false
            })
        })
    },
    saveConfig (silent = false) {
      if (!this.canEdit) return
      this.saving = true
      const fd = new FormData()
      fd.append('nombre', this.config.nombre || '')
      fd.append('tipo', this.config.tipo || 'unico')
      fd.append('descripcion', this.config.descripcion || '')
      if (this.config.deporte) fd.append('deporte', this.config.deporte)
      if (this.config.fecha_inicio) fd.append('fecha_inicio', this.config.fecha_inicio)
      if (this.config.fecha_fin) fd.append('fecha_fin', this.config.fecha_fin)
      if (this.config.imagen) fd.append('imagen', this.config.imagen)
      if (this.config.banner) fd.append('banner', this.config.banner)

      this.$axios.post(`campeonatos/${this.config.id}?_method=PUT`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(() => {
          if (!silent) this.$alert.success('Configuracion actualizada')
          this.cargarCampeonato()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar'))
        .finally(() => { this.saving = false })
    },
    cargarMensajes () {
      this.$axios.get(`public/campeonatos/${this.$route.params.code}/mensajes`)
        .then(r => { this.mensajes = r.data || [] })
        .catch(() => { this.mensajes = [] })
    },
    sendMessage () {
      if (!this.newMessage || !this.newMessage.trim()) { this.$alert.error('Escribe un mensaje'); return }
      this.sendingMessage = true
      this.$axios.post(`public/campeonatos/${this.$route.params.code}/mensajes`, { mensaje: this.newMessage.trim() })
        .then(() => {
          this.newMessage = ''
          this.cargarMensajes()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo enviar mensaje'))
        .finally(() => { this.sendingMessage = false })
    },
    toggleVisible (m) {
      this.$axios.patch(`campeonatos/${this.campeonato.id}/mensajes/${m.id}/visible`)
        .then(() => this.cargarMensajes())
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cambiar visibilidad'))
    }
  }
}
</script>

<style scoped>
.public-page { background: linear-gradient(180deg, #0f172a 0%, #111827 45%, #0b1220 100%); }
.public-page { min-height: 100vh; overflow-x: hidden; }
.hero { min-height: 240px; background-size: cover; background-position: center; }
.hero-overlay { background: linear-gradient(100deg, rgba(2, 6, 23, 0.92), rgba(30, 41, 59, 0.8)); min-height: 240px; display: flex; align-items: center; }
.hero-content { width: 100%; display: flex; align-items: center; gap: 14px; padding: 18px; }
.hero-main { min-width: 220px; }
.hero-logo { border: 2px solid rgba(255, 255, 255, 0.7); }
.actions-col { min-width: 180px; }
.panel-shell { border-radius: 14px; overflow: hidden; background: #111827; border-color: rgba(148, 163, 184, 0.25); color: #e5e7eb; }
.pub-tabs { padding: 10px 10px 6px; background: #0b1220; }
.pub-tabs :deep(.q-tab) { margin-right: 8px; border-radius: 10px; color: #cbd5e1; padding: 0 12px; }
.pub-tabs :deep(.q-tab__label) { text-transform: none; letter-spacing: 0; }
.tab-active { color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.32); }
.tab-inicio.tab-active { background: rgba(37, 99, 235, 0.78); }
.tab-clasificacion.tab-active { background: rgba(14, 116, 144, 0.78); }
.tab-ranking.tab-active { background: rgba(180, 83, 9, 0.78); }
.tab-configuracion.tab-active { background: rgba(79, 70, 229, 0.78); }
.panels { background: #111827; }
.bg-dark-card { background: #0b1220; border-color: rgba(148, 163, 184, 0.25); }
.messages-list { background: #0b1220; border-color: rgba(148, 163, 184, 0.25); }
.panel-light-blue { background: rgba(30, 64, 175, 0.15); }
.panel-light-amber { background: rgba(180, 83, 9, 0.18); }
.btn-enter-cat {
  color: #ffffff !important;
}
.btn-enter-cat :deep(.q-btn__content) {
  color: #ffffff !important;
}
.public-page.mode-light .btn-enter-cat {
  color: #ffffff !important;
}
.public-page.mode-light .btn-enter-cat :deep(.q-btn__content) {
  color: #ffffff !important;
}
.btn-parent-back,
.btn-parent-back :deep(.q-btn__content),
.public-page.mode-light .btn-parent-back,
.public-page.mode-light .btn-parent-back :deep(.q-btn__content) {
  color: #ffffff !important;
}
.public-page.mode-light {
  background: linear-gradient(180deg, #f3f4f6 0%, #eef2ff 60%, #f8fafc 100%);
}
.public-page.mode-light .text-white {
  color: #0f172a !important;
}
.public-page.mode-light .hero-overlay {
  background: linear-gradient(100deg, rgba(255, 255, 255, 0.82), rgba(248, 250, 252, 0.72));
}
.public-page.mode-light .panel-shell {
  background: #ffffff;
  color: #0f172a;
  border-color: #dbe4f0;
}
.public-page.mode-light .pub-tabs {
  background: #eef2ff;
}
.public-page.mode-light .pub-tabs :deep(.q-tab) {
  color: #334155;
}
.public-page.mode-light .tab-active {
  color: #0f172a !important;
  border-color: #94a3b8;
}
.public-page.mode-light .panels {
  background: #ffffff;
}
.public-page.mode-light .bg-dark-card,
.public-page.mode-light .messages-list {
  background: #ffffff;
  border-color: #dbe4f0;
}
.public-page.mode-light .text-blue-1,
.public-page.mode-light .text-blue-2,
.public-page.mode-light .text-cyan-2,
.public-page.mode-light .text-deep-orange-3,
.public-page.mode-light .text-grey-3,
.public-page.mode-light .text-grey-4 {
  color: #334155 !important;
}
@media (max-width: 700px) {
  .hero-content {
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
  }
  .hero-main {
    min-width: 0;
    flex: 1 1 180px;
  }
  .actions-col {
    min-width: 0;
    width: 100%;
    display: grid;
    grid-template-columns: 1fr;
  }
  .actions-col :deep(.q-btn) {
    width: 100%;
  }
  .panel-shell {
    margin: 8px !important;
  }
}
</style>
