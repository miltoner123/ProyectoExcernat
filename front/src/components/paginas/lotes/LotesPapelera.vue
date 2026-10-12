
<template>
    <div class="contenedor-papelera">

        <div class="encabezado">
            <div>
                <h1>Papelera de Lotes</h1>
                <p>Consulta y recuperación de lotes eliminados</p>
            </div>

            <button class="btn-volver" @click="volver">
                Volver a Lotes
            </button>
        </div>

        <div class="informacion">
            Los lotes de esta sección no han sido eliminados
            físicamente de la base de datos. Puedes restaurarlos
            cuando sea necesario.
        </div>

        <div class="filtros">
            <input
                type="text"
                v-model="buscar"
                placeholder="Buscar por código de lote o producto..."
            >

            <select v-model.number="pagination.per_page" @change="cambiarLimite">
                <option :value="10">10 registros</option>
                <option :value="25">25 registros</option>
                <option :value="50">50 registros</option>
            </select>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código de lote</th>
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Producción</th>
                        <th>Vencimiento</th>
                        <th>Fecha de eliminación</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="cargando">
                        <td colspan="8" class="mensaje">
                            Cargando lotes eliminados...
                        </td>
                    </tr>

                    <template v-else>
                        <tr
                            v-for="lote in lotesFiltrados"
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

                            <td>{{ formatearFecha(lote.fecha_vencimiento) }}</td>

                            <td>{{ formatearFechaHora(lote.deleted_at) }}</td>

                            <td>
                                <button
                                    class="btn-restaurar"
                                    :disabled="restaurandoId === lote.id_lote"
                                    @click="restaurarLote(lote)"
                                >
                                    {{ restaurandoId === lote.id_lote ? 'Restaurando...' : 'Restaurar' }}
                                </button>
                            </td>
                        </tr>

                        <tr v-if="lotesFiltrados.length === 0">
                            <td colspan="8" class="mensaje">
                                No se encontraron lotes en la papelera.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="paginacion">
            <span>
                Total de lotes eliminados:
                <strong>{{ pagination.total }}</strong>
            </span>

            <div class="controles">
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
import axios from 'axios';

export default {

    data() {
        return {
            lotes: [],
            buscar: '',
            cargando: false,
            restaurandoId: null,

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }
        };
    },

    computed: {

        lotesFiltrados() {
            const texto = this.buscar.toLowerCase().trim();

            if (!texto) {
                return this.lotes;
            }

            return this.lotes.filter(lote => {
                const codigo = lote.codigo_lote?.toLowerCase() || '';
                const producto = lote.presentacion?.producto?.nombre?.toLowerCase() || '';
                const presentacion = lote.presentacion?.nombre_presentacion?.toLowerCase() || '';

                return codigo.includes(texto)
                    || producto.includes(texto)
                    || presentacion.includes(texto);
            });
        }

    },

    mounted() {
        this.lotesGet();
    },

    methods: {

        lotesGet() {
            this.cargando = true;

            axios.get('http://localhost:8000/api/lotes-papelera', {
                params: {
                    page: this.pagination.current_page,
                    limit: this.pagination.per_page
                }
            })
            .then(response => {
                this.lotes = response.data.data;
                this.pagination.current_page = response.data.current_page;
                this.pagination.last_page = response.data.last_page;
                this.pagination.total = response.data.total;
            })
            .catch(error => {
                console.error('Error al obtener la papelera:', error);
                alert('No se pudo cargar la papelera de lotes.');
            })
            .finally(() => {
                this.cargando = false;
            });
        },

        restaurarLote(lote) {
            if (!confirm(`¿Deseas restaurar el lote ${lote.codigo_lote}?`)) {
                return;
            }

            this.restaurandoId = lote.id_lote;

            axios.post(
                `http://localhost:8000/api/lotes/${lote.id_lote}/restaurar`
            )
            .then(response => {
                alert(response.data.message);

                if (this.lotes.length === 1 && this.pagination.current_page > 1) {
                    this.pagination.current_page--;
                }

                this.lotesGet();
            })
            .catch(error => {
                alert(
                    error.response?.data?.message ||
                    'No se pudo restaurar el lote.'
                );
            })
            .finally(() => {
                this.restaurandoId = null;
            });
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

        volver() {
            this.$router.push('/lotes');
        },

        formatearFecha(fecha) {
            if (!fecha) return '—';

            const partes = fecha.substring(0, 10).split('-');

            if (partes.length !== 3) return fecha;

            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        },

        formatearFechaHora(fecha) {
            if (!fecha) return '—';

            const valor = new Date(fecha);

            if (Number.isNaN(valor.getTime())) {
                return fecha;
            }

            return valor.toLocaleString('es-BO');
        }

    }
};
</script>

<style scoped>
.contenedor-papelera {
    width: 100%;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
}

.encabezado h1 {
    margin: 0 0 6px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
}

.btn-volver {
    background: #475569;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
}

.informacion {
    padding: 14px;
    background: #eff6ff;
    color: #1e40af;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}

.filtros {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filtros input,
.filtros select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.filtros input {
    flex: 1;
    min-width: 240px;
}

.tabla-contenedor {
    overflow-x: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
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
    white-space: nowrap;
}

.mensaje {
    text-align: center;
    padding: 25px;
    color: #6b7280;
}

.btn-restaurar {
    background: #16a34a;
    color: white;
    border: none;
    padding: 8px 13px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-restaurar:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 20px;
    font-size: 14px;
}

.controles {
    display: flex;
    align-items: center;
    gap: 12px;
}

.controles button {
    padding: 8px 13px;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    background: white;
    cursor: pointer;
}

.controles button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
