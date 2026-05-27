# Barcode Plugin

Barcode is a web-based AureusERP plugin for:

- inventory operations
- inventory adjustments
- bundled `html5-qrcode` scanning

## Installation

1. Copy `plugins/webkul/barcode` into the host app.
2. Refresh autoload:

```bash
composer dump-autoload
php artisan package:discover --ansi
```

3. Install the plugin:

```bash
php artisan barcode:install --no-interaction
```

4. Publish Filament assets:

```bash
php artisan filament:assets --no-interaction
```

## Routes

- `/barcode`
- `/admin/barcode/login`
- `/admin/barcode`
- `/admin/barcode/inventory-adjustments`
- `/admin/barcode/operations/{operationType}`
- `/admin/barcode/operations/{operationType}/transfers/{operation}`

## Host app changes

Only these host changes are required outside the plugin:

### `bootstrap/providers.php`

The app must load:

```php
Webkul\Barcode\BarcodeServiceProvider::class,
```

### `routes/web.php`

Keep the normal login alias:

```php
use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/admin/login')
    ->name('login');
```
