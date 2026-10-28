<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
foreach (['about', 'services', 'contact', 'privacy'] as $page) {
    Route::get('/'.$page, fn () => app(SiteController::class)->page($page));
}
Route::get('/services/{service}', [SiteController::class, 'service']);
Route::get('/request/{type}', [SiteController::class, 'form']);
Route::post('/request/{type}', [SiteController::class, 'submit'])->middleware('throttle:5,1')->block(10, 10);
Route::view('/thank-you', 'thanks');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nSitemap: ".rtrim(config('app.url'), '/')."/sitemap.xml\n", 200, ['Content-Type' => 'text/plain']));
Route::get('/sitemap.xml', function () {
    $paths = ['', '/about', '/services', '/contact'];
    foreach (array_keys(config('company.services')) as $slug) {
        $paths[] = '/services/'.$slug;
    }$xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($paths as $p) {
        $xml .= '<url><loc>'.htmlspecialchars(rtrim(config('app.url'), '/').$p, ENT_XML1).'</loc></url>';
    }

    return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
});
Route::middleware('guest')->group(function () {
    Route::view('/admin/login', 'login')->name('login');
    Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:5,1');
});
Route::middleware(['auth', 'private.admin'])->group(function () {
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::view('/admin/password', 'password');
    Route::patch('/admin/password', [AdminController::class, 'password'])->middleware('throttle:5,1');
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/admin/enquiries/{enquiry}', [AdminController::class, 'show']);
    Route::patch('/admin/enquiries/{enquiry}', [AdminController::class, 'update']);
    Route::view('/admin/settings', 'settings');
    Route::patch('/admin/settings', [AdminController::class, 'settings']);
});
