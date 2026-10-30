<template>
  <section class="categoria-aviso">
    <div class="categoria-icono" aria-hidden="true">▦</div>
    <h1>Administración de categorías</h1>
    <p>Las categorías se gestionan desde el formulario de productos para mantener un único registro centralizado.</p>
    <router-link to="/productos/crear?tab=categoria" class="categoria-enlace">Ir a categorías de productos</router-link>
  </section>
</template>
<style scoped>
.categoria-aviso{max-width:700px;margin:36px auto;padding:36px;text-align:center;background:#fff;border:1px solid #dce8df;border-top:5px solid #f47b20;border-radius:16px;box-shadow:0 10px 28px rgba(12,48,37,.06)}
.categoria-icono{font-size:40px;color:#124734}.categoria-aviso h1{color:#0c3025;font-size:25px}.categoria-aviso p{color:#62796c;line-height:1.6}.categoria-enlace{display:inline-block;margin-top:12px;background:#f47b20;color:white;text-decoration:none;border-radius:9px;padding:12px 20px;font-weight:700}
</style>
