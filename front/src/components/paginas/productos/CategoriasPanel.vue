<template>
  <section class="categorias-panel">
    <header class="encabezado"><div><h2>Gestión de Categorías</h2><p>Organiza y clasifica los productos de EXCERNAT.</p></div><button class="primario" @click="mostrarFormulario = !mostrarFormulario">{{ mostrarFormulario ? 'Cancelar' : '+ Nueva categoría' }}</button></header>
    <form v-if="mostrarFormulario" class="formulario" @submit.prevent="guardar">
      <h3>Nueva categoría</h3>
      <div class="campos"><label>Nombre <input v-model.trim="form.nombre" maxlength="255" required placeholder="Nombre de categoría" /></label><label>Descripción <input v-model.trim="form.descripcion" placeholder="Descripción opcional" /></label></div>
      <button class="primario" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Guardar categoría' }}</button>
    </form>
    <p v-if="mensaje" class="mensaje" role="status">{{ mensaje }}</p><p v-if="error" class="error" role="alert">{{ error }}</p>
    <div class="barra"><input v-model="buscar" placeholder="Buscar categorías..." aria-label="Buscar categorías" /><button class="secundario" @click="alternarPapelera">{{ verPapelera ? 'Ver categorías' : 'Ver papelera' }}</button></div>
    <div class="tabla"><table><thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead><tbody>
      <tr v-if="cargando"><td colspan="4">Cargando categorías...</td></tr>
      <tr v-for="cat in filtradas" :key="cat.id_categoria ?? cat.id"><td>#{{ cat.id_categoria ?? cat.id }}</td><td><strong>{{ cat.nombre }}</strong></td><td>{{ cat.descripcion || '—' }}</td><td><button class="accion" :disabled="procesando === (cat.id_categoria ?? cat.id)" @click="verPapelera ? restaurar(cat) : eliminar(cat)">{{ verPapelera ? 'Restaurar' : 'Enviar a papelera' }}</button></td></tr>
      <tr v-if="!cargando && !filtradas.length"><td colspan="4">No se encontraron categorías.</td></tr>
    </tbody></table></div>
  </section>
</template>
<script>
import api from '../../../services/api';
export default {
  data() { return { categorias: [], buscar: '', verPapelera: false, mostrarFormulario: false, form: { nombre: '', descripcion: '' }, cargando: false, guardando: false, procesando: null, error: '', mensaje: '' }; },
  computed: { filtradas() { const q = this.buscar.toLocaleLowerCase().trim(); return this.categorias.filter(c => `${c.nombre || ''} ${c.descripcion || ''}`.toLocaleLowerCase().includes(q)); } },
  mounted() { this.cargar(); },
  methods: {
    async cargar() { this.cargando = true; this.error = ''; try { const { data } = await api.get(this.verPapelera ? '/api/categorias-papelera' : '/api/categorias', { params: this.verPapelera ? { limit: 100 } : {} }); this.categorias = Array.isArray(data) ? data : (data.data || []); } catch (e) { this.error = e.response?.data?.message || 'No se pudieron cargar las categorías.'; } finally { this.cargando = false; } },
    async guardar() { if (!this.form.nombre || this.guardando) return; this.guardando = true; this.error = ''; this.mensaje = ''; try { await api.post('/api/categorias', this.form); this.form = { nombre: '', descripcion: '' }; this.mostrarFormulario = false; this.mensaje = 'Categoría registrada correctamente.'; await this.cargar(); } catch (e) { this.error = e.response?.data?.message || 'No se pudo registrar la categoría.'; } finally { this.guardando = false; } },
    async eliminar(cat) { const id = cat.id_categoria ?? cat.id; if (!confirm(`¿Enviar la categoría «${cat.nombre}» a la papelera?`)) return; this.procesando = id; this.error = ''; this.mensaje = ''; try { const { data } = await api.delete(`/api/categorias/${id}`); this.mensaje = data.message || 'Categoría enviada a la papelera.'; await this.cargar(); } catch (e) { this.error = e.response?.data?.message || 'No se pudo eliminar la categoría.'; } finally { this.procesando = null; } },
    async restaurar(cat) { const id = cat.id_categoria ?? cat.id; this.procesando = id; this.error = ''; this.mensaje = ''; try { const { data } = await api.post(`/api/categorias/${id}/restaurar`); this.mensaje = data.message || 'Categoría restaurada.'; await this.cargar(); } catch (e) { this.error = e.response?.data?.message || 'No se pudo restaurar la categoría.'; } finally { this.procesando = null; } },
    alternarPapelera() { this.verPapelera = !this.verPapelera; this.buscar = ''; this.mensaje = ''; this.cargar(); }
  }
};
</script>
<style scoped>
.categorias-panel{background:white;border:1px solid #dfe8e2;border-radius:13px;padding:24px;box-shadow:0 3px 16px #0b30250b}.encabezado,.barra{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.encabezado{margin-bottom:22px}.encabezado h2{margin:0 0 6px;color:#0b3025;font-size:23px}.encabezado p{margin:0;color:#697c71;font-size:13px}.primario{background:#f47b20;color:#fff;border:0;padding:12px 18px;border-radius:8px;font-weight:700;cursor:pointer}.primario:disabled,.accion:disabled{opacity:.6}.secundario{background:#e8f3ed;color:#124734;border:0;padding:11px 15px;border-radius:8px;font-weight:700;cursor:pointer}.formulario{background:#f5f9f6;padding:20px;border:1px solid #dce8df;border-radius:10px;margin-bottom:20px}.formulario h3{margin:0 0 16px;color:#124734}.campos{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:15px}.campos label{display:grid;gap:7px;font-size:13px;font-weight:700;color:#124734}input{padding:11px;border:1px solid #d4e0d8;border-radius:8px;width:100%;font-size:13px}.barra{margin:15px 0}.barra input{max-width:420px}.tabla{overflow:auto;border:1px solid #e1eae4;border-radius:9px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:14px;border-bottom:1px solid #e1eae4;font-size:13px}th{background:#0b3025;color:white}.accion{border:0;background:#fff0e3;color:#b9580d;border-radius:6px;padding:8px 11px;cursor:pointer;font-weight:700}.error,.mensaje{padding:12px;border-radius:8px;font-size:13px}.error{background:#fff0ee;color:#a83226}.mensaje{background:#e8f3ed;color:#124734}@media(max-width:650px){.campos{grid-template-columns:1fr}.categorias-panel{padding:15px}}
</style>
