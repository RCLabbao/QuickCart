<?php
declare(strict_types=1);
// Secure session cookies
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $https,
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Paths
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');

// Composer autoload (optional for libraries like PHPMailer)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) { require BASE_PATH . '/vendor/autoload.php'; }

// Autoload minimal core
require_once APP_PATH . '/core/Helpers.php';
require_once APP_PATH . '/core/Router.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/View.php';
require_once APP_PATH . '/core/DB.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/CSRF.php';
require_once APP_PATH . '/core/Mailer.php';

// Simple autoloader for controllers
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\Controllers\\') === 0) {
        $controllerName = substr($class, strlen('App\\Controllers\\'));
        $file = APP_PATH . '/controllers/' . $controllerName . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Load config if installed
$configFile = CONFIG_PATH . '/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
    \App\Core\DB::init(CONFIG['db']);
    \App\Core\Auth::init();
}

use App\Core\Router;

$router = new Router();

// Storefront routes
$router->get('/', 'HomeController@index');
$router->get('/products', 'ProductController@index'); // all products (infinite scroll)
$router->get('/products/load', 'ProductController@loadMore'); // ajax
$router->get('/products/(?P<slug>[a-z0-9\-]+)', 'ProductController@show');
$router->get('/search', 'ProductController@search');
$router->get('/api/search', 'ProductController@searchApi');

// Cart
$router->get('/cart', 'CartController@index');
$router->get('/cart/summary', 'CartController@summary');
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');

// Checkout
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout', 'CheckoutController@placeOrder');
$router->get('/checkout/success', 'CheckoutController@success');

$router->get('/checkout/success/(?P<slug>[A-Za-z0-9_-]+)', 'CheckoutController@successSlug');

// Admin (hidden link)
$router->get('/admin/login', 'AuthController@login');
$router->post('/admin/login', 'AuthController@doLogin');
$router->get('/admin/logout', 'AuthController@logout');

$router->get('/admin', 'AdminController@dashboard', ['auth' => 'admin']);

// Admin Settings
$router->get('/admin/settings', 'AdminSettingsController@index', ['perm' => 'settings.write']);
$router->post('/admin/settings', 'AdminSettingsController@update', ['perm' => 'settings.write']);
$router->post('/admin/settings/fees', 'AdminSettingsController@addCityFee', ['perm' => 'settings.write']);
$router->post('/admin/settings/fees/(?P<id>\d+)/delete', 'AdminSettingsController@deleteCityFee', ['perm' => 'settings.write']);

// Public API
$router->get('/api/shipping-fee', 'CheckoutController@fee');

// Admin Users
$router->get('/admin/users', 'AdminUsersController@index', ['perm' => 'users.read']);
$router->get('/admin/users/create', 'AdminUsersController@create', ['perm' => 'users.write']);
$router->post('/admin/users', 'AdminUsersController@store', ['perm' => 'users.write']);
$router->get('/admin/users/(?P<id>\d+)/edit', 'AdminUsersController@edit', ['perm' => 'users.write']);
$router->post('/admin/users/(?P<id>\d+)', 'AdminUsersController@update', ['perm' => 'users.write']);
$router->post('/admin/users/(?P<id>\d+)/delete', 'AdminUsersController@destroy', ['perm' => 'users.write']);

// Admin Roles & Permissions
$router->get('/admin/roles', 'AdminRolesController@index', ['perm' => 'roles.write']);
$router->get('/admin/roles/create', 'AdminRolesController@create', ['perm' => 'roles.write']);
$router->post('/admin/roles', 'AdminRolesController@store', ['perm' => 'roles.write']);

$router->get('/admin/roles/(?P<id>\d+)', 'AdminRolesController@edit', ['perm' => 'roles.write']);
$router->post('/admin/roles/(?P<id>\d+)', 'AdminRolesController@update', ['perm' => 'roles.write']);

// Admin Customers
$router->get('/admin/customers', 'AdminCustomersController@index', ['perm' => 'users.read']);
$router->get('/admin/customer', 'AdminCustomersController@index', ['perm' => 'users.read']); // alias for singular path
$router->get('/admin/customers/view', 'AdminCustomersController@show', ['perm' => 'users.read']);
$router->post('/admin/customers/profile', 'AdminCustomersController@updateProfile', ['perm' => 'users.write']);

// Admin Collections
$router->get('/admin/collections', 'AdminCollectionsController@index', ['perm' => 'collections.write']);
$router->get('/admin/collections/create', 'AdminCollectionsController@create', ['perm' => 'collections.write']);
$router->post('/admin/collections', 'AdminCollectionsController@store', ['perm' => 'collections.write']);
$router->get('/admin/collections/(?P<id>\d+)/edit', 'AdminCollectionsController@edit', ['perm' => 'collections.write']);
$router->post('/admin/collections/(?P<id>\d+)', 'AdminCollectionsController@update', ['perm' => 'collections.write']);
$router->post('/admin/collections/(?P<id>\d+)/delete', 'AdminCollectionsController@destroy', ['perm' => 'collections.write']);

// Admin Reports
$router->get('/admin/reports', 'AdminReportsController@index', ['perm' => 'orders.read']);


// Admin Products
$router->get('/admin/products', 'AdminProductsController@index', ['perm' => 'products.read']);
$router->get('/admin/products/create', 'AdminProductsController@create', ['perm' => 'products.write']);
$router->post('/admin/products', 'AdminProductsController@store', ['perm' => 'products.write']);
$router->get('/admin/products/(?P<id>\d+)/edit', 'AdminProductsController@edit', ['perm' => 'products.write']);
$router->post('/admin/products/(?P<id>\d+)', 'AdminProductsController@update', ['perm' => 'products.write']);
$router->post('/admin/products/(?P<id>\d+)/delete', 'AdminProductsController@destroy', ['perm' => 'products.write']);
$router->post('/admin/products/(?P<id>\d+)/images/(?P<image_id>\d+)/delete', 'AdminProductsController@deleteImage', ['perm' => 'products.write']);
$router->post('/admin/products/bulk', 'AdminProductsController@bulk', ['perm' => 'products.write']);
$router->get('/admin/products/export', 'AdminProductsController@export', ['perm' => 'products.read']);
$router->post('/admin/products/(?P<id>\d+)/images/sort', 'AdminProductsController@sortImages', ['perm' => 'products.write']);
$router->post('/admin/products/quick-update', 'AdminProductsController@quickUpdate', ['perm' => 'products.write']);
$router->post('/admin/products/import-stock', 'AdminProductsController@importStock', ['perm' => 'products.write']);
$router->post('/admin/products/adjust-prices', 'AdminProductsController@adjustPrices', ['perm' => 'products.write']);
$router->get('/admin/products/search', 'AdminProductsController@search', ['perm' => 'products.read']);
$router->get('/admin/products/duplicates', 'AdminProductsController@duplicates', ['perm' => 'products.read']);






// Admin Orders
$router->get('/admin/orders', 'AdminOrdersController@index', ['perm' => 'orders.read']);
$router->get('/admin/orders/today', 'AdminOrdersController@today', ['perm' => 'orders.read']);
$router->get('/admin/orders/export-items', 'AdminOrdersController@exportItems', ['perm' => 'orders.read']);
$router->post('/admin/orders/bulk-status', 'AdminOrdersController@bulkStatus', ['perm' => 'orders.write']);
$router->post('/admin/orders/(?P<id>\d+)/delete', 'AdminOrdersController@delete', ['perm' => 'orders.write']);
$router->post('/admin/orders/(?P<id>\d+)/fulfill', 'AdminOrdersController@fulfill', ['perm' => 'orders.write']);
$router->post('/admin/orders/(?P<id>\d+)/note', 'AdminOrdersController@note', ['perm' => 'orders.write']);

$router->get('/admin/orders/create', 'AdminOrdersController@createManual', ['perm' => 'orders.write']);
$router->post('/admin/orders/manual', 'AdminOrdersController@storeManual', ['perm' => 'orders.write']);

$router->get('/admin/orders/(?P<id>\d+)', 'AdminOrdersController@show', ['perm' => 'orders.read']);
$router->get('/admin/orders/export', 'AdminOrdersController@export', ['perm' => 'orders.read']);
$router->get('/admin/customers/export', 'AdminCustomersController@export', ['perm' => 'users.read']);
// Admin Maintenance
$router->get('/admin/maintenance', 'AdminMaintenanceController@index', ['perm' => 'settings.read']);
$router->post('/admin/maintenance/optimize', 'AdminMaintenanceController@optimize', ['perm' => 'settings.write']);
$router->post('/admin/maintenance/seed-demo', 'AdminMaintenanceController@seedDemo', ['perm' => 'settings.write']);
$router->post('/admin/maintenance/wipe', 'AdminMaintenanceController@wipe', ['perm' => 'settings.write']);
$router->post('/admin/maintenance/wipe-demo', 'AdminMaintenanceController@wipeDemo', ['perm' => 'settings.write']);

// Admin Coupons
$router->get('/admin/coupons', 'AdminCouponsController@index', ['perm' => 'settings.read']);
$router->get('/admin/coupons/create', 'AdminCouponsController@create', ['perm' => 'settings.write']);
$router->post('/admin/coupons', 'AdminCouponsController@store', ['perm' => 'settings.write']);
$router->get('/admin/coupons/(?P<id>\d+)/edit', 'AdminCouponsController@edit', ['perm' => 'settings.write']);
$router->post('/admin/coupons/(?P<id>\d+)', 'AdminCouponsController@update', ['perm' => 'settings.write']);
$router->post('/admin/coupons/(?P<id>\d+)/delete', 'AdminCouponsController@delete', ['perm' => 'settings.write']);


// Order quick actions
$router->post('/admin/orders/(?P<id>\d+)/refund', 'AdminOrdersController@refund', ['perm' => 'orders.write']);

$router->get('/admin/orders/(?P<id>\d+)/invoice', 'AdminOrdersController@invoice', ['perm' => 'orders.read']);

$router->post('/admin/orders/(?P<id>\d+)/status', 'AdminOrdersController@updateStatus', ['perm' => 'orders.write']);
$router->post('/admin/products/(?P<id>\d+)/duplicate', 'AdminProductsController@duplicate', ['perm' => 'products.write']);
$router->get('/orders/(?P<slug>[A-Za-z0-9_-]+)', 'OrdersController@showSlug');


$router->get('/orders/(?P<id>\d+)/(?P<token>[A-Za-z0-9]+)', 'OrdersController@show');

// Collections (storefront)
$router->get('/collections', 'CollectionsController@index');
$router->get('/collections/(?P<slug>[a-z0-9\-]+)', 'CollectionsController@show');

$router->get('/collections/(?P<slug>[a-z0-9\-]+)/load', 'CollectionsController@loadMore');

$router->dispatch($_SERVER['REQUEST_METHOD'], strtok($_SERVER['REQUEST_URI'], '?'));

