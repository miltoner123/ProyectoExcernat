<template>
  <div class="formulario exc-modern">
    <div class="encabezado"><h1>Editar Personal</h1><p>Actualice la información laboral del trabajador.</p></div>
    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="cargando">Cargando información...</p>
    <form v-else-if="encontrado" @submit.prevent="actualizarPersonal">
      <div class="seccion">
        <h3>Trabajador</h3>
        <div class="persona-info">
          <strong>{{ [persona.nombres_razon_social, persona.apellidos].filter(Boolean).join(' ') }}</strong>
          <span>Documento: {{ persona.tipo_documento || '—' }} {{ persona.numero_documento || 'Sin documento' }}</span>
          <span>Teléfono: {{ persona.telefono || '—' }}</span>
        </div>
        <small>Para cambiar los datos personales, utilice Gestión de Personas.</small>
      </div>
      <div class="seccion">
        <h3>Fotografía del trabajador</h3>
        <img v-if="fotoVista || fotoActual" :src="fotoVista || fotoActual" class="foto-personal" alt="Fotografía" />
        <input type="file" accept="image/jpeg,image/png,image/webp" @change="seleccionarFoto" />
        <label><input type="checkbox" v-model="eliminarFoto" /> Eliminar fotografía actual</label>
      </div>
      <div class="seccion">
        <h3>Información laboral</h3>
        <div class="fila">
          <div class="campo"><label>Código de empleado:</label><input v-model.trim="trabajador.codigo_empleado" maxlength="50" readonly required /></div>
          <div class="campo"><label>Cargo:</label><select v-model="trabajador.id_cargo" required><option value="" disabled>Seleccione un cargo</option><option v-for="cargo in cargos" :key="cargo.id_cargo" :value="cargo.id_cargo">{{ cargo.nombre }}{{ cargo.estado ? '' : ' (Inactivo)' }}</option></select></div>
        </div>
        <div class="fila">
          <div class="campo"><label>Fecha de ingreso:</label><input v-model="trabajador.fecha_ingreso" type="date" required /></div>
          <div class="campo"><label>Tipo de contrato:</label><select v-model="trabajador.tipo_contrato"><option value="">Sin especificar</option><option value="INDEFINIDO">Indefinido</option><option value="PLAZO_FIJO">Plazo fijo</option><option value="EVENTUAL">Eventual</option><option value="SERVICIOS">Servicios</option></select></div>
        </div>
        <div class="fila">
          <div class="campo"><label>Salario base (Bs):</label><input v-model.number="trabajador.salario_base" type="number" min="0" step="0.01" required /></div>
          <div class="campo"><label>Estado:</label><select v-model="trabajador.estado"><option :value="true">Activo</option><option :value="false">Inactivo</option></select></div>
        </div>
      </div>
      <div class="botones"><button type="submit" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Actualizar Personal' }}</button><button type="button" class="btn-cancelar" @click="$router.push('/personal')">Cancelar</button></div>
    </form>
  </div>
</template>

<script>
import api from '../../../services/api';

export default {
  name: 'PersonalEditar',
  data() {
    return {
      trabajador: { id_persona: null, id_cargo: '', codigo_empleado: '', fecha_ingreso: '', tipo_contrato: '', salario_base: 0, estado: true },
      persona: {}, fotoActual: null, fotoVista: null, fotoArchivo: null, eliminarFoto: false, cargos: [], cargando: true, encontrado: false, guardando: false, error: ''
    };
  },
  mounted() { this.cargarDatos(); },
  methods: {
    seleccionarFoto(event) {
      const f = event.target.files?.[0] || null;
      if (f && (!['image/jpeg','image/png','image/webp'].includes(f.type) || f.size > 2*1024*1024)) {
        alert('Imagen JPG, PNG o WEBP de máximo 2 MB.'); event.target.value = ''; return;
      }
      this.fotoArchivo = f;
      this.fotoVista = f ? URL.createObjectURL(f) : null;
      if (f) this.eliminarFoto = false;
    },
    async cargarDatos() {
      this.cargando = true;
      try {
        const { data: p } = await api.get(`/api/personal/${this.$route.params.id}`);
        this.persona = p.persona || {};
        this.fotoActual = p.foto_url || null;
        this.trabajador = {
          id_persona: p.id_persona,
          id_cargo: p.id_cargo,
          codigo_empleado: p.codigo_empleado || '',
          fecha_ingreso: String(p.fecha_ingreso || '').slice(0, 10),
          tipo_contrato: p.tipo_contrato || '',
          salario_base: Number(p.salario_base ?? 0),
          estado: Boolean(p.estado)
        };
        const todos = [];
        let pagina = 1, ultima = 1;
        do {
          const { data } = await api.get('/api/cargos', { params: { page: pagina, limit: 50 } });
          todos.push(...(data.data || []));
          ultima = data.last_page || 1;
          pagina++;
        } while (pagina <= ultima);
        this.cargos = todos.filter(c => c.estado || Number(c.id_cargo) === Number(p.id_cargo));
        this.encontrado = true;
      } catch (e) {
        this.error = e.response?.data?.message || 'No fue posible cargar los datos del trabajador.';
      } finally { this.cargando = false; }
    },
    async actualizarPersonal() {
      if (this.guardando) return;
      this.guardando = true;
      this.error = '';
      try {
        const datos = { ...this.trabajador, tipo_contrato: this.trabajador.tipo_contrato || null };
        const formulario = new FormData();
        Object.entries(datos).forEach(([clave, valor]) => {
          if (valor !== null && valor !== undefined && valor !== '') formulario.append(clave, typeof valor === 'boolean' ? (valor ? '1' : '0') : valor);
        });
        if (this.fotoArchivo) formulario.append('foto', this.fotoArchivo);
        formulario.append('eliminar_foto', this.eliminarFoto ? '1' : '0');
        formulario.append('_method', 'PUT');
        await api.post(`/api/personal/${this.$route.params.id}`, formulario);
        alert('Personal actualizado correctamente.');
        this.$router.push('/personal');
      } catch (e) {
        const errores = e.response?.data?.errors;
        this.error = errores ? Object.values(errores).flat().join('\n') : (e.response?.data?.message || 'No se pudo actualizar el personal.');
      } finally { this.guardando = false; }
    }
  }
};
</script>

<style scoped>
.formulario { max-width: 900px; margin: 0 auto; }
.encabezado { margin-bottom: 25px; }
.encabezado h1 { margin: 0 0 5px; color: #1f2937; }
.encabezado p, small { color: #6b7280; }
.seccion { background: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; }
.seccion h3 { margin-top: 0; margin-bottom: 20px; color: #1f2937; }
.fila { display: flex; gap: 20px; }
.campo { display: flex; flex: 1; flex-direction: column; margin-bottom: 18px; }
.campo label { margin-bottom: 7px; font-weight: 600; color: #374151; }
.campo input, .campo select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
.persona-info { display: flex; flex-direction: column; gap: 5px; background: #f9fafb; border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px; margin-bottom: 10px; }
.botones { display: flex; gap: 10px; }
.botones button { padding: 11px 18px; border: 0; border-radius: 6px; background: #2563eb; color: white; cursor: pointer; }
.botones .btn-cancelar { background: #6b7280; }
.botones button:disabled { opacity: .6; cursor: not-allowed; }
.error { color: #991b1b; background: #fee2e2; padding: 12px; border-radius: 6px; white-space: pre-line; }
@media (max-width: 700px) { .fila { flex-direction: column; gap: 0; } }
</style>

<style scoped>
.foto-personal{display:block;width:120px;height:120px;object-fit:cover;border-radius:12px;margin:10px 0;border:2px solid #124734;}
</style>
