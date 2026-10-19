<template>
    <div class="contenedor">
        <h1>Crear Presentación</h1>
        <p>Registrar una nueva presentación de producto</p>

        <form @submit.prevent="crearPresentacion" class="formulario">

            <div class="campo">
                <label>Producto *</label>
                <select v-model="presentacion.id_producto" required>
                    <option value="" disabled>Seleccione un producto</option>
                    <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                        {{ producto.nombre }}
                    </option>
                </select>
            </div>

            <div class="campo">
                <label>Código SKU *</label>
                <input v-model.trim="presentacion.codigo_sku" type="text"
                       placeholder="Ej. GRA-100G" required>
            </div>

            <div class="campo">
                <label>Nombre de presentación *</label>
                <input v-model.trim="presentacion.nombre_presentacion" type="text"
                       placeholder="Ej. Granola 100 g" required>
            </div>

            <div class="campo">
                <label>Fotografía de la presentación</label>
                <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       @change="seleccionarImagen">
                <small>JPG, PNG o WEBP. Máximo 2 MB.</small>

                <img v-if="vistaPrevia" :src="vistaPrevia"
                     alt="Vista previa" class="vista-previa">
            </div>

            <div class="campo">
                <label>Peso neto *</label>
                <input v-model="presentacion.peso_neto" type="number"
                       min="0.01" step="0.01" required>
            </div>

            <div class="campo">
                <label>Unidad de medida *</label>
                <select v-model="presentacion.unidad_medida" required>
                    <option value="" disabled>Seleccione</option>
                    <option value="g">Gramos (g)</option>
                    <option value="kg">Kilogramos (kg)</option>
                </select>
            </div>

            <div class="campo">
                <label>Tipo de envase *</label>
                <select v-model="presentacion.tipo_envase" required>
                    <option value="" disabled>Seleccione</option>
                    <option value="Caja">Caja</option>
                    <option value="Bolsa">Bolsa</option>
                </select>
            </div>

            <div class="campo">
                <label>Unidades por paquete *</label>
                <input v-model="presentacion.unidades_por_paquete"
                       type="number" min="1" step="1" required>
            </div>

            <div class="campo">
                <label>Precio unitario (Bs) *</label>
                <input v-model="presentacion.precio_unitario"
                       type="number" min="0" step="0.01" required>
            </div>

            <div class="campo">
                <label>Precio por paquete (Bs)</label>
                <input v-model="presentacion.precio_paquete"
                       type="number" min="0" step="0.01">
            </div>

            <div class="acciones">
                <button type="button" class="btn-cancelar"
                        @click="$router.push('/presentaciones')">
                    Cancelar
                </button>

                <button type="submit" class="btn-guardar" :disabled="guardando">
                    {{ guardando ? 'Guardando...' : 'Crear Presentación' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import api from '../../../services/api';

export default {
    data() {
        return {
            presentacion: {
                id_producto: '',
                codigo_sku: '',
                nombre_presentacion: '',
                peso_neto: '',
                unidad_medida: '',
                tipo_envase: '',
                unidades_por_paquete: '',
                precio_unitario: '',
                precio_paquete: ''
            },
            productos: [],
            imagenSeleccionada: null,
            vistaPrevia: null,
            guardando: false
        };
    },

    mounted() {
        this.productosGet();
    },

    beforeUnmount() {
        if (this.vistaPrevia) URL.revokeObjectURL(this.vistaPrevia);
    },

    methods: {
        async productosGet() {
            try {
                const { data } = await api.get(
                    'http://localhost:8000/api/productos',
                    { params: { limit: 100 } }
                );
                this.productos = data.data.filter(p => Boolean(p.estado));
            } catch (error) {
                console.error('Error al cargar productos:', error);
                alert('No se pudieron cargar los productos.');
            }
        },

        seleccionarImagen(event) {
            const archivo = event.target.files?.[0];

            if (this.vistaPrevia) {
                URL.revokeObjectURL(this.vistaPrevia);
                this.vistaPrevia = null;
            }

            this.imagenSeleccionada = null;
            if (!archivo) return;

            const tipos = ['image/jpeg', 'image/png', 'image/webp'];

            if (!tipos.includes(archivo.type) || archivo.size > 2 * 1024 * 1024) {
                alert('Seleccione una imagen JPG, PNG o WEBP de máximo 2 MB.');
                event.target.value = '';
                return;
            }

            this.imagenSeleccionada = archivo;
            this.vistaPrevia = URL.createObjectURL(archivo);
        },

        async crearPresentacion() {
            if (this.guardando) return;
            this.guardando = true;

            try {
                const formData = new FormData();

                Object.entries(this.presentacion).forEach(([campo, valor]) => {
                    if (valor !== '' && valor !== null) {
                        formData.append(campo, valor);
                    }
                });

                if (this.imagenSeleccionada) {
                    formData.append('imagen', this.imagenSeleccionada);
                }

                await api.post(
                    'http://localhost:8000/api/presentaciones',
                    formData
                );

                alert('Presentación registrada correctamente.');
                this.$router.push('/presentaciones');

            } catch (error) {
                console.error('Error al crear presentación:', error);

                const errores = error.response?.data?.errors;
                const mensaje = errores
                    ? Object.values(errores).flat().join('\n')
                    : error.response?.data?.message ||
                      'No se pudo registrar la presentación.';

                alert(mensaje);
            } finally {
                this.guardando = false;
            }
        }
    }
};
</script>

<style scoped>
.contenedor {
    max-width: 850px;
    margin: 0 auto;
    padding: 20px;
}

h1 { margin-bottom: 5px; color: #1f2937; }
p { color: #6b7280; margin-bottom: 25px; }

.formulario {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.campo { display: flex; flex-direction: column; gap: 6px; }

.campo label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

.campo input:focus,
.campo select:focus {
    outline: none;
    border-color: #2563eb;
}

.campo small { color: #6b7280; font-size: 12px; }

.vista-previa {
    width: 130px;
    height: 130px;
    object-fit: contain;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-top: 8px;
}

.acciones {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 10px;
}

.acciones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-cancelar { background: #e5e7eb; color: #374151; }
.btn-guardar { background: #2563eb; color: white; }
.btn-guardar:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 600px) {
    .formulario { grid-template-columns: 1fr; }
}
</style>
