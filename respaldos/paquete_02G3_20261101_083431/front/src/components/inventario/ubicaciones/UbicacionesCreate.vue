
<template>
    <div class="formulario">
        <h1>Registrar Ubicación</h1>
        <p>Registro de almacenes, sucursales y stands de eventos.</p>

        <form @submit.prevent="crearUbicacion">

            <div class="campo">
                <label>Nombre *</label>
                <input v-model.trim="ubicacion.nombre" type="text"
                       :placeholder="ubicacion.tipo === 'STAND' ? 'Nombre automático al guardar' : 'Ej. Almacén Central'" :required="ubicacion.tipo !== 'STAND'">
                <small v-if="ubicacion.tipo === 'STAND'">Se propone el nombre del evento + STAND y correlativo. Puedes escribir un nombre personalizado.</small>
            </div>

            <div class="campo">
                <label>Tipo de ubicación *</label>
                <select v-model="ubicacion.tipo" @change="cambiarTipo" required>
                    <option value="" disabled>Seleccione un tipo</option>
                    <option value="ALMACEN">Almacén</option>
                    <option value="SUCURSAL">Sucursal</option>
                    <option value="STAND">Stand de feria</option>
                </select>
            </div>

            <div class="campo">
                <label>Propiedad *</label>
                <select v-model="ubicacion.propiedad"
                        :disabled="ubicacion.tipo === 'STAND'" required>
                    <option value="" disabled>Seleccione una opción</option>
                    <option value="PROPIA">Propia</option>
                    <option value="EXTERNA">Externa</option>
                    <option value="TEMPORAL">Temporal</option>
                </select>
            </div>

            <div v-if="ubicacion.tipo === 'STAND'" class="campo">
                <label>Evento asociado *</label>
                <select v-model="ubicacion.id_evento" @change="sincronizarEvento" required>
                    <option :value="null" disabled>Seleccione un evento</option>
                    <option v-for="evento in eventosDisponibles"
                            :key="evento.id_evento" :value="evento.id_evento">
                        {{ evento.nombre }} - {{ evento.departamento }}
                    </option>
                </select>
                <small>Este stand quedará vinculado al evento seleccionado.</small>
                <small v-if="!eventosDisponibles.length">
                    No hay eventos activos disponibles.
                </small>
            </div>

            <div v-if="ubicacion.tipo === 'STAND' && eventoSeleccionado" class="campo">
                <div style="background:#e9f4ed;padding:12px;border-radius:8px;border-left:4px solid #f47b20">
                    <strong>Datos heredados del evento</strong>
                    <p>Evento: {{ eventoSeleccionado.nombre }}</p>
                    <p>Lugar: {{ eventoSeleccionado.lugar }}</p>
                    <p>Departamento: {{ eventoSeleccionado.departamento }}</p>
                    <small>La ubicación general se reutiliza automáticamente; no es necesario escribirla otra vez.</small>
                </div>
            </div>
            <div v-if="ubicacion.tipo !== 'STAND'" class="campo">
                <label>Departamento *</label>
                <select v-model="ubicacion.departamento" required>
                    <option value="" disabled>Seleccione un departamento</option>
                    <option v-for="d in departamentos" :key="d" :value="d">
                        {{ d }}
                    </option>
                </select>
            </div>

            <div v-if="ubicacion.tipo !== 'STAND'" class="campo">
                <label>Dirección</label>
                <input v-model.trim="ubicacion.direccion" type="text"
                       placeholder="Ingrese la dirección">
            </div>

            <div class="campo">
                <label>Estado</label>
                <select v-model="ubicacion.estado">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>

            <div class="botones">
                <button type="submit" :disabled="guardando">
                    {{ guardando ? 'Guardando...' : 'Guardar Ubicación' }}
                </button>

                <button type="button" class="btn-cancelar"
                        @click="$router.push('/ubicaciones')">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import api from '../../../services/api';

const API = 'http://localhost:8000/api';

export default {
    data() {
        return {
            ubicacion: {
                id_evento: null,
                nombre: '',
                tipo: '',
                propiedad: '',
                departamento: '',
                direccion: '',
                estado: true
            },
            eventos: [],
            guardando: false,
            departamentos: [
                'La Paz', 'Cochabamba', 'Santa Cruz',
                'Oruro', 'Potosí', 'Chuquisaca',
                'Tarija', 'Beni', 'Pando'
            ]
        };
    },

    computed: {
        eventoSeleccionado() { return this.eventos.find(e => Number(e.id_evento) === Number(this.ubicacion.id_evento)) || null; },
        eventosDisponibles() {
            return this.eventos.filter(e =>
                e.estado === true || e.estado === 1 || e.estado === '1'
            );
        }
    },

    mounted() {
        this.eventosGet();

        const tipo = this.$route.query.tipo;
        const idEvento = this.$route.query.id_evento;

        if (['ALMACEN', 'SUCURSAL', 'STAND'].includes(tipo)) {
            this.ubicacion.tipo = tipo;
            this.cambiarTipo();
        }

        if (this.ubicacion.tipo === 'STAND' && idEvento) {
            this.ubicacion.id_evento = Number(idEvento);
        }
    },

    methods: {
        async eventosGet() {
            try {
                const { data } = await api.get(`${API}/eventos`, {
                    params: { limit: 100 }
                });

                this.eventos = data.data ?? data;
                this.sincronizarEvento();
            } catch (error) {
                console.error('Error al obtener eventos:', error);
            }
        },

        sincronizarEvento() {
            if (this.ubicacion.tipo !== 'STAND' || !this.eventoSeleccionado) return;
            this.ubicacion.departamento = this.eventoSeleccionado.departamento;
            this.ubicacion.direccion = this.eventoSeleccionado.lugar;
        },
        cambiarTipo() {
            if (this.ubicacion.tipo === 'STAND') {
                this.ubicacion.propiedad = 'TEMPORAL';
                this.sincronizarEvento();
            } else {
                this.ubicacion.id_evento = null;

                if (this.ubicacion.propiedad === 'TEMPORAL') {
                    this.ubicacion.propiedad = '';
                }
            }
        },

        async crearUbicacion() {
            if (this.guardando) return;

            if (this.ubicacion.tipo === 'STAND' &&
                !this.ubicacion.id_evento) {
                alert('Debe seleccionar un evento para el stand.');
                return;
            }

            this.sincronizarEvento();
            this.guardando = true;

            const datos = {
                ...this.ubicacion,
                id_evento: this.ubicacion.tipo === 'STAND'
                    ? this.ubicacion.id_evento
                    : null,
                propiedad: this.ubicacion.tipo === 'STAND'
                    ? 'TEMPORAL'
                    : this.ubicacion.propiedad
            };

            try {
                await api.post(`${API}/ubicaciones`, datos);

                alert('Ubicación registrada correctamente.');
                this.$router.push('/ubicaciones');

            } catch (error) {
                console.error('Error al registrar ubicación:', error);
                console.error('Respuesta del servidor:', error.response?.data);

                const errores = error.response?.data?.errors;
                const mensaje = errores
                    ? Object.values(errores).flat().join('\n')
                    : error.response?.data?.message ||
                      'No se pudo registrar la ubicación.';

                alert(mensaje);
            } finally {
                this.guardando = false;
            }
        }
    }
};
</script>

<style scoped>
.formulario {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
}

h1 { margin-bottom: 8px; }
p { color: #6b7280; margin-bottom: 22px; }

.campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.campo label { font-weight: 600; }

.campo input,
.campo select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-sizing: border-box;
}

.campo small { color: #6b7280; }

.botones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.botones button {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background: #2563eb;
    color: white;
}

.botones .btn-cancelar { background: #6b7280; }
button:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
