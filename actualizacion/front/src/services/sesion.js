import { reactive } from 'vue';
export const sesion = reactive({ usuario: null, comprobada: false });
export function establecerUsuario(usuario) { sesion.usuario = usuario; sesion.comprobada = true; }
