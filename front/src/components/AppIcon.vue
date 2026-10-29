<script setup>
import { computed } from 'vue'
const props=defineProps({name:{type:String,default:'package'}})
const paths={menu:'M4 6h16M4 12h16M4 18h16',dashboard:'M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z',package:'M12 2 3 7l9 5 9-5-9-5ZM3 7v10l9 5 9-5V7M12 12v10',layers:'m12 2-9 5 9 5 9-5-9-5ZM3 12l9 5 9-5M3 17l9 5 9-5',tag:'M2 12V3h9l11 11-8 8L2 12ZM7 7h.01',pin:'M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0ZM12 10h.01',arrows:'M8 3 4 7l4 4M4 7h16M16 21l4-4-4-4M20 17H4',clipboard:'M8 4h8M9 2h6v4H9zM5 4H4v18h16V4h-1M8 11h8M8 15h8M8 19h6',calendar:'M3 6h18v15H3zM7 3v6M17 3v6M3 11h18',users:'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',briefcase:'M3 7h18v14H3zM8 7V4h8v3M3 13h18',shield:'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4'}
const d=computed(()=>paths[props.name]||paths.package)
</script>
<template><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="d"/></svg></template>
