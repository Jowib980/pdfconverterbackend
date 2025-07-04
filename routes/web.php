<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ConversionController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Admin\RoleController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\API\PaymentController;

Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('web')->post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // user section
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashabord');
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
   
    Route::get('/all-payments', [PaymentController::class, 'index'])->name('all-payments');
    Route::get('/view-payment/{id}', [PaymentController::class, 'view'])->name('view-payment');
    Route::delete('/delete-payment/{id}', [PaymentController::class, 'destroy'])->name('delete-payment');
    Route::delete('/bulk-delete-payment', [PaymentController::class, 'bulkDelete'])->name('bulk-delete-payment');

    Route::resource('roles', RoleController::class);


   
});

 Route::get('/setup-roles', [RolePermissionController::class, 'setupRolesAndPermissions']);

 Route::get('/auth/token-login', function (Request $request) {
    $token = $request->query('token');

    $user = User::where('login_token', $token)
        ->where('login_token_expires_at', '>', now())
        ->first();

    if (! $user) {
        abort(401, 'Invalid or expired token');
    }

    // Log the user in using Laravel's session (cookie)
    Auth::login($user);
    

    // Invalidate the token after use
    $user->login_token = null;
    $user->login_token_expires_at = null;
    $user->save();

    return redirect('/dashboard');
});

require __DIR__.'/auth.php';
