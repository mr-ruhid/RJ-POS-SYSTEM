<?php

use Illuminate\Support\Facades\Route;
use App\Models\Role;
// Controllerləri burada import edirik
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\ProductDiscountController;
use App\Http\Controllers\CashRegisterController;
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
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorReportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemUpdateController; // YENİ: Sistem Yeniləmə Controlleri

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. ANA SƏHİFƏ (DASHBOARD)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/sync', [DashboardController::class, 'syncNow'])->name('dashboard.sync');

// 2. ROLLAR
Route::get('/roles', function () {
    $roles = Role::all();
    return view('admin.roles.index', compact('roles'));
})->name('roles.index');


// 3. MƏHSULLAR VƏ KATEQORİYALAR
// ---------------------------------------------------------

// Barkod səhifəsi
Route::get('/products/print/barcodes', [ProductController::class, 'barcodes'])->name('products.barcodes');

// Mağaza Endirimləri
Route::get('/products/discounts', [ProductDiscountController::class, 'index'])->name('products.discounts');
Route::post('/products/discounts', [ProductDiscountController::class, 'store'])->name('discounts.store');
Route::get('/pos/check-promo', [App\Http\Controllers\PosController::class, 'checkPromo'])->name('pos.check_promo');
Route::post('/products/discounts/{discount}/stop', [ProductDiscountController::class, 'stop'])->name('discounts.stop');

// Kritik Limit Yeniləmə
Route::post('/products/{product}/alert', [StockController::class, 'updateAlert'])->name('products.update_alert');

// Resource-lar
Route::resource('products', ProductController::class);
Route::resource('categories', CategoryController::class);


// 4. STOK VƏ ANBAR SİSTEMİ
// ---------------------------------------------------------

// Ümumi Stok
Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');

// Mal Qəbulu
Route::get('/stocks/create', [StockController::class, 'create'])->name('stocks.create');
Route::post('/stocks', [StockController::class, 'storeData'])->name('stocks.store');

// Anbar və Mağaza Stoku
Route::get('/stocks/warehouse', [StockController::class, 'warehouse'])->name('stocks.warehouse');
Route::get('/stocks/market', [StockController::class, 'store'])->name('stocks.market');

// Transfer Sistemi
Route::get('/stocks/transfer', [StockController::class, 'transfer'])->name('stocks.transfer');
Route::post('/stocks/transfer', [StockController::class, 'processTransfer'])->name('stocks.transfer.process');

// Partiya Redaktə və Silmə
Route::get('/stocks/{batch}/edit', [StockController::class, 'edit'])->name('stocks.edit');
Route::put('/stocks/{batch}', [StockController::class, 'update'])->name('stocks.update');
Route::delete('/stocks/{batch}', [StockController::class, 'destroy'])->name('stocks.destroy');


// 5. SATIŞ VƏ DİGƏR BÖLMƏLƏR
// ---------------------------------------------------------

// Kassa (POS)
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.store');

// Satış Tarixçəsi
Route::get('/sales', [OrderController::class, 'index'])->name('sales.index');
Route::get('/sales/{order}', [OrderController::class, 'show'])->name('sales.show');
Route::get('/sales/{order}/print-official', [OrderController::class, 'printOfficial'])->name('sales.print_official');

// Qaytarma
Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
Route::get('/returns/search', [ReturnController::class, 'search'])->name('returns.search');
Route::post('/returns/{order}', [ReturnController::class, 'store'])->name('returns.store');

// Lotoreyalar
Route::get('/lotteries', [LotteryController::class, 'index'])->name('lotteries.index');

// Promokodlar
Route::resource('promocodes', PromocodeController::class)->only(['index', 'store', 'destroy']);

// Partnyorlar
Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
Route::post('/partners/assign', [PartnerController::class, 'assignCode'])->name('partners.assign_code');
Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

// Digər
Route::get('/suppliers', function () { return "Təchizatçılar Səhifəsi (v3)"; })->name('suppliers.index');


// 6. HESABLAR
// ---------------------------------------------------------
Route::get('/users/admins', [UserController::class, 'admins'])->name('users.admins');
Route::get('/users/cashiers', [UserController::class, 'cashiers'])->name('users.cashiers');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');


// 7. TƏNZİMLƏMƏLƏR
// ---------------------------------------------------------

// Mağaza Məlumatları
Route::get('/settings/store', [StoreSettingController::class, 'index'])->name('settings.store');
Route::post('/settings/store', [StoreSettingController::class, 'update'])->name('settings.store.update');

// Kassalar
Route::get('/settings/registers', [CashRegisterController::class, 'index'])->name('settings.registers');
Route::post('/settings/registers', [CashRegisterController::class, 'store'])->name('registers.store');
Route::post('/settings/registers/{register}/toggle', [CashRegisterController::class, 'toggle'])->name('registers.toggle');
Route::delete('/settings/registers/{register}', [CashRegisterController::class, 'destroy'])->name('registers.destroy');

// Vergi Tənzimləmələri
Route::get('/settings/taxes', [TaxController::class, 'index'])->name('settings.taxes');
Route::post('/settings/taxes', [TaxController::class, 'store'])->name('taxes.store');
Route::post('/settings/taxes/{tax}/toggle', [TaxController::class, 'toggle'])->name('taxes.toggle');
Route::delete('/settings/taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');

// Qəbz Şablonu
Route::get('/settings/receipt', [ReceiptSettingController::class, 'index'])->name('settings.receipt');
Route::post('/settings/receipt', [ReceiptSettingController::class, 'update'])->name('settings.receipt.update');

// Ödəniş Növləri
Route::get('/settings/payments', [App\Http\Controllers\PaymentMethodController::class, 'index'])->name('settings.payments');
Route::post('/settings/payments', [App\Http\Controllers\PaymentMethodController::class, 'store'])->name('settings.payments.store');
Route::post('/settings/payments/{paymentMethod}/toggle', [App\Http\Controllers\PaymentMethodController::class, 'toggle'])->name('settings.payments.toggle');
Route::delete('/settings/payments/{paymentMethod}', [App\Http\Controllers\PaymentMethodController::class, 'destroy'])->name('settings.payments.destroy');

// API Tənzimləmələri
Route::get('/settings/api', [ApiSettingController::class, 'index'])->name('settings.api');
Route::post('/settings/api', [ApiSettingController::class, 'update'])->name('settings.api.update');

// Server Quraşdırma
Route::get('/system/server', [ServerSetupController::class, 'index'])->name('settings.server');
Route::post('/system/server', [ServerSetupController::class, 'update'])->name('settings.server.update');


// 8. SİSTEM
// ---------------------------------------------------------

// Backup Sistemi
Route::prefix('system/backup')->name('system.backup.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/create', [BackupController::class, 'create'])->name('create');
    Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
    Route::get('/restore/{filename}', [BackupController::class, 'restoreDb'])->name('restore');
    Route::delete('/delete/{filename}', [BackupController::class, 'delete'])->name('delete');
});

// Xəta Bildirişi
Route::post('/system/error-report', [ErrorReportController::class, 'send'])->name('system.error_report');

// Sistem Yeniləmələri (Dəyişdirildi)
Route::get('/system/updates', [SystemUpdateController::class, 'index'])->name('system.updates');

// Dillər (v3)
Route::get('/system/languages', function() { return "Dillər və Tərcümə (v3)"; })->name('system.languages');


// 9. HESABATLAR
// ---------------------------------------------------------
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');           // İcmal
    Route::get('/profit', [ReportController::class, 'profit'])->name('profit');     // Mənfəət
    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');       // Satışlar
    Route::get('/stock', [ReportController::class, 'stock'])->name('stock');       // Stok və Anbar
    Route::get('/partners', [ReportController::class, 'partners'])->name('partners'); // Partnyorlar
    Route::get('/promocodes', [ReportController::class, 'promocodes'])->name('promocodes'); // Promokodlar
});
