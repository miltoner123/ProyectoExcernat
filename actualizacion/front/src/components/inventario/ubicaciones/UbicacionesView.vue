
<template>
  <div class="ubicaciones">
    <div class="encabezado">
      <div>
        <h1>Gestión de Ubicaciones</h1>
        <p>Administración de almacenes, sucursales y stands.</p>
      </div>
      <button @click="$router.push('/ubicaciones/crear')">
        + Nueva Ubicación
      </button>
    </div>

    <div class="filtros">
      <input
        v-model="buscar"
        type="text"
        placeholder="Buscar por nombre, departamento o dirección..."
        @input="buscarUbicaciones"
      >

      <select v-model="tipo" @change="filtrar">
        <option value="">Todos los tipos</option>
        <option value="ALMACEN">Almacenes</option>
        <option value="SUCURSAL">Sucursales</option>
        <option value="STAND">Stands</option>
      </select>

      <select v-model="estado" @change="filtrar">
        <option value="">Todos los estados</option>
        <option value="true">Activos</option>
        <option value="false">Inactivos</option>
      </select>

      <select v-model.number="paginacion.per_page" @change="filtrar">
        <option :value="10">10 registros</option>
        <option :value="25">25 registros</option>
        <option :value="50">50 registros</option>
      </select>
    </div>

    <div class="tabla-contenedor">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Propiedad</th>
            <th>Departamento</th>
            <th>Dirección</th>
            <th>Evento</th>
            <th>Estado</th>
            <th>Opciones</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="cargando">
            <td colspan="9" class="mensaje">Cargando ubicaciones...</td>
          </tr>

          <template v-else>
            <tr v-for="u in ubicaciones" :key="u.id_ubicacion">
              <td>{{ u.id_ubicacion }}</td>
              <td>{{ u.nombre }}</td>
              <td>{{ u.tipo }}</td>
              <td>{{ u.propiedad }}</td>
              <td>{{ u.departamento }}</td>
              <td>{{ u.direccion || '—' }}</td>
              <td>{{ u.evento?.nombre || '—' }}</td>
              <td>
                <span :class="u.estado ? 'activo' : 'inactivo'">
                  {{ u.estado ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <div class="acciones">
                  <button @click="$router.push(`/ubicaciones/editar/${u.id_ubicacion}`)">
                    Editar
                  </button>
                  <button class="eliminar" @click="eliminarUbicacion(u.id_ubicacion)">
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!ubicaciones.length">
              <td colspan="9" class="mensaje">
                No se encontraron ubicaciones.
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="paginacion">
      <span>Total: {{ paginacion.total }} registros</span>

      <div class="controles">
        <button
          :disabled="cargando || paginacion.current_page <= 1"
          @click="cambiarPagina(-1)"
        >
          Anterior
        </button>

        <span>
          Página {{ paginacion.current_page }} de {{ paginacion.last_page }}
        </span>

        <button
          :disabled="cargando || paginacion.current_page >= paginacion.last_page"
          @click="cambiarPagina(1)"
        >
          Siguiente
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../../services/api';

const API = 'http://localhost:8000/api/ubicaciones';

export default {
  data() {
    return {
      ubicaciones: [],
      buscar: '',
      tipo: '',
      estado: '',
      cargando: false,
      temporizador: null,
      numeroPeticion: 0,
      paginacion: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0
      }
    };
  },

  mounted() {
    this.obtenerUbicaciones();
  },

  beforeUnmount() {
    clearTimeout(this.temporizador);
    this.numeroPeticion++;
  },

  methods: {
    async obtenerUbicaciones() {
      const peticion = ++this.numeroPeticion;
      this.cargando = true;

      const params = {
        page: this.paginacion.current_page,
        limit: this.paginacion.per_page
      };

      if (this.buscar.trim()) params.buscar = this.buscar.trim();
      if (this.tipo) params.tipo = this.tipo;
      if (this.estado !== '') params.estado = this.estado;

      try {
        const { data } = await api.get(API, { params });

        if (peticion !== this.numeroPeticion) return;

        console.log('Ubicaciones recibidas:', data);

        // Laravel paginate() devuelve registros dentro de "data".
        const registros = Array.isArray(data)
          ? data
          : Array.isArray(data.data)
            ? data.data
            : Array.isArray(data.ubicaciones?.data)
              ? data.ubicaciones.data
              : [];

        this.ubicaciones = registros;

        const pagina = data.ubicaciones ?? data;

        this.paginacion.current_page = pagina.current_page ?? 1;
        this.paginacion.last_page = pagina.last_page ?? 1;
        this.paginacion.total = pagina.total ?? registros.length;

      } catch (error) {
        if (peticion !== this.numeroPeticion) return;

        console.error('Error al obtener ubicaciones:', error.response?.data || error);

        this.ubicaciones = [];
        this.paginacion.total = 0;

        alert(
          error.response?.data?.message ||
          'No se pudieron cargar las ubicaciones.'
        );
      } finally {
        if (peticion === this.numeroPeticion) {
          this.cargando = false;
        }
      }
    },

    buscarUbicaciones() {
      clearTimeout(this.temporizador);

      this.temporizador = setTimeout(() => {
        this.filtrar();
      }, 400);
    },

    filtrar() {
      this.paginacion.current_page = 1;
      this.obtenerUbicaciones();
    },

    cambiarPagina(direccion) {
      const nueva = this.paginacion.current_page + direccion;

      if (nueva < 1 || nueva > this.paginacion.last_page) return;

      this.paginacion.current_page = nueva;
      this.obtenerUbicaciones();
    },

    async eliminarUbicacion(id) {
      if (!confirm('¿Está seguro de eliminar esta ubicación?')) return;

      try {
        const { data } = await api.delete(`${API}/${id}`);

        alert(data.message || 'Ubicación eliminada correctamente.');

        if (this.ubicaciones.length === 1 &&
            this.paginacion.current_page > 1) {
          this.paginacion.current_page--;
        }

        await this.obtenerUbicaciones();
      } catch (error) {
        alert(
          error.response?.data?.message ||
          'No se pudo eliminar la ubicación.'
        );
      }
    }
  }
};
</script>

<style scoped>
.encabezado, .paginacion {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.encabezado p { color: #777; margin-top: 5px; }

.filtros {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.filtros input, .filtros select {
  flex: 1;
  min-width: 140px;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.filtros input { flex: 2; min-width: 220px; }

.tabla-contenedor { width: 100%; overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

th, td {
  padding: 12px;
  border-bottom: 1px solid #e5e5e5;
  text-align: left;
}

.mensaje { text-align: center; color: #777; }

.acciones, .controles {
  display: flex;
  align-items: center;
  gap: 8px;
}

.activo { color: #15803d; font-weight: 600; }
.inactivo { color: #b91c1c; font-weight: 600; }

.eliminar { background: #dc3545; color: white; }

.paginacion { margin-top: 20px; }

button:disabled { opacity: .5; cursor: not-allowed; }
</style>
