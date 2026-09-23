<template>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Productos</h1>
    <span> Esta es la vista de Productos</span>
        <button @click="crearProducto">Crear Producto</button>

    </div>
    <div style="">
        <button @click="pagination.current_page -- ; productosGet()">Anterior</button>
        <button @click="pagination.current_page ++ ; productosGet()">Siguiente</button>

    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Imagen</th>
                <th>Categoría</th>
                <th>Activo</th>
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="producto in productos" :key="producto.id">
                <td>{{ producto.id }}</td>
                <td>{{ producto.nombre }}</td>
                <td>{{ producto.descripcion }}</td>
                <td>
                    <img :src="'http://localhost:8000/imagenes/' + producto.imagen" alt="Imagen del producto" style="max-width: 45px; max-height: 100px;" v-if="producto.imagen">
                </td>
                <td>{{ producto.categoria.nombre }}</td>
                <td>{{ producto.activo }}</td>
                <td>
                    <button @click="editarProducto(producto.id)">Editar</button>
                    <button @click="eliminarProducto(producto.id)">Eliminar</button>
                </td>
            </tr>
        </tbody>
    </table>
</template>
<script>
import axios from 'axios';
export default {

    data() {
        return {
            productos: [],
            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }
        }
    },
    mounted() {
        this.productosGet();
    },
    methods: {
        productosGet() {
            axios.get('http://localhost:8000/api/productos?limit=10' + '&page=' + this.pagination.current_page)
                .then(response => {
                    this.productos = response.data.data;
                    this.pagination.totalPages = response.data.last_page;
                    this.pagination.total = response.data.total;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        crearProducto() {
            this.$router.push('/productos/crear');
        },
        eliminarProducto(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
                axios.delete(`http://localhost:8000/api/productos/${id}`)
                    .then(response => {
                        this.productosGet();
                    })
                    .catch(error => {
                        console.error(error);
                    });
            }
        },
        editarProducto(id) {
            this.$router.push(`/productos/editar/${id}`);
        }

    }

}
</script>
<style scoped>

/* =========================================
   TÍTULO
========================================= */

h1 {
    font-size: 26px;
    font-weight: 600;
    color: #1f2937;

    margin-bottom: 25px;

    padding-bottom: 12px;

    border-bottom: 2px solid #e5e7eb;
}


/* =========================================
   TABLA
========================================= */

table {
    width: 100%;

    border-collapse: collapse;

    background-color: #ffffff;

    border-radius: 8px;

    overflow: hidden;

    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}


/* =========================================
   CABECERA DE LA TABLA
========================================= */

thead {
    background-color: #1f2937;
}

thead th {
    color: white;

    font-size: 14px;

    font-weight: 600;

    text-align: left;

    padding: 14px 15px;

    border-bottom: 2px solid #374151;
}


/* =========================================
   FILAS
========================================= */

tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:nth-child(even) {
    background-color: #f9fafb;
}

tbody tr:hover {
    background-color: #eef4ff;
}


/* =========================================
   CELDAS
========================================= */

tbody td {
    padding: 13px 15px;

    border-bottom: 1px solid #e5e7eb;

    color: #374151;

    font-size: 14px;

    vertical-align: middle;
}


/* ID */

tbody td:first-child {
    font-weight: 600;

    color: #6b7280;

    width: 70px;
}


/* =========================================
   COLUMNA DESCRIPCIÓN
========================================= */

tbody td:nth-child(3) {
    max-width: 300px;

    line-height: 1.5;
}


/* =========================================
   BOTONES
========================================= */

button {
    border: none;

    padding: 8px 13px;

    border-radius: 5px;

    cursor: pointer;

    font-size: 13px;

    font-weight: 500;

    margin-right: 6px;

    transition: all 0.2s ease;
}


/* BOTÓN EDITAR */

button:first-child {
    background-color: #f59e0b;

    color: white;
}

button:first-child:hover {
    background-color: #d97706;

    transform: translateY(-1px);
}


/* BOTÓN ELIMINAR */

button:last-child {
    background-color: #dc2626;

    color: white;
}

button:last-child:hover {
    background-color: #b91c1c;

    transform: translateY(-1px);
}


/* =========================================
   COLUMNA OPCIONES
========================================= */

td:last-child {
    white-space: nowrap;
}


/* =========================================
   IMAGEN
========================================= */

td:nth-child(4) {
    color: #6b7280;

    font-size: 13px;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 900px) {

    table {
        display: block;

        overflow-x: auto;

        white-space: nowrap;
    }

}


/* =========================================
   RESPONSIVE CELULAR
========================================= */

@media (max-width: 600px) {

    h1 {
        font-size: 21px;
    }

    thead th,
    tbody td {
        padding: 10px;

        font-size: 12px;
    }

    button {
        padding: 6px 9px;

        font-size: 11px;
    }

}

</style>