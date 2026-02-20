# Ecommerce Custom Domains Implementation

This document outlines how the custom domain (white-labeling) feature works in the Refresh Mobile Inventory/Ecommerce system.

## Overview

The system allows each "Market" (consumer-facing shop) to be accessed via its own custom domain (e.g., `shop.mybrand.com`) instead of just a path on the main application (e.g., `app.refreshmobile.com/market/my-shop`).

This enables full white-labeling capabilities where each market appears as a standalone website.

## Technical Implementation

### Routing (`routes/web.php`)

Custom domains are handled using Laravel's domain routing feature. The routes are defined inside a group that captures any domain pointing to the application:

```php
Route::domain('{custom_domain}')
    ->middleware([\App\Http\Middleware\DetectMarketFromHost::class])
    ->group(function () {
        // Market routes (Home, Products, Cart, etc.)
        Route::get('/', [MarketController::class, 'index'])->name('market.domain.index');
        Route::get('/products', [MarketController::class, 'products'])->name('market.domain.products');
        // ...
    });
```

### Middleware (`app/Http/Middleware/DetectMarketFromHost.php`)

This middleware is the core component. It intercepts requests to any domain that is *not* explicitly excluded (like `localhost` or the main app domain).

**Workflow:**
1.  **Intercept Request:** The middleware captures the incoming `Host` header.
2.  **Check Exclusions:** Checks `config/ecommerce.php` for `excluded_hosts`. If the host matches an exclusion, the request proceeds normally (skipping market detection).
3.  **Find Market:** Queries the `markets` database table for a record where `custom_domain` matches the request host and `is_active` is true.
4.  **Inject Market:** If a market is found, the middleware:
    *   Injects the `Market` model instance into the route parameters as `market`.
    *   Removes the `custom_domain` parameter to clean up the route.
    *   Allows the request to proceed to the specific controller method (e.g., `products`, `cart`).
5.  **Handle Not Found:** If no market matches the domain, it returns a 404 error.

### Controller (`MarketController.php`)

The `MarketController` methods are designed to work with both path-based routing (e.g., `/market/{slug}`) and domain-based routing. They accept a `Market $market` dependency, which is automatically injected by Laravel (either from the URL slug or our middleware).

```php
public function index(Request $request, Market $market)
{
    // Logic uses $market regardless of how the user arrived
}
```

## Setup Guide

To configure a custom domain for a market:

1.  **DNS Configuration:**
    *   Create an `A` record for the custom domain (e.g., `shop.example.com`) pointing to the server's IP address.
    *   Alternatively, use a `CNAME` record pointing to the main application domain.

2.  **Server Configuration (Nginx/Apache):**
    *   Ensure the web server is configured to accept requests for the custom domain.
    *   Typically, this involves adding the domain to the `server_name` directive in Nginx or `ServerAlias` in Apache, or using a wildcard configuration.
    *   **SSL:** An SSL certificate must be generated for the custom domain (e.g., via Let's Encrypt).

3.  **Database Configuration:**
    *   Find the market record in the database.
    *   Set the `custom_domain` column to the full domain name (e.g., `shop.example.com`).
    *   Ensure `is_active` is set to `1` (true).

## Configuration (`config/ecommerce.php`)

The `excluded_hosts` array defines domains that should **never** be treated as custom market domains. This prevents the system from trying to find a market for the admin panel or local development URLs.

```php
// config/ecommerce.php
return [
    'excluded_hosts' => [
        'localhost',
        '127.0.0.1',
        'app.refreshmobile.com', // Main app domain
        '*.test',                // Local Valet domains
    ],
];
```

## Local Development & Testing

To test custom domains locally without purchasing a domain:

1.  **Edit Hosts File:**
    *   Add an entry to your local hosts file (`/etc/hosts` on Mac/Linux, `C:\Windows\System32\drivers\etc\hosts` on Windows).
    *   Example: `127.0.0.1  myshop.test`

2.  **Update Database:**
    *   Set the `custom_domain` of a test market to `myshop.test`.

3.  **Access:**
    *   Visit `http://myshop.test` in your browser.
    *   You should see the specific market's homepage.
    *   Verify navigation works (e.g., clicking "Products" goes to `http://myshop.test/products`).

## Troubleshooting

*   **404 Not Found:**
    *   Is the `custom_domain` set correctly in the database?
    *   Is `is_active` true?
    *   Is the domain listed in `excluded_hosts`?

*   **Redirects to Main App:**
    *   Check if the web server configuration is correctly routing the request to the application directory.

*   **Links Go to Wrong Domain:**
    *   The application uses relative links or `route()` helper. Ensure `APP_URL` in `.env` doesn't hardcode the base URL for generated links, or that the application correctly detects the current host.
