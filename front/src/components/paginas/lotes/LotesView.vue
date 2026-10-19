<template>
    <div class="contenedor-lotes">
        <!-- ENCABEZADO -->
        <div class="encabezado">
            <div>
                <h1>Gestión de Lotes</h1>
                <p>Control de producción, cantidades y fechas de vencimiento</p>
            </div>

            <div class="botones-encabezado">

                <button class="btn-crear" @click="crearLote">
                    + Crear Lote
                </button>

            </div>
        </div>
        <!-- FILTROS -->
        <div class="filtros">
            <input
                type="text"
                v-model="buscar"
                @input="buscarLotes"
                placeholder="Buscar lote, producto, SKU o presentación..."
            >

            <select v-model="estado" @change="filtrarLotes">
                <option value="">Todos los estados</option>
                <option value="true">Activos</option>
                <option value="false">Inactivos</option>
            </select>

            <select v-model="vencimiento" @change="filtrarLotes">
                <option value="">Todos los vencimientos</option>
                <option value="VENCIDO">Vencidos</option>
                <option value="PROXIMO">Próximos (0 a 90 días)</option>
                <option value="PREVENTIVO">Preventivos (91 a 180 días)</option>
                <option value="VIGENTE">Vigentes (más de 180 días)</option>
            </select>

            <select v-model.number="pagination.per_page" @change="cambiarLimite">
                <option :value="10">10 registros</option>
                <option :value="25">25 registros</option>
                <option :value="50">50 registros</option>
            </select>
        </div>

        <!-- TABLA -->
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código de lote</th>
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Producción</th>
                        <th>Vida útil</th>
                        <th>Vencimiento</th>
                        <th>Situación</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="cargando">
                        <td colspan="11" class="mensaje-tabla">
                            Cargando lotes...
                        </td>
                    </tr>

                    <tr
                        v-for="lote in lotes"
                        v-else
                        :key="lote.id_lote"
                    >
                        <td>{{ lote.id_lote }}</td>

                        <td>
                            <strong>{{ lote.codigo_lote }}</strong>
                        </td>

                        <td>
                            {{ lote.presentacion?.producto?.nombre || '—' }}
                        </td>

                        <td>
                            {{ lote.presentacion?.nombre_presentacion || '—' }}
                        </td>

                        <td>{{ formatearFecha(lote.fecha_produccion) }}</td>

                        <td>{{ lote.vida_util_meses }} meses</td>

                        <td>{{ formatearFecha(lote.fecha_vencimiento) }}</td>

                        <td>
                            <span
                                class="etiqueta-vencimiento"
                                :class="claseVencimiento(lote.fecha_vencimiento)"
                            >
                                {{ textoVencimiento(lote.fecha_vencimiento) }}
                            </span>
                        </td>

                        <td>
                            <strong>{{ lote.cantidad_producida }}</strong>
                        </td>

                        <td>
                            <span
                                class="etiqueta-estado"
                                :class="lote.estado ? 'estado-activo' : 'estado-inactivo'"
                            >
                                {{ lote.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">
                                <button
                                    class="btn-editar"
                                    @click="editarLote(lote.id_lote)"
                                >
                                    Editar
                                </button>
                                <button
                                    :class="lote.estado ? 'btn-inactivar' : 'btn-activar'"
                                    @click="cambiarEstadoLote(lote)"
                                >
                                    {{ lote.estado ? 'Inactivar' : 'Activar' }}
                                </button>
                                <button
                                    class="btn-eliminar"
                                    @click="eliminarLote(lote.id_lote)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!cargando && lotes.length === 0">
                        <td colspan="11" class="mensaje-tabla">
                            No se encontraron lotes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <div class="paginacion">
            <div>
                Total de registros:
                <strong>{{ pagination.total }}</strong>
            </div>

            <div class="controles-paginacion">
                <button
                    @click="paginaAnterior"
                    :disabled="pagination.current_page <= 1 || cargando"
                >
                    Anterior
                </button>

                <span>
                    Página {{ pagination.current_page }}
                    de {{ pagination.last_page }}
                </span>

                <button
                    @click="paginaSiguiente"
                    :disabled="pagination.current_page >= pagination.last_page || cargando"
                >
                    Siguiente
                </button>
            </div>
        </div>

    </div>

</template>

<script>
import api from '../../../services/api';

export default {

    data() {
        return {
            lotes: [],
            buscar: '',
            estado: '',
            vencimiento: '',
            cargando: false,
            temporizadorBusqueda: null,
            solicitudActual: 0,

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }
        };
    },

    mounted() {
        this.lotesGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
        this.solicitudActual++;
    },

    methods: {

        lotesGet() {
            const solicitud = ++this.solicitudActual;
            this.cargando = true;

            api.get('http://localhost:8000/api/lotes', {
                params: {
                    page: this.pagination.current_page,
                    limit: this.pagination.per_page,
                    buscar: this.buscar,
                    estado: this.estado,
                    vencimiento: this.vencimiento
                }
            })
            .then(response => {
                if (solicitud !== this.solicitudActual) return;

                this.lotes = response.data.data;
                this.pagination.current_page = response.data.current_page;
                this.pagination.last_page = response.data.last_page;
                this.pagination.total = response.data.total;
            })
            .catch(error => {
                if (solicitud !== this.solicitudActual) return;

                console.error('Error al obtener lotes:', error);
                alert('No se pudieron cargar los lotes.');
            })
            .finally(() => {
                if (solicitud === this.solicitudActual) {
                    this.cargando = false;
                }
            });
        },

        buscarLotes() {
            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {
                this.pagination.current_page = 1;
                this.lotesGet();
            }, 400);
        },

        filtrarLotes() {
            this.pagination.current_page = 1;
            this.lotesGet();
        },

        cambiarLimite() {
            this.pagination.current_page = 1;
            this.lotesGet();
        },

        paginaAnterior() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.lotesGet();
            }
        },

        paginaSiguiente() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.lotesGet();
            }
        },

        crearLote() {
            this.$router.push('/lotes/crear');
        },

        editarLote(id) {
            this.$router.push(`/lotes/editar/${id}`);
        },

        formatearFecha(fecha) {
            if (!fecha) return '—';

            const partes = fecha.substring(0, 10).split('-');

            if (partes.length !== 3) return fecha;

            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        },

        diasParaVencer(fechaVencimiento) {
            if (!fechaVencimiento) return null;

            const fechaTexto = fechaVencimiento.substring(0, 10);
            const fecha = new Date(`${fechaTexto}T00:00:00`);

            if (Number.isNaN(fecha.getTime())) return null;

            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);

            return Math.round((fecha.getTime() - hoy.getTime()) / 86400000);
        },

        textoVencimiento(fechaVencimiento) {
            const dias = this.diasParaVencer(fechaVencimiento);

            if (dias === null) return 'Sin fecha';
            if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
            if (dias === 0) return 'Vence hoy';
            if (dias <= 90) return `Vence en ${dias} días`;
            if (dias <= 180) return `Preventivo: ${dias} días`;

            return 'Vigente';
        },

        claseVencimiento(fechaVencimiento) {
            const dias = this.diasParaVencer(fechaVencimiento);

            if (dias === null) return 'sin-fecha';
            if (dias <= 90) return 'alerta-roja';
            if (dias <= 180) return 'alerta-amarilla';

            return 'alerta-verde';
        },
        eliminarLote(id) {

    if (!confirm('¿Enviar este lote a la papelera?')) {
        return;
    }

    api.delete(`http://localhost:8000/api/lotes/${id}`)
        .then(response => {
            alert(response.data.message);
            this.lotesGet();
        })
        .catch(error => {
            alert(error.response?.data?.message || 'Error al eliminar el lote.');
        });
},

    cambiarEstadoLote(lote) {

        const nuevoEstado = !lote.estado;
        const accion = nuevoEstado ? 'activar' : 'inactivar';

        if (!confirm(`¿Desea ${accion} el lote ${lote.codigo_lote}?`)) {
            return;
        }

        api.post(`http://localhost:8000/api/lotes/${lote.id_lote}/estado`, {
            estado: nuevoEstado
        })
        .then(response => {
            alert(response.data.message);
            this.lotesGet();
        })
        .catch(error => {
            alert(error.response?.data?.message || 'No se pudo cambiar el estado.');
        });
    }

    }
};
</script>

<style scoped>
.contenedor-lotes {
    width: 100%;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.encabezado h1 {
    margin: 0 0 6px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}
.botones-encabezado {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-papelera {
    background: #475569;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
}
.btn-crear {
    background: #2563eb;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
}
.btn-inactivar {
    background: #d97706;
}

.btn-activar {
    background: #16a34a;
}

.filtros {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filtros input,
.filtros select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 14px;
}

.filtros input {
    flex: 1;
    min-width: 250px;
}

.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
    white-space: nowrap;
}

tbody tr:hover {
    background: #f9fafb;
}

.mensaje-tabla {
    text-align: center;
    padding: 28px;
    color: #6b7280;
}

.etiqueta-vencimiento,
.etiqueta-estado {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.alerta-roja {
    background: #fee2e2;
    color: #991b1b;
}

.alerta-amarilla {
    background: #fef3c7;
    color: #92400e;
}

.alerta-verde,
.estado-activo {
    background: #dcfce7;
    color: #166534;
}

.estado-inactivo {
    background: #e5e7eb;
    color: #374151;
}

.sin-fecha {
    background: #f3f4f6;
    color: #6b7280;
}

.acciones {
    display: flex;
    gap: 7px;
}

.acciones button {
    border: none;
    padding: 7px 11px;
    border-radius: 5px;
    color: white;
    cursor: pointer;
}

.btn-editar {
    background: #2563eb;
}

.btn-eliminar {
    background: #dc2626;
}

.acciones button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
    color: #374151;
    font-size: 14px;
}

.controles-paginacion {
    display: flex;
    align-items: center;
    gap: 12px;
}

.controles-paginacion button {
    padding: 8px 13px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 5px;
    cursor: pointer;
}

.controles-paginacion button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 700px) {
    .encabezado {
        align-items: flex-start;
        flex-direction: column;
    }

    .filtros input,
    .filtros select {
        width: 100%;
    }
}
</style>
