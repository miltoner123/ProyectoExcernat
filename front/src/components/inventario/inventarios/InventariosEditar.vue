<template>
  <section class="inv-edit">
    <div class="inv-header"><div><div class="inv-kicker">INVENTARIO / ADMINISTRACIÓN</div><h1>Editar inventario</h1><p>Actualiza únicamente el stock mínimo. El stock real cambia mediante movimientos de inventario.</p></div><button class="inv-secondary" type="button" @click="$router.push('/inventarios')">← Volver al listado</button></div>
    <div v-if="cargando" class="inv-card">Cargando inventario...</div>
    <div v-else-if="errorCarga" class="inv-card inv-error">{{ errorCarga }} <button class="inv-secondary" @click="cargar">Reintentar</button></div>
    <form v-else class="inv-card" @submit.prevent="guardar">
      <h2>Información del inventario</h2><p class="inv-note">Los datos del lote y la ubicación son informativos y no se modifican desde esta pantalla.</p>
      <div class="inv-grid">
        <div class="inv-field"><label>Ubicación</label><input :value="inventario.ubicacion?.nombre || '—'" disabled></div>
        <div class="inv-field"><label>Producto</label><input :value="inventario.lote?.presentacion?.producto?.nombre || '—'" disabled></div>
        <div class="inv-field"><label>Presentación</label><input :value="inventario.lote?.presentacion?.nombre_presentacion || '—'" disabled></div>
        <div class="inv-field"><label>Lote</label><input :value="inventario.lote?.codigo_lote || '—'" disabled></div>
        <div class="inv-field"><label>Stock real</label><input :value="inventario.stock_actual ?? 0" disabled><small>Solo se modifica mediante movimientos autorizados.</small></div>
        <div class="inv-field"><label for="stock-minimo">Stock mínimo *</label><input id="stock-minimo" v-model.number="stockMinimo" type="number" min="0" step="1" required><small>Se utilizará para las alertas de stock bajo.</small></div>
      </div>
      <p v-if="errorGuardar" class="inv-error">{{ errorGuardar }}</p>
      <div class="inv-actions"><button type="button" class="inv-secondary" @click="$router.push('/inventarios')">Cancelar</button><button type="submit" class="inv-primary" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Guardar cambios' }}</button></div>
    </form>
  </section>
</template>
<script>
import api from '../../../services/api';
export default {
  data() { return { inventario: {}, stockMinimo: 0, cargando: true, guardando: false, errorCarga: '', errorGuardar: '' }; },
  mounted() { this.cargar(); },
  methods: {
    async cargar() {
      this.cargando = true; this.errorCarga = '';
      try {
        const { data } = await api.get(`/api/inventarios/${this.$route.params.id}`);
        this.inventario = data?.inventario || data;
        this.stockMinimo = Number(this.inventario.stock_minimo ?? 0);
      } catch (e) { this.errorCarga = e.response?.data?.message || 'No se pudo cargar el inventario.'; }
      finally { this.cargando = false; }
    },
    async guardar() {
      this.errorGuardar = '';
      if (!Number.isInteger(Number(this.stockMinimo)) || Number(this.stockMinimo) < 0) { this.errorGuardar = 'El stock mínimo debe ser un número entero igual o mayor a cero.'; return; }
      this.guardando = true;
      try {
        await api.post(`/api/inventarios/${this.$route.params.id}`, { stock_minimo: Number(this.stockMinimo) });
        this.$router.push('/inventarios');
      } catch (e) { this.errorGuardar = e.response?.data?.message || 'No se pudo actualizar el inventario.'; }
      finally { this.guardando = false; }
    }
  }
};
</script>
<style scoped>
.inv-edit{max-width:1120px;margin:0 auto;padding:20px 12px;color:#103b2d}.inv-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap}.inv-kicker{font-size:11px;font-weight:800;letter-spacing:.11em;color:#ef7618}.inv-header h1{font-size:30px;margin:8px 0;color:#0d382a}.inv-header p,.inv-note{color:#637d70;font-size:14px;margin:5px 0}.inv-card{background:white;border:1px solid #d9e6de;border-radius:16px;padding:28px;box-shadow:0 10px 28px #153c2a0a}.inv-card h2{font-size:18px;margin:0 0 8px}.inv-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:25px}.inv-field{display:flex;flex-direction:column;gap:8px}.inv-field label{font-size:13px;font-weight:750}.inv-field input{width:100%;min-height:44px;box-sizing:border-box;border:1px solid #cddfd3;border-radius:9px;padding:10px 13px;font:inherit;color:#143d2f;background:white}.inv-field input:focus{outline:2px solid #f47b20;outline-offset:1px}.inv-field input:disabled{background:#f1f5f2;color:#526b5d}.inv-field small{color:#6c8275;font-size:12px}.inv-actions{border-top:1px solid #e1eae3;padding-top:20px;margin-top:30px;display:flex;justify-content:flex-end;gap:12px}.inv-primary,.inv-secondary{border-radius:9px;padding:12px 19px;cursor:pointer;font-weight:750;border:1px solid #d3e1d8}.inv-primary{background:#f47b20;color:white;border-color:#f47b20}.inv-secondary{background:white;color:#164331}.inv-primary:disabled{opacity:.6;cursor:wait}.inv-error{color:#b6342b;margin:16px 0}@media(max-width:650px){.inv-grid{grid-template-columns:1fr}.inv-card{padding:17px}.inv-header h1{font-size:25px}}
</style>
