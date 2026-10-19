
<template>
  <div class="personas">
    <div class="encabezado">
      <div>
        <h1>Gestión de Personas</h1>
        <p>Administración de personas registradas en el sistema.</p>
      </div>
      <button class="btn-primario" @click="$router.push('/personas/crear')">
        + Nueva Persona
      </button>
    </div>

    <div class="filtros">
      <input
        v-model="buscar"
        type="text"
        placeholder="Buscar por nombre, apellido o documento..."
        @input="buscarPersonas"
      >

      <select v-model="tipo_persona" @change="filtrar">
        <option value="">Todos los tipos</option>
        <option value="PERSONAL">Personal</option>
        <option value="CLIENTE">Cliente</option>
        <option value="OTRO">Otro</option>
      </select>

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

    <p v-if="error" class="mensaje-error">{{ error }}</p>

    <div class="tabla-contenedor">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Documento</th>
            <th>Nombre / Razón Social</th>
            <th>Apellidos</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Opciones</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="cargando">
            <td colspan="9" class="sin-registros">Cargando personas...</td>
          </tr>

          <tr v-for="persona in (cargando ? [] : personas)" :key="persona.id_persona">
            <td>{{ persona.id_persona }}</td>
            <td>
              {{ persona.numero_documento
                ? `${persona.tipo_documento || ''} ${persona.numero_documento}`
                : 'Sin documento' }}
            </td>
            <td><strong>{{ persona.nombres_razon_social }}</strong></td>
            <td>{{ persona.apellidos || '—' }}</td>
            <td>{{ persona.telefono || '—' }}</td>
            <td>{{ persona.correo || '—' }}</td>
            <td>{{ persona.tipo_persona }}</td>
            <td>
              <span :class="persona.estado ? 'estado-activo' : 'estado-inactivo'">
                {{ persona.estado ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td>
              <div class="acciones">
                <button @click="$router.push(`/personas/editar/${persona.id_persona}`)">
                  Editar
                </button>
                <button class="btn-eliminar" @click="eliminarPersona(persona)">
                  Eliminar
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="!cargando && !error && !personas.length">
            <td colspan="9" class="sin-registros">
              No se encontraron personas.
            </td>
          </tr>

          <tr v-if="!cargando && error">
            <td colspan="9" class="sin-registros">
              No fue posible cargar los registros.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="paginacion">
      <span>Total: {{ pagination.total }} registros</span>

      <div class="controles">
        <button
          :disabled="cargando || pagination.current_page <= 1"
          @click="cambiarPagina(-1)"
        >
          Anterior
        </button>

        <span>
          Página {{ pagination.current_page }} de {{ pagination.last_page }}
        </span>

        <button
          :disabled="cargando || pagination.current_page >= pagination.last_page"
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

export default {
  name: 'PersonasView',

  data() {
    return {
      personas: [],
      buscar: '',
      tipo_persona: '',
      estado: '',
      cargando: false,
      error: '',
      temporizador: null,
      solicitudActual: 0,
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0
      }
    };
  },

  mounted() {
    this.personasGet();
  },

  beforeUnmount() {
    clearTimeout(this.temporizador);
    this.solicitudActual++;
  },

  methods: {
    async personasGet() {
      const solicitud = ++this.solicitudActual;
      this.cargando = true;
      this.error = '';

      try {
        const { data } = await api.get('/api/personas', {
          params: {
            page: this.pagination.current_page,
            limit: this.pagination.per_page,
            buscar: this.buscar.trim(),
            tipo_persona: this.tipo_persona,
            estado: this.estado
          }
        });

        if (solicitud !== this.solicitudActual) return;

        // Compatible con paginación Laravel y respuestas envueltas.
        const respuesta = data?.data?.data
          ? data.data
          : data?.data && !Array.isArray(data.data) && data.data.personas
            ? data.data
            : data;

        const registros = Array.isArray(respuesta)
          ? respuesta
          : respuesta?.data ?? respuesta?.personas;

        if (!Array.isArray(registros)) {
          throw new Error('Formato de respuesta de personas no reconocido.');
        }

        this.personas = registros;
        this.pagination.current_page = Number(respuesta.current_page ?? 1);
        this.pagination.last_page = Number(respuesta.last_page ?? 1);
        this.pagination.total = Number(respuesta.total ?? registros.length);

      } catch (error) {
        if (solicitud !== this.solicitudActual) return;

        console.error('Error al obtener personas:', error);
        this.personas = [];
        this.pagination.total = 0;
        this.pagination.last_page = 1;

        this.error = error.response?.data?.message
          || error.message
          || 'No se pudieron cargar las personas.';

      } finally {
        if (solicitud === this.solicitudActual) {
          this.cargando = false;
        }
      }
    },

    buscarPersonas() {
      clearTimeout(this.temporizador);
      this.temporizador = setTimeout(() => this.filtrar(), 400);
    },

    filtrar() {
      this.pagination.current_page = 1;
      this.personasGet();
    },

    cambiarPagina(direccion) {
      const nueva = this.pagination.current_page + direccion;
      if (nueva < 1 || nueva > this.pagination.last_page) return;
      this.pagination.current_page = nueva;
      this.personasGet();
    },

    async eliminarPersona(persona) {
      if (!confirm(`¿Eliminar a "${persona.nombres_razon_social}"?`)) return;

      try {
        const { data } = await api.delete(`/api/personas/${persona.id_persona}`);
        alert(data.message || 'Persona eliminada correctamente.');

        await this.personasGet();

        if (!this.personas.length && this.pagination.current_page > 1) {
          this.pagination.current_page--;
          await this.personasGet();
        }
      } catch (error) {
        console.error('Error al eliminar persona:', error);
        alert(error.response?.data?.message || 'No se pudo eliminar la persona.');
      }
    }
  }
};
</script>

<style scoped>
.personas { width: 100%; }

.encabezado, .paginacion, .controles, .acciones {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.encabezado { margin-bottom: 24px; }
.encabezado h1 { margin: 0 0 5px; color: #1f2937; }
.encabezado p { margin: 0; color: #6b7280; }

button {
  cursor: pointer;
  border: 0;
  border-radius: 6px;
  padding: 9px 13px;
}

.btn-primario, .acciones button {
  background: #2563eb;
  color: white;
}

.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.filtros input, .filtros select {
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
}

.filtros input { flex: 1; min-width: 260px; }

.tabla-contenedor {
  background: white;
  border-radius: 8px;
  overflow-x: auto;
}

table { width: 100%; border-collapse: collapse; }

th, td {
  padding: 12px;
  text-align: left;
  font-size: 14px;
  border-bottom: 1px solid #e5e7eb;
}

th { background: #f9fafb; color: #374151; }
.acciones { justify-content: flex-start; gap: 6px; }
.acciones .btn-eliminar { background: #dc2626; }

.estado-activo, .estado-inactivo {
  display: inline-block;
  padding: 5px 8px;
  border-radius: 5px;
}

.estado-activo { background: #dcfce7; color: #166534; }
.estado-inactivo { background: #fee2e2; color: #991b1b; }

.sin-registros {
  text-align: center;
  color: #6b7280;
  padding: 25px;
}

.paginacion { margin-top: 20px; }
.controles button { border: 1px solid #d1d5db; background: white; }
button:disabled { opacity: .5; cursor: not-allowed; }

.mensaje-error {
  padding: 12px;
  margin: 12px 0;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #b91c1c;
  background: #fef2f2;
}

@media (max-width: 650px) {
  .encabezado, .paginacion { align-items: flex-start; flex-direction: column; }
  .filtros input, .filtros select { width: 100%; }
}
</style>
