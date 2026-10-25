<template>
  <div class="eventos-dashboard">
    <header class="cabecera">
      <div>
        <div class="etiqueta-seccion"><span class="punto"></span> MÓDULO DE EVENTOS</div>
        <h1>Gestión de Eventos</h1>
        <p>Administra ferias, exposiciones, responsables y stands desde un solo lugar.</p>
      </div>
      <button class="btn btn-principal" @click="$router.push('/eventos/crear')">＋ Nuevo evento</button>
    </header>

    <section class="indicadores">
      <div class="indicador indicador-verde">
        <div class="indicador-icono">▦</div>
        <div><span>Eventos encontrados</span><strong>{{ pagination.total }}</strong><small>Según los filtros actuales</small></div>
      </div>
      <div class="indicador indicador-naranja">
        <div class="indicador-icono">✓</div>
        <div><span>Activos en esta página</span><strong>{{ eventos.filter(e => e.estado).length }}</strong><small>Eventos disponibles</small></div>
      </div>
      <div class="indicador indicador-blanco">
        <div class="indicador-icono">⌕</div>
        <div><span>Registros por página</span><strong>{{ pagination.per_page }}</strong><small>Página {{ pagination.current_page }} de {{ pagination.last_page }}</small></div>
      </div>
    </section>

    <section class="panel">
      <div class="panel-encabezado">
        <div><h2>Directorio de eventos</h2><p>Busca, filtra y administra tus eventos registrados.</p></div>
        <span class="etiqueta-total">{{ pagination.total }} registros</span>
      </div>
      <div class="filtros">
        <label class="campo-busqueda">
          <span class="sr-only">Buscar eventos</span>
          <input v-model="buscar" @input="buscarEventos" placeholder="⌕  Buscar por nombre, tipo, departamento o lugar..." />
        </label>
        <select v-model="tipo" aria-label="Filtrar por tipo" @change="filtrarEventos">
          <option value="">Todos los tipos</option>
          <option value="FERIA">Feria</option><option value="EXPOSICION">Exposición</option>
          <option value="PROMOCION">Promoción</option><option value="OTRO">Otro</option>
        </select>
        <select v-model="departamento" aria-label="Filtrar por departamento" @change="filtrarEventos">
          <option value="">Todos los departamentos</option>
          <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
        </select>
        <select v-model="estado" aria-label="Filtrar por estado" @change="filtrarEventos">
          <option value="">Todos los estados</option>
          <option value="true">Activos</option><option value="false">Inactivos</option>
        </select>
        <select v-model.number="pagination.per_page" aria-label="Registros por página" @change="filtrarEventos">
          <option :value="10">10 registros</option><option :value="25">25 registros</option><option :value="50">50 registros</option>
        </select>
      </div>

      <p v-if="error" class="mensaje-error" role="alert">{{ error }}</p>
      <div class="tabla-contenedor">
        <table class="tabla-eventos">
          <thead><tr>
            <th>ID</th><th>Evento</th><th>Tipo</th><th>Departamento</th><th>Lugar</th>
            <th>Fecha inicio</th><th>Fecha fin</th><th>Responsable</th><th>Estado</th><th>Acciones</th>
          </tr></thead>
          <tbody>
            <template v-for="evento in eventos" :key="evento.id_evento">
              <tr :class="{ 'evento-seleccionado': eventoAbierto === evento.id_evento }">
                <td><span class="id-evento">#{{ evento.id_evento }}</span></td>
                <td><strong class="nombre-evento">{{ evento.nombre }}</strong></td>
                <td><span class="tipo-evento">{{ evento.tipo }}</span></td>
                <td>{{ evento.departamento }}</td><td class="celda-lugar">{{ evento.lugar }}</td>
                <td>{{ formatearFecha(evento.fecha_inicio) }}</td>
                <td>{{ formatearFecha(evento.fecha_fin) }}</td>
                <td>{{ nombreResponsable(evento) }}</td>
                <td><span :class="evento.estado ? 'estado-activo' : 'estado-inactivo'">{{ evento.estado ? '● Activo' : '● Inactivo' }}</span></td>
                <td>
                  <div class="acciones">
                    <button class="btn btn-stands" :aria-expanded="eventoAbierto === evento.id_evento"
                      @click="alternarStands(evento.id_evento)">
                      {{ eventoAbierto === evento.id_evento ? '▴ Ocultar' : '▾ Stands' }}
                    </button>
                    <button class="btn btn-editar" @click="$router.push(`/eventos/editar/${evento.id_evento}`)">Editar</button>
                    <button class="btn btn-eliminar" :disabled="eliminando === evento.id_evento" @click="eliminarEvento(evento)">Eliminar</button>
                  </div>
                </td>
              </tr>
              <tr v-if="eventoAbierto === evento.id_evento" class="fila-stands">
                <td colspan="10">
                  <div class="stands-detalle">
                    <div class="stands-cabecera">
                      <div><span class="subtitulo">STANDS VINCULADOS</span><h3>Stands de {{ evento.nombre }}</h3><p>Ubicaciones asignadas exclusivamente a este evento.</p></div>
                      <button class="btn btn-principal" @click="$router.push(`/ubicaciones/crear?tipo=STAND&id_evento=${evento.id_evento}`)">＋ Nuevo stand</button>
                    </div>
                    <p v-if="cargandoStands" class="mensaje-info">Cargando stands...</p>
                    <p v-else-if="errorStands" class="mensaje-error" role="alert">{{ errorStands }}</p>
                    <div v-else class="tabla-stands">
                      <table>
                        <thead><tr><th>ID</th><th>Stand</th><th>Propiedad</th><th>Departamento</th><th>Dirección</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                          <tr v-for="stand in stands" :key="stand.id_ubicacion">
                            <td><span class="id-evento">#{{ stand.id_ubicacion }}</span></td>
                            <td><strong>{{ stand.nombre }}</strong></td><td>{{ stand.propiedad }}</td>
                            <td>{{ stand.departamento }}</td><td>{{ stand.direccion || '—' }}</td>
                            <td><span :class="stand.estado ? 'estado-activo' : 'estado-inactivo'">{{ stand.estado ? '● Activo' : '● Inactivo' }}</span></td>
                            <td><button class="btn btn-editar" @click="$router.push(`/ubicaciones/editar/${stand.id_ubicacion}`)">Editar</button></td>
                          </tr>
                          <tr v-if="!stands.length"><td colspan="7" class="sin-registros">Este evento todavía no tiene stands registrados.</td></tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-if="cargando"><td colspan="10" class="sin-registros">Cargando eventos...</td></tr>
            <tr v-else-if="!eventos.length && !error"><td colspan="10" class="sin-registros">No se encontraron eventos con los filtros seleccionados.</td></tr>
          </tbody>
        </table>
      </div>
      <footer class="paginacion">
        <span>Mostrando página <strong>{{ pagination.current_page }}</strong> de <strong>{{ pagination.last_page }}</strong> · Total: {{ pagination.total }}</span>
        <div class="controles">
          <button class="btn btn-pagina" :disabled="cargando || pagination.current_page <= 1" @click="cambiarPagina(-1)">← Anterior</button>
          <button class="btn btn-pagina" :disabled="cargando || pagination.current_page >= pagination.last_page" @click="cambiarPagina(1)">Siguiente →</button>
        </div>
      </footer>
    </section>
  </div>
</template>

<script>
import api from '../../services/api';

export default {
  name: 'EventosView',
  data() {
    return {
      eventos: [], buscar: '', tipo: '', departamento: '', estado: '',
      cargando: false, eliminando: null, error: '', temporizadorBusqueda: null, numeroPeticion: 0,
      eventoAbierto: null, stands: [], cargandoStands: false, errorStands: '', numeroPeticionStands: 0,
      departamentos: ['La Paz', 'Cochabamba', 'Santa Cruz', 'Oruro', 'Potosí', 'Chuquisaca', 'Tarija', 'Beni', 'Pando'],
      pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    };
  },
  mounted() { this.eventosGet(); },
  beforeUnmount() { clearTimeout(this.temporizadorBusqueda); this.numeroPeticion++; this.numeroPeticionStands++; },
  methods: {
    async alternarStands(idEvento) {
      if (this.eventoAbierto === idEvento) {
        this.eventoAbierto = null;
        this.numeroPeticionStands++;
        return;
      }
      this.eventoAbierto = idEvento;
      this.stands = [];
      this.errorStands = '';
      this.cargandoStands = true;
      const peticion = ++this.numeroPeticionStands;
      try {
        const resultado = [];
        let pagina = 1;
        let ultimaPagina = 1;
        do {
          const { data } = await api.get('/api/ubicaciones', {
            params: { id_evento: idEvento, tipo: 'STAND', limit: 50, page: pagina }
          });
          if (peticion !== this.numeroPeticionStands) return;
          resultado.push(...(Array.isArray(data) ? data : (data.data || [])));
          ultimaPagina = Number(data.last_page || 1);
          pagina++;
        } while (pagina <= ultimaPagina);
        this.stands = resultado.filter(stand => stand.tipo === 'STAND' && Number(stand.id_evento) === Number(idEvento));
      } catch (error) {
        if (peticion !== this.numeroPeticionStands) return;
        this.errorStands = error.response?.data?.message || 'No se pudieron cargar los stands del evento.';
        console.error('Error al cargar stands:', error);
      } finally {
        if (peticion === this.numeroPeticionStands) this.cargandoStands = false;
      }
    },
    async eventosGet() {
      const peticion = ++this.numeroPeticion;
      this.cargando = true;
      this.error = '';
      this.eventoAbierto = null;
      this.numeroPeticionStands++;
      this.stands = [];
      try {
        const { data } = await api.get('/api/eventos', {
          params: {
            page: this.pagination.current_page, limit: this.pagination.per_page,
            buscar: this.buscar, tipo: this.tipo, departamento: this.departamento, estado: this.estado
          }
        });
        if (peticion !== this.numeroPeticion) return;
        this.eventos = Array.isArray(data) ? data : (data.data || []);
        this.pagination.current_page = data.current_page || 1;
        this.pagination.last_page = data.last_page || 1;
        this.pagination.total = data.total ?? this.eventos.length;
      } catch (error) {
        if (peticion !== this.numeroPeticion) return;
        this.eventos = [];
        this.pagination.total = 0;
        this.error = error.response?.data?.message || 'No se pudieron cargar los eventos. Verifique la conexión con Laravel.';
        console.error('Error al obtener eventos:', error);
      } finally {
        if (peticion === this.numeroPeticion) this.cargando = false;
      }
    },
    buscarEventos() {
      clearTimeout(this.temporizadorBusqueda);
      this.temporizadorBusqueda = setTimeout(() => this.filtrarEventos(), 400);
    },
    filtrarEventos() {
      clearTimeout(this.temporizadorBusqueda);
      this.pagination.current_page = 1;
      this.eventosGet();
    },
    cambiarPagina(paso) {
      const pagina = this.pagination.current_page + paso;
      if (pagina < 1 || pagina > this.pagination.last_page) return;
      this.pagination.current_page = pagina;
      this.eventosGet();
    },
    async eliminarEvento(evento) {
      if (!confirm(`¿Está seguro de eliminar el evento "${evento.nombre}"?`)) return;
      this.eliminando = evento.id_evento;
      try {
        const { data } = await api.delete(`/api/eventos/${evento.id_evento}`);
        alert(data.message || 'Evento eliminado correctamente.');
        if (this.eventos.length === 1 && this.pagination.current_page > 1) this.pagination.current_page--;
        await this.eventosGet();
      } catch (error) {
        alert(error.response?.data?.message || 'No se pudo eliminar el evento.');
      } finally { this.eliminando = null; }
    },
    nombreResponsable(evento) {
      const persona = evento.responsable?.persona;
      return persona ? [persona.nombres_razon_social, persona.apellidos].filter(Boolean).join(' ') : 'Sin responsable';
    },
    formatearFecha(fecha) { return fecha ? String(fecha).substring(0, 10) : '—'; }
  }
};
</script>

<style scoped>
.eventos-dashboard { --verde: #124734; --verde-profundo: #0c3025; --verde-suave: #e8f3ed; --naranja: #f47b20; --naranja-oscuro: #d65c08; --texto: #1b2b27; --muted: #718078; --borde: #e3eae6; color: var(--texto); width: 100%; padding: 4px 0 26px; }
* { box-sizing: border-box; }
.cabecera { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 23px; }
.etiqueta-seccion, .subtitulo { font-size: 11px; font-weight: 800; letter-spacing: 1.3px; color: var(--verde); }
.punto { display: inline-block; width: 8px; height: 8px; background: var(--naranja); border-radius: 50%; margin-right: 6px; }
.cabecera h1 { font-size: clamp(25px, 2.4vw, 34px); font-weight: 800; margin: 8px 0 5px; color: var(--verde-profundo); }
.cabecera p, .panel-encabezado p, .stands-cabecera p { color: var(--muted); margin: 0; font-size: 13px; }
.btn { border: 0; border-radius: 8px; padding: 9px 13px; font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background .15s, transform .15s; }
.btn:hover:not(:disabled) { transform: translateY(-1px); }
.btn:disabled { cursor: not-allowed; opacity: .5; }
.btn-principal { background: var(--naranja); color: white; padding: 12px 19px; box-shadow: 0 4px 12px #f47b2026; }
.btn-principal:hover { background: var(--naranja-oscuro); }
.indicadores { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
.indicador { display: flex; align-items: center; gap: 16px; border-radius: 12px; padding: 21px 22px; min-height: 118px; box-shadow: 0 3px 15px #172f2310; }
.indicador-verde { background: linear-gradient(115deg, #124734, #21644c); color: white; }
.indicador-naranja { background: linear-gradient(115deg, #e76d12, #fa9237); color: white; }
.indicador-blanco { background: white; border: 1px solid var(--borde); }
.indicador-icono { display: grid; place-items: center; flex-shrink: 0; width: 48px; height: 48px; border-radius: 11px; background: #ffffff27; font-size: 27px; font-weight: 700; }
.indicador-blanco .indicador-icono { background: var(--verde-suave); color: var(--verde); }
.indicador span, .indicador small { display: block; font-size: 12px; opacity: .87; }
.indicador strong { display: block; font-size: 30px; line-height: 1.25; font-weight: 800; }
.indicador small { font-size: 11px; }
.panel { background: white; border: 1px solid var(--borde); border-radius: 13px; box-shadow: 0 3px 18px #142b2010; overflow: hidden; }
.panel-encabezado { padding: 22px 23px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.panel-encabezado h2 { color: var(--verde-profundo); font-size: 19px; margin: 0 0 4px; }
.etiqueta-total { color: var(--verde); background: var(--verde-suave); border-radius: 30px; padding: 8px 13px; font-weight: 700; font-size: 12px; }
.filtros { padding: 0 23px 20px; display: flex; gap: 10px; flex-wrap: wrap; }
.campo-busqueda { flex: 1; min-width: 230px; }
.filtros input, .filtros select { width: 100%; background: #fafcfb; color: var(--texto); border: 1px solid #dce5df; border-radius: 8px; padding: 11px 12px; font-size: 12px; min-height: 42px; outline-color: var(--naranja); }
.filtros select { width: auto; max-width: 210px; }
.tabla-contenedor, .tabla-stands { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { background: var(--verde-profundo); color: #fff; text-transform: uppercase; letter-spacing: .35px; font-size: 10px; font-weight: 700; }
th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid var(--borde); vertical-align: middle; }
td { font-size: 12px; color: #3c4c43; }
.tabla-eventos > tbody > tr:not(.fila-stands):hover { background: #f8fbf9; }
.evento-seleccionado { background: #edf7f0 !important; }
.id-evento { font-weight: 700; color: var(--muted); }
.nombre-evento { color: var(--verde-profundo); font-size: 13px; }
.tipo-evento { color: var(--verde); background: var(--verde-suave); padding: 5px 8px; border-radius: 5px; font-weight: 700; font-size: 10px; }
.celda-lugar { min-width: 160px; max-width: 240px; }
.estado-activo, .estado-inactivo { display: inline-block; padding: 6px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.estado-activo { background: #e1f5e7; color: #1b7846; }
.estado-inactivo { background: #fde9e7; color: #b84236; }
.acciones { display: flex; align-items: center; gap: 6px; }
.btn-stands { background: var(--verde); color: white; }
.btn-stands:hover { background: var(--verde-profundo); }
.btn-editar { background: #fff0df; color: #b8570b; }
.btn-editar:hover { background: #ffe1bd; }
.btn-eliminar { background: #fff0ee; color: #c24135; }
.btn-eliminar:hover { background: #ffded9; }
.fila-stands > td { background: #f1f6f2; padding: 17px 22px; }
.stands-detalle { border: 1px solid #cfe3d6; background: white; border-left: 4px solid var(--naranja); border-radius: 10px; padding: 19px; box-shadow: 0 2px 12px #142b2010; }
.stands-cabecera { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 16px; }
.stands-cabecera h3 { margin: 5px 0; font-size: 18px; color: var(--verde-profundo); }
.tabla-stands { border-radius: 8px; border: 1px solid var(--borde); }
.tabla-stands th { background: #e7f0e9; color: var(--verde-profundo); }
.sin-registros { text-align: center; color: var(--muted); padding: 27px 12px; }
.mensaje-error { margin: 12px 23px; padding: 12px 15px; color: #9d3228; background: #fff0ee; border-radius: 8px; font-size: 13px; }
.mensaje-info { color: var(--muted); font-size: 13px; }
.paginacion { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 23px; color: var(--muted); font-size: 12px; }
.controles { display: flex; gap: 8px; }
.btn-pagina { background: #f3f7f4; color: var(--verde); border: 1px solid #dce8df; }
.btn-pagina:hover:not(:disabled) { background: var(--verde-suave); }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
@media (max-width: 900px) { .indicadores { gap: 10px; } .indicador { padding: 15px; } .indicador-icono { width: 38px; height: 38px; } .indicador strong { font-size: 25px; } }
@media (max-width: 680px) { .cabecera, .stands-cabecera, .paginacion { flex-wrap: wrap; } .indicadores { grid-template-columns: 1fr; } .filtros select { flex: 1; max-width: none; } .panel-encabezado, .filtros, .paginacion { padding-left: 15px; padding-right: 15px; } }
</style>
