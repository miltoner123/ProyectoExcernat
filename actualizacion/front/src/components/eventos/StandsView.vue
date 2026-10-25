<template>
    <div class="stands">

        <div class="encabezado">
            <div>
                <h1>Stands del Evento</h1>
                <p v-if="evento">
                    {{ evento.nombre }} - {{ evento.departamento }}
                </p>
            </div>

            <div class="botones">
                <button @click="nuevoStand">
                    + Nuevo Stand
                </button>

                <button
                    class="btn-volver"
                    @click="$router.push('/eventos')"
                >
                    Volver
                </button>
            </div>
        </div>

        <p v-if="error" class="mensaje-error">{{ error }}</p>

        <div v-if="cargando">
            Cargando stands...
        </div>

        <div v-else-if="!error" class="tabla-contenedor">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Stand</th>
                        <th>Propiedad</th>
                        <th>Departamento</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="stand in stands"
                        :key="stand.id_ubicacion"
                    >
                        <td>{{ stand.id_ubicacion }}</td>

                        <td>
                            <strong>
                                {{ stand.nombre }}
                            </strong>
                        </td>

                        <td>
                            {{ stand.propiedad }}
                        </td>

                        <td>
                            {{ stand.departamento }}
                        </td>

                        <td>
                            {{ stand.direccion || '—' }}
                        </td>

                        <td>
                            <span
                                :class="stand.estado
                                    ? 'activo'
                                    : 'inactivo'"
                            >
                                {{ stand.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>

                    <tr v-if="stands.length === 0">
                        <td colspan="6" class="sin-registros">
                            Este evento todavía no tiene stands registrados.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>
</template>

<script>
import api from '../../services/api';

export default {

    data() {
        return {
            evento: null,
            stands: [],
            cargando: true,
            error: ''
        };
    },

    mounted() {
        this.cargarDatos();
    },

    methods: {

        async cargarDatos() {
            this.cargando = true;
            this.error = '';
            try {
                const id = this.$route.params.id;
                const eventoResponse = await api.get(`/api/eventos/${id}`);
                this.evento = eventoResponse.data;
                const resultado = [];
                let pagina = 1;
                let ultimaPagina = 1;
                do {
                    const response = await api.get('/api/ubicaciones', {
                        params: { id_evento: id, tipo: 'STAND', limit: 50, page: pagina }
                    });
                    const datos = response.data;
                    resultado.push(...(Array.isArray(datos) ? datos : (datos.data ?? [])));
                    ultimaPagina = Number(datos.last_page ?? 1);
                    pagina++;
                } while (pagina <= ultimaPagina);
                this.stands = resultado;
            } catch (error) {
                console.error('Error al cargar stands:', error);
                this.error = error.response?.data?.message || 'No se pudieron cargar los stands del evento.';
                this.stands = [];
            } finally {
                this.cargando = false;
            }
        },

        nuevoStand() {

            const id = this.$route.params.id;

            this.$router.push(
                `/ubicaciones/crear?tipo=STAND&id_evento=${id}`
            );

        }

    }

};
</script>

<style scoped>

.stands {
    width: 100%;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.botones {
    display: flex;
    gap: 10px;
}

.botones button {
    border: none;
    background-color: #2563eb;
    color: white;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
}

.botones .btn-volver {
    background-color: #6b7280;
}

.tabla-contenedor {
    background-color: white;
    border-radius: 8px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
}

th {
    background-color: #f9fafb;
    color: #374151;
}

.activo {
    background-color: #dcfce7;
    color: #166534;
    padding: 5px 8px;
    border-radius: 5px;
}

.inactivo {
    background-color: #fee2e2;
    color: #991b1b;
    padding: 5px 8px;
    border-radius: 5px;
}

.mensaje-error { color: #b91c1c; margin-bottom: 15px; }

.sin-registros {
    text-align: center;
    color: #6b7280;
    padding: 25px;
}

</style>