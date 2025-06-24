<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception; 
use Illuminate\Support\Str;
use App\Models\DownloadToken;
use App\Models\ConvertedDocuments;


class ConversionController extends Controller
{
    //

    private function handleLibreOfficeConversion(Request $request, string $fileKey, string $inputSubDir, string $outputExt)
    {
        if (!$request->hasFile($fileKey)) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $files = $request->file($fileKey);
        $userId = $request->user_id;
        if (!is_array($files)) {
            $files = [$files];
        }

        $inputDir = storage_path("app/{$inputSubDir}");
        $outputDir = storage_path('app/converted');
        $publicDir = storage_path('app/public/converted');

        foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
            if (!file_exists($dir)) mkdir($dir, 0777, true);
        }

        $urls = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file: ' . $file->getClientOriginalName());
                }

                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext = $file->getClientOriginalExtension();
                $uniqueName = $filename . '_' . time() . '.' . $ext;

                $file->move($inputDir, $uniqueName);
                $absolutePath = $inputDir . '/' . $uniqueName;

                $inputPath = 'file:///' . str_replace('\\', '/', $absolutePath);
                $outputDirCmdPath = str_replace('\\', '/', $outputDir);

                $soffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
                $cmd = "\"$soffice\" --headless --convert-to {$outputExt} \"$inputPath\" --outdir \"$outputDirCmdPath\" 2>&1";

                exec($cmd, $output, $returnCode);

                \Log::info("LibreOffice Command: $cmd");
                \Log::info("LibreOffice Output: " . implode("\n", $output));

                $convertedName = pathinfo($uniqueName, PATHINFO_FILENAME) . '.' . $outputExt;
                $convertedPath = $outputDir . '/' . $convertedName;

                if (!file_exists($convertedPath)) {
                    throw new \Exception("Conversion failed for $uniqueName.");
                }

                $publicPath = $publicDir . '/' . $convertedName;
                rename($convertedPath, $publicPath);

                $urls[] = asset('storage/converted/' . $convertedName);

                $convertedDoc = null;

                try {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => $fileKey,
                        'convert_into' => $outputExt,
                        'original_name' => $file->getClientOriginalName(),
                        'converted_name' => $convertedName,
                        'original_doc' => "storage/{$inputSubDir}/$uniqueName",
                        'converted_pdf' => "storage/converted/$convertedName",
                    ]);
                } catch (\Exception $ex) {
                    \Log::error("Failed to insert record: " . $ex->getMessage());
                }


            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (count($errors) && count($urls) === 0) {
            return response()->json(['error' => implode("\n", $errors)], 500);
        }


        if ($convertedDoc) {
            $token = Str::random(32);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id,
                'token' => $token,
                'files' => json_encode($urls),
                'expires_at' => now()->addMinutes(30),
            ]);
        }

        return response()->json(['token' => $token]);
    }

    public function convertWordToPdf(Request $request)
    {
        return $this->handleLibreOfficeConversion($request, 'word_file', 'word_files', 'pdf');
    }

    public function convertPdfToWord(Request $request)
    {
        return $this->handleLibreOfficeConversion($request, 'pdf_file', 'pdf_files', 'docx');
    }

    public function convertPptToPdf(Request $request)
    {
        return $this->handleLibreOfficeConversion($request, 'ppt_file', 'ppt_files', 'pdf');
    }

    public function convertExcelToPdf(Request $request)
    {
        return $this->handleLibreOfficeConversion($request, 'excel_file', 'excel_files', 'pdf');
    }

    public function convertHtmlToPdf(Request $request)
    {
        return $this->handleLibreOfficeConversion($request, 'html_file', 'html_files', 'pdf');
    }



   // public function convertWordToPdf(Request $request)
   //  {
   //      // Validate input
   //      if (!$request->hasFile('word_file')) {
   //          return response()->json(['error' => 'No file uploaded'], 400);
   //      }

   //      // Create necessary directories if they don't exist
   //      $wordDir = storage_path('app/word_files');
   //      $outputDir = storage_path('app/converted');
   //      $publicDir = storage_path('app/public/converted');

   //      if (!file_exists($wordDir)) {
   //          mkdir($wordDir, 0777, true);
   //      }

   //      if (!file_exists($outputDir)) {
   //          mkdir($outputDir, 0777, true);
   //      }

   //      if (!file_exists($publicDir)) {
   //          mkdir($publicDir, 0777, true);
   //      }

   //      $files = $request->file('word_file');
   //      $urls = [];
   //      $errors = [];

   //      foreach ($files as $file) {
   //          try {
   //              if (!$file->isValid()) {
   //                  throw new \Exception('Invalid file upload: ' . $file->getClientOriginalName());
   //              }

   //              $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
   //              $extension = $file->getClientOriginalExtension();
   //              $uniqueName = $filename . '_' . time() . '.' . $extension;
                
   //              $file->move($wordDir, $uniqueName); // ensure manual move
   //              $absoluteInputPath = $wordDir . '/' . $uniqueName;

   //              $inputPath = 'file:///' . str_replace('\\', '/', $absoluteInputPath);
   //              $outputDirCmdPath = str_replace('\\', '/', $outputDir);
                
   //              $sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
   //              $command = "\"$sofficePath\" --headless --convert-to pdf \"$inputPath\" --outdir \"$outputDirCmdPath\" 2>&1";

   //              exec($command, $output, $returnCode);

   //              $pdfFilename = pathinfo($uniqueName, PATHINFO_FILENAME) . '.pdf';
   //              $pdfPath = $outputDir . '/' . $pdfFilename;

   //              if (!file_exists($pdfPath)) {
   //                  \Log::error('LibreOffice conversion failed', [
   //                      'command' => $command,
   //                      'output' => $output,
   //                      'returnCode' => $returnCode,
   //                      'inputPathExists' => file_exists($absoluteInputPath),
   //                      'inputPath' => $absoluteInputPath,
   //                  ]);
                    
   //                  throw new \Exception("Conversion failed for {$file->getClientOriginalName()}.");
   //              }

   //              // Move to public directory
   //              $fullPublicPath = $publicDir . '/' . $pdfFilename;
   //              if (!rename($pdfPath, $fullPublicPath)) {
   //                  throw new \Exception("Failed to move PDF to public directory");
   //              }

   //              $urls[] = asset('storage/converted/' . $pdfFilename);
   //          } catch (\Exception $e) {
   //              $errors[] = $e->getMessage();
   //          }
   //      }

   //      if (count($errors) > 0 && count($urls) === 0) {
   //          return response()->json(['error' => implode("\n", $errors)], 500);
   //      }

        
   //      $token = Str::random(32);

   //      DownloadToken::create([
   //          'token' => $token,
   //          'files' => json_encode($urls),
   //          'expires_at' => now()->addMinutes(30),
   //      ]);

   //      return response()->json([
   //          'token' => $token,
   //      ]);
   //  }

   //  public function convertPdfToWord(Request $request)
   //  {
   //      // Validate input
   //      if (!$request->hasFile('pdf_file')) {
   //          return response()->json(['error' => 'No file uploaded'], 400);
   //      }

   //      // Create necessary directories if they don't exist
   //      $pdfDir = storage_path('app/pdf_files');
   //      $outputDir = storage_path('app/converted');
   //      $publicDir = storage_path('app/public/converted');

   //      if (!file_exists($pdfDir)) {
   //          mkdir($pdfDir, 0777, true);
   //      }

   //      if (!file_exists($outputDir)) {
   //          mkdir($outputDir, 0777, true);
   //      }

   //      if (!file_exists($publicDir)) {
   //          mkdir($publicDir, 0777, true);
   //      }

   //      $files = $request->file('pdf_file');
   //      $urls = [];
   //      $errors = [];

   //      if (!is_array($files)) {
   //          $files = [$files]; // normalize single file
   //      }

   //      foreach ($files as $file) {
   //          try {
   //              if (!$file->isValid()) {
   //                  throw new \Exception('Invalid file upload: ' . $file->getClientOriginalName());
   //              }

   //              $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
   //              $extension = $file->getClientOriginalExtension();
   //              $uniqueName = $filename . '_' . time() . '.' . $extension;
                
   //              $file->move($pdfDir, $uniqueName); // ensure manual move
   //              $absoluteInputPath = $pdfDir . '/' . $uniqueName;

   //              $inputPath = 'file:///' . str_replace('\\', '/', $absoluteInputPath);
   //              $outputDirCmdPath = str_replace('\\', '/', $outputDir);
                
   //              $sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
   //              $command = "\"$sofficePath\" --headless --convert-to docx \"$inputPath\" --outdir \"$outputDirCmdPath\" 2>&1";

   //              exec($command, $output, $returnCode);

   //              \Log::info("LibreOffice Command: $command");
   //              \Log::info("LibreOffice Output: " . implode("\n", $output));


   //              $pdfFilename = pathinfo($uniqueName, PATHINFO_FILENAME) . '.docx';
   //              $pdfPath = $outputDir . '/' . $pdfFilename;

   //              if (!file_exists($pdfPath)) {
   //                  \Log::error('LibreOffice conversion failed', [
   //                      'command' => $command,
   //                      'output' => $output,
   //                      'returnCode' => $returnCode,
   //                      'inputPathExists' => file_exists($absoluteInputPath),
   //                      'inputPath' => $absoluteInputPath,
   //                  ]);
                    
   //                  throw new \Exception("Conversion failed for {$file->getClientOriginalName()}.");
   //              }

   //              // Move to public directory
   //              $fullPublicPath = $publicDir . '/' . $pdfFilename;
   //              if (!rename($pdfPath, $fullPublicPath)) {
   //                  throw new \Exception("Failed to move PDF to public directory");
   //              }

   //              $urls[] = asset('storage/converted/' . $pdfFilename);
   //          } catch (\Exception $e) {
   //              $errors[] = $e->getMessage();
   //          }
   //      }

   //      if (count($errors) > 0 && count($urls) === 0) {
   //          return response()->json(['error' => implode("\n", $errors)], 500);
   //      }

        
   //      $token = Str::random(32);

   //      DownloadToken::create([
   //          'token' => $token,
   //          'files' => json_encode($urls),
   //          'expires_at' => now()->addMinutes(30),
   //      ]);

   //      return response()->json([
   //          'token' => $token,
   //      ]);
   //  }

   //  public function convertPptToPdf(Request $request)
   //  {
   //      if (!$request->hasFile('ppt_file')) {
   //          return response()->json(['error' => 'No file uploaded'], 400);
   //      }

   //      $files = $request->file('ppt_file');
   //      if (!is_array($files)) {
   //          $files = [$files];
   //      }

   //      $inputDir = storage_path('app/ppt_files');
   //      $outputDir = storage_path('app/converted');
   //      $publicDir = storage_path('app/public/converted');

   //      foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
   //          if (!file_exists($dir)) mkdir($dir, 0777, true);
   //      }

   //      $urls = [];
   //      $errors = [];

   //      foreach ($files as $file) {
   //          try {
   //              if (!$file->isValid()) {
   //                  throw new \Exception('Invalid file: ' . $file->getClientOriginalName());
   //              }

   //              $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
   //              $ext = $file->getClientOriginalExtension();
   //              $uniqueName = $filename . '_' . time() . '.' . $ext;

   //              $file->move($inputDir, $uniqueName);
   //              $absolutePath = $inputDir . '/' . $uniqueName;

   //              $inputPath = 'file:///' . str_replace('\\', '/', $absolutePath);
   //              $outputDirCmdPath = str_replace('\\', '/', $outputDir);

   //              $soffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
   //              $cmd = "\"$soffice\" --headless --convert-to pdf \"$inputPath\" --outdir \"$outputDirCmdPath\" 2>&1";

   //              exec($cmd, $output, $returnCode);

   //              \Log::info("LibreOffice Command: $cmd");
   //              \Log::info("LibreOffice Output: " . implode("\n", $output));

   //              $pdfName = pathinfo($uniqueName, PATHINFO_FILENAME) . '.pdf';
   //              $pdfPath = $outputDir . '/' . $pdfName;

   //              if (!file_exists($pdfPath)) {
   //                  throw new \Exception("Conversion failed for $uniqueName.");
   //              }

   //              $publicPath = $publicDir . '/' . $pdfName;
   //              rename($pdfPath, $publicPath);

   //              $urls[] = asset('storage/converted/' . $pdfName);
   //          } catch (\Exception $e) {
   //              $errors[] = $e->getMessage();
   //          }
   //      }

   //      if (count($errors) && count($urls) === 0) {
   //          return response()->json(['error' => implode("\n", $errors)], 500);
   //      }

   //      $token = Str::random(32);
   //      DownloadToken::create([
   //          'token' => $token,
   //          'files' => json_encode($urls),
   //          'expires_at' => now()->addMinutes(30),
   //      ]);

   //      return response()->json(['token' => $token]);
   //  }

   //  public function convertExcelToPdf(Request $request)
   //  {
   //      if (!$request->hasFile('excel_file')) {
   //          return response()->json(['error' => 'No file uploaded'], 400);
   //      }

   //      $files = $request->file('excel_file');
   //      if (!is_array($files)) {
   //          $files = [$files];
   //      }

   //      $inputDir = storage_path('app/excel_files');
   //      $outputDir = storage_path('app/converted');
   //      $publicDir = storage_path('app/public/converted');

   //      foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
   //          if (!file_exists($dir)) mkdir($dir, 0777, true);
   //      }

   //      $urls = [];
   //      $errors = [];

   //      foreach ($files as $file) {
   //          try {
   //              if (!$file->isValid()) {
   //                  throw new \Exception('Invalid file: ' . $file->getClientOriginalName());
   //              }

   //              $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
   //              $ext = $file->getClientOriginalExtension();
   //              $uniqueName = $filename . '_' . time() . '.' . $ext;

   //              $file->move($inputDir, $uniqueName);
   //              $absolutePath = $inputDir . '/' . $uniqueName;

   //              $inputPath = 'file:///' . str_replace('\\', '/', $absolutePath);
   //              $outputDirCmdPath = str_replace('\\', '/', $outputDir);

   //              $soffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
   //              $cmd = "\"$soffice\" --headless --convert-to pdf \"$inputPath\" --outdir \"$outputDirCmdPath\" 2>&1";

   //              exec($cmd, $output, $returnCode);

   //              \Log::info("LibreOffice Command: $cmd");
   //              \Log::info("LibreOffice Output: " . implode("\n", $output));

   //              $pdfName = pathinfo($uniqueName, PATHINFO_FILENAME) . '.pdf';
   //              $pdfPath = $outputDir . '/' . $pdfName;

   //              if (!file_exists($pdfPath)) {
   //                  throw new \Exception("Conversion failed for $uniqueName.");
   //              }

   //              $publicPath = $publicDir . '/' . $pdfName;
   //              rename($pdfPath, $publicPath);

   //              $urls[] = asset('storage/converted/' . $pdfName);
   //          } catch (\Exception $e) {
   //              $errors[] = $e->getMessage();
   //          }
   //      }

   //      if (count($errors) && count($urls) === 0) {
   //          return response()->json(['error' => implode("\n", $errors)], 500);
   //      }

   //      $token = Str::random(32);
   //      DownloadToken::create([
   //          'token' => $token,
   //          'files' => json_encode($urls),
   //          'expires_at' => now()->addMinutes(30),
   //      ]);

   //      return response()->json(['token' => $token]);
   //  }


   //  public function convertHtmlToPdf(Request $request)
   //  {
   //      if (!$request->hasFile('html_file')) {
   //          return response()->json(['error' => 'No file uploaded'], 400);
   //      }

   //      $files = $request->file('html_file');
   //      if (!is_array($files)) {
   //          $files = [$files];
   //      }

   //      $inputDir = storage_path('app/html_files');
   //      $outputDir = storage_path('app/converted');
   //      $publicDir = storage_path('app/public/converted');

   //      foreach ([$inputDir, $outputDir, $publicDir] as $dir) {
   //          if (!file_exists($dir)) mkdir($dir, 0777, true);
   //      }

   //      $urls = [];
   //      $errors = [];

   //      foreach ($files as $file) {
   //          try {
   //              if (!$file->isValid()) {
   //                  throw new \Exception('Invalid file: ' . $file->getClientOriginalName());
   //              }

   //              $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
   //              $ext = $file->getClientOriginalExtension();
   //              $uniqueName = $filename . '_' . time() . '.' . $ext;

   //              $file->move($inputDir, $uniqueName);
   //              $absolutePath = $inputDir . '/' . $uniqueName;

   //              $inputPath = 'file:///' . str_replace('\\', '/', $absolutePath);
   //              $outputDirCmdPath = str_replace('\\', '/', $outputDir);

   //              $soffice = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
   //              $cmd = "\"$soffice\" --headless --convert-to pdf \"$inputPath\" --outdir \"$outputDirCmdPath\" 2>&1";

   //              exec($cmd, $output, $returnCode);

   //              \Log::info("LibreOffice Command: $cmd");
   //              \Log::info("LibreOffice Output: " . implode("\n", $output));

   //              $pdfName = pathinfo($uniqueName, PATHINFO_FILENAME) . '.pdf';
   //              $pdfPath = $outputDir . '/' . $pdfName;

   //              if (!file_exists($pdfPath)) {
   //                  throw new \Exception("Conversion failed for $uniqueName.");
   //              }

   //              $publicPath = $publicDir . '/' . $pdfName;
   //              rename($pdfPath, $publicPath);

   //              $urls[] = asset('storage/converted/' . $pdfName);
   //          } catch (\Exception $e) {
   //              $errors[] = $e->getMessage();
   //          }
   //      }

   //      if (count($errors) && count($urls) === 0) {
   //          return response()->json(['error' => implode("\n", $errors)], 500);
   //      }

   //      $token = Str::random(32);
   //      DownloadToken::create([
   //          'token' => $token,
   //          'files' => json_encode($urls),
   //          'expires_at' => now()->addMinutes(30),
   //      ]);

   //      return response()->json(['token' => $token]);
   //  }

}
