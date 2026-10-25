<template>
  <main class="usuarios">
    <header class="encabezado">
      <div>
        <h1>Gestión de usuarios</h1>
        <p>Administración de cuentas y accesos de EXCERNAT</p>
      </div>
      <button @click="abrirCrear">+ Nuevo usuario</button>
    </header>

    <section class="panel">
      <div class="herramientas">
        <input
          v-model="buscar"
          placeholder="Buscar por nombre, documento o correo"
          @keyup.enter="cargarUsuarios"
        />
        <button @click="cargarUsuarios">Buscar</button>
      </div>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="mensaje" class="exito">{{ mensaje }}</p>

      <div class="tabla-contenedor">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Persona</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Opciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuarios" :key="u.id">
              <td>{{ u.id }}</td>
              <td>
                {{ u.persona?.nombres_razon_social || u.name }}
                {{ u.persona?.apellidos || '' }}
              </td>
              <td>{{ u.email }}</td>
              <td>{{ u.roles?.map(r => r.name).join(', ') || 'Sin rol' }}</td>
              <td>
                <span :class="u.estado ? 'activo' : 'inactivo'">
                  {{ u.estado ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <button class="secundario" @click="abrirEditar(u)">
                  Editar
                </button>
              </td>
            </tr>
            <tr v-if="!usuarios.length">
              <td colspan="6">No se encontraron usuarios.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="paginacion">
        <button
          class="secundario"
          :disabled="pagina <= 1"
          @click="pagina--; cargarUsuarios()"
        >
          Anterior
        </button>
        <span>Página {{ pagina }} de {{ ultimaPagina }}</span>
        <button
          class="secundario"
          :disabled="pagina >= ultimaPagina"
          @click="pagina++; cargarUsuarios()"
        >
          Siguiente
        </button>
      </div>
    </section>

    <section v-if="mostrarFormulario" class="panel formulario">
      <h2>{{ editando ? 'Editar usuario' : 'Registrar usuario' }}</h2>

      <form @submit.prevent="guardar">
        <template v-if="!editando">
          <label>Buscar persona registrada</label>
          <input
            v-model="buscarPersona"
            placeholder="Nombre o documento"
            @input="cargarPersonas"
          />

          <label>Persona</label>
          <select v-model="form.id_persona" required>
            <option value="">Seleccione una persona</option>
            <option
              v-for="p in personas"
              :key="p.id_persona"
              :value="p.id_persona"
            >
              {{ p.nombres_razon_social }}
              {{ p.apellidos || '' }}
              — {{ p.numero_documento || 'Sin documento' }}
            </option>
          </select>
        </template>

        <label>Correo electrónico</label>
        <input v-model="form.email" type="email" required />

        <label>Rol</label>
        <select v-model="form.rol" required>
          <option value="">Seleccione un rol</option>
          <option v-for="r in roles" :key="r.id" :value="r.name">
            {{ r.name.replaceAll('_', ' ') }}
          </option>
        </select>

        <label>
          {{ editando ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
        </label>
        <input
          v-model="form.password"
          type="password"
          minlength="12"
          :required="!editando"
          autocomplete="new-password"
        />

        <label>Confirmar contraseña</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          :required="!editando"
          autocomplete="new-password"
        />

        <template v-if="editando">
          <label>Estado de la cuenta</label>
          <select v-model="form.estado">
            <option :value="true">Activo</option>
            <option :value="false">Inactivo</option>
          </select>
        </template>

        <p v-if="errorFormulario" class="error">
          {{ errorFormulario }}
        </p>

        <div class="acciones">
          <button type="submit" :disabled="guardando">
            {{ guardando ? 'Guardando...' : 'Guardar usuario' }}
          </button>
          <button
            type="button"
            class="secundario"
            @click="mostrarFormulario = false"
          >
            Cancelar
          </button>
        </div>
      </form>
    </section>
  </main>
</template>

<script>
import api from '../../services/api';

export default {
  name: 'UsuariosView',

  data() {
    return {
      usuarios: [],
      roles: [],
      personas: [],
      buscar: '',
      buscarPersona: '',
      pagina: 1,
      ultimaPagina: 1,
      mostrarFormulario: false,
      editando: null,
      guardando: false,
      error: '',
      errorFormulario: '',
      mensaje: '',
      form: this.formularioVacio()
    };
  },

  mounted() {
    this.cargarUsuarios();
    this.cargarRoles();
  },

  methods: {
    formularioVacio() {
      return {
        id_persona: '',
        email: '',
        password: '',
        password_confirmation: '',
        rol: '',
        estado: true
      };
    },

    mensajeError(e) {
      const errores = e.response?.data?.errors;
      if (errores) {
        return Object.values(errores).flat().join(' ');
      }
      return e.response?.data?.message || 'Ocurrió un error.';
    },

    async cargarUsuarios() {
      this.error = '';
      try {
        const { data } = await api.get('/api/usuarios', {
          params: {
            buscar: this.buscar,
            page: this.pagina,
            limit: 10
          }
        });
        this.usuarios = data.data || [];
        this.ultimaPagina = data.last_page || 1;
      } catch (e) {
        this.error = this.mensajeError(e);
      }
    },

    async cargarRoles() {
      try {
        const { data } = await api.get('/api/usuarios/roles');
        this.roles = data;
      } catch (e) {
        this.error = this.mensajeError(e);
      }
    },

    async cargarPersonas() {
      try {
        const { data } = await api.get(
          '/api/usuarios/personas-disponibles',
          { params: { buscar: this.buscarPersona } }
        );
        this.personas = data;
      } catch (e) {
        this.errorFormulario = this.mensajeError(e);
      }
    },

    abrirCrear() {
      this.editando = null;
      this.form = this.formularioVacio();
      this.errorFormulario = '';
      this.mostrarFormulario = true;
      this.cargarPersonas();
    },

    abrirEditar(usuario) {
      this.editando = usuario.id;
      this.form = {
        id_persona: usuario.id_persona,
        email: usuario.email,
        password: '',
        password_confirmation: '',
        rol: usuario.roles?.[0]?.name || '',
        estado: Boolean(usuario.estado)
      };
      this.errorFormulario = '';
      this.mostrarFormulario = true;
    },

    async guardar() {
      this.guardando = true;
      this.errorFormulario = '';
      this.mensaje = '';

      try {
        const datos = { ...this.form };

        if (this.editando && !datos.password) {
          delete datos.password;
          delete datos.password_confirmation;
        }

        if (this.editando) {
          await api.put(`/api/usuarios/${this.editando}`, datos);
          this.mensaje = 'Usuario actualizado correctamente.';
        } else {
          await api.post('/api/usuarios', datos);
          this.mensaje = 'Usuario registrado correctamente.';
        }

        this.mostrarFormulario = false;
        await this.cargarUsuarios();
      } catch (e) {
        this.errorFormulario = this.mensajeError(e);
      } finally {
        this.guardando = false;
      }
    }
  }
};
</script>

<style scoped>
.usuarios {
  padding: 24px;
  color: #1e293b;
}
.encabezado, .herramientas, .acciones, .paginacion {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.encabezado { margin-bottom: 20px; }
h1 { font-size: 25px; margin: 0 0 5px; }
h2 { font-size: 19px; }
p { color: #64748b; }
.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}
input, select {
  padding: 11px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  font: inherit;
  background: #fff;
}
input:focus, select:focus {
  outline: none;
  border-color: #2563eb;
}
.herramientas input { flex: 1; min-width: 220px; }
button {
  border: none;
  border-radius: 7px;
  padding: 11px 16px;
  color: white;
  background: #2563eb;
  cursor: pointer;
}
button:disabled { opacity: .5; cursor: not-allowed; }
button.secundario {
  color: #334155;
  background: #e2e8f0;
}
.tabla-contenedor { overflow-x: auto; margin-top: 16px; }
table { width: 100%; border-collapse: collapse; }
th, td {
  padding: 12px;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}
th { background: #f8fafc; font-size: 13px; }
.activo { color: #15803d; font-weight: 600; }
.inactivo { color: #b91c1c; font-weight: 600; }
.formulario { max-width: 650px; }
form { display: grid; gap: 12px; }
label { font-weight: 600; font-size: 14px; }
.error { color: #dc2626; }
.exito { color: #15803d; }
.paginacion { margin-top: 18px; }
</style>
