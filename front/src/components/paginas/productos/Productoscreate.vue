<template>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Productos</h1>

    </div>
    <p>Formulario para crear productos</p>

    <form @submit.prevent="crearProducto">
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
            <input type="file" id="imagen" @change="handleFileUpload" required accept="image/*">
        </div>
        <!-- <div>
            <label for="estado">Estado:</label>
            <input type="checkbox" id="estado" v-model="producto.estado">
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
        <button type="submit">Crear Producto</button>

    </form>
<h2>
    Crear Categoria
</h2>
    <form @submit.prevent="crearCategoria">
        <div>
            <label for="nombre">Nombre de la categoria:</label>
            <input type="text" id="nombre" v-model="categoria.nombre" required>
        </div>
        <div>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" v-model="categoria.descripcion" required></textarea>
        </div>
        <button type="submit">Crear Categoria</button>
    </form>

</template>
<script>
import axios from 'axios';
export default {
    name : 'Productoscreate',
    data() {
        return {
            producto: {
                nombre: '',
                descripcion: '',
                imagen: '',
                estado: true,
                categoria_producto_id: ''
        },
        categorias: [],
        categoria: {
            nombre: '',
            descripcion: ''
        }   
        };
    },
    mounted() {
        this.categoriasGet();
    },
    methods:{
        crearCategoria() {
            axios.post('http://localhost:8000/api/categorias', this.categoria)
                .then(response => {
                    console.log('Categoria creada:', response.data);
                    this.categoria.nombre = '';
                    this.categoria.descripcion = '';    
                    this.categoriasGet(); // Actualizar la lista de categorías después de crear una nueva
                })
                .catch(error => {
                    console.error('Error al crear la categoría:', error);
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
        crearProducto(){
            const formData = new FormData();
            for (const key in this.producto) {
                formData.append(key, this.producto[key]);
            }
            formData.append('imagen', this.producto.imagen);
            axios.post('http://localhost:8000/api/productos', formData)
                .then(response => {
                    console.log(response.data);
                    this.$router.push('/productos');
                })
                .catch(error => {
                    console.error(error);
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