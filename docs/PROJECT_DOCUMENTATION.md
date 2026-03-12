# Refresh Mobile Inventory System - Project Documentation

## Overview

**Refresh Mobile InventoryV2** is a comprehensive inventory management system for mobile phone retailers. Built with **Laravel 11** (backend) and **Vue.js 3** with Inertia.js (frontend), it supports multi-tenancy, e-commerce, and full inventory tracking.

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2, Laravel 11.31 |
| Frontend | Vue.js 3 + TypeScript + Inertia.js |
| Build Tool | Vite 6.0 |
| CSS | Tailwind CSS 3.4 |
| UI Components | PrimeVue 4.3 |
| Authentication | Laravel Jetstream + Sanctum |
| Media | Spatie Media Library |
| Database Queries | Spatie Query Builder |
| Testing | PHPUnit (Backend), Vitest (Frontend) |

---

## Architecture

### Multi-Tenancy System

The system uses a **company-based multi-tenancy** model:

```
Company (1) ──────< User (Many)
      │
      ├────< Shop (Many)
      │          └────< Item (Many)
      │
      └────< Storage (Many)
                 └────< Item (Many)
```

### User Roles

| Role | Description |
|------|-------------|
| `OWNER` | Company owner with full access |
| `ADMIN` | Administrator with elevated privileges |
| `USER` | Regular user with basic access |

---

## Database Models

### Core Models

#### Item (Main Inventory Model)

Represents a product in the inventory (phones, accessories, etc.)

**File:** `app/Models/Item.php`

**Fillable Fields:**
- `date`, `sale_id`, `supplier`, `manufacturer`, `storage_id`, `position`
- `model`, `colour`, `battery`, `grade`, `issues`, `cost`, `imei`, `selling_price`
- `customer`, `sold`, `hold`, `discount`, `tax`, `subtotal`, `profit`
- `user_id`, `vendor_id`, `custom_values`, `shop_id`, `type`, `product_model_id`
- `sold_storage_id`, `sold_position`, `sold_storage_name`

**Relationships:**
```php
$item->shop        // BelongsTo Shop
$item->sale        // BelongsTo Sale
$item->vendor      // BelongsTo Vendor
$item->storage     // BelongsTo Storage
$item->productModel // BelongsTo ProductModel
$item->tabItems    // BelongsTo TabItem
```

**Global Scopes:**
- `CompanyItemScope` - Filters items by company of authenticated user

**Key Methods:**
- `removeSale()` - Removes item from sale and restores to inventory
- `getNextAvailablePosition($storageId)` - Finds next free position
- Auto-assigns position on creation if storage is provided

#### Company

Represents a business entity in the system.

**File:** `app/Models/Company.php`

**Fillable:** `name`, `owner_id`

**Relationships:**
```php
$company->owner    // BelongsTo User
$company->users    // HasMany User
$company->shops   // HasMany Shop
$company->items   // HasManyThrough Item (via Shop)
$company->storages // HasMany Storage
```

#### Shop

Represents a physical or online store belonging to a company.

**File:** `app/Models/Shop.php`

**Fillable:** `name`, `slug`, `company_id`, `address`, `public_tabs`

**Relationships:**
```php
$shop->company     // BelongsTo Company
$shop->items       // HasMany Item
```

**Features:**
- Auto-generates unique slugs
- Route model binding by slug

#### Storage

Represents a physical storage location within a company.

**File:** `app/Models/Storage.php`

**Fillable:** `name`, `limit`, `company_id`, `priority`, `is_default`

**Relationships:**
```php
$storage->company  // BelongsTo Company
$storage->items   // HasMany Item
$storage->draftItems // HasMany DraftItem
```

**Global Scopes:**
- `CompanyScope` - Filters by company_id

**Key Methods:**
- `getOccupiedPositions($storageId, $excludeDraftId)` - Get occupied positions
- `getOccupiedPositionsBatch($storageIds, $excludeDraftId)` - Batch query optimization
- `getAvailableSlots($storageId, $excludeDraftId)` - Available slots count
- `isPositionOccupied($storageId, $position)` - Check specific position
- `findFirstAvailableInStorage($storageId)` - Find first free position
- `findFirstAvailablePosition()` - Find first available across all storages

#### User

**File:** `app/Models/User.php`

**Fillable:** `name`, `email`, `password`, `store_id`, `location_id`, `role`, `company_id`, `printable_tag_fields`, `printable_invoice_fields`, `timezone`

**Relationships:**
```php
$user->company        // BelongsTo Company
$user->ownedCompany  // HasOne Company (if OWNER)
$user->store         // BelongsTo Store
$user->location     // BelongsTo Location
$user->drafts       // HasMany Draft
```

**Default Attributes:**
- `role` = 'USER'
- `printable_tag_fields` = `["manufacturer","model","storage","colour","battery","imei"]`

---

## Global Scopes

### CompanyItemScope

**File:** `app/Models/Scopes/CompanyItemScope.php`

Filters items where `storage_id` OR `user_id` belongs to the authenticated user's company.

```php
// Applied automatically to all Item queries when user is authenticated
$storageIds = Storage::where('company_id', $companyId)->pluck('id');
$userIds = User::where('company_id', $companyId)->pluck('id');

$builder->where(function($q) use ($storageIds, $userIds) {
    $q->whereIn('items.storage_id', $storageIds)
      ->orWhereIn('items.user_id', $userIds);
});
```

### CompanyScope

Applied to: `Storage`, `Customer`, `MailList`, `Prospect`

Filters by `company_id` of authenticated user.

### CompanyUsersSharedScope

Applied to: `Vendor`, `Sale`, `Draft`, `Tax`, `EmailTemplate`

Filters by `user_id` of users belonging to the same company.

---

## API Endpoints

### Web Routes (Authentication Required)

All routes under `/inventory/*` require:
- `auth:sanctum`
- `config('jetstream.auth_session')`
- `verified`

### Key Item Endpoints

| Method | Route | Controller Method |
|--------|-------|-------------------|
| GET | `/inventory/items` | `ItemController@index` |
| GET | `/inventory/items/getItems` | `ItemController@getItems` |
| POST | `/inventory/items/storeWithBill` | `ItemController@storeWithBill` |
| PUT | `/inventory/items/hold` | `ItemController@hold` |
| PUT | `/inventory/items/unhold` | `ItemController@unhold` |
| GET | `/inventory/items/hold` | `ItemController@viewHold` |
| POST | `/inventory/items/assign-storage` | `ItemController@assignStorage` |
| DELETE | `/inventory/items/obliterate` | `ItemController@obliterate` |

### API Routes

| Method | Route | Controller Method |
|--------|-------|-------------------|
| GET | `/api/items` | `ItemController@list` |
| POST | `/api/items/get-specific-items` | `ItemController@getSpecificItems` |
| POST | `/api/items/get_unique_models_by_manufacturer` | `ItemController@getUniqueModelsByManufacturer` |
| GET | `/api/items/search` | `ItemController@search` |

---

## Testing Infrastructure

### Test Directory Structure

```
tests/
├── Feature/
│   ├── AuthenticationTest.php
│   ├── ItemCrudTest.php          # [TO BE CREATED]
│   ├── ItemStorageTest.php       # [TO BE CREATED]
│   ├── ItemHoldTest.php          # [TO BE CREATED]
│   └── ...
├── Unit/
│   ├── ExampleTest.php
│   └── ItemTest.php              # [TO BE CREATED]
└── TestCase.php                   # Laravel base
```

### Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific test file
./vendor/bin/phpunit tests/Feature/ItemCrudTest.php

# Filter by name
./vendor/bin/phpunit --filter=ItemCrudTest

# With coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Creating Test Data

For tests to work with the multi-tenancy system, you need to create:

1. **Company** - The business entity
2. **User** - With `company_id` set
3. **Shop** (optional) - With `company_id` set
4. **Storage** - With `company_id` set
5. **Item** - With relationships to above

### Factories to Create

| Factory | Purpose |
|---------|---------|
| `CompanyFactory` | Create companies |
| `ShopFactory` | Create shops |
| `StorageFactory` | Create storage locations |
| `VendorFactory` | Create vendors |
| `ItemFactory` | Create inventory items |
| `TabFactory` | Create tabs for organization |
| `SaleFactory` | Create sales |
| `CustomerFactory` | Create customers |

---

## Item Lifecycle

### 1. Creation
- Item created with `storage_id` and optional `position`
- If no position provided, auto-assigned via `getNextAvailablePosition()`
- `user_id` set to authenticated user
- `shop_id` set to first shop of user's company

### 2. In Inventory
- `sold` = null
- `hold` = null
- Appears in inventory lists

### 3. On Hold
- `hold` = timestamp
- `customer` = customer name
- Removed from main inventory view
- Can be unholded (restored to inventory)

### 4. Sold
- `sale_id` = sale record ID
- `sold` = timestamp
- Position freed in storage
- Original position stored in `sold_storage_id`, `sold_position`

### 5. Returned
- `removeSale()` called
- Restored to inventory if original position available
- `sale_id`, `sold`, `customer` cleared

---

## Position Management

### Rules

1. Each storage has a `limit` (max items)
2. Positions are numbered starting from 1
3. Position must be unique within a storage
4. Sold items free up their position
5. Draft items also occupy positions

### Conflict Resolution

When storing items:
1. Check if requested position is occupied
2. If occupied or not provided, find next available
3. If current storage full, try other storages by priority
4. Return conflict info to frontend for user decision

---

## Frontend Structure

**Path:** `resources/js/`

```
resources/js/
├── Pages/              # Inertia pages
│   ├── Inventory/
│   ├── Auth/
│   └── ...
├── Components/        # Vue components
├── stores/           # Pinia stores
└── __tests__/        # Vitest tests
```

---

## Key Files Reference

| Purpose | File Path |
|---------|-----------|
| Item Controller | `app/Http/Controllers/ItemController.php` |
| Item Model | `app/Models/Item.php` |
| Item Form Request | `app/Http/Requests/ItemForm.php` |
| Web Routes | `routes/web.php` |
| API Routes | `routes/api.php` |
| Company Scope | `app/Models/Scopes/CompanyItemScope.php` |
| Storage Policy | `app/Policies/StoragePolicy.php` |

---

## Common Testing Scenarios

### 1. Creating an Item

```php
// Setup
$company = Company::factory()->create();
$user = User::factory()->create(['company_id' => $company->id]);
$storage = Storage::factory()->create(['company_id' => $company->id]);

// Create item
$item = Item::factory()->for($storage)->create([
    'user_id' => $user->id,
]);
```

### 2. Testing with Authentication

```php
$user = User::factory()->create();
$this->actingAs($user);

$response = $this->get('/inventory/items');
$response->assertStatus(200);
```

### 3. Disabling Global Scopes

```php
// For specific tests that need all items
Item::withoutGlobalScope(CompanyItemScope::class)->get();

// Disable all scopes
Item::withoutGlobalScopes()->get();
```

---

## Notes

- All inventory items are filtered by company scope automatically
- Storage position auto-assignment happens on item creation/update
- Items in tabs (`TabItem`) are excluded from main inventory
- Items on hold (`hold` not null) are excluded from main inventory
- Sold items (`sold` not null) are excluded from main inventory

---

## Permissions & Roles Management

### Centralized Permissions System

The system uses a centralized configuration for default user permissions, ensuring consistency across the backend, frontend, and maintenance scripts.

**Configuration File:** `config/permissions.php`

This file defines the `defaults` array, which includes all pages and their corresponding tabs that an `ADMIN` (and by extension, new company owners) should have access to by default.

#### Default Permissions Structure:
- **ADMIN/OWNER Defaults** (`permissions.defaults`):
    - **Dashboard**: Full access.
    - **Inventory**: `Active Inventory`, `On Hold`, `Sold`, `View Organization Data`.
    - **Accounting**: `Payments`, `Expenses`, `Bills`, `Taxes`, `View Organization Data`.
    - **Contacts**: `Customers`, `Prospects`, `Vendors`, `Mailing list`, `Email editor`, `View Organization Data`.
- **Regular USER Defaults** (`permissions.user_defaults`):
    - **Inventory**: `Active Inventory`, `On Hold`, `Sold`.

### Implementation Details

#### 1. Backend Fallback
The `CheckPagePermissions` middleware and the root route redirection logic use `config('permissions.defaults')` for administrators and `config('permissions.user_defaults')` for regular users if their `page_permissions` field is `null` or empty.

#### 2. Automatic Assignment
When a new `User` is created, the `booted` method in the `User` model automatically assigns the role-appropriate centralized default permissions if none are provided.

#### 3. Frontend Sync
Default permissions are shared with the frontend via Inertia in `HandleInertiaRequests.php`, dynamically choosing the set based on the current user's role. The `Navbar.vue` component uses these shared defaults as a fallback.

#### 4. Maintenance Command
The Artisan command `php artisan app:fix-user-roles` uses the centralized configuration to update existing users who might have outdated or missing permissions.

#### 5. Verification (Tests)
- **Backend**: `tests/Feature/Users/UserCrudTest.php` and `tests/Feature/RegistrationTest.php` verify that default permissions are correctly applied upon user creation and registration.
- **Frontend**: `resources/js/__tests__/components/Navbar.spec.ts` verifies that the navigation menu correctly filters items based on the user's role and permissions, utilizing the centralized defaults as a fallback.

---

*Last Updated: March 2026*
