<?php

// ============================================
// ROUTES AUTH PUBLIQUES (login/register)
// ============================================
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);

use Illuminate\Support\Facades\Route;

// ============================================
// ROUTES PUBLIQUES (sans authentification)
// ============================================

// Produits publics
Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
Route::get('/products/{slug}', [\App\Http\Controllers\Api\ProductController::class, 'show']);

// CatÃ©gories et marques publiques
Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
Route::get('/brands', [\App\Http\Controllers\Api\BrandController::class, 'index']);

// Taux de change public
Route::get('/admin/taux-change/actif', [\App\Http\Controllers\Api\Admin\TauxChangeController::class, 'getActif']);

// Inscription admin (premier admin)
Route::prefix('admin-registration')->group(function () {
    Route::get('/check', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'check']);
    Route::post('/register', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'register']);
});

// Avis publics
Route::get('/produits/{produitId}/avis', [\App\Http\Controllers\Api\ReviewController::class, 'index']);
Route::get('/products/{produitId}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'index']);

// ============================================
// ROUTES AUTHENTIFIÃ‰ES (utilisateur connectÃ©)
// ============================================
Route::middleware('auth:api')->group(function () {

    // AUTH
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::post('/refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh']);
    Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::get('/check-session', [\App\Http\Controllers\Api\AuthController::class, 'checkSession']);

    // PANIER
    Route::prefix('cart')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CartController::class, 'index']);
        Route::post('/add', [\App\Http\Controllers\Api\CartController::class, 'add']);
        Route::put('/update/{articleId}', [\App\Http\Controllers\Api\CartController::class, 'update']);
        Route::delete('/remove/{articleId}', [\App\Http\Controllers\Api\CartController::class, 'remove']);
        Route::delete('/clear', [\App\Http\Controllers\Api\CartController::class, 'clear']);
    });

    // ADRESSES
    Route::prefix('addresses')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AddressController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\AddressController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AddressController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AddressController::class, 'destroy']);
        Route::post('/{id}/set-default', [\App\Http\Controllers\Api\AddressController::class, 'setDefault']);
    });

    // COMMANDES UTILISATEUR - LES DEUX URLS (orders ET commandes)
    Route::prefix('orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\OrderController::class, 'userOrders']);
        Route::get('/{numero}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\OrderController::class, 'store']);
        Route::post('/{numero}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    });

    // Alias /commandes pour compatibilitÃ© frontend
    Route::prefix('commandes')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\OrderController::class, 'userOrders']);
        Route::get('/{numero}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\OrderController::class, 'store']);
        Route::post('/{numero}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    });

    // PAIEMENT MAISHAPAY
    Route::prefix('payment/maishapay')->group(function () {
        Route::post('/initier/{numeroCommande}', [\App\Http\Controllers\Api\MaishaPayController::class, 'initier']);
        Route::get('/verifier/{numeroCommande}', [\App\Http\Controllers\Api\MaishaPayController::class, 'verifier']);
    });

    // Alias pour compatibilitÃ©
    Route::prefix('payment')->group(function () {
        Route::post('/initiate/{numeroCommande}', [\App\Http\Controllers\Api\MaishaPayController::class, 'initier']);
        Route::get('/verify/{numeroCommande}', [\App\Http\Controllers\Api\MaishaPayController::class, 'verifier']);
    });

    // AVIS (crÃ©ation)
    Route::post('/avis/produit/{produitId}', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
    Route::post('/products/{produitId}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
    Route::post('/avis/{id}/utile', [\App\Http\Controllers\Api\ReviewController::class, 'voterUtile']);
    Route::post('/avis/{id}/inutile', [\App\Http\Controllers\Api\ReviewController::class, 'voterInutile']);
    Route::post('/avis/{avisId}/repondre', [\App\Http\Controllers\Api\ReviewController::class, 'repondre']);
    Route::post('/avis/{avisId}/signaler', [\App\Http\Controllers\Api\ReviewController::class, 'signaler']);

    // NOTIFICATIONS
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    });

    // WISHLIST
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\WishlistController::class, 'index']);
        Route::post('/add', [\App\Http\Controllers\Api\WishlistController::class, 'add']);
        Route::delete('/remove/{produitId}', [\App\Http\Controllers\Api\WishlistController::class, 'remove']);
    });
});

// ============================================
// ROUTES ADMIN (authentifiÃ©es + rÃ´le admin)
// ============================================
Route::middleware(['auth:api'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

    // Statistiques
    Route::prefix('statistics')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\StatisticsController::class, 'index']);
    });

    // Produits admin
    Route::prefix('products')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ProductController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\ProductController::class, 'store']);
        Route::post('/upload', [\App\Http\Controllers\Api\Admin\ProductController::class, 'uploadImage']);
        Route::get('/{product}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'show']);
        Route::put('/{product}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'destroy']);
        Route::delete('/{id}/images/{imageId}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'deleteImage']);
        Route::post('/{id}/images/{imageId}/set-main', [\App\Http\Controllers\Api\Admin\ProductController::class, 'setMainImage']);
    });

    // CatÃ©gories admin
    Route::prefix('categories')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'store']);
        Route::get('/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'show']);
        Route::put('/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'update']);
        Route::delete('/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'destroy']);
    });

    // Marques admin
    Route::prefix('brands')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\BrandController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\BrandController::class, 'store']);
        Route::get('/{brand}', [\App\Http\Controllers\Api\Admin\BrandController::class, 'show']);
        Route::put('/{brand}', [\App\Http\Controllers\Api\Admin\BrandController::class, 'update']);
        Route::delete('/{brand}', [\App\Http\Controllers\Api\Admin\BrandController::class, 'destroy']);
    });

    // Commandes admin
    Route::prefix('orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\OrderController::class, 'adminOrders']);
        Route::get('/{numeroCommande}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
        Route::post('/{id}/change-status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'changeStatus']);
        Route::post('/{id}/add-note', [\App\Http\Controllers\Api\Admin\OrderController::class, 'addNote']);
    });

    // Alias commandes admin
    Route::prefix('commandes')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\OrderController::class, 'adminOrders']);
        Route::get('/{numeroCommande}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
        Route::post('/{id}/change-status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'changeStatus']);
    });

    // Utilisateurs admin
    Route::prefix('users')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleStatus']);
        Route::post('/{id}/change-role', [\App\Http\Controllers\Api\Admin\UserController::class, 'changeRole']);
    });

    // ParamÃ¨tres
    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'index']);
        Route::put('/', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'update']);
    });

    // Taux de change admin
    Route::prefix('taux-change')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\TauxChangeController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\TauxChangeController::class, 'store']);
        Route::get('/history', [\App\Http\Controllers\Api\Admin\TauxChangeController::class, 'history']);
    });

    // SÃ©curitÃ©
    Route::prefix('security')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'dashboard']);
        Route::post('/block-ip', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'blockIp']);
        Route::delete('/unblock-ip/{ip}', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'unblockIp']);
        Route::post('/clean-logs', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'cleanLogs']);
    });

    // Avis admin
    Route::prefix('reviews')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'index']);
        Route::post('/{id}/approuver', [\App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'approuver']);
        Route::post('/{id}/desapprouver', [\App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'desapprouver']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'destroy']);
    });

    // Stock
    Route::prefix('stock')->group(function () {
        Route::post('/ajouter', [\App\Http\Controllers\Api\Admin\StockController::class, 'ajouter']);
        Route::post('/ajuster/{produitId}', [\App\Http\Controllers\Api\Admin\StockController::class, 'ajuster']);
        Route::get('/historique/{produitId}', [\App\Http\Controllers\Api\Admin\StockController::class, 'historique']);
        Route::get('/statistiques', [\App\Http\Controllers\Api\Admin\StockController::class, 'statistiques']);
        Route::get('/mouvements', [\App\Http\Controllers\Api\Admin\StockController::class, 'tousMouvements']);
    });
});

// ============================================
// ROUTES WEBHOOK (sans auth)
// ============================================
Route::prefix('payment/maishapay')->group(function () {
    Route::post('/notify', [\App\Http\Controllers\Api\MaishaPayController::class, 'notify']);
    Route::get('/callback', [\App\Http\Controllers\Api\MaishaPayController::class, 'callback']);
});
