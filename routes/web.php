<?php

use App\Http\Controllers\Admin\LogApplicationController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LogViewController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Language switcher
Route::get('/lang/{locale}', function (string $locale) {
    $supported = ['en', 'vi'];
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return redirect()->back()->withInput();
})->name('lang.switch')->where('locale', '[a-z]{2}');

// Redirect root
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('logs.index')
        : redirect()->route('login');
});

// Auth routes
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Log viewer
    Route::get('/logs', [LogViewController::class, 'index'])->name('logs.index');
    Route::get('/logs/{logApp}', [LogViewController::class, 'show'])->name('logs.show');
    Route::get('/logs/{logApp}/fetch', [LogViewController::class, 'fetch'])->name('logs.fetch');
    Route::post('/logs/{logApp}/execute', [LogViewController::class, 'executeScript'])->name('logs.execute');
    Route::post('/logs/{logApp}/git-pull', [LogViewController::class, 'gitPull'])->name('logs.git-pull');
    Route::post('/logs/{logApp}/restart', [LogViewController::class, 'executeRestart'])->name('logs.restart');
    Route::post('/logs/{logApp}/button/{index}', [LogViewController::class, 'executeButton'])->name('logs.button');

    // Profile (tất cả user đều có thể sửa thông tin của mình)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Admin panel
    Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {

        Route::get('/', function () {
            return redirect()->route('admin.servers.index');
        })->name('dashboard');

        // Servers CRUD + SSH / Agent test
        Route::resource('servers', ServerController::class);
        Route::post('/servers/test-ssh',   [ServerController::class, 'testSsh'])->name('servers.test-ssh');
        Route::post('/servers/test-agent', [ServerController::class, 'testAgent'])->name('servers.test-agent');
        Route::get('/servers/{server}/browse-server', [ServerController::class, 'browseServer'])->name('servers.browse-server');
        Route::get('/servers/{server}/browse-agent',  [ServerController::class, 'browseAgent'])->name('servers.browse-agent');

        // Log Applications CRUD
        Route::resource('log-apps', LogApplicationController::class);

        // Tags CRUD
        Route::resource('tags', TagController::class)->except(['show']);

        // Users management
        Route::get('/users',             [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create',      [UserController::class, 'create'])->name('users.create');
        Route::post('/users',            [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}',      [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',   [UserController::class, 'destroy'])->name('users.destroy');
    });
});
