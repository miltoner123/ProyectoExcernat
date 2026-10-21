<template>
  <div class="eventos">
    <div class="encabezado">
      <div><h1>Gestión de Eventos</h1><p>Administración de ferias, exposiciones y eventos comerciales.</p></div>
      <button @click="$router.push('/eventos/crear')">+ Nuevo Evento</button>
    </div>

    <div class="filtros">
      <input v-model="buscar" @input="buscarEventos" placeholder="Buscar por nombre, tipo, departamento o lugar..." />
      <select v-model="tipo" @change="filtrarEventos">
        <option value="">Todos los tipos</option>
        <option value="FERIA">Feria</option><option value="EXPOSICION">Exposición</option>
        <option value="PROMOCION">Promoción</option><option value="OTRO">Otro</option>
      </select>
      <select v-model="departamento" @change="filtrarEventos">
        <option value="">Todos los departamentos</option>
        <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
      </select>
      <select v-model="estado" @change="filtrarEventos">
        <option value="">Todos los estados</option>
        <option value="true">Activos</option><option value="false">Inactivos</option>
      </select>
      <select v-model.number="pagination.per_page" @change="filtrarEventos">
        <option :value="10">10 registros</option><option :value="25">25 registros</option><option :value="50">50 registros</option>
      </select>
    </div>

    <p v-if="error" class="mensaje-error" role="alert">{{ error }}</p>
    <div class="tabla-contenedor">
      <table>
        <thead><tr>
          <th>ID</th><th>Evento</th><th>Tipo</th><th>Departamento</th><th>Lugar</th>
          <th>Fecha Inicio</th><th>Fecha Fin</th><th>Responsable</th><th>Estado</th><th>Opciones</th>
        </tr></thead>
        <tbody>
          <tr v-for="evento in eventos" :key="evento.id_evento">
            <td>{{ evento.id_evento }}</td><td><strong>{{ evento.nombre }}</strong></td>
            <td>{{ evento.tipo }}</td><td>{{ evento.departamento }}</td><td>{{ evento.lugar }}</td>
            <td>{{ formatearFecha(evento.fecha_inicio) }}</td><td>{{ formatearFecha(evento.fecha_fin) }}</td>
            <td>{{ nombreResponsable(evento) }}</td>
            <td><span :class="evento.estado ? 'estado-activo' : 'estado-inactivo'">{{ evento.estado ? 'Activo' : 'Inactivo' }}</span></td>
            <td><div class="acciones">
              <button @click="$router.push(`/eventos/${evento.id_evento}/stands`)">Stands</button>
              <button @click="$router.push(`/eventos/editar/${evento.id_evento}`)">Editar</button>
              <button class="btn-eliminar" :disabled="eliminando === evento.id_evento" @click="eliminarEvento(evento)">Eliminar</button>
            </div></td>
          </tr>
          <tr v-if="cargando"><td colspan="10">Cargando eventos...</td></tr>
          <tr v-else-if="!eventos.length && !error"><td colspan="10">No se encontraron eventos.</td></tr>
        </tbody>
      </table>
    </div>

    <div class="paginacion">
      <span>Total: {{ pagination.total }} registros</span>
      <div class="controles">
        <button :disabled="cargando || pagination.current_page <= 1" @click="cambiarPagina(-1)">Anterior</button>
        <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
        <button :disabled="cargando || pagination.current_page >= pagination.last_page" @click="cambiarPagina(1)">Siguiente</button>
      </div>
    </div>
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
      departamentos: ['La Paz', 'Cochabamba', 'Santa Cruz', 'Oruro', 'Potosí', 'Chuquisaca', 'Tarija', 'Beni', 'Pando'],
      pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    };
  },
  mounted() { this.eventosGet(); },
  beforeUnmount() { clearTimeout(this.temporizadorBusqueda); this.numeroPeticion++; },
  methods: {
    async eventosGet() {
      const peticion = ++this.numeroPeticion;
      this.cargando = true;
      this.error = '';
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
.eventos { width: 100%; }
.encabezado { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 12px; }
.encabezado h1 { color: #1f2937; margin-bottom: 5px; }
.encabezado p { color: #6b7280; }
.encabezado button, .acciones button { background: #2563eb; color: white; border: none; border-radius: 6px; padding: 11px 18px; cursor: pointer; }
.filtros { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filtros input, .filtros select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
.filtros input { min-width: 250px; flex: 1; }
.tabla-contenedor { background: white; border-radius: 8px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 14px; }
th { background: #f9fafb; color: #374151; }
.estado-activo, .estado-inactivo { padding: 5px 8px; border-radius: 5px; }
.estado-activo { background: #dcfce7; color: #166534; }
.estado-inactivo { background: #fee2e2; color: #991b1b; }
.acciones { display: flex; gap: 5px; }
.acciones button { padding: 7px 10px; white-space: nowrap; }
.acciones .btn-eliminar { background: #dc2626; }
button:disabled { cursor: not-allowed; opacity: .5; }
.paginacion { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.controles { display: flex; align-items: center; gap: 10px; }
.controles button { padding: 8px 12px; cursor: pointer; }
.mensaje-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin: 12px 0; }
@media (max-width: 650px) { .encabezado, .paginacion { flex-wrap: wrap; } }
</style>
