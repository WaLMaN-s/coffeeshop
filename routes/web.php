<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Site\AkunController;
use App\Http\Controllers\Site\BerandaController;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\KeranjangController;
use App\Http\Controllers\Site\MejaController;
use App\Http\Controllers\Site\PesananController as SitePesananController;
use App\Http\Controllers\Kasir;
use App\Http\Controllers\Admin;

/*
|--------------------------------------------------------------------------
| URL sengaja dipertahankan sama persis dengan versi non-framework
| (meja.php, kasir/pesanan.php, dst) supaya QR meja yang sudah dicetak,
| bookmark, dan semua link relatif di view lama tetap berfungsi.
|--------------------------------------------------------------------------
*/

/* ---------- Pelanggan ---------- */
Route::match(['get', 'post'], 'meja.php', [MejaController::class, 'form']);
Route::get('keluar.php', [MejaController::class, 'keluar']);
Route::get('masuk.php', fn () => redirect('meja.php'));
Route::get('daftar.php', fn () => redirect('meja.php'));

Route::middleware('meja')->group(function () {
    Route::get('/', [BerandaController::class, 'index']);
    Route::get('index.php', [BerandaController::class, 'index']);
    Route::match(['get', 'post'], 'keranjang.php', [KeranjangController::class, 'index']);
    Route::match(['get', 'post'], 'checkout.php', [CheckoutController::class, 'index']);
    Route::get('pesanan_saya.php', [SitePesananController::class, 'daftar']);
    Route::match(['get', 'post'], 'pesanan_lihat.php', [SitePesananController::class, 'lihat']);
    Route::get('pesanan_status.php', [SitePesananController::class, 'status']);
    Route::match(['get', 'post'], 'akun.php', [AkunController::class, 'index']);
});

/* ---------- Kasir ---------- */
Route::prefix('kasir')->group(function () {
    Route::match(['get', 'post'], 'login.php', [Kasir\AuthController::class, 'login']);
    Route::get('logout.php', [Kasir\AuthController::class, 'logout']);

    Route::middleware('kasir')->group(function () {
        Route::get('/', [Kasir\DashboardController::class, 'index']);
        Route::get('index.php', [Kasir\DashboardController::class, 'index']);
        Route::get('pesanan.php', [Kasir\PesananController::class, 'daftar']);
        Route::match(['get', 'post'], 'pesanan_detail.php', [Kasir\PesananController::class, 'detail']);
        Route::match(['get', 'post'], 'pesanan_baru.php', [Kasir\PosController::class, 'index']);
        Route::get('struk.php', [Kasir\PesananController::class, 'struk']);
        Route::match(['get', 'post'], 'pembayaran.php', [Kasir\PembayaranController::class, 'index']);
        Route::get('pendapatan.php', [Kasir\PendapatanController::class, 'index']);
        Route::match(['get', 'post'], 'pelanggan.php', [Kasir\PelangganController::class, 'index']);
        Route::match(['get', 'post'], 'api/notifikasi.php', [Kasir\NotifikasiController::class, 'index']);
    });
});

/* ---------- Admin ---------- */
Route::prefix('admin')->group(function () {
    Route::match(['get', 'post'], 'login.php', [Admin\AuthController::class, 'login']);
    Route::get('logout.php', [Admin\AuthController::class, 'logout']);

    Route::middleware('admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index']);
        Route::get('index.php', [Admin\DashboardController::class, 'index']);
        Route::match(['get', 'post'], 'menu.php', [Admin\MenuController::class, 'index']);
        Route::match(['get', 'post'], 'kategori.php', [Admin\KategoriController::class, 'index']);
        Route::match(['get', 'post'], 'meja.php', [Admin\MejaController::class, 'index']);
        Route::get('meja_cetak.php', [Admin\MejaController::class, 'cetak']);
        Route::get('scan_test.php', [Admin\MejaController::class, 'scanTest']);
        Route::get('pesanan.php', [Admin\PesananController::class, 'daftar']);
        Route::match(['get', 'post'], 'pesanan_detail.php', [Admin\PesananController::class, 'detail']);
        Route::match(['get', 'post'], 'pembayaran.php', [Admin\PembayaranController::class, 'index']);
        Route::match(['get', 'post'], 'pelanggan.php', [Admin\PelangganController::class, 'index']);
        Route::match(['get', 'post'], 'kasir.php', [Admin\KasirAkunController::class, 'index']);
        Route::get('laporan.php', [Admin\LaporanController::class, 'index']);
        Route::get('laporan_excel.php', [Admin\LaporanController::class, 'excel']);
        Route::match(['get', 'post'], 'pengaturan.php', [Admin\PengaturanController::class, 'index']);
        Route::match(['get', 'post'], 'api/notifikasi.php', [Admin\NotifikasiController::class, 'index']);
        Route::post('api/simpan_qr.php', [Admin\MejaController::class, 'simpanQr']);
    });
});
