<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangMasukController;
use App\Http\Controllers\BarangKeluarController;
use App\Http\Controllers\NotifikasiStokController;
use App\Http\Controllers\LaporanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // UMKM routes - Role checking handled in controller
    Route::post('/umkm', [UmkmController::class, 'store']);
    Route::put('/umkm', [UmkmController::class, 'update']);
    Route::get('/umkm', [UmkmController::class, 'show']);

    // Petugas routes - Only accessible by admin
    Route::get('/petugas', [PetugasController::class, 'index']);
    Route::post('/petugas', [PetugasController::class, 'store']);
    Route::get('/petugas/{id}', [PetugasController::class, 'show']);
    Route::put('/petugas/{id}', [PetugasController::class, 'update']);
    Route::delete('/petugas/{id}', [PetugasController::class, 'destroy']);

    // Supplier routes - Accessible by admin and petugas
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    // Kategori routes - Accessible by admin and petugas
    Route::get('/categories', [KategoriController::class, 'index']);
    Route::post('/categories', [KategoriController::class, 'store']);
    Route::get('/categories/{id}', [KategoriController::class, 'show']);
    Route::put('/categories/{id}', [KategoriController::class, 'update']);
    Route::delete('/categories/{id}', [KategoriController::class, 'destroy']);

    // Barang routes - Accessible by admin and petugas
    Route::get('/products', [BarangController::class, 'index']);
    Route::post('/products', [BarangController::class, 'store']);
    Route::get('/products/{id}', [BarangController::class, 'show']);
    Route::put('/products/{id}', [BarangController::class, 'update']);
    Route::delete('/products/{id}', [BarangController::class, 'destroy']);

    // Barang Masuk routes - Accessible by admin and petugas
    Route::get('/stock-in', [BarangMasukController::class, 'index']);
    Route::post('/stock-in', [BarangMasukController::class, 'store']);
    Route::get('/stock-in/{id}', [BarangMasukController::class, 'show']);
    Route::delete('/stock-in/{id}', [BarangMasukController::class, 'destroy']);

    // Barang Keluar routes - Accessible by admin and petugas
    Route::get('/stock-out', [BarangKeluarController::class, 'index']);
    Route::post('/stock-out', [BarangKeluarController::class, 'store']);
    Route::get('/stock-out/{id}', [BarangKeluarController::class, 'show']);
    Route::delete('/stock-out/{id}', [BarangKeluarController::class, 'destroy']);

    // Notification routes - Accessible by admin and petugas
    Route::get('/notifications', [NotifikasiStokController::class, 'index']);
    Route::get('/notifications/unread', [NotifikasiStokController::class, 'unread']);
    Route::put('/notifications/{id}/read', [NotifikasiStokController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotifikasiStokController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotifikasiStokController::class, 'destroy']);

    // Report routes - Accessible by admin and petugas
    Route::get('/reports/stock-in', [LaporanController::class, 'stockInReport']);
    Route::get('/reports/stock-out', [LaporanController::class, 'stockOutReport']);
    Route::get('/reports/stock-summary', [LaporanController::class, 'stockSummary']);
    Route::get('/reports/export/stock-in', [LaporanController::class, 'exportStockIn']);
    Route::get('/reports/export/stock-out', [LaporanController::class, 'exportStockOut']);
});
