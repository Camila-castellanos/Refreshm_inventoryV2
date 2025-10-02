# Carrito de Compras - Backend Implementation Guide

## 📋 Estado Actual

El carrito de compras está implementado completamente en el frontend usando:
- ✅ **Pinia** como state manager
- ✅ **pinia-plugin-persistedstate** para persistencia en localStorage
- ✅ **Eventos globales** para sincronización entre componentes

### Archivos Implementados

1. **Store**: `resources/js/stores/cartStore.js`
2. **Composable**: `resources/js/composables/useCart.js`
3. **Componentes actualizados**:
   - `resources/js/Components/Ecommerce/Cart.vue`
   - `resources/js/Pages/Ecommerce/PublicMarket/OrderReview.vue`

---

## 🚀 Backend Implementation (Pendiente)

### Paso 1: Crear el Controller

Crear: `app/Http/Controllers/Ecommerce/CartController.php`

```php
<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Sync cart items to backend (session or database)
     * POST /api/market/{market}/cart/sync
     */
    public function sync(Request $request, Market $market)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            // Store in session (simple approach)
            session()->put("cart.{$market->slug}", [
                'items' => $request->items,
                'updated_at' => now()
            ]);

            // OR: Store in database for logged-in users
            // if (auth()->check()) {
            //     auth()->user()->carts()->updateOrCreate(
            //         ['market_id' => $market->id],
            //         ['items' => json_encode($request->items)]
            //     );
            // }

            return response()->json([
                'success' => true,
                'message' => 'Cart synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cart sync error', [
                'market' => $market->slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync cart'
            ], 500);
        }
    }

    /**
     * Get cart items from backend
     * GET /api/market/{market}/cart
     */
    public function get(Market $market)
    {
        try {
            // Get from session
            $cartData = session()->get("cart.{$market->slug}", [
                'items' => [],
                'updated_at' => null
            ]);

            // OR: Get from database for logged-in users
            // if (auth()->check()) {
            //     $cart = auth()->user()->carts()
            //         ->where('market_id', $market->id)
            //         ->first();
            //     
            //     if ($cart) {
            //         $cartData = [
            //             'items' => json_decode($cart->items, true),
            //             'updated_at' => $cart->updated_at
            //         ];
            //     }
            // }

            // Validate items still exist and are available
            $validatedItems = [];
            foreach ($cartData['items'] as $cartItem) {
                $item = Item::where('id', $cartItem['id'])
                    ->where('shop_id', $market->shop_id)
                    ->where('sold', false)
                    ->where('hold', false)
                    ->first();

                if ($item) {
                    $validatedItems[] = array_merge($cartItem, [
                        'model' => $item->model,
                        'manufacturer' => $item->manufacturer,
                        'selling_price' => $item->selling_price,
                        'imei' => $item->imei
                    ]);
                }
            }

            return response()->json([
                'items' => $validatedItems,
                'updated_at' => $cartData['updated_at']
            ]);
        } catch (\Exception $e) {
            Log::error('Cart get error', [
                'market' => $market->slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'items' => [],
                'error' => 'Failed to load cart'
            ], 500);
        }
    }

    /**
     * Clear cart
     * DELETE /api/market/{market}/cart
     */
    public function clear(Market $market)
    {
        try {
            session()->forget("cart.{$market->slug}");

            // OR: Clear from database
            // if (auth()->check()) {
            //     auth()->user()->carts()
            //         ->where('market_id', $market->id)
            //         ->delete();
            // }

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cart clear error', [
                'market' => $market->slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart'
            ], 500);
        }
    }
}
```

### Paso 2: Registrar las Rutas API

En `routes/api.php`:

```php
use App\Http\Controllers\Ecommerce\CartController;

// Cart API routes (no authentication required for now)
Route::prefix('market/{market:slug}')->group(function () {
    Route::post('/cart/sync', [CartController::class, 'sync'])->name('api.cart.sync');
    Route::get('/cart', [CartController::class, 'get'])->name('api.cart.get');
    Route::delete('/cart', [CartController::class, 'clear'])->name('api.cart.clear');
});

// For authenticated users (future implementation)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('market/{market:slug}')->group(function () {
        // Route::post('/cart/save', [CartController::class, 'save'])->name('api.cart.save');
        // Route::get('/cart/history', [CartController::class, 'history'])->name('api.cart.history');
    });
});
```

### Paso 3: (Opcional) Crear Modelo de Base de Datos

Si quieres guardar carritos en la base de datos para usuarios autenticados:

**Migración**: `database/migrations/xxxx_create_carts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->string('session_id')->nullable()->index(); // For guest users
            $table->json('items'); // Cart items as JSON
            $table->timestamp('last_accessed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'market_id']);
            $table->index(['session_id', 'market_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
```

**Modelo**: `app/Models/Ecommerce/Cart.php`

```php
<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'market_id',
        'session_id',
        'items',
        'last_accessed_at'
    ];

    protected $casts = [
        'items' => 'array',
        'last_accessed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}
```

### Paso 4: Activar Sincronización en el Frontend

En `resources/js/stores/cartStore.js`, descomentar las líneas de sincronización:

```javascript
// Línea ~120
const syncToBackend = async () => {
    if (!marketSlug.value) {
        console.warn('[CartStore] Cannot sync: No market slug set')
        return
    }

    try {
        // DESCOMENTAR ESTO:
        const response = await axios.post(`/api/market/${marketSlug.value}/cart/sync`, {
            items: items.value.map(item => ({
                id: item.id,
                quantity: item.quantity
            }))
        })
        console.log('[CartStore] Synced to backend:', response.data)
    } catch (error) {
        console.error('[CartStore] Failed to sync cart to backend:', error)
    }
}

// Línea ~140
const loadFromBackend = async (slug) => {
    try {
        // DESCOMENTAR ESTO:
        const response = await axios.get(`/api/market/${slug}/cart`)
        if (response.data.items && Array.isArray(response.data.items)) {
            items.value = response.data.items
            marketSlug.value = slug
            console.log('[CartStore] Loaded cart from backend:', items.value.length, 'items')
        }
    } catch (error) {
        console.error('[CartStore] Failed to load cart from backend:', error)
    }
}
```

### Paso 5: Llamar `loadFromBackend` al Cargar la Página

En los componentes que necesiten cargar el carrito desde el backend:

```javascript
// En OrderReview.vue o MarketLayout.vue
onMounted(async () => {
    if (props.market?.slug) {
        cartStore.setMarket(props.market.slug)
        
        // Cargar desde backend
        await cartStore.loadFromBackend(props.market.slug)
    }
})
```

---

## 🎯 Ventajas de Implementar el Backend

1. **Carritos entre dispositivos**: Los usuarios pueden continuar sus compras desde otro dispositivo
2. **Análisis de carritos abandonados**: Track de qué productos interesan pero no se compran
3. **Recuperación de carritos**: Email marketing para recuperar ventas
4. **Validación en tiempo real**: Verificar que los productos aún están disponibles
5. **Historial de carritos**: Para usuarios registrados

---

## 📊 Roadmap de Implementación

### Fase 1: MVP (Simple Session Storage)
- [ ] Crear CartController con métodos básicos
- [ ] Registrar rutas API
- [ ] Descomentar sincronización en cartStore.js
- [ ] Probar sincronización

### Fase 2: Database Storage (Para usuarios registrados)
- [ ] Crear migración y modelo Cart
- [ ] Actualizar CartController para usar DB
- [ ] Implementar autenticación
- [ ] Migrar carritos de session a DB al hacer login

### Fase 3: Features Avanzados
- [ ] Carritos abandonados tracking
- [ ] Email notifications
- [ ] Analytics de productos más agregados
- [ ] Wishlists separadas del carrito
- [ ] Compartir carrito (link único)

---

## 🔧 Testing

```bash
# Probar sincronización
POST /api/market/{slug}/cart/sync
{
  "items": [
    { "id": 1, "quantity": 1 },
    { "id": 2, "quantity": 1 }
  ]
}

# Obtener carrito
GET /api/market/{slug}/cart

# Limpiar carrito
DELETE /api/market/{slug}/cart
```

---

## 📝 Notas

- El frontend funciona 100% sin backend (offline-first)
- localStorage se usa como cache principal
- Backend es opcional para sincronización y features avanzados
- La implementación actual permite agregar backend gradualmente sin romper nada

