<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // dd(bcrypt("DgDWqyvJ"));
    \Artisan::call('cache:clear');
    // \Artisan::call('config:clear');
    // \Artisan::call('config:cache');
    // \Artisan::call('route:clear');
    // \Artisan::call('view:clear');
    // \Artisan::call('event:clear');
    // \Artisan::call('optimize:clear');
    // \Artisan::call("queue:retry all");
    return view('welcome');
});

Auth::routes(['register' => false]);

Route::get('/home', 'HomeController@products')->name('home');
Route::get('/products', 'HomeController@products')->name('products');
Route::get('/orders', 'OrderController@index')->name('orders');
Route::get('/order-detail/{id}', 'OrderController@detail')->name('order-detail');
Route::get('/check-status', 'HomeController@checkStatus')->name('check-status');
Route::get('/check-stock', 'HomeController@checkquantity')->name('check-stock');
Route::get('/check-live', 'HomeController@checkLive')->name('check-live');
Route::get('/fetch-products', 'ProductController@fetchProducts')->name('fetch-products');
Route::get('/push-products', 'ProductController@pushProducts')->name('push-products');
Route::get('/resubmit-product/{id}', 'ProductController@ReSubmit')->name('resubmit-product');
Route::get('/edit-product/{id}', 'ProductController@editProduct')->name('edit-product');
Route::get('/delete-product/{id}', 'ProductController@deleteProduct')->name('delete-product');
Route::get('/add-product', 'ProductController@addProduct')->name('add-product');
Route::get('/push-prices/{id}', 'ProductController@pushPrices')->name('push-prices');
Route::get('/delete-image/{slug}', 'ProductController@deleteImage')->name('delete-image');

Route::get('/print-invoice/{lang}/{id}', 'OrderController@printInvoice')->name('print-invoice');
Route::get('/print-delivery-note/{lang}/{id}', 'OrderController@printDeliveryNote')->name('print-invoice');
// Route::get('/print-bill/{lang}/{id}', 'OrderController@printBill')->name('print-invoice');
Route::get('/print-return-slip/{lang}/{id}', 'OrderController@printReturnSlip')->name('print-invoice');
Route::get('/print-return-flyer/{lang}', 'OrderController@returnFlyer')->name('return-flyer');


Route::post('/add-tracking/{id}', 'OrderController@addTracking')->name('add-tracking');
Route::get('/line-item-status/{orderId}/{itemId}/{lineId}/{status}', 'OrderController@updateLineStatus')->name('update-line-status');



// Route::get('print-delivery-note/{id}',array('as'=>'print-delivery-note','uses'=>'OrderController@printDeliveryNote'));
// Route::get('print-bill/{id}',array('as'=>'print-bill','uses'=>'OrderController@printBill'));
// Route::get('print-return-slip/{id}',array('as'=>'print-return-slip','uses'=>'OrderController@printReturnSlip'));

Route::post('/save-product', 'ProductController@saveProduct')->name('save-product');
Route::post('/webhook/woocommerce', 'OrderController@handleWooCommerceWebhook');
Route::get('/woocommerce/test', 'WebhookController@testWoocommerce');

// Google Sheets Settings Routes
Route::get('/settings', 'SettingsController@index')->name('settings.index');
Route::post('/settings/save-spreadsheet', 'SettingsController@saveSpreadsheet')->name('settings.save-spreadsheet');
Route::get('/settings/test-connection', 'SettingsController@testConnection')->name('settings.test-connection');

// Sync Settings Routes
Route::prefix('sync-settings')->group(function () {
    Route::get('/', 'SyncSettingsController@index')->name('sync-settings.index');
    Route::post('/trigger-full-sync', 'SyncSettingsController@triggerFullSync')->name('sync-settings.trigger-full');
    Route::post('/trigger-incremental-sync', 'SyncSettingsController@triggerIncrementalSync')->name('sync-settings.trigger-incremental');
    Route::post('/force-reset/{syncType}', 'SyncSettingsController@forceReset')->name('sync-settings.force-reset');
    Route::get('/status', 'SyncSettingsController@getSyncStatus')->name('sync-settings.status');
    Route::get('/failed-orders', 'SyncSettingsController@failedOrders')->name('sync-settings.failed-orders');
    Route::post('/failed-orders/{id}/resolve', 'SyncSettingsController@resolveFailedOrder')->name('sync-settings.resolve');
    Route::post('/failed-orders/{id}/retry', 'SyncSettingsController@retryFailedOrder')->name('sync-settings.retry');
});

// Google OAuth Routes
Route::get('/google/redirect', 'GoogleAuthController@redirectToGoogle')->name('google.redirect');
Route::get('/google/callback', 'GoogleAuthController@handleGoogleCallback')->name('google.callback');
Route::get('/google/disconnect', 'GoogleAuthController@disconnect')->name('google.disconnect');
