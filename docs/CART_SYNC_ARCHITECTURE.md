# Arquitectura de Sincronización del Carrito

## 🏗️ Flujo de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    Pinia Cart Store                          │
│                  (Single Source of Truth)                    │
│                                                              │
│  - items: []                                                 │
│  - itemCount: computed                                       │
│  - subtotal: computed                                        │
│  - total: computed                                           │
│                                                              │
│  Persistencia: localStorage                                  │
│  Key: 'refreshm-ecommerce-cart'                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (reactive)
                            ▼
        ┌───────────────────────────────────────┐
        │                                       │
        ▼                                       ▼
┌───────────────┐                    ┌──────────────────┐
│ MarketLayout  │                    │   Cart.vue       │
│               │                    │   (Drawer)       │
│ cartCount ────┼──► computed from   │                  │
│ (computed)    │    cartStore       │  items ◄─────────┼─ computed
│               │                    │  itemCount       │  from store
└───────────────┘                    └──────────────────┘
        │                                       │
        │                                       │
        ▼                                       ▼
┌───────────────┐                    ┌──────────────────┐
│ ProductCard   │                    │  OrderReview     │
│               │                    │                  │
│ useCart() ────┼──► composable      │  cartStore ◄─────┼─ direct access
│ addItem()     │    wrapper         │  items           │
└───────────────┘                    └──────────────────┘
```

## 🔄 Cómo Funciona la Sincronización

### 1. **Al Cargar la Página** (Page Load)

```javascript
// MarketLayout.vue - onMounted()
onMounted(() => {
    // Inicializa el store con el market actual
    cartStore.setMarket(props.market.slug)
    
    // El cartCount es computed, por lo que se actualiza automáticamente
    // desde el store que ya tiene los items persistidos en localStorage
    console.log('Initial cart count:', cartStore.itemCount)
})
```

**Secuencia:**
1. ✅ Pinia carga el store
2. ✅ Plugin de persistencia restaura items desde localStorage
3. ✅ `cartCount` (computed) se actualiza automáticamente con el valor correcto
4. ✅ Badge del header muestra el número correcto

### 2. **Al Agregar un Item**

```javascript
// ProductCard.vue o cualquier componente
const { addItem } = useCart()

const handleAddToCart = () => {
    const success = addItem(product)
    // ✅ Store actualizado
    // ✅ localStorage actualizado (automático)
    // ✅ Todos los computed properties actualizados (automático)
    // ✅ Badge del header actualizado (automático)
}
```

**Secuencia:**
1. ✅ `cartStore.addItem()` modifica el array `items`
2. ✅ Plugin de persistencia guarda en localStorage (automático)
3. ✅ `cartStore.itemCount` (computed) se recalcula (automático)
4. ✅ `MarketLayout.cartCount` (computed) se actualiza (automático)
5. ✅ UI se actualiza en todos los componentes (automático)

### 3. **Al Remover un Item**

```javascript
// Cart.vue o OrderReview.vue
const removeItem = (itemId) => {
    cartStore.removeItem(itemId)
    // Todo se actualiza automáticamente
}
```

**Secuencia:**
1. ✅ `cartStore.removeItem()` elimina del array
2. ✅ Persistencia automática a localStorage
3. ✅ Todos los computed se recalculan
4. ✅ UI actualizada en tiempo real

### 4. **Al Limpiar el Carrito**

```javascript
cartStore.clearCart()
// items = []
// itemCount = 0
// cartCount badge = 0
// Todo actualizado automáticamente
```

## 🎯 Ventajas de esta Arquitectura

### ✅ **Single Source of Truth**
- Solo un lugar donde se guardan los items del carrito
- No hay riesgo de desincronización entre componentes

### ✅ **Reactividad Automática**
- Vue detecta todos los cambios automáticamente
- No necesitas emitir eventos manualmente
- No necesitas callbacks complicados

### ✅ **Persistencia Transparente**
- El plugin guarda automáticamente en localStorage
- No necesitas `watch()` ni `onBeforeUnmount()`
- Funciona incluso si cierras el navegador

### ✅ **Computed Properties**
- `itemCount`, `subtotal`, `total` se calculan automáticamente
- Siempre están actualizados
- Performance optimizada por Vue

### ✅ **Type Safety** (opcional)
- Puedes agregar TypeScript fácilmente
- Autocomplete en el IDE
- Menos bugs

## 🔍 Debugging

### Ver el estado actual del store

```javascript
// En la consola del navegador
const { useCartStore } = await import('/resources/js/stores/cartStore.js')
const cartStore = useCartStore()

console.log('Items:', cartStore.items)
console.log('Count:', cartStore.itemCount)
console.log('Total:', cartStore.total)
```

### Ver localStorage

```javascript
// En la consola
const cart = JSON.parse(localStorage.getItem('refreshm-ecommerce-cart'))
console.log(cart)
```

### Vue DevTools

1. Abre Vue DevTools
2. Ve a la pestaña "Pinia"
3. Selecciona el store "cart"
4. Verás todo el estado en tiempo real

## 🚫 Qué NO Hacer

### ❌ No mantengas estado local del carrito

```javascript
// ❌ MALO
const items = ref([]) // No hagas tu propia copia
const count = ref(0)  // No calcules manualmente

// ✅ BUENO
const { items, itemCount } = useCart() // Usa el store
```

### ❌ No emitas eventos innecesarios

```javascript
// ❌ MALO
emit('cart-updated', newCount) // No es necesario

// ✅ BUENO
cartStore.addItem(product) // El store maneja todo
```

### ❌ No guardes en localStorage manualmente

```javascript
// ❌ MALO
localStorage.setItem('cart', JSON.stringify(items))

// ✅ BUENO
cartStore.addItem(product) // Persistencia automática
```

## 📊 Performance

### Optimizaciones Incluidas

1. **Computed Caching**: Vue cachea valores computed
2. **Batch Updates**: Vue agrupa múltiples cambios
3. **Shallow Reactivity**: Solo propiedades usadas disparan re-render
4. **LocalStorage Throttling**: Plugin optimiza escrituras

### Mediciones Típicas

- Agregar item: < 1ms
- Calcular total: < 0.1ms
- Guardar en localStorage: < 5ms
- Actualizar UI: < 16ms (1 frame)

## 🔐 Seguridad

### Validación en Backend (Próxima Fase)

Cuando implementes el backend:

```javascript
// Frontend envía solo IDs
await cartStore.syncToBackend()
// POST /api/market/{slug}/cart/sync
// { items: [{ id: 1, quantity: 1 }, ...] }

// Backend valida:
// - Item existe
// - Item disponible
// - Precio correcto
// - Stock disponible
```

### No confíes en localStorage

- Los precios se verifican en el backend
- La disponibilidad se verifica en el backend
- localStorage es solo para UX, no para seguridad

## 📚 Recursos

- [Pinia Docs](https://pinia.vuejs.org/)
- [Pinia Persistence Plugin](https://prazdevs.github.io/pinia-plugin-persistedstate/)
- [Vue Reactivity](https://vuejs.org/guide/essentials/reactivity-fundamentals.html)

## 🎓 Ejemplo Completo: Nuevo Componente

```vue
<template>
    <div>
        <p>Carrito: {{ itemCount }} items</p>
        <p>Total: ${{ total }}</p>
        
        <button @click="add">Agregar</button>
        <button @click="clear">Limpiar</button>
    </div>
</template>

<script setup>
import { useCart } from '@/composables/useCart'

const { 
    items, 
    itemCount, 
    total, 
    addItem, 
    clearCart 
} = useCart()

const add = () => {
    addItem({
        id: 123,
        model: 'iPhone 13',
        manufacturer: 'Apple',
        selling_price: 899.99,
        quantity: 1,
        type: 'smartphone'
    })
}

const clear = () => {
    clearCart()
}
</script>
```

---

**Última actualización:** Octubre 1, 2025  
**Autor:** Sistema de Carrito con Pinia  
**Versión:** 1.0
