<template>
  <div class="cargos">
    <div class="encabezado">
      <div><h1>Gestión de Cargos</h1><p>Administración de los cargos del personal de EXCERNAT.</p></div>
      <button @click="crearCargo">+ Nuevo Cargo</button>
    </div>

    <div class="filtros">
      <input v-model="buscar" type="text" placeholder="Buscar por nombre o descripción..." @input="buscarCargos">
      <select v-model="estado" @change="filtrar">
        <option value="">Todos los estados</option>
        <option value="true">Activos</option>
        <option value="false">Inactivos</option>
      </select>
      <select v-model.number="pagination.per_page" @change="filtrar">
        <option :value="10">10 registros</option>
        <option :value="25">25 registros</option>
        <option :value="50">50 registros</option>
      </select>
    </div>

    <p v-if="error" class="mensaje-error" role="alert">{{ error }}</p>
    <p v-if="mensaje" class="mensaje-exito" role="status">{{ mensaje }}</p>

    <div class="tabla-contenedor">
      <table>
        <thead><tr><th>ID</th><th>Cargo</th><th>Descripción</th><th>Estado</th><th>Opciones</th></tr></thead>
        <tbody>
          <tr v-for="cargo in cargos" :key="cargo.id_cargo">
            <td>{{ cargo.id_cargo }}</td>
            <td><strong>{{ cargo.nombre }}</strong></td>
            <td>{{ cargo.descripcion || '—' }}</td>
            <td><span :class="esActivo(cargo.estado) ? 'estado-activo' : 'estado-inactivo'">{{ esActivo(cargo.estado) ? 'Activo' : 'Inactivo' }}</span></td>
            <td><div class="acciones">
              <button @click="editarCargo(cargo.id_cargo)">Editar</button>
              <button class="btn-eliminar" :disabled="eliminando === cargo.id_cargo" @click="eliminarCargo(cargo)">{{ eliminando === cargo.id_cargo ? 'Eliminando...' : 'Eliminar' }}</button>
            </div></td>
          </tr>
          <tr v-if="cargando"><td colspan="5" class="sin-registros">Cargando cargos...</td></tr>
          <tr v-else-if="!error && cargos.length === 0"><td colspan="5" class="sin-registros">No se encontraron cargos.</td></tr>
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
import api from '../../../services/api';

export default {
  data() {
    return {
      cargos: [], buscar: '', estado: '', error: '', mensaje: '',
      cargando: false, eliminando: null, temporizadorBusqueda: null, numeroPeticion: 0,
      pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 }
    };
  },
  mounted() { this.cargosGet(); },
  beforeUnmount() { clearTimeout(this.temporizadorBusqueda); this.numeroPeticion++; },
  methods: {
    esActivo(valor) { return valor === true || valor === 1 || valor === '1' || valor === 'true'; },
    async cargosGet() {
      const peticion = ++this.numeroPeticion;
      this.cargando = true;
      this.error = '';
      try {
        const { data } = await api.get('/api/cargos', {
          params: {
            page: this.pagination.current_page, limit: this.pagination.per_page,
            buscar: this.buscar, estado: this.estado
          }
        });
        if (peticion !== this.numeroPeticion) return;
        this.cargos = Array.isArray(data.data) ? data.data : [];
        this.pagination.current_page = data.current_page || 1;
        this.pagination.last_page = data.last_page || 1;
        this.pagination.total = data.total || 0;
      } catch (error) {
        if (peticion !== this.numeroPeticion) return;
        this.cargos = [];
        this.error = error.response?.data?.message || 'No se pudieron cargar los cargos.';
        console.error('Error al obtener cargos:', error);
      } finally {
        if (peticion === this.numeroPeticion) this.cargando = false;
      }
    },
    buscarCargos() {
      clearTimeout(this.temporizadorBusqueda);
      this.temporizadorBusqueda = setTimeout(() => this.filtrar(), 400);
    },
    filtrar() { this.pagination.current_page = 1; this.mensaje = ''; this.cargosGet(); },
    cambiarPagina(direccion) {
      const pagina = this.pagination.current_page + direccion;
      if (pagina < 1 || pagina > this.pagination.last_page) return;
      this.pagination.current_page = pagina;
      this.cargosGet();
    },
    crearCargo() { this.$router.push('/cargos/crear'); },
    editarCargo(id) { this.$router.push(`/cargos/editar/${id}`); },
    async eliminarCargo(cargo) {
      if (this.eliminando !== null || !confirm(`¿Está seguro de eliminar el cargo "${cargo.nombre}"?`)) return;
      this.error = ''; this.mensaje = ''; this.eliminando = cargo.id_cargo;
      try {
        const { data } = await api.delete(`/api/cargos/${cargo.id_cargo}`);
        this.mensaje = data.message || 'Cargo eliminado correctamente.';
        if (this.cargos.length === 1 && this.pagination.current_page > 1) this.pagination.current_page--;
        await this.cargosGet();
      } catch (error) {
        this.error = error.response?.data?.message || 'No se pudo eliminar el cargo.';
      } finally { this.eliminando = null; }
    }
  }
};
</script>

<style scoped>

.cargos {
    width: 100%;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.encabezado h1 {
    margin: 0 0 5px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
}

.encabezado button {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
}

.filtros {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filtros input,
.filtros select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.filtros input {
    min-width: 300px;
    flex: 1;
}

.tabla-contenedor {
    background-color: white;
    border-radius: 8px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}

th {
    background-color: #f9fafb;
    color: #374151;
}

.estado-activo {
    background-color: #dcfce7;
    color: #166534;
    padding: 5px 8px;
    border-radius: 5px;
}

.estado-inactivo {
    background-color: #fee2e2;
    color: #991b1b;
    padding: 5px 8px;
    border-radius: 5px;
}

.acciones {
    display: flex;
    gap: 5px;
}

.acciones button {
    border: none;
    padding: 7px 10px;
    border-radius: 5px;
    cursor: pointer;
    background-color: #2563eb;
    color: white;
}

.acciones .btn-eliminar {
    background-color: #dc2626;
}

.sin-registros {
    text-align: center;
    padding: 25px;
    color: #6b7280;
}

.paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.controles {
    display: flex;
    align-items: center;
    gap: 10px;
}

.controles button {
    padding: 8px 12px;
    cursor: pointer;
}

.controles button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.mensaje-error, .mensaje-exito { padding: 10px 12px; border-radius: 6px; margin: 0 0 14px; }
.mensaje-error { color: #991b1b; background: #fee2e2; }
.mensaje-exito { color: #166534; background: #dcfce7; }
.acciones button:disabled { opacity: .6; cursor: not-allowed; }
</style>