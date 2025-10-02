# Guía de Uso del Cart Store con Pinia

## 📚 Implementación Completada

Se ha implementado un sistema completo de carrito de compras usando **Pinia** con persistencia automática en **localStorage**.

---

## 🎯 Características

✅ **Persistencia automática** - El carrito se guarda automáticamente en localStorage  
✅ **Sincronización entre componentes** - Todos los componentes ven los mismos datos  
✅ **Eventos globales** - Los cambios se propagan a través de eventos del navegador  
✅ **Preparado para backend** - Métodos listos para sincronizar con Laravel API  
✅ **TypeScript friendly** - Tipado completo disponible  

---

## 🚀 Cómo Usar el Cart Store

### Opción 1: Usando el Composable (Recomendado)

```vue
<script setup>
import { useCart } from '@/composables/useCart'

// Obtener todas las funciones del carrito
const {
    items,           // Computed: array de items
    itemCount,       // Computed: total de items
    subtotal,        // Computed: subtotal del carrito
    total,           // Computed: total del carrito
    isEmpty,         // Computed: true si el carrito está vacío
    
    addItem,         // Function: agregar item
    removeItem,      // Function: remover item
    clearCart,       // Function: limpiar carrito
    hasItem,         // Function: verificar si existe item
    setMarket,       // Function: establecer market actual
} = useCart()

// Usar en el template o métodos
const handleAddToCart = (product) => {
    const success = addItem(product)
    if (success) {
        console.log('Item agregado!')
    } else {
        console.log('Item ya existe en el carrito')
    }
}

const handleRemove = (itemId) => {
    removeItem(itemId)
}

const checkIfInCart = (productId) => {
    return hasItem(productId)
}
</script>

<template>
    <div>
        <p>Items en carrito: {{ itemCount }}</p>
        <p>Total: ${{ total }}</p>
        
        <button @click="handleAddToCart(product)">
            Agregar al Carrito
        </button>
        
        <button v-if="!isEmpty" @click="clearCart">
            Limpiar Carrito
        </button>
    </div>
</template>
```

### Opción 2: Usando el Store Directamente

```vue
<script setup>
import { useCartStore } from '@/stores/cartStore'
import { computed } from 'vue'

const cartStore = useCartStore()

// Acceso directo a state y getters
const items = computed(() => cartStore.items)
const itemCount = computed(() => cartStore.itemCount)

// Llamar acciones directamente
const addToCart = (product) => {
    cartStore.addItem(product)
}
</script>
```

---

## 📦 Estructura de un Item del Carrito

```javascript
{
    id: 123,                    // ID del producto (requerido)
    model: "iPhone 13 Pro",     // Modelo del producto
    manufacturer: "Apple",      // Fabricante
    selling_price: 899.99,      // Precio de venta
    quantity: 1,                // Cantidad (siempre 1 para refurbished)
    type: "smartphone",         // Tipo de producto
    imei: "123456789012345",    // IMEI del dispositivo
    issues: "Screen scratches", // Problemas conocidos
    addedAt: "2025-10-01T..."  // Timestamp de cuando se agregó
}
```

---

## 🔧 Métodos Disponibles

### `setMarket(slug: string)`
Establece el market actual. Si cambias de market, el carrito se limpia automáticamente.

```javascript
cartStore.setMarket('my-market-slug')
```

### `addItem(product: Object): boolean`
Agrega un item al carrito. Retorna `true` si se agregó, `false` si ya existía.

```javascript
const added = cartStore.addItem({
    id: 123,
    model: "iPhone 13",
    manufacturer: "Apple",
    selling_price: 899.99,
    quantity: 1,
    type: "smartphone",
    imei: "123456789012345"
})

if (!added) {
    alert('Este producto ya está en tu carrito')
}
```

### `removeItem(itemId: number)`
Remueve un item del carrito por su ID.

```javascript
cartStore.removeItem(123)
```

### `clearCart()`
Limpia todos los items del carrito.

```javascript
cartStore.clearCart()
```

### `hasItem(productId: number): boolean`
Verifica si un producto está en el carrito.

```javascript
const isInCart = cartStore.hasItem(123)
```

### `getItem(productId: number): Object | undefined`
Obtiene un item específico del carrito.

```javascript
const item = cartStore.getItem(123)
if (item) {
    console.log('Precio:', item.selling_price)
}
```

---

## 🌐 Eventos Globales

El cart store emite eventos globales que puedes escuchar:

```javascript
// Escuchar cambios en el carrito
window.addEventListener('cart-updated', (event) => {
    const { action, itemId, totalCount } = event.detail
    
    console.log(`Acción: ${action}`) // 'added', 'removed', 'updated', 'cleared'
    console.log(`Item ID: ${itemId}`)
    console.log(`Total items: ${totalCount}`)
})

// Ejemplo en un componente
onMounted(() => {
    const handleCartUpdate = (event) => {
        console.log('Carrito actualizado!', event.detail)
    }
    
    window.addEventListener('cart-updated', handleCartUpdate)
    
    onUnmounted(() => {
        window.removeEventListener('cart-updated', handleCartUpdate)
    })
})
```

---

## 💾 Persistencia

El carrito se guarda automáticamente en `localStorage` con la clave:
```
refreshm-ecommerce-cart
```

Puedes ver el contenido en las DevTools:
```javascript
// En la consola del navegador
localStorage.getItem('refreshm-ecommerce-cart')
```

---

## 🔄 Sincronización con Backend (Preparado)

Los métodos de sincronización están listos para usar cuando implementes el backend:

```javascript
// Sincronizar carrito actual al backend
await cartStore.syncToBackend()

// Cargar carrito desde el backend
await cartStore.loadFromBackend('market-slug')
```

Para activarlos, ve al archivo `CART_BACKEND_IMPLEMENTATION.md` y sigue las instrucciones.

---

## 📱 Ejemplo Completo: ProductCard con Cart

```vue
<template>
    <div class="product-card">
        <h3>{{ product.model }}</h3>
        <p>${{ product.selling_price }}</p>
        
        <button 
            v-if="!isInCart"
            @click="addToCart"
            class="btn-primary"
        >
            <i class="pi pi-shopping-cart"></i>
            Agregar al Carrito
        </button>
        
        <button 
            v-else
            @click="removeFromCart"
            class="btn-danger"
        >
            <i class="pi pi-trash"></i>
            Remover del Carrito
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useCart } from '@/composables/useCart'

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
})

const { addItem, removeItem, hasItem } = useCart()

const isInCart = computed(() => hasItem(props.product.id))

const addToCart = () => {
    const success = addItem(props.product)
    if (success) {
        // Mostrar toast de éxito
        console.log('Producto agregado al carrito')
    }
}

const removeFromCart = () => {
    removeItem(props.product.id)
    console.log('Producto removido del carrito')
}
</script>
```

---

## 🐛 Debugging

Para ver los logs del cart store en la consola:

```javascript
// Todos los métodos tienen logs con el prefijo [CartStore]
console.log(cartStore.items)           // Ver items actuales
console.log(cartStore.itemCount)       // Ver cantidad de items
console.log(cartStore.total)           // Ver total
```

---

## 📊 Estado del Carrito en Vue DevTools

Si tienes Vue DevTools instalado:

1. Abre las DevTools
2. Ve a la pestaña "Pinia"
3. Selecciona "cart"
4. Verás todo el estado en tiempo real

---

## ⚠️ Notas Importantes

1. **Unique Items**: Para productos refurbished, no se permiten duplicados (quantity siempre es 1)
2. **Market Switch**: Al cambiar de market, el carrito se limpia automáticamente
3. **Persistencia**: El carrito persiste entre recargas de página
4. **Reactivity**: Todos los cambios son reactivos y se reflejan inmediatamente en la UI

---

## 🎓 Mejores Prácticas

1. **Usa el composable** `useCart()` en lugar del store directamente
2. **Verifica éxito** al agregar items (puede fallar si ya existe)
3. **Escucha eventos** globales para sincronizar componentes externos
4. **Establece market** al montar componentes de ecommerce
5. **No modifiques** items directamente, usa los métodos del store

---

## 📚 Archivos Relacionados

- **Store**: `resources/js/stores/cartStore.js`
- **Composable**: `resources/js/composables/useCart.js`
- **Componente Cart**: `resources/js/Components/Ecommerce/Cart.vue`
- **Página OrderReview**: `resources/js/Pages/Ecommerce/PublicMarket/OrderReview.vue`
- **Configuración App**: `resources/js/app.js`
- **Guía Backend**: `CART_BACKEND_IMPLEMENTATION.md`

