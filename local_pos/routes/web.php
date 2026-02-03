<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;
// ... digər controllerlər ...
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDiscountController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\ReceiptSettingController;
use App\Http\Controllers\ApiSettingController;
use App\Http\Controllers\PromocodeController;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\ServerSetupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\ErrorReportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\PaymentMethodController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. ANA SƏHİFƏ (DASHBOARD)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/sync', [DashboardController::class, 'syncNow'])->name('dashboard.sync');

// ... (Digər marşrutlar olduğu kimi qalır) ...

// 6. PARTNYORLAR (YENİLƏNMİŞ)
// ---------------------------------------------------------

// A. Serverdən (Node.js) gözləyən Telegram istifadəçilərini çəkmək (AJAX)
Route::get('/partners/fetch-telegram', [PartnerController::class, 'fetchTelegramRequests'])->name('partners.fetch_telegram');

// B. Telegram Sorğusunu Təsdiqləyib Partnyor Yaratmaq (Promokod + Faizlər)
// Bu, sizin istədiyiniz əsas funksiyadır
Route::post('/partners/create-from-telegram', [PartnerController::class, 'createFromTelegram'])->name('partners.create_from_telegram');

// Standart Partnyor əməliyyatları
Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

// ... (Digər marşrutlar olduğu kimi qalır) ...
// 2. ROLLAR
Route::get('/roles', function () {
    $roles = \App\Models\Role::all();
    return view('admin.roles.index', compact('roles'));
})->name('roles.index');


// 3. MƏHSULLAR VƏ KATEQORİYALAR
// ---------------------------------------------------------
Route::get('/products/print/barcodes', [ProductController::class, 'barcodes'])->name('products.barcodes');
Route::get('/products/discounts', [ProductDiscountController::class, 'index'])->name('products.discounts');
Route::post('/products/discounts', [ProductDiscountController::class, 'store'])->name('discounts.store');
Route::post('/products/discounts/{discount}/stop', [ProductDiscountController::class, 'stop'])->name('discounts.stop');
Route::post('/products/{product}/alert', [StockController::class, 'updateAlert'])->name('products.update_alert');
Route::resource('products', ProductController::class);
Route::resource('categories', CategoryController::class);


// 4. STOK VƏ ANBAR SİSTEMİ
// ---------------------------------------------------------
Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
Route::get('/stocks/create', [StockController::class, 'create'])->name('stocks.create');
Route::post('/stocks', [StockController::class, 'storeData'])->name('stocks.store');
Route::get('/stocks/warehouse', [StockController::class, 'warehouse'])->name('stocks.warehouse');
Route::get('/stocks/market', [StockController::class, 'store'])->name('stocks.market');
Route::get('/stocks/transfer', [StockController::class, 'transfer'])->name('stocks.transfer');
Route::post('/stocks/transfer', [StockController::class, 'processTransfer'])->name('stocks.transfer.process');
Route::get('/stocks/{batch}/edit', [StockController::class, 'edit'])->name('stocks.edit');
Route::put('/stocks/{batch}', [StockController::class, 'update'])->name('stocks.update');
Route::delete('/stocks/{batch}', [StockController::class, 'destroy'])->name('stocks.destroy');


// 5. SATIŞ (POS)
// ---------------------------------------------------------
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
Route::get('/pos/check-promo', [PosController::class, 'checkPromo'])->name('pos.check_promo'); // Promokod yoxlanışı
Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.store');

Route::get('/sales', [OrderController::class, 'index'])->name('sales.index');
Route::get('/sales/{order}', [OrderController::class, 'show'])->name('sales.show');
Route::get('/sales/{order}/print-official', [OrderController::class, 'printOfficial'])->name('sales.print_official');

Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
Route::get('/returns/search', [ReturnController::class, 'search'])->name('returns.search');
Route::post('/returns/{order}', [ReturnController::class, 'store'])->name('returns.store');

Route::get('/lotteries', [LotteryController::class, 'index'])->name('lotteries.index');

Route::resource('promocodes', PromocodeController::class)->only(['index', 'store', 'destroy']);

// 7. HESABLAR VƏ DİGƏR
// ---------------------------------------------------------
Route::get('/suppliers', function () { return "Təchizatçılar Səhifəsi (v3)"; })->name('suppliers.index');
Route::get('/users/admins', [UserController::class, 'admins'])->name('users.admins');
Route::get('/users/cashiers', [UserController::class, 'cashiers'])->name('users.cashiers');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');


// 8. TƏNZİMLƏMƏLƏR
// ---------------------------------------------------------
Route::get('/settings/store', [StoreSettingController::class, 'index'])->name('settings.store');
Route::post('/settings/store', [StoreSettingController::class, 'update'])->name('settings.store.update');

Route::get('/settings/registers', [CashRegisterController::class, 'index'])->name('settings.registers');
Route::post('/settings/registers', [CashRegisterController::class, 'store'])->name('registers.store');
Route::post('/settings/registers/{register}/toggle', [CashRegisterController::class, 'toggle'])->name('registers.toggle');
Route::delete('/settings/registers/{register}', [CashRegisterController::class, 'destroy'])->name('registers.destroy');

Route::get('/settings/taxes', [TaxController::class, 'index'])->name('settings.taxes');
Route::post('/settings/taxes', [TaxController::class, 'store'])->name('taxes.store');
Route::post('/settings/taxes/{tax}/toggle', [TaxController::class, 'toggle'])->name('taxes.toggle');
Route::delete('/settings/taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');

Route::get('/settings/receipt', [ReceiptSettingController::class, 'index'])->name('settings.receipt');
Route::post('/settings/receipt', [ReceiptSettingController::class, 'update'])->name('settings.receipt.update');

Route::get('/settings/payments', [PaymentMethodController::class, 'index'])->name('settings.payments');
Route::post('/settings/payments', [PaymentMethodController::class, 'store'])->name('settings.payments.store');
Route::post('/settings/payments/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])->name('settings.payments.toggle');
Route::delete('/settings/payments/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('settings.payments.destroy');

Route::get('/settings/api', [ApiSettingController::class, 'index'])->name('settings.api');
Route::post('/settings/api', [ApiSettingController::class, 'update'])->name('settings.api.update');

Route::get('/system/server', [ServerSetupController::class, 'index'])->name('settings.server');
Route::post('/system/server', [ServerSetupController::class, 'update'])->name('settings.server.update');


// 9. SİSTEM
// ---------------------------------------------------------
Route::prefix('system/backup')->name('system.backup.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/create', [BackupController::class, 'create'])->name('create');
    Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
    Route::get('/restore/{filename}', [BackupController::class, 'restoreDb'])->name('restore');
    Route::delete('/delete/{filename}', [BackupController::class, 'delete'])->name('delete');
});

Route::post('/system/error-report', [ErrorReportController::class, 'send'])->name('system.error_report');
Route::get('/system/updates', [SystemUpdateController::class, 'index'])->name('system.updates');
Route::get('/system/languages', function() { return "Dillər və Tərcümə (v3)"; })->name('system.languages');


// 10. HESABATLAR
// ---------------------------------------------------------
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/profit', [ReportController::class, 'profit'])->name('profit');
    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
    Route::get('/partners', [ReportController::class, 'partners'])->name('partners');
    Route::get('/promocodes', [ReportController::class, 'promocodes'])->name('promocodes');
});
