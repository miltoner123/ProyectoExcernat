<template>
    <div class="contenedor">
        <h1>Editar Presentación</h1>
        <p>Actualizar información y fotografía de la presentación</p>

        <form v-if="cargado" @submit.prevent="actualizarPresentacion" class="formulario">

            <div class="campo">
                <label>Producto *</label>
                <select v-model="presentacion.id_producto" required>
                    <option v-for="p in productos" :key="p.id" :value="p.id">
                        {{ p.nombre }}
                    </option>
                </select>
            </div>

            <div class="campo">
                <label>Código SKU *</label>
                <input v-model.trim="presentacion.codigo_sku" type="text" required>
            </div>

            <div class="campo">
                <label>Nombre de presentación *</label>
                <input v-model.trim="presentacion.nombre_presentacion" type="text" required>
            </div>

            <div class="campo">
                <label>Fotografía de la presentación</label>

                <img v-if="imagenMostrada" :src="imagenMostrada"
                     alt="Fotografía de presentación" class="vista-previa">

                <span v-else class="sin-imagen">Sin fotografía registrada</span>

                <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       @change="seleccionarImagen">

                <small>Selecciona una nueva imagen solo si deseas reemplazarla. Máximo 2 MB.</small>
            </div>

            <div class="campo">
                <label>Peso neto *</label>
                <input v-model="presentacion.peso_neto" type="number"
                       min="0.01" step="0.01" required>
            </div>

            <div class="campo">
                <label>Unidad de medida *</label>
                <select v-model="presentacion.unidad_medida" required>
                    <option value="g">Gramos (g)</option>
                    <option value="kg">Kilogramos (kg)</option>
                </select>
            </div>

            <div class="campo">
                <label>Tipo de envase *</label>
                <select v-model="presentacion.tipo_envase" required>
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
                <label>Precio paquete (Bs)</label>
                <input v-model="presentacion.precio_paquete"
                       type="number" min="0" step="0.01">
            </div>

            <div class="acciones">
                <button type="button" class="btn-cancelar"
                        @click="$router.push('/presentaciones')">
                    Cancelar
                </button>

                <button type="submit" class="btn-guardar" :disabled="guardando">
                    {{ guardando ? 'Actualizando...' : 'Actualizar Presentación' }}
                </button>
            </div>
        </form>

        <p v-else>Cargando presentación...</p>
    </div>
</template>

<script>
import axios from 'axios';

const API = 'http://localhost:8000/api';
const STORAGE = 'http://localhost:8000/storage/';

export default {
    data() {
        return {
            presentacion: {},
            productos: [],
            imagenSeleccionada: null,
            vistaPrevia: null,
            guardando: false,
            cargado: false
        };
    },

    computed: {
        imagenMostrada() {
            if (this.vistaPrevia) return this.vistaPrevia;
            return this.presentacion.imagen
                ? STORAGE + this.presentacion.imagen
                : null;
        }
    },

    mounted() {
        this.cargarDatos();
    },

    beforeUnmount() {
        if (this.vistaPrevia) URL.revokeObjectURL(this.vistaPrevia);
    },

    methods: {
        async cargarDatos() {
            try {
                const id = this.$route.params.id;

                const [resProductos, resPresentacion] = await Promise.all([
                    axios.get(`${API}/productos`, { params: { limit: 100 } }),
                    axios.get(`${API}/presentaciones/${id}`)
                ]);

                this.presentacion = resPresentacion.data;

                // Incluir el producto actual aunque esté inactivo.
                this.productos = resProductos.data.data.filter(p =>
                    Boolean(p.estado) || p.id === this.presentacion.id_producto
                );

                this.cargado = true;
            } catch (error) {
                console.error('Error al cargar datos:', error);
                alert('No se pudo cargar la presentación.');
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

        async actualizarPresentacion() {
            if (this.guardando) return;
            this.guardando = true;

            try {
                const id = this.$route.params.id;
                const formData = new FormData();

                const campos = [
                    'id_producto', 'codigo_sku', 'nombre_presentacion',
                    'peso_neto', 'unidad_medida', 'tipo_envase',
                    'unidades_por_paquete', 'precio_unitario', 'precio_paquete'
                ];

                campos.forEach(campo => {
                    formData.append(campo, this.presentacion[campo] ?? '');
                });

                if (this.imagenSeleccionada) {
                    formData.append('imagen', this.imagenSeleccionada);
                }

                // Laravel procesa la actualización y el archivo correctamente.
                formData.append('_method', 'PUT');

                await axios.post(`${API}/presentaciones/${id}`, formData);

                alert('Presentación actualizada correctamente.');
                this.$router.push('/presentaciones');

            } catch (error) {
                console.error('Error al actualizar:', error);

                const errores = error.response?.data?.errors;
                const mensaje = errores
                    ? Object.values(errores).flat().join('\n')
                    : error.response?.data?.message ||
                      'No se pudo actualizar la presentación.';

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

.campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.campo label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.campo input,
.campo select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
}

.campo input:focus,
.campo select:focus {
    outline: none;
    border-color: #2563eb;
}

.campo small {
    font-size: 12px;
    color: #6b7280;
}

.vista-previa {
    width: 140px;
    height: 140px;
    object-fit: contain;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 5px;
}

.sin-imagen { color: #9ca3af; font-size: 13px; }

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
