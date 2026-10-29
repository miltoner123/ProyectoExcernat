<template>
  <main class="exc-login">
    <div class="exc-login__background" aria-hidden="true"></div>
    <section class="exc-login__shell" aria-label="Acceso a EXCERNAT">
      <div class="exc-login__welcome">
        <span class="exc-login__eyebrow">EXCELENTES CEREALES NATURALES</span>
        <div class="exc-login__welcome-content">
          <span class="exc-login__mark">EXCERNAT</span>
          <h1>Lo natural que<br /><em>alimenta tu vida</em></h1>
          <p>Sistema integral de gestión de producción, almacenes, inventarios y ventas.</p>
          <span class="exc-login__rule"></span>
          <small>Gestión empresarial con trazabilidad y control.</small>
        </div>
      </div>

      <form class="exc-login__card" @submit.prevent="iniciarSesion">
        <div class="exc-login__logo-wrap">
          <img class="exc-login__logo" src="/excernat-logo.webp" alt="Logotipo de Excelentes Cereales Naturales" />
        </div>
        <div class="exc-login__heading">
          <span class="exc-login__caption">BIENVENIDO A EXCERNAT</span>
          <h2>Iniciar sesión</h2>
          <p>Ingresa tus credenciales para continuar.</p>
        </div>

        <label for="exc-email">Correo electrónico</label>
        <input id="exc-email" v-model.trim="email" type="email" autocomplete="username" placeholder="correo@empresa.com" required :disabled="cargando" />

        <div class="exc-login__password-label">
          <label for="exc-password">Contraseña</label>
        </div>
        <div class="exc-login__password-field">
          <input id="exc-password" v-model="password" :type="mostrarPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="Ingresa tu contraseña" required :disabled="cargando" />
          <button type="button" class="exc-login__toggle" :aria-label="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="mostrarPassword" @click="mostrarPassword = !mostrarPassword">{{ mostrarPassword ? 'Ocultar' : 'Mostrar' }}</button>
        </div>

        <p v-if="error" class="exc-login__error" role="alert">{{ error }}</p>
        <button class="exc-login__submit" type="submit" :disabled="cargando">
          {{ cargando ? 'Ingresando...' : 'Ingresar al sistema' }}
          <span aria-hidden="true">→</span>
        </button>
        <div class="exc-login__footer">© {{ new Date().getFullYear() }} EXCERNAT · Excelentes Cereales Naturales</div>
      </form>
    </section>
  </main>
</template>

<script>
import api from '../../services/api';
import { establecerUsuario } from '../../services/sesion';

export default {
  name: 'LoginView',
  data() {
    return { email: '', password: '', error: '', cargando: false, mostrarPassword: false };
  },
  methods: {
    async iniciarSesion() {
      this.error = '';
      this.cargando = true;
      try {
        await api.get('/sanctum/csrf-cookie');
        const respuesta = await api.post('/api/login', {
          email: this.email,
          password: this.password
        });
        establecerUsuario(respuesta.data.usuario);
        const destino = this.$route.query.redirect;
        this.$router.replace(
          typeof destino === 'string' && destino.startsWith('/') && !destino.startsWith('//')
            ? destino : '/'
        );
      } catch (error) {
        console.error('Error de inicio de sesión:', error);
        this.error = error.response?.data?.errors?.email?.[0]
          || error.response?.data?.message
          || 'No se pudo iniciar sesión.';
      } finally {
        this.cargando = false;
      }
    }
  }
};
</script>

<style scoped>
.exc-login, .exc-login * { box-sizing: border-box; }
.exc-login { --green:#103d2e; --orange:#f47b20; position:relative; isolation:isolate; display:grid; place-items:center; min-height:100vh; min-height:100dvh; padding:32px 20px; font-family:Inter, 'Segoe UI', Arial, sans-serif; color:#173629; overflow:hidden; }
.exc-login__background { position:absolute; z-index:-2; inset:-18px; background:linear-gradient(120deg,rgba(5,36,25,.64),rgba(7,42,31,.37)),url('/trigo-login.jpg') center / cover no-repeat; filter:blur(5px); transform:scale(1.05); }
.exc-login__shell { display:grid; grid-template-columns:minmax(0,1.08fr) minmax(360px,.92fr); width:min(1040px,100%); min-height:590px; overflow:hidden; border-radius:24px; box-shadow:0 25px 75px rgba(0,0,0,.28); background:#fff; }
.exc-login__welcome { display:flex; flex-direction:column; justify-content:space-between; padding:43px 48px; color:white; background:linear-gradient(145deg,rgba(12,55,40,.97),rgba(21,82,59,.88)),url('/trigo-login.jpg') center / cover; }
.exc-login__eyebrow { font-size:11px; font-weight:800; letter-spacing:2px; color:#f4c68b; }
.exc-login__welcome-content { margin:auto 0; }
.exc-login__mark { display:inline-block; border:1px solid rgba(255,255,255,.28); border-radius:100px; padding:8px 14px; font-size:12px; font-weight:800; letter-spacing:2px; }
.exc-login__welcome h1 { margin:28px 0 18px; font-size:clamp(34px,4vw,51px); line-height:1.13; letter-spacing:-1.5px; }
.exc-login__welcome h1 em { color:#ffb76e; font-style:normal; }
.exc-login__welcome p { max-width:370px; color:#e0eee6; line-height:1.7; font-size:15px; }
.exc-login__rule { display:block; width:58px; height:4px; background:var(--orange); border-radius:4px; margin:30px 0 18px; }
.exc-login__welcome small { color:#c4ddd0; }
.exc-login__card { display:flex; flex-direction:column; justify-content:center; padding:36px clamp(25px,4vw,60px); background:#fff; }
.exc-login__logo-wrap { display:flex; align-items:center; justify-content:center; width:116px; height:116px; margin:0 auto 22px; padding:8px; border-radius:50%; background:#f6f9f4; border:1px solid #e4eee6; }
.exc-login__logo { width:100%; height:100%; border-radius:50%; object-fit:contain; }
.exc-login__heading { text-align:center; margin-bottom:28px; }
.exc-login__caption { font-size:11px; font-weight:800; letter-spacing:1.5px; color:#b66825; }
.exc-login__heading h2 { margin:9px 0; font-size:27px; color:#103d2e; }
.exc-login__heading p { color:#718178; font-size:14px; margin:0; }
.exc-login__card label { font-size:13px; font-weight:700; margin:0 0 8px; color:#244535; }
.exc-login__card input { width:100%; min-height:48px; padding:12px 14px; border:1px solid #d5e2d9; border-radius:10px; font-size:14px; color:#163c2b; background:#fbfdfb; outline:none; transition:border-color .2s,box-shadow .2s; }
.exc-login__card input:focus { border-color:#ef8a35; box-shadow:0 0 0 3px rgba(244,123,32,.14); }
.exc-login__card > input { margin-bottom:20px; }
.exc-login__password-field { position:relative; }
.exc-login__password-field input { padding-right:88px; }
.exc-login__toggle { position:absolute; right:9px; top:8px; height:32px; padding:0 9px; border:0; background:transparent; color:#36614b; font-size:12px; font-weight:700; cursor:pointer; }
.exc-login__submit { display:flex; align-items:center; justify-content:center; gap:14px; width:100%; min-height:49px; margin-top:24px; padding:12px; background:var(--orange); color:#fff; border:0; border-radius:10px; font-size:14px; font-weight:800; cursor:pointer; transition:background .2s,transform .2s; }
.exc-login__submit:hover:not(:disabled) { background:#dc6712; transform:translateY(-1px); }
.exc-login__submit:disabled { opacity:.65; cursor:not-allowed; }
.exc-login__error { margin:14px 0 0; padding:11px 13px; color:#a51e25; background:#fff0f0; border:1px solid #f2c6c6; border-radius:9px; font-size:13px; }
.exc-login__footer { margin-top:26px; text-align:center; font-size:11px; color:#89958c; }
@media(max-width:760px) { .exc-login { padding:18px; } .exc-login__shell { grid-template-columns:1fr; max-width:470px; } .exc-login__welcome { padding:24px; } .exc-login__welcome-content { margin:16px 0 0; } .exc-login__welcome h1 { font-size:26px; margin:12px 0; } .exc-login__welcome p,.exc-login__rule,.exc-login__welcome small { display:none; } .exc-login__card { padding:26px 28px 32px; } .exc-login__logo-wrap { width:95px; height:95px; } }
@media(prefers-reduced-motion:reduce) { .exc-login__submit,.exc-login__card input { transition:none; } }
</style>

