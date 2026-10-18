<template>
  <div class="login-container">
    <form class="login-card" @submit.prevent="iniciarSesion">
      <h2>Iniciar sesión</h2>
      <p>Acceso al sistema EXCERNAT</p>

      <label>Correo electrónico</label>
      <input
        v-model="email"
        type="email"
        placeholder="Ingrese su correo"
        required
      />

      <label>Contraseña</label>
      <input
        v-model="password"
        type="password"
        placeholder="Ingrese su contraseña"
        required
      />

      <p v-if="error" class="error">{{ error }}</p>

      <button type="submit" :disabled="cargando">
        {{ cargando ? 'Ingresando...' : 'Ingresar' }}
      </button>
    </form>
  </div>
</template>

<script>
import api from '../../services/api';
import { establecerUsuario } from '../../services/sesion';

export default {
  name: 'LoginView',

  data() {
    return {
      email: '',
      password: '',
      error: '',
      cargando: false
    };
  },

  methods: {
    async iniciarSesion() {
      this.error = '';
      this.cargando = true;

      try {
        // Obtener cookie de protección CSRF
        await api.get('/sanctum/csrf-cookie');

        // Enviar credenciales a Laravel
        const respuesta = await api.post('/api/login', {
          email: this.email,
          password: this.password
        });

        establecerUsuario(respuesta.data.usuario);
        const destino = this.$route.query.redirect;
        this.$router.replace(typeof destino === 'string' && destino.startsWith('/') && !destino.startsWith('//') ? destino : '/');
      } catch (error) {
        this.error =
          error.response?.data?.errors?.email?.[0] ||
          error.response?.data?.message ||
          'No se pudo iniciar sesión.';
      } finally {
        this.cargando = false;
      }
    }
  }
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f1f5f9;
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 400px;
  padding: 32px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,.08);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.login-card h2 {
  color: #1e293b;
  text-align: center;
  margin: 0;
}

.login-card p {
  color: #64748b;
  text-align: center;
}

.login-card label {
  font-weight: 600;
  color: #334155;
}

.login-card input {
  padding: 12px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  font-size: 14px;
}

.login-card input:focus {
  outline: none;
  border-color: #2563eb;
}

.login-card button {
  padding: 12px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 7px;
  cursor: pointer;
}

.login-card button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.error {
  color: #dc2626 !important;
  font-size: 13px;
}
</style>
