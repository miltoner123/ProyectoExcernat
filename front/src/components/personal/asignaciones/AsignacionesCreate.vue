<template>
    <div class="formulario">

        <div class="encabezado">
            <h1>Nueva Asignación</h1>
            <p>Asigne un trabajador a una ubicación o evento.</p>
        </div>

        <p v-if="errorCarga" class="mensaje-error">{{ errorCarga }}</p>
        <p v-if="cargandoDatos">Cargando trabajadores, ubicaciones y eventos...</p>

        <form @submit.prevent="guardar">

            <div class="seccion">

                <h3>Personal</h3>

                <div class="fila">

                    <div class="campo">
                        <label>Trabajador:</label>

                        <select v-model="asignacion.id_personal" required>
                            <option value="" disabled>Seleccione</option>

                            <option
                                v-for="trabajador in personal"
                                :key="trabajador.id_personal"
                                :value="trabajador.id_personal"
                            >
                                {{ nombrePersonal(trabajador) }}
                                - {{ trabajador.cargo?.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Función asignada:</label>

                        <select v-if="standSeleccionado" v-model="asignacion.funcion_asignada" required>
                            <option value="" disabled>Seleccione una función</option>
                            <option value="ENCARGADO">Encargado</option>
                            <option value="VENDEDOR">Vendedor</option>
                        </select>
                        <input v-else type="text" v-model.trim="asignacion.funcion_asignada" maxlength="100" placeholder="Ej. Responsable de almacén" required>
                    </div>

                </div>

            </div>

<div class="seccion">
        <h3>Destino de la asignación</h3>
        <div class="campo">
          <label>Tipo de destino:</label>
          <select v-model="tipoDestino" @change="cambiarDestino">
            <option value="FERIA">Feria / Evento</option>
            <option value="OTRO">Sucursal / Almacén</option>
          </select>
        </div>
        <div class="fila" v-if="tipoDestino === 'FERIA'">
          <div class="campo">
            <label>Evento:</label>
            <select v-model="asignacion.id_evento" @change="eventoSeleccionado" required>
              <option :value="null" disabled>Seleccione un evento</option>
              <option v-for="e in eventos" :key="e.id_evento" :value="e.id_evento">{{ e.nombre }}</option>
            </select>
          </div>
          <div class="campo">
            <label>Stand del evento:</label>
            <select v-model="asignacion.id_ubicacion" @change="ubicacionSeleccionada" required :disabled="!asignacion.id_evento">
              <option :value="null" disabled>Seleccione un stand</option>
              <option v-for="u in ubicacionesFiltradas" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }}</option>
            </select>
            <small>Solo se muestran stands del evento seleccionado. Las fechas se sugieren automáticamente y pueden editarse.</small>
          </div>
        </div>
        <div class="campo" v-else>
          <label>Ubicación:</label>
          <select v-model="asignacion.id_ubicacion" @change="ubicacionSeleccionada" required>
            <option :value="null" disabled>Seleccione sucursal o almacén</option>
            <option v-for="u in ubicacionesFiltradas" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }} - {{ u.tipo }}</option>
          </select>
          <small>Las sucursales y almacenes no se vinculan a eventos.</small>
        </div>
      </div>
      <div class="seccion">

                <h3>Remuneración</h3>

                <div class="campo">

                    <label>Tipo de remuneración:</label>

                    <select
                        v-model="asignacion.tipo_remuneracion"
                        required
                    >
                        <option value="" disabled>Seleccione</option>
                        <option value="SALARIO">Salario</option>
                        <option value="MONTO_FIJO">Monto fijo</option>
                        <option value="COMISION">Comisión</option>
                        <option value="MIXTO">Mixto</option>
                    </select>

                </div>

                <div
                    v-if="mostrarMontoFijo"
                    class="campo"
                >
                    <label>Monto fijo (Bs):</label>

                    <input
                        type="number"
                        v-model="asignacion.monto_fijo"
                        min="0"
                        step="0.01"
                    >
                </div>

                <div v-if="esEncargadoStand" class="campo">
                    <label><input type="checkbox" v-model="comisionOpcional" /> Asignar comisión adicional al encargado</label>
                    <small>Opcional. Si no se activa, no se registrarán comisiones.</small>
                </div>
                <div
                    v-if="mostrarComisiones"
                    class="fila"
                >

                    <div class="campo">
                        <label>Comisión productos en polvo (Bs/unidad):</label>

                        <input
                            type="number"
                            v-model="asignacion.comision_polvo"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <div class="campo">
                        <label>Comisión granola (Bs/unidad):</label>

                        <input
                            type="number"
                            v-model="asignacion.comision_granola"
                            min="0"
                            step="0.01"
                        >
                    </div>

                </div>

            </div>

            <div class="seccion">

                <h3>Periodo</h3>

                <div class="fila">

                    <div class="campo">
                        <label>Fecha inicio:</label>

                        <input
                            type="date"
                            v-model="asignacion.fecha_inicio"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label>Fecha fin:</label>

                        <input
                            type="date"
                            v-model="asignacion.fecha_fin"
                            :min="asignacion.fecha_inicio"
                        >
                    </div>

                </div>

                <div class="campo">
                    <label>Estado:</label>

                    <select v-model="asignacion.estado">
                        <option :value="true">Activa</option>
                        <option :value="false">Inactiva</option>
                    </select>
                </div>

            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando || cargandoDatos || !!errorCarga"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Asignación' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/asignaciones')"
                >
                    Cancelar
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

            asignacion: {
                id_personal: '',
                id_ubicacion: null,
                id_evento: null,
                funcion_asignada: '',
                tipo_remuneracion: '',
                monto_fijo: null,
                comision_polvo: null,
                comision_granola: null,
                fecha_inicio: '',
                fecha_fin: '',
                estado: true
            },

            tipoDestino: 'OTRO',
            personal: [],
            ubicaciones: [],
            eventos: [],

            comisionOpcional: false,
            guardando: false,
            cargandoDatos: false,
            errorCarga: ''
        };
    },

    computed: {

        ubicacionActual() {

            return this.ubicaciones.find(
                ubicacion =>
                    Number(ubicacion.id_ubicacion) ===
                    Number(this.asignacion.id_ubicacion)
            );

        },

        standSeleccionado() {
            return this.tipoDestino === 'FERIA';
        },

    ubicacionesFiltradas() {
      return this.ubicaciones.filter(u => this.tipoDestino === 'FERIA'
        ? u.tipo === 'STAND' && Number(u.id_evento) === Number(this.asignacion.id_evento)
        : ['SUCURSAL', 'ALMACEN'].includes(u.tipo));
    },
        mostrarMontoFijo() {

            return [
                'MONTO_FIJO',
                'MIXTO'
            ].includes(this.asignacion.tipo_remuneracion);

        },

        esEncargadoStand() {
            return this.tipoDestino === 'FERIA' && String(this.asignacion.funcion_asignada).toUpperCase() === 'ENCARGADO';
        },
        mostrarComisiones() {

            return (this.esEncargadoStand && this.comisionOpcional) || ['COMISION', 'MIXTO'].includes(this.asignacion.tipo_remuneracion);

        }

    },

    mounted() {
        this.cargarDatos();
    },

    methods: {

        async cargarDatos() {
            this.cargandoDatos = true;
            this.errorCarga = '';
            try {
                const [personal, ubicaciones, eventos] = await Promise.all([
                    this.obtenerTodos('/api/personal', { estado: true }),
                    this.obtenerTodos('/api/ubicaciones', { estado: true }),
                    this.obtenerTodos('/api/eventos', { estado: true })
                ]);
                this.personal = personal;
                this.ubicaciones = ubicaciones;
                this.eventos = eventos;
            } catch (error) {
                console.error('Error al cargar datos de asignación:', error);
                this.errorCarga = error.response?.data?.message ||
                    'No se pudieron cargar los datos. Recargue la página para intentar nuevamente.';
            } finally {
                this.cargandoDatos = false;
            }
        },

        async obtenerTodos(url, filtros = {}) {
            const registros = [];
            let pagina = 1;
            let ultimaPagina = 1;
            do {
                const { data } = await api.get(url, {
                    params: { ...filtros, page: pagina, limit: 50 }
                });
                registros.push(...(Array.isArray(data) ? data : (data.data || [])));
                ultimaPagina = Array.isArray(data) ? 1 : Number(data.last_page || 1);
                pagina++;
            } while (pagina <= ultimaPagina);
            return registros;
        },

    cambiarDestino() {
      this.asignacion.id_ubicacion = null;
      this.asignacion.id_evento = null;
      this.asignacion.funcion_asignada = '';
      this.asignacion.fecha_inicio = '';
      this.asignacion.fecha_fin = '';
    },
    eventoSeleccionado() {
      this.asignacion.id_ubicacion = null;
      this.asignacion.funcion_asignada = '';
      const evento = this.eventos.find(e => Number(e.id_evento) === Number(this.asignacion.id_evento));
      this.asignacion.fecha_inicio = String(evento?.fecha_inicio || '').slice(0, 10);
      this.asignacion.fecha_fin = String(evento?.fecha_fin || '').slice(0, 10);
    },
        ubicacionSeleccionada() {
            if (this.tipoDestino === 'OTRO') this.asignacion.id_evento = null;
            this.asignacion.funcion_asignada = '';
        },

        nombrePersonal(trabajador) {

            if (!trabajador.persona) {
                return trabajador.codigo_empleado;
            }

            return [
                trabajador.persona.nombres_razon_social,
                trabajador.persona.apellidos
            ]
            .filter(Boolean)
            .join(' ');

        },

        guardar() {

            if (
                !this.asignacion.id_ubicacion ||
                (this.tipoDestino === 'FERIA' && !this.asignacion.id_evento)
            ) {

                alert(
                    'Debe seleccionar una ubicación o un evento.'
                );

                return;
            }

            if (
                this.asignacion.fecha_fin &&
                this.asignacion.fecha_fin <
                this.asignacion.fecha_inicio
            ) {

                alert(
                    'La fecha final no puede ser anterior a la fecha inicial.'
                );

                return;
            }

            this.guardando = true;

            const datos = {
                ...this.asignacion,

                fecha_fin:
                    this.asignacion.fecha_fin || null,

                monto_fijo:
                    this.mostrarMontoFijo
                        ? this.asignacion.monto_fijo
                        : null,

                comision_polvo:
                    this.mostrarComisiones
                        ? this.asignacion.comision_polvo
                        : null,

                comision_granola:
                    this.mostrarComisiones
                        ? this.asignacion.comision_granola
                        : null
            };

            api.post(
                '/api/asignaciones-personal',
                datos
            )
            .then(response => {

                alert(response.data.message);

                this.$router.push('/asignaciones');

            })
            .catch(error => {

                console.error(error);

                if (
                    error.response?.status === 422 &&
                    error.response?.data?.errors
                ) {

                    alert(
                        Object.values(
                            error.response.data.errors
                        )
                        .flat()
                        .join('\n')
                    );

                    return;
                }

                alert(
                    error.response?.data?.message ||
                    'No se pudo registrar la asignación.'
                );

            })
            .finally(() => {

                this.guardando = false;

            });

        }

    }

};
</script>

<style scoped>
.mensaje-error { color: #b91c1c; background: #fef2f2; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
.formulario {
    max-width: 950px;
    margin: 0 auto;
}

.encabezado {
    margin-bottom: 25px;
}

.encabezado h1 {
    margin: 0 0 5px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
}

.seccion {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.seccion h3 {
    margin-top: 0;
    color: #1f2937;
}

.fila {
    display: flex;
    gap: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-bottom: 18px;
}

.campo label {
    margin-bottom: 7px;
    font-weight: 600;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.campo small {
    margin-top: 5px;
    color: #6b7280;
}

.botones {
    display: flex;
    gap: 10px;
}

.botones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    background: #2563eb;
    color: white;
    cursor: pointer;
}

.botones .btn-cancelar {
    background: #6b7280;
}

@media (max-width: 700px) {
    .fila {
        flex-direction: column;
        gap: 0;
    }
}
</style>
<style scoped>
.formulario{max-width:1050px;margin:24px auto;padding:28px;background:#fff;border:1px solid #d9e7de;border-radius:18px;box-shadow:0 10px 32px rgba(12,48,37,.07)}
.encabezado{border-left:5px solid #f47b20;padding:4px 0 12px 16px;margin-bottom:24px}
.encabezado h1{font-size:25px;color:#0c3025;margin:0 0 6px}
.encabezado p{color:#637b6d;margin:0}
.seccion{background:#f8fbf9;border:1px solid #e0eae3;border-radius:14px;padding:20px;margin:16px 0 20px}
.seccion h3{color:#124734;font-size:17px;margin:0 0 18px;padding-bottom:10px;border-bottom:1px solid #dce8df}
.fila{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.campo{display:flex;flex-direction:column;gap:7px;margin-bottom:14px;min-width:0}
.campo label{color:#173d2d;font-weight:700;font-size:13px}
.campo select,.campo input{width:100%;min-height:42px;box-sizing:border-box;background:#fff;border:1px solid #cbdcd0;border-radius:9px;padding:10px 12px;color:#163d2c;outline:none}
.campo select:focus,.campo input:focus{border-color:#f47b20;box-shadow:0 0 0 3px rgba(244,123,32,.12)}
.campo small{color:#678071;font-size:12px}
.botones{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:22px}
.botones button[type="submit"]{background:#f47b20;color:white;border:0;border-radius:9px;padding:12px 20px;font-weight:800}
.botones .btn-cancelar{background:#e9f1ec;color:#124734;border:0;border-radius:9px;padding:12px 20px;font-weight:700}
@media(max-width:720px){.formulario{padding:14px;margin:10px}.fila{grid-template-columns:1fr}.seccion{padding:14px}}
</style>
