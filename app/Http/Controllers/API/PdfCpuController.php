<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\DownloadToken;

class PdfCpuController extends Controller
{
    
    public function merge(Request $request)
    {
        if (!$request->hasFile('pdf_files')) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        $files = $request->file('pdf_files');
        if (count($files) < 2) {
            return response()->json(['error' => 'Upload at least 2 PDF files'], 400);
        }

        $inputDir = storage_path('app/pdf_inputs');
        $outputDir = storage_path('app/converted');
        $publicDir = storage_path('app/public/converted');

        foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
            if (!file_exists($dir)) mkdir($dir, 0777, true);
        }

        $inputPaths = [];
        foreach ($files as $file) {
            $name = time() . '_' . $file->getClientOriginalName();
            $file->move($inputDir, $name);
            $inputPaths[] = $inputDir . '/' . $name;
        }

        $mergedName = 'merged_' . time() . '.pdf';
        $mergedPath = $outputDir . '/' . $mergedName;

        $pdfcpuPath = 'C:\pdfcpu\pdfcpu.exe'; // Use your correct path

        $command = "\"$pdfcpuPath\" merge \"$mergedPath\" " . implode(' ', array_map('escapeshellarg', $inputPaths));
        exec($command, $output, $code);

        \Log::info("LibreOffice Command: $command");

        if ($code !== 0 || !file_exists($mergedPath)) {
            return response()->json([
                'error' => 'PDF merge failed',
                'command' => $command,
                'output' => $output,
            ], 500);
        }

        rename($mergedPath, $publicDir . '/' . $mergedName);

        $url = asset('storage/converted/' . $mergedName);
        $urls = [$url];


        $token = Str::random(32);
        DownloadToken::create([
            'token' => $token,
            'files' => json_encode($urls),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json(['token' => $token]);
    }

    public function split(Request $request)
    {
        if (!$request->hasFile('pdf_file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('pdf_file');
        if (!$file->isValid()) {
            return response()->json(['error' => 'Invalid file'], 400);
        }

        $inputDir = storage_path('app/pdf_inputs');
        $outputDir = storage_path('app/converted');
        $publicDir = storage_path('app/public/converted');

        foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
            if (!file_exists($dir)) mkdir($dir, 0777, true);
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $inputPath = $inputDir . '/' . $filename;
        $file->move($inputDir, $filename);

        $pdfcpuPath = 'C:\pdfcpu\pdfcpu.exe';
        $command = "\"$pdfcpuPath\" split \"$inputPath\" \"$outputDir\"";
        exec($command, $output, $code);

        if ($code !== 0) {
            return response()->json([
                'error' => 'Split failed',
                'command' => $command,
                'output' => $output,
            ], 500);
        }

        // Move all split files to public dir
        $splitFiles = glob($outputDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_*.pdf');
        $urls = [];

        foreach ($splitFiles as $splitFile) {
            $publicPath = $publicDir . '/' . basename($splitFile);
            rename($splitFile, $publicPath);
            $urls[] = asset('storage/converted/' . basename($splitFile));
        }

        $token = Str::random(32);
        DownloadToken::create([
            'token' => $token,
            'files' => json_encode($urls),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json(['token' => $token]);
    }

    public function compress(Request $request)
    {
        if (!$request->hasFile('pdf_file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $level = $request->input('level', 'medium');

        // Validate level
        if (!in_array($level, ['low', 'medium', 'high'])) {
            return response()->json(['error' => 'Invalid compression level. Use: low, medium, or high'], 400);
        }

        $file = $request->file('pdf_file');
        if (!$file->isValid()) {
            return response()->json(['error' => 'Invalid file'], 400);
        }

        $inputDir = storage_path('app/pdf_inputs');
        $outputDir = storage_path('app/converted');
        $publicDir = storage_path('app/public/converted');

        foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
            if (!file_exists($dir)) mkdir($dir, 0777, true);
        }

        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . $originalName;
        $inputPath = $inputDir . '/' . $filename;
        $file->move($inputDir, $filename);

        $compressedName = "compressed_{$level}_" . time() . '.pdf';
        $compressedPath = $outputDir . '/' . $compressedName;

        $pdfcpuPath = 'C:\pdfcpu\pdfcpu.exe';

        // 🔁 Build command based on level
        $qualityFlag = match ($level) {
            'low' => '-mode=compress -imageQuality=80 -resample=150',
            'medium' => '-mode=compress -imageQuality=60 -resample=100',
            'high' => '-mode=compress -imageQuality=30 -resample=72',
        };

        $command = "\"$pdfcpuPath\" optimize -mode compress \"$inputPath\" \"$compressedPath\"";
        exec($command, $output, $code);

        if ($code !== 0 || !file_exists($compressedPath)) {
            return response()->json([
                'error' => 'Compression failed',
                'command' => $command,
                'output' => $output,
            ], 500);
        }

        // Move to public folder
        $publicPath = $publicDir . '/' . $compressedName;
        rename($compressedPath, $publicPath);

        $url = asset('storage/converted/' . $compressedName);
        $urls = [$url];

        $token = Str::random(32);
        DownloadToken::create([
            'token' => $token,
            'files' => json_encode($urls),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json(['token' => $token]);
    }


}
