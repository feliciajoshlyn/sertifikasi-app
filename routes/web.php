<?php

declare(strict_types=1);

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Entry Redirect: route visitors to login or their role-specific landing page.
Route::get('/', function (): RedirectResponse {
    $user = Auth::user();
    if ($user === null) {
        return redirect()->route('login');
    }

    return $user->isAdmin()
        ? redirect()->route('admin.reports.index')
        : redirect()->route('penyewa.rentals.index');
});

// Guest Auth: registration and login are restricted to unauthenticated visitors.
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Authenticated Logout: available to any signed-in user regardless of role.
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Penyewa Routes: protected by EnsureRole ('role:penyewa'), requiring approved status and penyewa role.
Route::prefix('penyewa')
    ->name('penyewa.')
    ->middleware(['auth', 'role:penyewa'])
    ->group(function (): void {
        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
        Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    });

// Admin Routes: protected by EnsureRole ('role:admin'), enforcing Admin-only verification, garage, extension, and report access.
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function (): void {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');

        Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

        Route::get('/rentals/extend', [RentalController::class, 'extendForm'])->name('rentals.extend.form');
        Route::post('/rentals/{rental}/extend', [RentalController::class, 'extend'])->name('rentals.extend');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
