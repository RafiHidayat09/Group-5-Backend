<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function download(Request $request, $fileName)
    {
        try {
            // Clean filename
            $fileName = basename($fileName);

            // Path ke file
            $filePath = 'attachments/' . $fileName;
            $fullPath = storage_path('app/public/' . $filePath);

            Log::info('Download request', [
                'file' => $fileName,
                'path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            // Cek file exists (manual check)
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan di: ' . $filePath
                ], 404);
            }

            // Get file info manual
            $size = filesize($fullPath);
            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

            // Original filename
            $originalName = $request->query('filename', $fileName);
            $originalName = basename($originalName);

            // Set headers
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $originalName . '"',
                'Content-Length' => $size,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            // Return file response manual
            return response()->file($fullPath, $headers);

        } catch (\Exception $e) {
            Log::error('Download error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
