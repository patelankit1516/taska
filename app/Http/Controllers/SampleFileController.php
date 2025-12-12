<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class SampleFileController extends Controller
{
    /**
     * Download a sample CSV file with proper headers
     */
    public function download($filename)
    {
        // Security: Only allow specific sample files
        $allowedFiles = [
            'medium_products.csv',
            'test_products_1000.csv',
            'test_products_2000.csv',
            'test_products_3000.csv',
            'test_products_4000.csv',
            'test_products_5000.csv',
            'large_products.csv',
        ];

        if (!in_array($filename, $allowedFiles)) {
            abort(404, 'File not found');
        }

        $filePath = public_path($filename);

        if (!File::exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
