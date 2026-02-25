# Testing Commands

This document details the commands necessary to run the backend (Laravel) and frontend (Vue/Vitest) tests.

## Backend (Laravel)

To run all backend tests (Feature and Unit tests):

```bash
php artisan test
```

If you wish to run a specific test file (for example, the one we recently created):

```bash
php artisan test tests/Feature/Payments/PaymentVisibilityTest.php
```

## Frontend (Vue.js + Vitest)

To run the frontend tests once:

```bash
npm run test:run
```

To run the tests in "watch" mode (they reload when changes are saved):

```bash
npm run test
```
