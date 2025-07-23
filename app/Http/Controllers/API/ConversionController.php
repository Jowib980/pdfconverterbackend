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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use App\PDF\PdfWithAlpha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use DomPDF\DomPDF;
use DomPDF\Options;


class PdfWithRotation extends Fpdi
{
    protected $angle = 0;

    public function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;

            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.5F %.5F cm',
                $c, $s, -$s, $c,
                $cx - $c * $cx + $s * $cy,
                $cy - $s * $cx - $c * $cy));
        }
    }

    public function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}


class ConversionController extends Controller
{
    //

    private function handleLibreOfficeConversion(Request $request, string $fileKey, string $inputSubDir, string $outputExt)
    {
        if (!$request->hasFile($fileKey)) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $files = $request->file($fileKey);
        $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;
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
        $token = null;

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

        return response()->json([
            'token' => $token,
            'urls' => $urls
        ]);

    }

    public function convertWordToPdf(Request $request)
    {
        return $this->handleLibreOfficeConversion($request, 'file', 'word_files', 'pdf');
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


    public function convertWord(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        $files = $request->file('file');

        if (!is_array($files)) {
            $files = [$files];
        }

        $pdfUrls = [];
        $lastConvertedDocId = null;

        try {
            foreach ($files as $file) {
                if ($file->getClientOriginalExtension() !== 'docx') {
                    continue;
                }

                $uniqueId = \Str::uuid();
                $filename = "converted_{$uniqueId}.pdf";
                $relativePath = "converted/{$filename}";
                $pdfPath = storage_path("app/public/" . $relativePath);

                \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getPathname());

                ob_start();
                \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->save('php://output');
                $htmlContent = ob_get_clean();

                // Add custom styles
                $style = "
                  <style>
                    body { font-family: 'Arial', sans-serif; font-size: 14px; line-height: 1.5; }
                    img { max-width: 100%; height: 100%; }
                    table { width: 100%; border-collapse: collapse; }
                    td, th { border: 1px solid #ccc; padding: 4px; }
                  </style>
                ";

                
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent);
                $images = $dom->getElementsByTagName('img');
                foreach ($images as $img) {
                    $img->setAttribute('style', 'max-width: 100%; height: auto;');
                }
                $htmlContent = $style . $dom->saveHTML($dom->getElementsByTagName('body')->item(0));

                \PDF::loadHTML($htmlContent)
                    ->setPaper('A4')
                    ->setOptions([
                        'isHtml5ParserEnabled' => true,
                        'isRemoteEnabled' => true,
                    ])
                    ->save($pdfPath);

                $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'word_files',
                        'convert_into' => 'pdf',
                        'original_name' => $file->getClientOriginalName(),
                        'converted_name' => $filename,
                        'original_doc' => $file->store('originals', 'public'),
                        'converted_pdf' => $relativePath,
                    ]);

                    $lastConvertedDocId = $convertedDoc->id;
                }
                $pdfUrls[] = asset('storage/' . $relativePath);
            }

            if (empty($pdfUrls)) {
                return response()->json(['error' => 'No valid .docx files found'], 400);
            }

            $token = \Str::random(32);

            DownloadToken::create([
                'converted_document_id' => $lastConvertedDocId ?? null,
                'token' => $token,
                'files' => json_encode($pdfUrls),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'urls' => $pdfUrls,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }


    // public function convertExcel(Request $request)
// {
    //     if (!$request->hasFile('file')) {
    //         return response()->json(['error' => 'No files uploaded'], 400);
    //     }

    //     $files = $request->file('file');

    //     if (!is_array($files)) {
    //         $files = [$files];
    //     }

    //     $pdfUrls = [];
    //     $lastConvertedDocId = null;

    //     try {
    //         foreach ($files as $file) {
    //             $extension = strtolower($file->getClientOriginalExtension());

    //             if (!in_array($extension, ['xls', 'xlsx'])) {
    //                 continue; // Skip unsupported files
    //             }

    //             // Load the Excel file
    //             $spreadsheet = SpreadsheetIOFactory::load($file->getPathname());

    //             // Convert to HTML
    //             ob_start();
    //             $writer = new HtmlWriter($spreadsheet);
    //             $writer->save('php://output');
    //             $htmlContent = ob_get_clean();

    //             $style = '
    //               <style>
    //                 body {
    //                   font-family: Arial, sans-serif;
    //                   font-size: 12px;
    //                 }

    //                 table {
    //                   width: 100%;
    //                   border-collapse: collapse;
    //                   table-layout: fixed;
    //                   word-wrap: break-word;
    //                 }

    //                 td, th {
    //                   border: 1px solid #ccc;
    //                   padding: 5px;
    //                   word-break: break-word;
    //                   font-size: 10px;
    //                 }

    //                 thead {
    //                   display: table-header-group;
    //                 }

    //                 tbody tr {
    //                   page-break-inside: avoid;
    //                 }
    //               </style>
    //             ';

    //             $htmlContent = $style . $htmlContent;


    //             // Convert HTML to PDF
    //             $uniqueId = Str::uuid();
    //             $filename = "converted_{$uniqueId}.pdf";
    //             $relativePath = "converted/{$filename}";
    //             $pdfPath = storage_path("app/public/" . $relativePath);

    //             \PDF::loadHTML($htmlContent)->save($pdfPath);

    //             $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

    //             if (!is_null($userId)) {
    //                 // Save to DB
    //                 $convertedDoc = ConvertedDocuments::create([
    //                     'user_id' => $userId,
    //                     'file_type' => 'excel_files',
    //                     'convert_into' => 'pdf',
    //                     'original_name' => $file->getClientOriginalName(),
    //                     'converted_name' => $filename,
    //                     'original_doc' => $file->store('originals', 'public'),
    //                     'converted_pdf' => $relativePath,
    //                 ]);

    //                 $lastConvertedDocId = $convertedDoc->id;
    //             }
    //             $pdfUrls[] = asset('storage/' . $relativePath);
    //         }

    //         if (empty($pdfUrls)) {
    //             return response()->json(['error' => 'No valid Excel files (.xls, .xlsx) found'], 400);
    //         }

    //         // Generate token
    //         $token = Str::random(32);

    //         DownloadToken::create([
    //             'converted_document_id' => $lastConvertedDocId ?? null,
    //             'token' => $token,
    //             'files' => json_encode($pdfUrls),
    //             'expires_at' => now()->addMinutes(30),
    //         ]);

    //         return response()->json([
    //             'urls' => $pdfUrls,
    //             'token' => $token
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
    //     }
    // }


public function convertExcel(Request $request)
{
    if (!$request->hasFile('file')) {
        return response()->json(['error' => 'No files uploaded'], 400);
    }

    $files = $request->file('file');
    if (!is_array($files)) {
        $files = [$files];
    }

    $pdfUrls = [];
    $lastConvertedDocId = null;

    try {
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, ['xls', 'xlsx'])) {
                continue; // Skip unsupported files
            }

            $spreadsheet = SpreadsheetIOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $rowsPerPdf = 200;
            $chunkCount = ceil(($highestRow - 1) / $rowsPerPdf); // -1 because row 1 is header

            for ($chunk = 0; $chunk < $chunkCount; $chunk++) {
                $newSpreadsheet = new Spreadsheet();
                $newSheet = $newSpreadsheet->getActiveSheet();

                // Copy headers
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellAddress = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
                    $header = $worksheet->getCell($cellAddress)->getValue();
                    $newSheet->setCellValue($cellAddress, $header);
                }

                // Determine row range for this chunk
                $startRow = $chunk * $rowsPerPdf + 2; // start from row 2 (after header)
                $endRow = min($startRow + $rowsPerPdf - 1, $highestRow); // Ensure we do not exceed highestRow
                $currentTargetRow = 2;

                // Copy rows from source to target
                for ($sourceRow = $startRow; $sourceRow <= $endRow; $sourceRow++, $currentTargetRow++) {
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $sourceCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $sourceRow;
                        $targetCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentTargetRow;
                        $value = $worksheet->getCell($sourceCell)->getValue();
                        $newSheet->setCellValue($targetCell, $value);
                    }
                }

                // Convert to HTML
                ob_start();
                $writer = new HtmlWriter($newSpreadsheet);
                $writer->save('php://output');
                $htmlContent = ob_get_clean();

                $style = '
                  <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; }
                    td, th { border: 1px solid #ccc; padding: 5px; word-break: break-word; font-size: 10px; }
                    thead { display: table-header-group; }
                    tbody tr { page-break-inside: avoid; }
                  </style>
                ';
                $htmlContent = $style . $htmlContent;

                // Save to PDF
                $uniqueId = Str::uuid();
                $filename = "converted_{$uniqueId}_part{$chunk}.pdf";
                $relativePath = "converted/{$filename}";
                $pdfPath = storage_path("app/public/" . $relativePath);

                \PDF::loadHTML($htmlContent)->save($pdfPath);

                $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'excel_files',
                        'convert_into' => 'pdf',
                        'original_name' => $file->getClientOriginalName(),
                        'converted_name' => $filename,
                        'original_doc' => $file->store('originals', 'public'),
                        'converted_pdf' => $relativePath,
                    ]);

                    $lastConvertedDocId = $convertedDoc->id;
                }

                $pdfUrls[] = asset('storage/' . $relativePath);
            }
        }

        if (empty($pdfUrls)) {
            return response()->json(['error' => 'No valid Excel files (.xls, .xlsx) found'], 400);
        }

        $token = Str::random(32);

        DownloadToken::create([
            'converted_document_id' => $lastConvertedDocId ?? null,
            'token' => $token,
            'files' => json_encode($pdfUrls),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'urls' => $pdfUrls,
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
        $pdfUrls = [];
        $lastConvertedDocId = null;

        try {   
            // Case 1: File Upload
            if ($request->hasFile('file')) {
                $files = $request->file('file');

                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, ['htm', 'html'])) {
                        continue;
                    }

                    $htmlContent = file_get_contents($file->getPathname());

                    $uniqueId = Str::uuid();
                    $filename = "converted_{$uniqueId}.pdf";
                    $relativePath = "converted/{$filename}";
                    $pdfPath = storage_path("app/public/" . $relativePath);

                    \PDF::loadHTML($htmlContent)->save($pdfPath);

                    $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                    if (!is_null($userId)) {
                        $convertedDoc = ConvertedDocuments::create([
                            'user_id' => $userId,
                            'file_type' => 'html_files',
                            'convert_into' => 'pdf',
                            'original_name' => $file->getClientOriginalName(),
                            'converted_name' => $filename,
                            'original_doc' => $file->store('originals', 'public'),
                            'converted_pdf' => $relativePath,
                        ]);

                        $lastConvertedDocId = $convertedDoc->id;
                    }
                    $pdfUrls[] = asset('storage/' . $relativePath);
                }
            }
            // Case 2: HTML URL Provided
            elseif ($request->filled('html_url')) {
                $htmlUrl = $request->html_url;

                if (!filter_var($htmlUrl, FILTER_VALIDATE_URL)) {
                    return response()->json(['error' => 'Invalid URL'], 400);
                }

                $response = Http::get($htmlUrl);

                if (!$response->successful()) {
                    return response()->json(['error' => 'Failed to fetch HTML from URL'], 400);
                }

                $htmlContent = $response->body();
                $originalName = basename(parse_url($htmlUrl, PHP_URL_PATH)) ?: 'html_url.html';

                $uniqueId = Str::uuid();
                $filename = "converted_{$uniqueId}.pdf";
                $relativePath = "converted/{$filename}";
                $pdfPath = storage_path("app/public/" . $relativePath);

                // Get user PDF preferences
                $orientation = $request->input('orientation', 'portrait');
                $pageSize = $request->input('page_size', 'A4');
                $margin = strtolower($request->input('margin', 'default'));

                // Margin presets
                $marginOptions = [
                    'default' => ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10],
                    'small' => ['top' => 5, 'right' => 5, 'bottom' => 5, 'left' => 5],
                    'none' => ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
                ];
                $chosenMargin = $marginOptions[$margin] ?? $marginOptions['default'];

                // Generate the PDF with options
                \PDF::loadHTML($htmlContent)
                    ->setPaper($pageSize, $orientation)
                    ->setOptions([
                        'margin_top'    => $chosenMargin['top'],
                        'margin_right'  => $chosenMargin['right'],
                        'margin_bottom' => $chosenMargin['bottom'],
                        'margin_left'   => $chosenMargin['left'],
                    ])
                ->save($pdfPath);


                $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'html_files',
                        'convert_into' => 'pdf',
                        'original_name' => $originalName,
                        'converted_name' => $filename,
                        'original_doc' => $htmlUrl ?? null, // No file uploaded
                        'converted_pdf' => $relativePath,
                    ]);

                    $lastConvertedDocId = $convertedDoc->id;
                }
                $pdfUrls[] = asset('storage/' . $relativePath);
            } else {
                return response()->json(['error' => 'No file or URL provided'], 400);
            }

            if (empty($pdfUrls)) {
                return response()->json(['error' => 'No valid HTML content to convert'], 400);
            }

            // Generate access token for download
            $token = Str::random(32);

            DownloadToken::create([
                'converted_document_id' => $lastConvertedDocId ?? null,
                'token' => $token,
                'files' => json_encode($pdfUrls),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'urls' => $pdfUrls,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }


    public function convertJPG(Request $request)
    {
        $request->validate([
            'file.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'orientation' => 'in:portrait,landscape',
            'margin' => 'in:0,4,6',
            'merge' => 'in:0,1',
            'mergePage' => 'in:0,1', // added validation
        ]);

        $orientation = $request->input('orientation', 'portrait');
        \Log::error("Orientation: " . $orientation);
        $margin = intval($request->input('margin', 0));
        $merge = $request->input('merge') == '1';
        $mergePage = $request->input('mergePage') == '1';

        $files = $request->file('file');
        $token = Str::random(32);
        $pdfUrls = [];
        $lastConvertedDocId = null;

        try {
            if ($merge) {
                $originalNames = [];
                $html = "<style>body { margin: {$margin}mm; }</style>";

                if ($mergePage) {
                    // ➕ All images on ONE page
                    $html = "
                        <!DOCTYPE html>
                        <html>
                          <head>
                            <meta charset='utf-8'>
                            <style>
                              @page {
                                size: A4 " . $orientation . ";
                                margin: {$margin}mm;
                              }
                              body {
                                margin: 0;
                                padding: 0;
                                font-family: sans-serif;
                              }
                              .img-row {
                                display: flex;
                                flex-wrap: wrap;
                                justify-content: center;
                                gap: 10px;
                                page-break-inside: avoid;
                              }
                              .img-row img {
                                width: 180px;
                                height: auto;
                                object-fit: contain;
                              }
                            </style>
                          </head>
                          <body>
                            <div class='img-row'>
                        ";
                foreach ($files as $file) {
                    $originalNames[] = $file->getClientOriginalName();
                    $imageData = base64_encode(file_get_contents($file->getRealPath()));
                    $rotationStyle = '';
                        if ($orientation === 'landscape') {

                            $rotationStyle = 'transform: rotate(90deg); transform-origin: center;';

                        }
                    $html .= "<img src='data:image/jpeg;base64,{$imageData}' style='max-width:100%; margin:{$margin}; height:auto; {$rotationStyle}'  />";
                }
                $html .= "
                    </div>
                  </body>
                </html>";


                } else {
                    // ➕ Each image on its own page
                    foreach ($files as $file) {
                        $originalNames[] = $file->getClientOriginalName();
                        $imageData = base64_encode(file_get_contents($file->getRealPath()));

                        $rotationStyle = '';
                        if ($orientation === 'landscape') {

                            $rotationStyle = 'transform: rotate(90deg); transform-origin: center;';

                        }

                        $html .= "<div style='page-break-after: always; text-align: center; margin: {$margin}mm;'>
                                    <img src='data:image/jpeg;base64,{$imageData}' style='max-width:100%; margin:{$margin}; height:auto; {$rotationStyle}' />
                                  </div>";
                    }
                }

                $pdf = Pdf::loadHTML($html)->setPaper('a4', $orientation);
                $filename = 'pdf_' . time() . '.pdf';
                $relativePath = 'converted/' . $filename;
                $pdfPath = storage_path("app/public/" . $relativePath);

                if (!file_exists(dirname($pdfPath))) {
                    mkdir(dirname($pdfPath), 0755, true);
                }

                $pdf->save($pdfPath);
                $pdfUrls[] = asset("storage/{$relativePath}");

                $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'image_files',
                        'convert_into' => 'pdf',
                        'original_name' => implode(', ', $originalNames),
                        'converted_name' => $filename,
                        'original_doc' => '', // could zip if needed
                        'converted_pdf' => $relativePath,
                    ]);

                    $lastConvertedDocId = $convertedDoc->id;
                }
            } else {
            // ➕ Separate PDF file for each image
            foreach ($files as $file) {
                $html = '';
                $imageData = base64_encode(file_get_contents($file->getRealPath()));
                $rotationStyle = '';

                if ($orientation === 'landscape') {
                    $rotationStyle = 'transform: rotate(90deg); transform-origin: center;';
                }

                $html .= "<div style='margin: {$margin}mm; text-align: center;'>
                            <img src='data:image/jpeg;base64,{$imageData}' style='max-width:100%; height:auto; {$rotationStyle}' />
                          </div>";

                $pdf = Pdf::loadHTML($html)->setPaper('a4', $orientation);
                $filename = 'pdf_' . time() . '_' . Str::random(5) . '.pdf';
                $relativePath = 'converted/' . $filename;
                $pdfPath = storage_path("app/public/" . $relativePath);

                if (!file_exists(dirname($pdfPath))) {
                    mkdir(dirname($pdfPath), 0755, true);
                }

                $pdf->save($pdfPath);
                $pdfUrls[] = asset("storage/{$relativePath}");

                $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'image_files',
                        'convert_into' => 'pdf',
                        'original_name' => $file->getClientOriginalName(),
                        'converted_name' => $filename,
                        'original_doc' => $file->store('originals', 'public'),
                        'converted_pdf' => $relativePath,
                    ]);

                    $lastConvertedDocId = $convertedDoc->id;
                }
            }
        }

            if (empty($pdfUrls)) {
                return response()->json(['error' => 'No valid image files found'], 400);
            }

            DownloadToken::create([
                'converted_document_id' => $lastConvertedDocId ?? null,
                'token' => $token,
                'files' => json_encode($pdfUrls),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'urls' => $pdfUrls,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Conversion failed: ' . $e->getMessage()], 500);
        }
    }

    public function mergePdf(Request $request)
    {
        if (!$request->hasFile('pdf_files')) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        $files = $request->file('pdf_files');

        if (count($files) < 2) {
            return response()->json(['error' => 'Upload at least 2 PDF files'], 400);
        }

        // Create temp storage directories
        $inputDir = storage_path('app/pdf_inputs');
        $outputDir = storage_path('app/public/converted');

        foreach ([$inputDir, $outputDir] as $dir) {
            if (!file_exists($dir)) mkdir($dir, 0777, true);
        }

        $inputPaths = [];
        $originalNames = [];

        foreach ($files as $file) {
            $name = time() . '_' . Str::random(5) . '_' . $file->getClientOriginalName();
            $file->move($inputDir, $name);
            $inputPaths[] = $inputDir . '/' . $name;
            $originalNames[] = $file->getClientOriginalName();
        }

        $pdf = new Fpdi();

        try {
            foreach ($inputPaths as $pdfPath) {
                $pageCount = $pdf->setSourceFile($pdfPath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);

                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            $mergedName = 'merged_' . time() . '.pdf';
            $relativePath = 'converted/' . $mergedName;
            $mergedPath = $outputDir . '/' . $mergedName;

            $pdf->Output('F', $mergedPath);

            $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

            if (!is_null($userId)) {
                $convertedDoc = ConvertedDocuments::create([
                    'user_id' => $userId,
                    'file_type' => 'pdf_files',
                    'convert_into' => 'pdf',
                    'original_name' => implode(', ', $originalNames),
                    'converted_name' => $mergedName,
                    'original_doc' => '',
                    'converted_pdf' => $relativePath,
                ]);
            }

            // Create download token
            $token = Str::random(32);
            $fileUrl = asset('storage/' . $relativePath);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id ?? null,
                'token' => $token,
                'files' => json_encode([$fileUrl]),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'token' => $token,
                'url' => $fileUrl
            ]);

        } catch (\Exception $ex) {
            \Log::error("PDF merge failed: " . $ex->getMessage());
            return response()->json(['error' => 'PDF merge failed.'], 500);
        }
    }

    public function splitPdf(Request $request)
    {
        if (!$request->hasFile('pdf_file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('pdf_file');

        if ($file->getClientOriginalExtension() !== 'pdf') {
            return response()->json(['error' => 'Only PDF files are supported'], 400);
        }

        $outputDir = storage_path('app/public/converted');
        if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

        $originalName = $file->getClientOriginalName();
        $inputPath = $file->getRealPath();

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($inputPath);
            $splitUrls = [];

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $split = new Fpdi();
                $split->setSourceFile($inputPath);

                $templateId = $split->importPage($pageNo);
                $size = $split->getTemplateSize($templateId);

                $split->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $split->useTemplate($templateId);

                $splitName = 'split_page_' . $pageNo . '_' . time() . '.pdf';
                $relativePath = 'converted/' . $splitName;
                $fullPath = storage_path('app/public/' . $relativePath);

                $split->Output('F', $fullPath);
                $splitUrls[] = asset('storage/' . $relativePath);

                $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'pdf_file',
                        'convert_into' => 'split_pdf',
                        'original_name' => $originalName,
                        'converted_name' => $splitName,
                        'original_doc' => '', // skipped storing original as in mergePdf
                        'converted_pdf' => $relativePath,
                    ]);
                }
            }

            // Generate one token for all split pages
            $token = Str::random(32);
            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id ?? null, // last one created
                'token' => $token,
                'files' => json_encode($splitUrls),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'token' => $token,
                'urls' => $splitUrls
            ]);

        } catch (\Exception $e) {
            \Log::error("PDF split failed: " . $e->getMessage());
            return response()->json(['error' => 'PDF split failed.'], 500);
        }
    }

    public function rotatePdf(Request $request)
    {
        if (!$request->hasFile('pdf_file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $angle = $request->input('angle', 0);
        $file = $request->file('pdf_file');

        if ($file->getClientOriginalExtension() !== 'pdf') {
            return response()->json(['error' => 'Only PDF files are supported'], 400);
        }

        $outputDir = storage_path('app/public/converted');
        if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

        $originalName = $file->getClientOriginalName();
        $inputPath = $file->getRealPath(); // ✅ no move

        $rotatedName = 'rotated_' . time() . '.pdf';
        $relativePath = 'converted/' . $rotatedName;
        $outputPath = storage_path('app/public/' . $relativePath);

        try {
            $pdf = new PdfWithRotation();
            $pageCount = $pdf->setSourceFile($inputPath);

            // for ($i = 1; $i <= $pageCount; $i++) {
            //     $templateId = $pdf->importPage($i);
            //     $size = $pdf->getTemplateSize($templateId);
            //     $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            //     $pdf->Rotate($angle, $size['width'] / 2, $size['height'] / 2);
            //     $pdf->useTemplate($templateId);
            //     $pdf->Rotate(0);
            // }

            for ($i = 1; $i <= $pageCount; $i++) {
                $templateId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($templateId);

                if (in_array($angle, [90, 270])) {
                    // Swap width and height for canvas
                    $canvasWidth = $size['height'];
                    $canvasHeight = $size['width'];
                    $orientation = $canvasWidth > $canvasHeight ? 'L' : 'P';

                    $pdf->AddPage($orientation, [$canvasWidth, $canvasHeight]);

                    // Rotate around the center of the canvas
                    $pdf->Rotate($angle, $canvasWidth / 2, $canvasHeight / 2);

                    // Use the template with adjusted coordinates
                    $pdf->useTemplate($templateId, 
                        ($canvasWidth - $size['width']) / 2, 
                        ($canvasHeight - $size['height']) / 2
                    );
                } else {
                    // No rotation or 180 (just flip)
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->Rotate($angle, $size['width'] / 2, $size['height'] / 2);
                    $pdf->useTemplate($templateId);
                }

                $pdf->Rotate(0); // Reset rotation
            }


            $pdf->Output('F', $outputPath);

            $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

            if (!is_null($userId)) {
                $convertedDoc = ConvertedDocuments::create([
                    'user_id' => $userId,
                    'file_type' => 'pdf_file',
                    'convert_into' => 'rotated_pdf',
                    'original_name' => $originalName,
                    'converted_name' => $rotatedName,
                    'original_doc' => '', // consistent with mergePdf
                    'converted_pdf' => $relativePath,
                ]);
            }

            $token = Str::random(32);

            DownloadToken::create([
                'converted_document_id' => $convertedDoc->id ?? null,
                'token' => $token,
                'files' => json_encode([asset('storage/' . $relativePath)]),
                'expires_at' => now()->addMinutes(30),
            ]);

            return response()->json([
                'token' => $token,
                'url' => asset('storage/' . $relativePath),
            ]);

        } catch (\Exception $e) {
            \Log::error("PDF rotation failed: " . $e->getMessage());
            return response()->json(['error' => 'PDF rotation failed.'], 500);
        }
    }


    public function watermarkPdf(Request $request)
    {
        $files = $request->file('pdf_file');

        if (!$files || !is_array($files)) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        $rotate = floatval($request->input('rotation', 0));
        $watermarkText = $request->input('watermark_text', 'CONFIDENTIAL');
        $imageFile = $request->file('watermark_image');
        $imageFile = $request->file('watermark_image');
        \Log::info('🧾 Uploaded image file:', [
            'exists' => $request->hasFile('watermark_image'),
            'file' => $imageFile,
        ]);

        
        $type = $request->input('watermark_type', 'text');
        $positionKey = $request->input('watermark_position', 'center');
        $isMosaic = $request->boolean('mosaic', false);
        $userId = $request->filled('user_id') && is_numeric($request->user_id) ? (int) $request->user_id : null;

        $outputDir = storage_path('app/public/converted');
        if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

        $positionMap = [
            'top-left' => [0.1, 0.1],
            'top-center' => [0.5, 0.1],
            'top-right' => [0.9, 0.1],
            'middle-left' => [0.1, 0.5],
            'center' => [0.5, 0.5],
            'middle-right' => [0.9, 0.5],
            'bottom-left' => [0.1, 0.9],
            'bottom-center' => [0.5, 0.9],
            'bottom-right' => [0.9, 0.9],
        ];

        $pdfUrls = [];
        $lastConvertedDocId = null;

        foreach ($files as $file) {
            if ($file->getClientOriginalExtension() !== 'pdf') continue;

            $originalName = $file->getClientOriginalName();
            $inputPath = $file->getRealPath();

            $watermarkedName = 'watermarked_' . time() . '_' . uniqid() . '.pdf';
            $relativePath = 'converted/' . $watermarkedName;
            $outputPath = storage_path('app/public/' . $relativePath);

            try {
                $pdf = new PdfWithRotation();
                $pageCount = $pdf->setSourceFile($inputPath);

                $positionsToApply = $isMosaic ? array_values($positionMap) : [$positionMap[$positionKey] ?? [0.5, 0.5]];

                for ($i = 1; $i <= $pageCount; $i++) {
                    $templateId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);

                    foreach ($positionsToApply as $relativePos) {
                        $x = $size['width'] * $relativePos[0];
                        $y = $size['height'] * $relativePos[1];

                        if ($type === 'image' && $imageFile && $imageFile->isValid()) {
                            $ext = $imageFile->getClientOriginalExtension();
                            $tmpImagePath = storage_path('app/temp_watermark.' . $ext);
                            copy($imageFile->getRealPath(), $tmpImagePath);

                            $imgWidth = 30;
                            $imgHeight = 30;
                            $x -= $imgWidth / 2;
                            $y -= $imgHeight / 2;
                            $x = max(0, min($x, $size['width'] - $imgWidth));
                            $y = max(0, min($y, $size['height'] - $imgHeight));

                            $pdf->Rotate($rotate, $x + $imgWidth / 2, $y + $imgHeight / 2);
                            $pdf->Image($tmpImagePath, $x, $y, $imgWidth, $imgHeight);
                            $pdf->Rotate(0);

                            register_shutdown_function(fn() => @unlink($tmpImagePath));
                        } else {
                            $pdf->SetFont('Arial', 'I', 20);
                            $pdf->SetTextColor(192, 192, 192);
                            if (method_exists($pdf, 'SetAlpha')) $pdf->SetAlpha(0.3);

                            $textWidth = $pdf->GetStringWidth($watermarkText) + 10;
                            $textHeight = 10;
                            $x -= $textWidth / 2;
                            $y -= $textHeight / 2;
                            $x = max(0, min($x, $size['width'] - $textWidth));
                            $y = max(0, min($y, $size['height'] - $textHeight));

                            $pdf->SetXY($x, $y);
                            $pdf->Rotate($rotate, $x + $textWidth / 2, $y + $textHeight / 2);
                            $pdf->Cell($textWidth, $textHeight, $watermarkText, 0, 0, 'L');
                            $pdf->Rotate(0);

                            if (method_exists($pdf, 'SetAlpha')) $pdf->SetAlpha(1);
                        }
                    }
                }

                $pdf->Output('F', $outputPath);

                if (!is_null($userId)) {
                    $convertedDoc = ConvertedDocuments::create([
                        'user_id' => $userId,
                        'file_type' => 'pdf_file',
                        'convert_into' => 'watermarked_pdf',
                        'original_name' => $originalName,
                        'converted_name' => $watermarkedName,
                        'original_doc' => '',
                        'converted_pdf' => $relativePath,
                    ]);
                    $lastConvertedDocId = $convertedDoc->id;
                }

                $pdfUrls[] = asset('storage/' . $relativePath);

            } catch (\Exception $e) {
                \Log::error("Watermarking failed for file {$originalName}: " . $e->getMessage());
            }
        }

        if (empty($pdfUrls)) {
            return response()->json(['error' => 'All PDF watermarking failed.'], 500);
        }

        $token = Str::random(32);

        DownloadToken::create([
            'converted_document_id' => $lastConvertedDocId,
            'token' => $token,
            'files' => json_encode($pdfUrls),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'token' => $token,
            'urls' => $pdfUrls,
        ]);
    }


}
