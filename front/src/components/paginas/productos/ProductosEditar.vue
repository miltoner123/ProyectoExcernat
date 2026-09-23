<template>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Productos</h1>

    </div>
    <p>Formulario para editar productos</p>

    <form @submit.prevent="ProductoEditar">
        <div>
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" v-model="producto.nombre" required>
        </div>
        <div>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" v-model="producto.descripcion" required></textarea>
        </div>
        <div>
            <label for="imagen">Imagen:</label>
            <input type="file" id="imagen" @change="handleFileUpload" accept="image/*">
        </div>
        <!-- <div>
            <label for="activo">Activo:</label>
            <input type="checkbox" id="activo" v-model="activo">
        </div> -->
        <div>
            <label for="categoria_id">Categoría:</label>
            <select id="categoria_id" v-model="producto.categoria_id" required>
                <option value="" disabled>Seleccione una categoría</option>
                <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                    {{ categoria.nombre }}
                </option>
            </select>
        </div>
        <button type="submit">Editar Producto</button>

    </form>
<pre>{{ categorias}} hola </pre>
</template>
<script>
import axios from 'axios';
export default {
    name : 'Productoseditar',
    data() {
        return {
            producto: {
                nombre: '',
                descripcion: '',
                imagen: '',
                activo: true,
                categoria_producto_id: ''
        },
        categorias: []
        };
    },
    mounted() {
        this.categoriasGet();
        this.productoGet();
    },
    methods:{
        productoGet() {
            const productoId = this.$route.params.id;
            axios.get(`http://localhost:8000/api/productos/${productoId}`)
                .then(response => {
                    this.producto = response.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        handleFileUpload(event) {
            const file = event.target.files[0];
            this.producto.imagen = file;
        },
        async categoriasGet() {
            axios.get('http://localhost:8000/api/categorias')
                .then(response => {
                    this.categorias = response.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        ProductoEditar(){
            const formData = new FormData();
            formData.append('nombre', this.producto.nombre);
            formData.append('descripcion', this.producto.descripcion);
            formData.append('categoria_id', this.producto.categoria_id);
            formData.append('activo', this.producto.activo);
            formData.append('imagen', this.producto.imagen);

            axios.post(`http://localhost:8000/api/productos/${this.$route.params.id}`, formData,{
                headers: {
                    'Content-Type': 'multipart/form-data'   
                },
            })
            .then((response) => {
                console.log('Producto editado:', response.data);
                this.$router.push('/productos');
            })
            .catch((error) => {
                console.error('Error al editar el producto:', error);
            });

        }
    }
}

</script>
<style scoped>
    form {
        display: flex;
        flex-direction: column;
        max-width: 400px;
    }

    div {
        margin-bottom: 10px;
    }

    label {
        font-weight: bold;
    }

    input, textarea, select {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;
    }

    button {
        padding: 10px;
        background-color: #007BFF;
        color: white;
        border: none;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }
</style>