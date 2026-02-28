<template>
  <div class="row q-col-gutter-md clasif-root" :class="{ 'mode-light': isLightMode }">
    <div class="col-12 col-lg-8">
      <q-card flat bordered class="bg-dark-card q-mb-md" :class="{ 'text-white': !isLightMode }">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-select
              v-model="faseId"
              dense
              outlined
              :dark="!isLightMode"
              emit-value
              map-options
              option-value="id"
              option-label="label"
              :options="faseOptions"
              label="Fase"
            />
          </div>
          <div class="col-12 col-md-4 row items-center q-gutter-sm">
            <span>Clasificacion por grupo</span>
            <q-toggle
              :model-value="activeFase?.agrupar_por_grupo"
              color="positive"
              :disable="!canEdit || !activeFase"
              @update:model-value="toggleAgrupar"
            />
          </div>
          <div class="col-12 col-md-4 text-right row justify-end q-gutter-sm">
            <q-btn color="primary" icon="refresh" no-caps label="Actualizar" @click="cargar" :loading="btnLoading.refresh" />
            <q-btn-dropdown
              color="deep-purple"
              icon="picture_as_pdf"
              no-caps
              label="Reportes PDF"
              :disable="btnLoading.report"
            >
              <q-list>
                <q-item clickable v-close-popup @click="downloadReport('tabla_posiciones')">
                  <q-item-section avatar><q-icon name="table_chart" /></q-item-section>
                  <q-item-section>Tabla de posiciones</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="downloadReport('partidos_fase')">
                  <q-item-section avatar><q-icon name="sports_soccer" /></q-item-section>
                  <q-item-section>Partidos de la fase</q-item-section>
                </q-item>
                <q-separator />
                <q-item clickable v-close-popup @click="downloadReport('ranking_total')"><q-item-section>Ranking general</q-item-section></q-item>
                <q-item clickable v-close-popup @click="downloadReport('ranking_goles')"><q-item-section>Ranking de goles</q-item-section></q-item>
                <q-item clickable v-close-popup @click="downloadReport('ranking_faltas')"><q-item-section>Ranking de faltas</q-item-section></q-item>
                <q-item clickable v-close-popup @click="downloadReport('ranking_amarillas')"><q-item-section>Ranking de amarillas</q-item-section></q-item>
                <q-item clickable v-close-popup @click="downloadReport('ranking_rojas')"><q-item-section>Ranking de rojas</q-item-section></q-item>
                <q-item clickable v-close-popup @click="downloadReport('ranking_sustituciones')"><q-item-section>Ranking de sustituciones</q-item-section></q-item>
                <q-item clickable v-close-popup @click="downloadReport('ranking_porteros')"><q-item-section>Ranking de porteros</q-item-section></q-item>
              </q-list>
            </q-btn-dropdown>
            <q-btn v-if="canEdit" color="indigo" icon="add_circle" no-caps label="Nueva fase" @click="abrirFaseDialog" :loading="btnLoading.fase" />
          </div>
        </q-card-section>
      </q-card>

      <template v-if="isEliminatoria">
        <q-card
          v-for="group in tabla"
          :key="group.grupo"
          flat
          bordered
          class="bg-dark-card q-mb-md"
          :class="{ 'text-white': !isLightMode }"
        >
          <q-card-section class="text-subtitle1 text-weight-bold">{{ group.grupo }}</q-card-section>
          <q-separator :dark="!isLightMode" />
          <q-list separator :dark="!isLightMode">
            <q-item v-for="r in group.rows" :key="r.equipo_id">
              <q-item-section avatar>
                <q-avatar rounded size="36px">
                  <q-img :src="equipoLogo(r.equipo_id)" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ r.equipo }}</q-item-label>
                <q-item-label caption>
                  Ganados: {{ r.pg }} | Empatados: {{ r.pe }} | Perdidos: {{ r.pp }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-chip dense color="indigo-2" text-color="indigo-10">PJ {{ r.pj }}</q-chip>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>

      <template v-else>
        <q-card
          v-for="group in tabla"
          :key="group.grupo"
          flat
          bordered
          class="bg-dark-card q-mb-md"
          :class="{ 'text-white': !isLightMode }"
        >
          <q-card-section class="text-center text-subtitle1 text-weight-bold">{{ group.grupo }}</q-card-section>
          <q-separator :dark="!isLightMode" />
          <q-markup-table dense flat :dark="!isLightMode" class="table-standings">
            <thead>
              <tr>
                <th class="text-left">Pos</th>
                <th class="text-left">Equipos</th>
                <th class="text-center">Pts</th>
                <th class="text-center">J</th>
                <th class="text-center">G</th>
                <th class="text-center">E</th>
                <th class="text-center">P</th>
                <th class="text-center">GF</th>
                <th class="text-center">GC</th>
                <th class="text-center">DIF</th>
                <th class="text-center">%</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, idx) in group.rows" :key="r.equipo_id">
                <td class="text-left">{{ idx + 1 }}</td>
                <td class="text-left">
                  <div class="row items-center no-wrap q-gutter-xs">
                    <q-avatar rounded size="24px">
                      <q-img :src="equipoLogo(r.equipo_id)" />
                    </q-avatar>
                    <span>{{ r.equipo }}</span>
                  </div>
                </td>
                <td class="text-center">{{ r.pts }}</td>
                <td class="text-center">{{ r.pj }}</td>
                <td class="text-center">{{ r.pg }}</td>
                <td class="text-center">{{ r.pe }}</td>
                <td class="text-center">{{ r.pp }}</td>
                <td class="text-center">{{ r.gf }}</td>
                <td class="text-center">{{ r.gc }}</td>
                <td class="text-center">{{ r.dif }}</td>
                <td class="text-center">{{ r.porcentaje }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card>
      </template>
    </div>

    <div class="col-12 col-lg-4">
      <q-card flat bordered class="bg-dark-card" :class="{ 'text-white': !isLightMode }">
        <q-card-section class="row items-center">
          <div class="text-h6 text-weight-bold">Juegos</div>
          <q-space />
          <q-select
            v-model="faseId"
            dense
            outlined
            :dark="!isLightMode"
            emit-value
            map-options
            option-value="id"
            option-label="label"
            :options="faseOptions"
            style="min-width: 160px"
          />
        </q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-card-section class="row q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-btn
              v-if="canEdit"
              class="full-width"
              color="primary"
              no-caps
              icon="auto_awesome"
              label="Generar partidos"
              @click="genDialog = true"
              :loading="btnLoading.generar"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-btn
              v-if="canEdit"
              class="full-width"
              color="teal"
              no-caps
              icon="sports_soccer"
              label="Agregar partido"
              @click="abrirPartidoDialog"
              :loading="btnLoading.partido"
            />
          </div>
        </q-card-section>

        <q-separator :dark="!isLightMode" />
        <div class="match-list q-pa-xs">
          <div class="match-list-grid">
            <q-card
              v-for="p in activePartidos"
              :key="p.id"
              flat
              bordered
              class="match-card"
              :class="{ editable: canEdit }"
              @dblclick="openEditPartido(p)"
            >
              <q-card-section class="q-pa-xs">
              <div class="row items-center q-col-gutter-sm">
                <div class="col-4 col-md-5 text-center">
                  <q-avatar rounded size="36px" class="match-avatar">
                    <q-img :src="equipoLogo(p.local_equipo_id, p.local?.imagen)" />
                  </q-avatar>
                  <div class="text-caption q-mt-xs">{{ p.local?.nombre || 'Pendiente' }}</div>
                </div>
                <div class="col-4 col-md-2 text-center">
                  <div class="match-score text-weight-bold">{{ formatScore(p) }}</div>
                  <div class="match-mid-card q-mt-xs" :class="estadoClass(p.estado)">
                    <div class="text-caption text-weight-medium">{{ estadoLabel(p.estado) }}</div>
                    <div class="text-caption">{{ p.grupo_nombre || 'General' }}</div>
                    <div class="text-caption">{{ formatPartidoSchedule(p.programado_at) }}</div>
                  </div>
                </div>
                <div class="col-4 col-md-5 text-center">
                  <q-avatar rounded size="36px" class="match-avatar">
                    <q-img :src="equipoLogo(p.visita_equipo_id, p.visita?.imagen)" />
                  </q-avatar>
                  <div class="text-caption q-mt-xs">{{ p.visita?.nombre || 'Pendiente' }}</div>
                </div>
              </div>
              <div class="row items-center q-gutter-xs q-mt-xs">
                <q-space />
                <q-btn
                  v-if="canEdit"
                  dense
                  flat
                  round
                  icon="edit"
                  color="primary"
                  @click="openEditPartido(p)"
                  :loading="btnLoading.editar"
                />
              </div>
              </q-card-section>
            </q-card>
          </div>
          <div v-if="!activePartidos.length" class="text-caption text-grey-6 q-pa-sm">No hay partidos en esta fase</div>
        </div>
      </q-card>
    </div>

    <div class="col-12">
      <q-card flat bordered class="bg-dark-card" :class="{ 'text-white': !isLightMode }">
        <q-card-section class="text-subtitle1 text-weight-bold">Comentarios</q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-10">
              <q-input
                v-model="newMessage"
                type="textarea"
                autogrow
                outlined
                :dark="!isLightMode"
                label="Escribe un comentario"
              />
            </div>
            <div class="col-12 col-md-2 flex flex-center">
              <q-btn color="deep-orange" no-caps icon="send" label="Enviar" class="full-width" @click="sendMessage" :loading="sendingMessage" />
            </div>
          </div>
        </q-card-section>
        <q-separator :dark="!isLightMode" />
        <q-list separator :dark="!isLightMode">
          <q-item v-for="m in mensajes" :key="m.id">
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ m.user?.name || m.user?.username || m.autor_nombre || 'Publico' }}</q-item-label>
              <q-item-label caption>{{ formatDateTime(m.created_at) }}</q-item-label>
              <q-item-label class="q-mt-xs">{{ m.mensaje }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!mensajes?.length">
            <q-item-section class="text-grey-6">Sin comentarios aun</q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-dialog v-model="genDialog" persistent>
      <q-card style="width: 420px; max-width: 95vw">
        <q-card-section class="text-h6">Generar partidos</q-card-section>
        <q-card-section>
          <q-option-group
            v-model="genMode"
            type="radio"
            :options="[
              { label: 'Solo ida', value: 'ida' },
              { label: 'Ida y vuelta', value: 'ida_vuelta' }
            ]"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="genDialog = false" />
          <q-btn color="primary" no-caps label="Continuar" @click="generarPartidos" :loading="btnLoading.generar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="faseDialog" persistent>
      <q-card style="width: 720px; max-width: 96vw">
        <q-card-section class="row items-center">
          <div class="text-h6">Gestionar fases</div>
          <q-space />
          <q-btn flat round dense icon="close" @click="faseDialog = false" />
        </q-card-section>
        <q-card-section class="row q-col-gutter-md">
          <div class="col-12 col-md-7">
            <q-list bordered separator>
              <q-item v-for="f in fases" :key="f.id">
                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ f.nombre }}</q-item-label>
                  <q-item-label caption>{{ f.tipo === 'eliminatoria' ? 'Eliminatoria' : 'Todos contra todos' }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row q-gutter-xs">
                    <q-btn dense flat icon="edit" color="primary" @click="editarFase(f)" />
                    <q-btn dense flat icon="delete" color="negative" @click="eliminarFase(f)" />
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
          <div class="col-12 col-md-5">
            <q-input v-model="faseForm.nombre" dense outlined label="Nombre fase" class="q-mb-sm" />
            <q-select
              v-model="faseForm.tipo"
              dense
              outlined
              emit-value
              map-options
              option-value="value"
              option-label="label"
              :options="[
                { label: 'Todos contra todos', value: 'liga' },
                { label: 'Eliminatoria', value: 'eliminatoria' }
              ]"
              label="Tipo"
              class="q-mb-sm"
            />
            <div class="row q-gutter-sm">
              <q-btn flat no-caps label="Nuevo" @click="resetFaseForm" />
              <q-btn color="primary" no-caps :label="faseForm.id ? 'Actualizar' : 'Crear fase'" @click="guardarFase" :loading="btnLoading.fase" />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="partidoDialog" persistent>
      <q-card style="width: 620px; max-width: 95vw">
        <q-card-section class="text-h6">Agregar partido</q-card-section>
        <q-card-section class="row q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.campeonato_fase_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="label"
              :options="faseOptions"
              label="Fase"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.grupo_nombre"
              dense
              outlined
              emit-value
              map-options
              option-value="value"
              option-label="label"
              :options="groupOptions"
              label="Grupo"
              clearable
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.estado"
              dense
              outlined
              emit-value
              map-options
              option-value="value"
              option-label="label"
              :options="estadoOptions"
              label="Estado"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.local_equipo_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="filteredTeamOptionsCreate"
              label="Local"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="partidoForm.visita_equipo_id"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="nombre"
              :options="filteredTeamOptionsCreate"
              label="Visita"
            />
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="partidoDialog = false" />
          <q-btn color="primary" no-caps label="Guardar" @click="guardarPartido" :loading="btnLoading.partido" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="editPartidoDialog" persistent>
      <q-card style="width: 900px; max-width: 96vw">
        <q-card-section class="row items-center">
          <div class="text-h6">Editar partido</div>
          <q-space />
          <q-btn dense flat round icon="close" @click="editPartidoDialog = false" />
        </q-card-section>
        <q-tabs v-model="editTab" dense no-caps align="left" class="q-px-md">
          <q-tab name="basico" label="Datos basicos" icon="edit" />
          <q-tab name="goles" label="Goles" icon="sports_soccer" />
          <q-tab name="faltas" label="Faltas" icon="sports" />
          <q-tab name="amarillas" label="Amarillas" icon="style" />
          <q-tab name="rojas" label="Rojas" icon="warning" />
          <q-tab name="sustituciones" label="Sustituciones" icon="swap_horiz" />
          <q-tab name="porteros" label="Porteros" icon="sports_handball" />
        </q-tabs>
        <q-separator />
        <q-tab-panels v-model="editTab" animated>
          <q-tab-panel name="basico">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-6">
                <q-select
                  v-model="editPartidoForm.grupo_nombre"
                  dense
                  outlined
                  emit-value
                  map-options
                  option-value="value"
                  option-label="label"
                  :options="groupOptions"
                  label="Grupo"
                  clearable
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="editPartidoForm.local_equipo_id"
                  dense
                  outlined
                  emit-value
                  map-options
                  option-value="id"
                  option-label="nombre"
                  :options="filteredTeamOptionsEdit"
                  label="Local"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-select
                  v-model="editPartidoForm.visita_equipo_id"
                  dense
                  outlined
                  emit-value
                  map-options
                  option-value="id"
                  option-label="nombre"
                  :options="filteredTeamOptionsEdit"
                  label="Visita"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input v-model.number="editPartidoForm.goles_local" dense outlined type="number" min="0" label="Goles local" />
              </div>
              <div class="col-12 col-md-4">
                <q-input v-model.number="editPartidoForm.goles_visita" dense outlined type="number" min="0" label="Goles visita" />
              </div>
              <div class="col-12 col-md-4">
                <q-select
                  v-model="editPartidoForm.estado"
                  dense
                  outlined
                  emit-value
                  map-options
                  option-value="value"
                  option-label="label"
                  :options="estadoOptions"
                  label="Estado"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="editPartidoForm.programado_fecha"
                  dense
                  outlined
                  type="date"
                  label="Fecha"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="editPartidoForm.programado_hora"
                  dense
                  outlined
                  type="time"
                  label="Hora"
                />
              </div>
            </div>
          </q-tab-panel>
          <q-tab-panel v-for="inc in incidenciaTabs" :key="inc.key" :name="inc.key">
            <div class="row items-center q-mb-sm">
              <div class="text-subtitle2">{{ incidenciaTitle }}</div>
              <q-space />
              <q-btn
                color="primary"
                no-caps
                :icon="incidenciaMeta.icon"
                :label="`Agregar ${incidenciaMeta.singular}`"
                @click="openIncidenciaDialog('insert')"
              />
            </div>

            <q-table
              :rows="incidenciaRows"
              :columns="incidenciaColumns"
              row-key="id"
              flat
              bordered
              dense
              :rows-per-page-options="[0]"
              no-data-label="Sin registros"
            >
              <template #body-cell-actions="props">
                <q-td :props="props" class="text-left">
                  <q-btn-dropdown dense no-caps size="11px" color="primary" label="Opciones">
                    <q-list>
                      <q-item clickable v-close-popup @click="openIncidenciaDialog('insert')">
                        <q-item-section avatar><q-icon name="add" /></q-item-section>
                        <q-item-section><q-item-label>Insertar</q-item-label></q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="openIncidenciaDialog('edit', props.row)">
                        <q-item-section avatar><q-icon name="edit" /></q-item-section>
                        <q-item-section><q-item-label>Modificar</q-item-label></q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="deleteCurrentRow(props.row)">
                        <q-item-section avatar><q-icon name="delete" /></q-item-section>
                        <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </q-td>
              </template>
            </q-table>
          </q-tab-panel>
        </q-tab-panels>
        <q-card-actions align="right">
          <q-btn
            v-if="canEdit && editPartidoForm.id"
            flat
            no-caps
            color="negative"
            icon="delete"
            label="Eliminar"
            @click="eliminarPartido"
          />
          <q-space />
          <q-btn flat no-caps label="Cancelar" @click="editPartidoDialog = false" />
          <q-btn color="primary" no-caps icon="save" label="Guardar" @click="guardarEdicionPartido" :loading="btnLoading.editar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="incidenciaDialog" persistent>
      <q-card style="width: 620px; max-width: 95vw">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ incidenciaDialogMode === 'edit' ? `Modificar ${incidenciaMeta.singular}` : `Agregar ${incidenciaMeta.singular}` }}</div>
          <q-space />
          <q-btn dense flat round icon="close" @click="incidenciaDialog = false" />
        </q-card-section>
        <q-card-section class="row q-col-gutter-sm">
          <template v-if="currentIncTab === 'goles' || currentIncTab === 'faltas' || currentIncTab === 'amarillas' || currentIncTab === 'rojas'">
            <div class="col-12 col-md-6"><q-select v-model="currentEventForm.equipo_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="editTeamOptions" label="Equipo" /></div>
            <div class="col-12 col-md-6"><q-select v-model="currentEventForm.jugador_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="playersByTeam(currentEventForm.equipo_id)" label="Jugador" clearable /></div>
            <div class="col-12 col-md-4"><q-input v-model.number="currentEventForm.minuto" dense outlined type="number" label="Minuto" /></div>
            <div class="col-12 col-md-8"><q-input v-model="currentEventForm.detalle" dense outlined label="Detalle" /></div>
          </template>

          <template v-if="currentIncTab === 'sustituciones'">
            <div class="col-12"><q-select v-model="currentEventForm.equipo_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="editTeamOptions" label="Equipo" /></div>
            <div class="col-12 col-md-6"><q-select v-model="currentEventForm.jugador_sale_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="playersByTeam(currentEventForm.equipo_id)" label="Jugador sale" clearable /></div>
            <div class="col-12 col-md-6"><q-select v-model="currentEventForm.jugador_entra_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="playersByTeam(currentEventForm.equipo_id)" label="Jugador entra" clearable /></div>
            <div class="col-12 col-md-4"><q-input v-model.number="currentEventForm.minuto" dense outlined type="number" label="Minuto" /></div>
          </template>

          <template v-if="currentIncTab === 'porteros'">
            <div class="col-12 col-md-6"><q-select v-model="currentEventForm.equipo_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="editTeamOptions" label="Equipo" /></div>
            <div class="col-12 col-md-6"><q-select v-model="currentEventForm.jugador_id" dense outlined emit-value map-options option-value="id" option-label="nombre" :options="playersByTeam(currentEventForm.equipo_id)" label="Jugador portero" clearable /></div>
            <div class="col-12"><q-input v-model="currentEventForm.nombre_portero" dense outlined label="Nombre libre (opcional)" /></div>
          </template>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat no-caps label="Cancelar" @click="incidenciaDialog = false" />
          <q-btn color="primary" no-caps :label="incidenciaDialogMode === 'edit' ? 'Guardar cambios' : 'Guardar'" @click="saveIncidenciaDialog" :loading="btnLoading.incidencia" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
const PHASE_NAMES = [
  'Primera fase',
  'Segunda fase',
  'Tercera fase',
  'Cuarta fase',
  'Quinta fase',
  'Sexta fase',
  'Septima fase',
  'Octava fase',
  'Novena fase',
  'Decima fase'
]

export default {
  name: 'ClasificacionPanel',
  props: {
    code: { type: String, required: true },
    campeonato: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
    isLightMode: { type: Boolean, default: false },
    mensajes: { type: Array, default: () => [] }
  },
  data () {
    return {
      fases: [],
      faseId: null,
      genDialog: false,
      genMode: 'ida',
      faseDialog: false,
      partidoDialog: false,
      editPartidoDialog: false,
      editTab: 'basico',
      incidenciaTabs: [
        { key: 'goles' },
        { key: 'faltas' },
        { key: 'amarillas' },
        { key: 'rojas' },
        { key: 'sustituciones' },
        { key: 'porteros' }
      ],
      incidenciaDialog: false,
      incidenciaDialogMode: 'insert',
      editingIncidenciaId: null,
      faseForm: {
        id: null,
        nombre: 'Primera fase',
        tipo: 'liga'
      },
      partidoForm: {
        campeonato_fase_id: null,
        campeonato_fecha_id: null,
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null,
        estado: 'no_realizado'
      },
      editPartidoForm: {
        id: null,
        campeonato_fecha_id: null,
        programado_fecha: '',
        programado_hora: '',
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null,
        goles_local: null,
        goles_visita: null,
        estado: 'no_realizado'
      },
      incidencias: {
        goles: [],
        tarjetas_amarillas: [],
        tarjetas_rojas: [],
        faltas: [],
        sustituciones: [],
        porteros: []
      },
      eventForms: {
        gol: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        amarilla: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        roja: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        falta: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        sustitucion: { equipo_id: null, jugador_sale_id: null, jugador_entra_id: null, minuto: null },
        portero: { equipo_id: null, jugador_id: null, nombre_portero: '' }
      },
      estadoOptions: [
        { label: 'No realizado', value: 'no_realizado' },
        { label: 'En vivo', value: 'en_vivo' },
        { label: 'Finalizado', value: 'finalizado' }
      ],
      btnLoading: {
        fase: false,
        generar: false,
        partido: false,
        editar: false,
        incidencia: false,
        refresh: false,
        report: false
      },
      newMessage: '',
      sendingMessage: false
    }
  },
  computed: {
    activeFase () {
      return this.fases.find(f => Number(f.id) === Number(this.faseId)) || null
    },
    faseOptions () {
      return (this.fases || []).map(f => ({
        ...f,
        label: `${f.nombre} (${f.tipo === 'eliminatoria' ? 'eliminatoria' : 'todos contra todos'})`
      }))
    },
    tabla () {
      return this.activeFase?.tabla || []
    },
    isEliminatoria () {
      return this.activeFase?.tipo === 'eliminatoria'
    },
    activePartidos () {
      return this.activeFase?.partidos || []
    },
    allTeamOptions () {
      return (this.campeonato?.equipos || []).map(e => ({
        id: e.id,
        nombre: e.nombre,
        imagen: e.imagen || 'torneoImagen.jpg',
        grupo_nombre: e.grupo?.nombre || e.grupo_nombre || null,
        jugadores: e.jugadores || []
      }))
    },
    editTeamOptions () {
      const ids = [Number(this.editPartidoForm.local_equipo_id), Number(this.editPartidoForm.visita_equipo_id)].filter(Boolean)
      if (!ids.length) return this.allTeamOptions
      return this.allTeamOptions.filter(t => ids.includes(Number(t.id)))
    },
    filteredTeamOptionsCreate () {
      if (!this.partidoForm.grupo_nombre) return this.allTeamOptions
      return this.allTeamOptions.filter(t => (t.grupo_nombre || 'Sin grupo') === this.partidoForm.grupo_nombre)
    },
    filteredTeamOptionsEdit () {
      if (!this.editPartidoForm.grupo_nombre) return this.allTeamOptions
      return this.allTeamOptions.filter(t => (t.grupo_nombre || 'Sin grupo') === this.editPartidoForm.grupo_nombre)
    },
    groupOptions () {
      return (this.campeonato?.grupos || []).map(g => ({ value: g.nombre, label: g.nombre }))
    },
    incidenciaMeta () {
      const map = {
        goles: { singular: 'Gol', plural: 'Goles', icon: 'sports_soccer', path: 'goles' },
        faltas: { singular: 'Falta', plural: 'Faltas', icon: 'sports', path: 'faltas' },
        amarillas: { singular: 'Tarjeta amarilla', plural: 'Tarjetas amarillas', icon: 'style', path: 'amarillas' },
        rojas: { singular: 'Tarjeta roja', plural: 'Tarjetas rojas', icon: 'warning', path: 'rojas' },
        sustituciones: { singular: 'Sustitucion', plural: 'Sustituciones', icon: 'swap_horiz', path: 'sustituciones' },
        porteros: { singular: 'Portero', plural: 'Porteros', icon: 'sports_handball', path: 'porteros' }
      }
      return map[this.currentIncTab] || map.goles
    },
    currentIncTab () {
      return this.editTab === 'basico' ? 'goles' : this.editTab
    },
    incidenciaTitle () {
      return this.incidenciaMeta.plural
    },
    incidenciaRows () {
      const map = {
        goles: this.incidencias.goles || [],
        faltas: this.incidencias.faltas || [],
        amarillas: this.incidencias.tarjetas_amarillas || [],
        rojas: this.incidencias.tarjetas_rojas || [],
        sustituciones: this.incidencias.sustituciones || [],
        porteros: this.incidencias.porteros || []
      }
      return map[this.currentIncTab] || []
    },
    incidenciaColumns () {
      if (this.currentIncTab === 'sustituciones') {
        return [
          { name: 'actions', label: 'Opciones', align: 'left' },
          { name: 'minuto', label: 'Min', align: 'left', field: row => row.minuto || '-' },
          { name: 'equipo', label: 'Equipo', align: 'left', field: row => row.equipo?.nombre || '-' },
          { name: 'sale', label: 'Sale', align: 'left', field: row => row.jugadorSale?.nombre || '-' },
          { name: 'entra', label: 'Entra', align: 'left', field: row => row.jugadorEntra?.nombre || '-' }
        ]
      }
      if (this.currentIncTab === 'porteros') {
        return [
          { name: 'actions', label: 'Opciones', align: 'left' },
          { name: 'equipo', label: 'Equipo', align: 'left', field: row => row.equipo?.nombre || '-' },
          { name: 'jugador', label: 'Jugador', align: 'left', field: row => row.jugador?.nombre || row.nombre_portero || '-' }
        ]
      }
      return [
        { name: 'actions', label: 'Opciones', align: 'left' },
        { name: 'minuto', label: 'Min', align: 'left', field: row => row.minuto || '-' },
        { name: 'equipo', label: 'Equipo', align: 'left', field: row => row.equipo?.nombre || '-' },
        { name: 'jugador', label: 'Jugador', align: 'left', field: row => row.jugador?.nombre || '-' },
        { name: 'detalle', label: 'Detalle', align: 'left', field: row => row.detalle || '-' }
      ]
    },
    currentEventForm () {
      const map = {
        goles: this.eventForms.gol,
        faltas: this.eventForms.falta,
        amarillas: this.eventForms.amarilla,
        rojas: this.eventForms.roja,
        sustituciones: this.eventForms.sustitucion,
        porteros: this.eventForms.portero
      }
      return map[this.currentIncTab] || this.eventForms.gol
    }
  },
  watch: {
    code () {
      this.cargar()
    },
    faseId (val) {
      this.partidoForm.campeonato_fase_id = val
    },
    'partidoForm.grupo_nombre' () {
      this.cleanInvalidTeams()
    },
    'editPartidoForm.grupo_nombre' () {
      this.cleanInvalidTeamsEdit()
    }
  },
  mounted () {
    this.cargar()
  },
  methods: {
    imageSrc (name) {
      return `${this.$url}../../images/${name || 'torneoImagen.jpg'}`
    },
    equipoLogo (equipoId, fallback = null) {
      const eq = (this.campeonato?.equipos || []).find(e => Number(e.id) === Number(equipoId))
      return this.imageSrc(eq?.imagen || fallback || 'torneoImagen.jpg')
    },
    playersByTeam (equipoId) {
      const eq = (this.campeonato?.equipos || []).find(e => Number(e.id) === Number(equipoId))
      return (eq?.jugadores || []).map(j => ({ id: j.id, nombre: j.nombre }))
    },
    formatScore (partido) {
      if (partido.goles_local === null || partido.goles_visita === null) return 'vs'
      return `${partido.goles_local}:${partido.goles_visita}`
    },
    formatDateTime (value) {
      if (!value) return ''
      return new Date(value).toLocaleString()
    },
    formatPartidoSchedule (value) {
      if (!value) return 'Sin fecha'
      const d = new Date(value)
      if (Number.isNaN(d.getTime())) return 'Sin fecha'
      const fecha = d.toLocaleDateString()
      const hora = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      return `${fecha} ${hora}`
    },
    buildProgramadoAt (fecha, hora) {
      if (!fecha) return null
      const hhmm = hora || '00:00'
      return `${fecha} ${hhmm}:00`
    },
    splitProgramadoAt (value) {
      if (!value) return { fecha: '', hora: '' }
      const d = new Date(value)
      if (!Number.isNaN(d.getTime())) {
        const yyyy = d.getFullYear()
        const mm = String(d.getMonth() + 1).padStart(2, '0')
        const dd = String(d.getDate()).padStart(2, '0')
        const hh = String(d.getHours()).padStart(2, '0')
        const mi = String(d.getMinutes()).padStart(2, '0')
        return { fecha: `${yyyy}-${mm}-${dd}`, hora: `${hh}:${mi}` }
      }
      const txt = String(value)
      const [datePart, timePart = ''] = txt.split(' ')
      return { fecha: datePart || '', hora: timePart.slice(0, 5) || '' }
    },
    estadoLabel (estado) {
      const map = {
        no_realizado: 'No realizado',
        en_vivo: 'En vivo',
        finalizado: 'Finalizado',
        jugado: 'Finalizado',
        pendiente: 'No realizado'
      }
      return map[estado] || 'No realizado'
    },
    estadoClass (estado) {
      if (estado === 'en_vivo') return 'estado-vivo'
      if (estado === 'finalizado' || estado === 'jugado') return 'estado-finalizado'
      return 'estado-no-realizado'
    },
    estadoColor (estado) {
      if (estado === 'en_vivo') return 'amber-6'
      if (estado === 'finalizado' || estado === 'jugado') return 'positive'
      return 'grey-5'
    },
    estadoTextColor (estado) {
      return estado === 'en_vivo' ? 'black' : 'white'
    },
    defaultFaseName () {
      const index = this.fases.length
      if (PHASE_NAMES[index]) return PHASE_NAMES[index]
      return `Fase ${index + 1}`
    },
    resetFaseForm () {
      this.faseForm = { id: null, nombre: this.defaultFaseName(), tipo: 'liga' }
    },
    editarFase (fase) {
      this.faseForm = { id: fase.id, nombre: fase.nombre, tipo: fase.tipo || 'liga' }
    },
    eliminarFase (fase) {
      this.$alert.dialog(`Deseas eliminar la fase ${fase.nombre}?`)
        .onOk(() => {
          this.btnLoading.fase = true
          this.$axios.delete(`campeonatos/${this.campeonato.id}/fases/${fase.id}`)
            .then(() => {
              this.$alert.success('Fase eliminada')
              this.resetFaseForm()
              this.cargar()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar fase'))
            .finally(() => { this.btnLoading.fase = false })
        })
    },
    cleanInvalidTeams () {
      const validIds = this.filteredTeamOptionsCreate.map(t => Number(t.id))
      if (!validIds.includes(Number(this.partidoForm.local_equipo_id))) {
        this.partidoForm.local_equipo_id = null
      }
      if (!validIds.includes(Number(this.partidoForm.visita_equipo_id))) {
        this.partidoForm.visita_equipo_id = null
      }
    },
    cleanInvalidTeamsEdit () {
      const validIds = this.filteredTeamOptionsEdit.map(t => Number(t.id))
      if (!validIds.includes(Number(this.editPartidoForm.local_equipo_id))) {
        this.editPartidoForm.local_equipo_id = null
      }
      if (!validIds.includes(Number(this.editPartidoForm.visita_equipo_id))) {
        this.editPartidoForm.visita_equipo_id = null
      }
    },
    abrirFaseDialog () {
      this.resetFaseForm()
      this.faseDialog = true
    },
    abrirPartidoDialog () {
      this.partidoForm = {
        campeonato_fase_id: this.activeFase?.id || this.faseId,
        campeonato_fecha_id: null,
        local_equipo_id: null,
        visita_equipo_id: null,
        grupo_nombre: null,
        estado: 'no_realizado'
      }
      this.partidoDialog = true
    },
    openEditPartido (p) {
      if (!this.canEdit) return
      const programado = this.splitProgramadoAt(p.programado_at)
      this.editPartidoForm = {
        id: p.id,
        campeonato_fecha_id: p.campeonato_fecha_id || null,
        programado_fecha: programado.fecha,
        programado_hora: programado.hora,
        local_equipo_id: p.local_equipo_id || null,
        visita_equipo_id: p.visita_equipo_id || null,
        grupo_nombre: p.grupo_nombre || null,
        goles_local: p.goles_local,
        goles_visita: p.goles_visita,
        estado: p.estado || 'no_realizado'
      }
      this.editTab = 'basico'
      this.editPartidoDialog = true
      this.loadIncidencias()
    },
    loadIncidencias () {
      if (!this.editPartidoForm.id || !this.canEdit) return
      this.$axios.get(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}/incidencias`)
        .then(r => { this.applyIncidencias(r.data) })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar incidencias'))
    },
    applyIncidencias (data) {
      this.incidencias = {
        goles: data?.goles || [],
        tarjetas_amarillas: data?.tarjetas_amarillas || [],
        tarjetas_rojas: data?.tarjetas_rojas || [],
        faltas: data?.faltas || [],
        sustituciones: data?.sustituciones || [],
        porteros: data?.porteros || []
      }
      if (Object.prototype.hasOwnProperty.call(data || {}, 'goles_local')) {
        this.editPartidoForm.goles_local = data.goles_local
      }
      if (Object.prototype.hasOwnProperty.call(data || {}, 'goles_visita')) {
        this.editPartidoForm.goles_visita = data.goles_visita
      }
    },
    cargar () {
      this.btnLoading.refresh = true
      this.$axios.get(`public/campeonatos/${this.code}/clasificacion`)
        .then(r => {
          const prevId = this.faseId
          this.fases = r.data || []
          if (!this.fases.length) return
          const exists = this.fases.some(f => Number(f.id) === Number(prevId))
          this.faseId = exists ? prevId : this.fases[0].id
          this.partidoForm.campeonato_fase_id = this.faseId
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo cargar clasificacion'))
        .finally(() => { this.btnLoading.refresh = false })
    },
    downloadBlob (blob, filename) {
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      a.remove()
      window.URL.revokeObjectURL(url)
    },
    downloadReport (type) {
      this.btnLoading.report = true
      const params = { type }
      if (this.faseId) params.fase_id = this.faseId
      this.$axios.get(`public/campeonatos/${this.code}/reportes/pdf`, {
        params,
        responseType: 'blob'
      })
        .then((res) => {
          const ext = (this.activeFase?.nombre || 'fase').toString().replace(/\s+/g, '_')
          this.downloadBlob(res.data, `reporte_${type}_${ext}.pdf`)
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo generar PDF'))
        .finally(() => { this.btnLoading.report = false })
    },
    toggleAgrupar (value) {
      if (!this.activeFase || !this.canEdit) return
      this.$axios.put(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}`, {
        nombre: this.activeFase.nombre,
        tipo: this.activeFase.tipo,
        agrupar_por_grupo: !!value
      })
        .then(() => this.cargar())
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo actualizar fase'))
    },
    guardarFase () {
      const nombre = (this.faseForm.nombre || '').trim()
      if (!nombre) {
        this.$alert.error('Nombre de fase requerido')
        return
      }
      this.btnLoading.fase = true
      const req = this.faseForm.id
        ? this.$axios.put(`campeonatos/${this.campeonato.id}/fases/${this.faseForm.id}`, {
          nombre,
          tipo: this.faseForm.tipo,
          agrupar_por_grupo: true
        })
        : this.$axios.post(`campeonatos/${this.campeonato.id}/fases`, {
          nombre,
          tipo: this.faseForm.tipo,
          agrupar_por_grupo: true
        })

      req
        .then(() => {
          this.$alert.success(this.faseForm.id ? 'Fase actualizada' : 'Fase creada')
          this.resetFaseForm()
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar fase'))
        .finally(() => { this.btnLoading.fase = false })
    },
    generarPartidos () {
      if (!this.activeFase) return
      this.btnLoading.generar = true
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${this.activeFase.id}/generar-partidos`, {
        modo: this.genMode
      })
        .then(() => {
          this.genDialog = false
          this.$alert.success('Partidos generados')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo generar partidos'))
        .finally(() => { this.btnLoading.generar = false })
    },
    guardarPartido () {
      const faseId = this.partidoForm.campeonato_fase_id
      if (!faseId) {
        this.$alert.error('Selecciona una fase')
        return
      }
      if (this.partidoForm.local_equipo_id && this.partidoForm.local_equipo_id === this.partidoForm.visita_equipo_id) {
        this.$alert.error('Local y visita no pueden ser el mismo equipo')
        return
      }
      const payload = {
        campeonato_fecha_id: null,
        local_equipo_id: this.partidoForm.local_equipo_id,
        visita_equipo_id: this.partidoForm.visita_equipo_id,
        grupo_nombre: this.partidoForm.grupo_nombre,
        estado: this.partidoForm.estado
      }
      this.btnLoading.partido = true
      this.$axios.post(`campeonatos/${this.campeonato.id}/fases/${faseId}/partidos`, payload)
        .then(() => {
          this.partidoDialog = false
          this.$alert.success('Partido agregado')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo agregar partido'))
        .finally(() => { this.btnLoading.partido = false })
    },
    guardarEdicionPartido () {
      if (!this.editPartidoForm.id) return
      if (this.editPartidoForm.local_equipo_id && this.editPartidoForm.local_equipo_id === this.editPartidoForm.visita_equipo_id) {
        this.$alert.error('Local y visita no pueden ser el mismo equipo')
        return
      }
      this.btnLoading.editar = true
      this.$axios.put(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}`, {
        campeonato_fecha_id: null,
        programado_at: this.buildProgramadoAt(this.editPartidoForm.programado_fecha, this.editPartidoForm.programado_hora),
        local_equipo_id: this.editPartidoForm.local_equipo_id,
        visita_equipo_id: this.editPartidoForm.visita_equipo_id,
        grupo_nombre: this.editPartidoForm.grupo_nombre,
        goles_local: this.editPartidoForm.goles_local,
        goles_visita: this.editPartidoForm.goles_visita,
        estado: this.editPartidoForm.estado || 'no_realizado'
      })
        .then(() => {
          this.editPartidoDialog = false
          this.$alert.success('Partido actualizado')
          this.cargar()
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar partido'))
        .finally(() => { this.btnLoading.editar = false })
    },
    eliminarPartido () {
      if (!this.editPartidoForm.id) return
      this.$alert.dialog('Se eliminara este partido. Deseas continuar?')
        .onOk(() => {
          this.btnLoading.editar = true
          this.$axios.delete(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}`)
            .then(() => {
              this.editPartidoDialog = false
              this.$alert.success('Partido eliminado')
              this.cargar()
            })
            .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar partido'))
            .finally(() => { this.btnLoading.editar = false })
        })
    },
    postIncidencia (path, payload) {
      if (!this.editPartidoForm.id) return
      this.btnLoading.incidencia = true
      return this.$axios.post(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}/${path}`, payload)
        .then(r => {
          this.applyIncidencias(r.data)
          if (path === 'goles') this.cargar()
          return r
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo guardar incidencia'))
        .finally(() => { this.btnLoading.incidencia = false })
    },
    deleteIncidencia (path, id) {
      if (!this.editPartidoForm.id) return
      this.btnLoading.incidencia = true
      return this.$axios.delete(`campeonatos/${this.campeonato.id}/partidos/${this.editPartidoForm.id}/${path}/${id}`)
        .then(r => {
          this.applyIncidencias(r.data)
          if (path === 'goles') this.cargar()
          return r
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo eliminar incidencia'))
        .finally(() => { this.btnLoading.incidencia = false })
    },
    resetCurrentEventForm () {
      this.eventForms = {
        ...this.eventForms,
        gol: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        amarilla: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        roja: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        falta: { equipo_id: null, jugador_id: null, minuto: null, detalle: '' },
        sustitucion: { equipo_id: null, jugador_sale_id: null, jugador_entra_id: null, minuto: null },
        portero: { equipo_id: null, jugador_id: null, nombre_portero: '' }
      }
    },
    openIncidenciaDialog (mode = 'insert', row = null) {
      this.incidenciaDialogMode = mode
      this.editingIncidenciaId = row?.id || null
      this.resetCurrentEventForm()
      if (mode === 'edit' && row) {
        if (this.currentIncTab === 'goles') this.eventForms.gol = { equipo_id: row.equipo_id || null, jugador_id: row.jugador_id || null, minuto: row.minuto || null, detalle: row.detalle || '' }
        if (this.currentIncTab === 'faltas') this.eventForms.falta = { equipo_id: row.equipo_id || null, jugador_id: row.jugador_id || null, minuto: row.minuto || null, detalle: row.detalle || '' }
        if (this.currentIncTab === 'amarillas') this.eventForms.amarilla = { equipo_id: row.equipo_id || null, jugador_id: row.jugador_id || null, minuto: row.minuto || null, detalle: row.detalle || '' }
        if (this.currentIncTab === 'rojas') this.eventForms.roja = { equipo_id: row.equipo_id || null, jugador_id: row.jugador_id || null, minuto: row.minuto || null, detalle: row.detalle || '' }
        if (this.currentIncTab === 'sustituciones') this.eventForms.sustitucion = { equipo_id: row.equipo_id || null, jugador_sale_id: row.jugador_sale_id || null, jugador_entra_id: row.jugador_entra_id || null, minuto: row.minuto || null }
        if (this.currentIncTab === 'porteros') this.eventForms.portero = { equipo_id: row.equipo_id || null, jugador_id: row.jugador_id || null, nombre_portero: row.nombre_portero || '' }
      }
      this.incidenciaDialog = true
    },
    saveIncidenciaDialog () {
      const path = this.incidenciaMeta.path
      const payload = { ...this.currentEventForm }
      if (this.incidenciaDialogMode === 'edit' && this.editingIncidenciaId) {
        this.deleteIncidencia(path, this.editingIncidenciaId).then(() => this.postIncidencia(path, payload)).then(() => { this.incidenciaDialog = false })
        return
      }
      this.postIncidencia(path, payload).then(() => { this.incidenciaDialog = false })
    },
    deleteCurrentRow (row) {
      if (!row?.id) return
      this.$alert.dialog(`Deseas eliminar ${this.incidenciaMeta.singular}?`).onOk(() => this.deleteIncidencia(this.incidenciaMeta.path, row.id))
    },
    addGol () { this.postIncidencia('goles', this.eventForms.gol) },
    deleteGol (id) { this.deleteIncidencia('goles', id) },
    addAmarilla () { this.postIncidencia('amarillas', this.eventForms.amarilla) },
    deleteAmarilla (id) { this.deleteIncidencia('amarillas', id) },
    addRoja () { this.postIncidencia('rojas', this.eventForms.roja) },
    deleteRoja (id) { this.deleteIncidencia('rojas', id) },
    addFalta () { this.postIncidencia('faltas', this.eventForms.falta) },
    deleteFalta (id) { this.deleteIncidencia('faltas', id) },
    addSustitucion () { this.postIncidencia('sustituciones', this.eventForms.sustitucion) },
    deleteSustitucion (id) { this.deleteIncidencia('sustituciones', id) },
    savePortero () { this.postIncidencia('porteros', this.eventForms.portero) },
    deletePortero (id) { this.deleteIncidencia('porteros', id) },
    sendMessage () {
      if (!this.newMessage || !this.newMessage.trim()) {
        this.$alert.error('Escribe un comentario')
        return
      }
      this.sendingMessage = true
      this.$axios.post(`public/campeonatos/${this.code}/mensajes`, {
        mensaje: this.newMessage.trim()
      })
        .then(() => {
          this.newMessage = ''
          this.$emit('refresh-mensajes')
        })
        .catch(e => this.$alert.error(e.response?.data?.message || 'No se pudo enviar comentario'))
        .finally(() => { this.sendingMessage = false })
    }
  }
}
</script>

<style scoped>
.bg-dark-card {
  background: #0b1220;
  border-color: rgba(148, 163, 184, 0.25);
}
.table-standings th,
.table-standings td {
  font-size: 12px;
}
.match-list {
  max-height: 620px;
  overflow-y: auto;
}
.match-list-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
}
.match-card {
  background: rgba(15, 23, 42, 0.45);
  border-color: rgba(148, 163, 184, 0.22);
}
.match-mid-card {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 8px;
  padding: 3px 4px;
  background: linear-gradient(180deg, #c79218 0%, #9b6b06 100%);
  color: #ffffff;
  line-height: 1.1;
}
.match-score {
  font-size: 40px;
  line-height: 1;
  color: #ffffff;
  letter-spacing: 0;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
}
.estado-no-realizado {
  border-color: #9ca3af;
  background: linear-gradient(180deg, #6b7280 0%, #4b5563 100%);
}
.estado-vivo {
  border-color: #ef4444;
  background: linear-gradient(180deg, #ef4444 0%, #b91c1c 100%);
}
.estado-finalizado {
  border-color: #22c55e;
  background: linear-gradient(180deg, #22c55e 0%, #15803d 100%);
}
.match-card.editable {
  cursor: pointer;
}
.clasif-root.mode-light .bg-dark-card {
  background: #ffffff;
  color: #0f172a !important;
  border-color: #dbe4f0;
}
.clasif-root.mode-light .table-standings {
  color: #0f172a !important;
}
.clasif-root.mode-light .match-card {
  background: #f8fafc;
  border-color: #dbe4f0;
}
.clasif-root.mode-light .match-mid-card {
  color: #ffffff;
}
.clasif-root.mode-light .match-score {
  color: #334155;
  text-shadow: none;
  font-weight: 800;
}
@media (min-width: 1024px) {
  .match-list-grid {
    grid-template-columns: 1fr 1fr;
  }
}
@media (max-width: 599px) {
  .match-list {
    max-height: none;
  }
  .match-score {
    font-size: 30px;
  }
  .match-mid-card {
    padding: 2px 3px;
  }
}
</style>
