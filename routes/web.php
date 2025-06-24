<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ConversionController;

Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // user section
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/add-user', [UserController::class, 'create'])->name('add-user');
    Route::post('/save-user', [UserController::class, 'store'])->name('save-user');
    Route::get('/edit-user/{id}', [UserController::class, 'edit'])->name('edit-user');
    Route::put('/update-user/{id}', [UserController::class, 'update'])->name('update-user');
    Route::get('/view-user/{id}', [UserController::class, 'view'])->name('view-user');
    Route::delete('/delete-user/{id}', [UserController::class, 'destroy'])->name('delete-user');
    Route::delete('/bulk-delete-users', [UserController::class, 'bulkDelete'])->name('bulk-delete-users');

    // file section
    Route::get('/all-files', [ConversionController::class, 'index'])->name('all-files');
    Route::delete('/delete-file/{id}', [ConversionController::class, 'destroy'])->name('delete-file');
    Route::delete('/bulk-delete-files', [ConversionController::class, 'bulkDelete'])->name('bulk-delete-files');

});



require __DIR__.'/auth.php';
