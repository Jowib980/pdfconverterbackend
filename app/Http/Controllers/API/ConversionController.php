<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception; 
use Illuminate\Support\Str;
use App\Models\DownloadToken;
use App\Models\ConvertedDocuments;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;



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


    // using php office library

    public function convertWord(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $userId = $request->user_id;

        if ($file->getClientOriginalExtension() !== 'docx') {
            return response()->json(['error' => 'Only .docx files are supported'], 400);
        }

        try {
            $phpWord = WordIOFactory::load($file->getPathname());

            ob_start();
            \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->save('php://output');
            $htmlContent = ob_get_clean();

            $uniqueId = Str::uuid();
            $filename = "converted_{$uniqueId}.pdf";
            $relativePath = "converted/{$filename}";
            $pdfPath = storage_path("app/public/" . $relativePath);

            \PDF::loadHTML($htmlContent)->save($pdfPath);

            $convertedDoc = ConvertedDocuments::create([
                'user_id' => $userId,
                'file_type' => 'word_files',
                'convert_into' => 'pdf',
                'original_name' => $file->getClientOriginalName(),
                'converted_name' => $filename,
                'original_doc' => $file->store('originals', 'public'),
                'converted_pdf' => $relativePath,
            ]);

            $token = Str::random(32);
            $url = asset('storage/' . $relativePath);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id,
                'token' => $token,
                'files' => json_encode([$url]),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'url' => $url,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }


    public function convertExcel(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $userId = $request->user_id;

        if (!in_array($file->getClientOriginalExtension(), ['xls', 'xlsx'])) {
            return response()->json(['error' => 'Only Excel files (.xls, .xlsx) are supported'], 400);
        }

        try {
            // Load the Excel file
            $spreadsheet = SpreadsheetIOFactory::load($file->getPathname());

            // Convert to HTML
            ob_start();
            $writer = new HtmlWriter($spreadsheet);
            $writer->save('php://output');
            $htmlContent = ob_get_clean();

            // Convert HTML to PDF
            $uniqueId = Str::uuid();
            $filename = "converted_{$uniqueId}.pdf";
            $relativePath = "converted/{$filename}";
            $pdfPath = storage_path("app/public/" . $relativePath);

            \PDF::loadHTML($htmlContent)->save($pdfPath);

            // Save record in DB
            $convertedDoc = ConvertedDocuments::create([
                'user_id' => $userId,
                'file_type' => 'excel_files',
                'convert_into' => 'pdf',
                'original_name' => $file->getClientOriginalName(),
                'converted_name' => $filename,
                'original_doc' => $file->store('originals', 'public'),
                'converted_pdf' => $relativePath,
            ]);

            // Generate token
            $token = Str::random(32);
            $url = asset('storage/' . $relativePath);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id,
                'token' => $token,
                'files' => json_encode([$url]),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'url' => $url,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }

    public function convertPPT(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $userId = $request->user_id;

        if ($file->getClientOriginalExtension() !== 'pptx') {
            return response()->json(['error' => 'Only .pptx files are supported'], 400);
        }

        try {
            $presentation = PresentationIOFactory::load($file->getPathname());

            ob_start();
            $writer = new HtmlWriter($presentation);
            $writer->save("php://output");
            $htmlContent = ob_get_clean();

            $uniqueId = Str::uuid();
            $filename = "converted_{$uniqueId}.pdf";
            $relativePath = "converted/{$filename}";
            $pdfPath = storage_path("app/public/" . $relativePath);

            \PDF::loadHTML($htmlContent)->save($pdfPath);

            $convertedDoc = ConvertedDocuments::create([
                'user_id' => $userId,
                'file_type' => 'ppt_files',
                'convert_into' => 'pdf',
                'original_name' => $file->getClientOriginalName(),
                'converted_name' => $filename,
                'original_doc' => $file->store('originals', 'public'),
                'converted_pdf' => $relativePath,
            ]);

            $token = Str::random(32);
            $url = asset('storage/' . $relativePath);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id,
                'token' => $token,
                'files' => json_encode([$url]),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'url' => $url,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }

    public function convertHtml(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $userId = $request->user_id;

        if (!in_array($file->getClientOriginalExtension(), ['html', 'htm'])) {
            return response()->json(['error' => 'Only HTML files are supported'], 400);
        }

        try {
            $htmlContent = file_get_contents($file->getPathname());

            $uniqueId = Str::uuid();
            $filename = "converted_{$uniqueId}.pdf";
            $relativePath = "converted/{$filename}";
            $pdfPath = storage_path("app/public/" . $relativePath);

            \PDF::loadHTML($htmlContent)->save($pdfPath);

            $convertedDoc = ConvertedDocuments::create([
                'user_id' => $userId,
                'file_type' => 'html_files',
                'convert_into' => 'pdf',
                'original_name' => $file->getClientOriginalName(),
                'converted_name' => $filename,
                'original_doc' => $file->store('originals', 'public'),
                'converted_pdf' => $relativePath,
            ]);

            $token = Str::random(32);
            $url = asset('storage/' . $relativePath);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id,
                'token' => $token,
                'files' => json_encode([$url]),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'url' => $url,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }

   
}
