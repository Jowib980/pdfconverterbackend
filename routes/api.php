<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ConversionController;
use App\Http\Controllers\API\DownloadController;
use App\Http\Controllers\API\PdfCpuController;
use App\Http\Controllers\API\GoogleAuthController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\PaymentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::get('/current-user', [AuthController::class, 'currentUser']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Add protected routes here
});

Route::post('/convert/word-to-pdf', [ConversionController::class, 'convertWordToPdf'])->name('word-to-pdf');
Route::post('/convert/ppt-to-pdf', [ConversionController::class, 'convertPptToPdf']);
Route::post('/convert/excel-to-pdf', [ConversionController::class, 'convertExcelToPdf']);
Route::post('/convert/html-to-pdf', [ConversionController::class, 'convertHtmlToPdf']);

Route::get('/fetch-download/{token}', [DownloadController::class, 'fetchDownload']);
Route::get('/download-file/{token}', [DownloadController::class, 'handleDownload']);

Route::post('/convert/pdf-to-word', [ConversionController::class, 'convertPdfToWord']);

Route::post('/merge-pdf', [PdfCpuController::class, 'merge']);
Route::post('/split-pdf', [PdfCpuController::class, 'split']);
Route::post('/compress-pdf', [PdfCpuController::class, 'compress']);
Route::post('/rotate-pdf', [PdfCpuController::class, 'rotate']);


Route::post('/auth/google-login', [GoogleAuthController::class, 'handleGoogleLogin']);

Route::post('/convert-word', [ConversionController::class, 'convertWord']);
Route::post('/convert-excel', [ConversionController::class, 'convertExcel']);
Route::post('/convert-ppt', [ConversionController::class, 'convertPPT']);
Route::post('/convert-html', [ConversionController::class, 'convertHtml']);
Route::post('/convert-jpg', [ConversionController:: class, 'convertJPG']);
Route::post('/merge', [ConversionController:: class, 'mergePdf']);
Route::post('/split', [ConversionController:: class, 'splitPdf']);
Route::post('/rotate', [ConversionController:: class, 'rotatePdf']);
Route::post('/watermark', [ConversionController:: class, 'watermarkPdf']);

Route::post('/sent-message', [ContactController:: class, 'create']);

Route::post('/save-payment', [PaymentController:: class, 'store']);
Route::post('/create-razorpay-order', [PaymentController::class, 'createRazorpayOrder']);
Route::get('/current-plan/{id}', [PaymentController::class, 'currentPlan']);

Route::get('/gateways', [PaymentController::class, 'allGateway']);

Route::get('/config', function () {
    return response()->json([
        'googleClientId' => env('GOOGLE_CLIENT_ID'),
        'paypalClientId' => env('PAYPAL_CLIENT_ID'),
        'razorpayKey' => env('RAZORPAY_KEY'),
    ]);
});
