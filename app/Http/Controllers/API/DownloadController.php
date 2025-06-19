<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DownloadToken;
use Illuminate\Support\Facades\Response;
use ZipArchive;

class DownloadController extends Controller
{
    
    public function fetchDownload($token)
    {
        $record = DownloadToken::where('token', $token)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->first();

        if (!$record) {
            return response()->json([
                'error' => 'Invalid or expired token'
            ], 404);
        }

        return response()->json([
            'urls' => json_decode($record->files, true)
        ]);
    }

    public function handleDownload($token)
{
    $record = DownloadToken::where('token', $token)->first();
    if (!$record) abort(404);

    $files = json_decode($record->files, true);

    if (count($files) === 1) {
        $file = basename($files[0]); // Extract only filename
        $path = storage_path("app/public/converted/{$file}");
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Access-Control-Allow-Origin' => '*',
            'Content-Disposition' => 'attachment; filename="' . $file . '"'
        ]);
    }

    // Multiple files – create zip
    $zip = new \ZipArchive();
    $zipFile = storage_path("app/temp/download_{$token}.zip");

    if (!file_exists(dirname($zipFile))) {
        mkdir(dirname($zipFile), 0777, true);
    }

    if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
        
        foreach ($files as $fileUrl) {
            $fileName = basename($fileUrl);
            $filePath = storage_path("app/public/converted/" . $fileName);

            if (file_exists($filePath)) {
                $zip->addFile($filePath, $fileName);
            }
        }
        $zip->close();
    }

    return response()->download($zipFile, "converted_pdfs_{$token}.zip", [
        'Content-Type' => 'application/zip',
        'Access-Control-Allow-Origin' => '*',
        'Content-Disposition' => 'attachment; filename="converted_pdfs_' . $token . '.zip"'
    ])->deleteFileAfterSend(true);

}


}
