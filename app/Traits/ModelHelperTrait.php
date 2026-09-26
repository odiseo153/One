<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

trait ModelHelperTrait
{
    public function deleteOldFile(string $attribute): void
    {
        $oldFilePath = null;

        // If the model exists in the database, get the original value from the database
        if ($this->exists && $this->getKey()) {
            // Use getOriginal() to get the value before any modifications
            $originalValue = $this->getOriginal($attribute);
            if (! empty($originalValue)) {
                $oldFilePath = $originalValue;
            }
        } elseif (! empty($this->attributes[$attribute])) {
            // If it's a new model, use the current attribute value
            $oldFilePath = $this->attributes[$attribute];
        }

        // Delete the old file if it exists
        if ($oldFilePath) {
            // Remove leading slash if present
            $oldFilePath = ltrim($oldFilePath, '/');
            $fullPath = public_path($oldFilePath);

            if (file_exists($fullPath) && is_writable($fullPath)) {
                @unlink($fullPath);
            }

            // Also try to delete using Storage disk
            Storage::disk('public')->delete($oldFilePath);
        }
    }

    /**
     * Check if string is base64 encoded
     */
    public function isBase64(string $string): bool
    {
        // Check if it's a data URL (data:image/...)
        if (strpos($string, 'data:') === 0) {
            return true;
        }

        // Check if it's a valid base64 string
        return base64_encode(base64_decode($string, true)) === $string;
    }

    /**
     * Handle file upload from base64, UploadedFile, or existing file path
     * Unified method that combines handleFileAttribute and handleFormDataFile
     *
     * @param  string  $attribute  The model attribute to store the file path
     * @param  string|UploadedFile|null  $value  The file (base64 string, UploadedFile, or existing path)
     * @param  string  $folder  The folder to store the file
     * @param  string  $fileName  Base name for the file
     */
    public function handleFile(string $attribute, string|UploadedFile|null $value, string $folder = 'documents', string $fileName = 'document'): void
    {
        // If value is null or empty, delete old file and set to null
        if (is_null($value) || $value === '' || $value === 'null') {
            $this->deleteOldFile($attribute);
            $this->attributes[$attribute] = null;

            return;
        }

        // If it's an uploaded file instance (multipart/form-data)
        if ($value instanceof UploadedFile) {
            $this->deleteOldFile($attribute);

            // Generate unique filename
            $extension = $value->getClientOriginalExtension();
            $filename = $this->generateUniqueFilename($fileName, $extension, $attribute);

            // Ensure directory exists
            $directory = public_path($folder);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Store the file directly in public directory
            $value->move($directory, $filename);
            $this->attributes[$attribute] = $folder.'/'.$filename;

            return;
        }

        // If it's a base64 string, store the new file
        if (is_string($value) && $this->isBase64($value)) {
            $this->deleteOldFile($attribute);
            $this->attributes[$attribute] = $this->storeBase64File($value, $folder, $fileName, $attribute);

            return;
        }

        // If it's already a file path (not base64), keep it as is
        if (is_string($value)) {
            $this->attributes[$attribute] = $value;

            return;
        }

        // Default case - set to null
        $this->attributes[$attribute] = null;
    }

    /**
     * Generate a unique filename for uploaded files
     */
    protected function generateUniqueFilename(string $file_name, string $extension, ?string $attribute = null): string
    {
        $modelClass = class_basename(get_class($this));
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = uniqid('', true);
        if ($attribute) {
            return "{$file_name}_{$modelClass}_{$attribute}_{$timestamp}_{$random}.{$extension}";
        }

        return "{$file_name}_{$modelClass}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Process document upload with type-based replacement
     * This method handles the specific case for IntakeDocument where we need to replace
     * documents of the same type before creating a new one
     */
    public function handleDocumentUpload(array $data, int $intakeId, string $type): array
    {
        // Delete existing documents of the same type
        $deletedCount = $this->deleteDocumentsByType($intakeId, $type);

        // Process the file upload
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $this->handleFile('file_path', $data['file'], 'intake-documents', 'document');
        }

        // Create the new document
        $document = $this->create($data);

        return [
            'document' => $document,
            'deleted_count' => $deletedCount,
        ];
    }

    /**
     * Delete documents by intake ID and type
     */
    protected function deleteDocumentsByType(int $intakeId, string $type): int
    {
        $documents = static::where('intake_id', $intakeId)
            ->where('type', $type)
            ->get();

        $deletedCount = 0;
        foreach ($documents as $document) {
            // Delete physical file
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            // Delete database record
            $document->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    protected function storeBase64File(string $base64File, string $folder, string $file_name = 'document', ?string $attribute = null): string
    {
        // Extrae el tipo de archivo y los datos base64
        $mimeType = null;
        $fileData = null;

        if (preg_match('/^data:([a-zA-Z0-9\/\.\-\+\;]+);base64,/', $base64File, $matches)) {
            $mimeType = $matches[1];
            $fileData = base64_decode(substr($base64File, strpos($base64File, ',') + 1));
        } else {
            // Si no tiene el prefijo data, asumimos que es base64 puro y tratamos de detectar el tipo después
            $fileData = base64_decode($base64File);
        }

        // Determina la extensión a partir del mime type si está disponible
        $extensions = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/bmp' => '.bmp',
            'image/svg+xml' => '.svg',
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'application/vnd.ms-powerpoint' => '.ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
            'text/plain' => '.txt',
            'application/zip' => '.zip',
            'application/x-rar-compressed' => '.rar',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'video/mp4' => '.mp4',
            // Agrega más tipos si es necesario
        ];

        $ext = '.bin'; // Valor por defecto

        if ($mimeType && isset($extensions[$mimeType])) {
            $ext = $extensions[$mimeType];
        } elseif (! $mimeType && function_exists('finfo_open')) {
            // Si no se detectó el mimeType, intenta detectarlo con finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_buffer($finfo, $fileData);
            finfo_close($finfo);
            if (isset($extensions[$detectedMime])) {
                $ext = $extensions[$detectedMime];
            }
        }

        // Genera un nombre de archivo único respetando el file_name proporcionado
        $uniqueSuffix = uniqid('', true);
        $filename = $file_name.'_'.$uniqueSuffix.$ext;
        $path = $folder.'/'.$filename;

        // Define la ruta de almacenamiento en la carpeta 'public'
        $filePath = public_path($path);

        // Asegúrate de que el directorio exista
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Guarda el archivo en el disco
        file_put_contents($filePath, $fileData);

        return "/{$path}";
    }

    /**
     * Handle document upload with optional page extraction for PDFs
     * Accepts both base64 encoded files and UploadedFile instances
     *
     * @param  string  $attribute  The model attribute to store the file path
     * @param  string|UploadedFile|null  $document  The document (base64 string or UploadedFile)
     * @param  string|null  $pages  Pages to extract (e.g., "1,3,5" or "1-5" or "1,3-5,8") - only for PDFs
     * @param  string  $folder  The folder to store the file
     * @param  string  $fileName  Base name for the file
     */
    public function handleDocumentWithPages(
        string $attribute,
        string|UploadedFile|null $document,
        ?string $pages = null,
        string $folder = 'documents',
        string $fileName = 'document'
    ): void {
        // If document is null or empty, delete old file and set to null
        if (is_null($document) || $document === '' || $document === 'null') {
            $this->deleteOldFile($attribute);
            $this->attributes[$attribute] = null;

            return;
        }

        // Convert document to temporary file path for processing
        $tempFilePath = null;
        $mimeType = null;
        $originalExtension = null;

        if ($document instanceof UploadedFile) {
            $tempFilePath = $document->getPathname();
            $mimeType = $document->getMimeType();
            $originalExtension = $document->getClientOriginalExtension();
        } elseif (is_string($document) && $this->isBase64($document)) {
            // Decode base64 and save to temp file
            $tempData = $this->decodeBase64Document($document);
            $tempFilePath = $tempData['temp_path'];
            $mimeType = $tempData['mime_type'];
            $originalExtension = $tempData['extension'];
        } elseif (is_string($document) && ! $this->isBase64($document)) {
            // It's already a file path, keep it
            $this->attributes[$attribute] = $document;

            return;
        }

        if (! $tempFilePath) {
            $this->attributes[$attribute] = null;

            return;
        }

        // Check if it's a PDF and pages are specified
        $isPdf = $this->isPdfFile($mimeType, $originalExtension);

        if ($isPdf && ! empty($pages)) {
            // Extract specific pages from PDF
            $processedFilePath = $this->extractPdfPages($tempFilePath, $pages, $folder, $fileName, $attribute);

            if ($processedFilePath) {
                $this->deleteOldFile($attribute);
                $this->attributes[$attribute] = $processedFilePath;
            } else {
                // If extraction fails, store the original document
                $this->storeDocumentFromTemp($attribute, $tempFilePath, $folder, $fileName, $originalExtension);
            }
        } else {
            // Store the document without page extraction
            $this->storeDocumentFromTemp($attribute, $tempFilePath, $folder, $fileName, $originalExtension);
        }

        // Clean up temp file if it was created from base64
        if ($document instanceof UploadedFile === false && file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }
    }

    /**
     * Decode base64 document and save to temporary file
     *
     * @return array{temp_path: string, mime_type: string|null, extension: string}
     */
    protected function decodeBase64Document(string $base64Document): array
    {
        $mimeType = null;
        $fileData = null;

        if (preg_match('/^data:([a-zA-Z0-9\/\.\-\+\;]+);base64,/', $base64Document, $matches)) {
            $mimeType = $matches[1];
            $fileData = base64_decode(substr($base64Document, strpos($base64Document, ',') + 1));
        } else {
            $fileData = base64_decode($base64Document);
        }

        // Detect mime type if not found
        if (! $mimeType && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $fileData);
            finfo_close($finfo);
        }

        $extension = $this->getExtensionFromMimeType($mimeType);

        // Save to temp file
        $tempPath = sys_get_temp_dir().'/'.uniqid('doc_', true).$extension;
        file_put_contents($tempPath, $fileData);

        return [
            'temp_path' => $tempPath,
            'mime_type' => $mimeType,
            'extension' => ltrim($extension, '.'),
        ];
    }

    /**
     * Get file extension from mime type
     */
    protected function getExtensionFromMimeType(?string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/bmp' => '.bmp',
            'image/svg+xml' => '.svg',
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'application/vnd.ms-powerpoint' => '.ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
            'text/plain' => '.txt',
            'application/zip' => '.zip',
            'application/x-rar-compressed' => '.rar',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'video/mp4' => '.mp4',
        ];

        return $extensions[$mimeType] ?? '.bin';
    }

    /**
     * Check if the file is a PDF based on mime type or extension
     */
    protected function isPdfFile(?string $mimeType, ?string $extension): bool
    {
        if ($mimeType === 'application/pdf') {
            return true;
        }

        $extension = strtolower($extension ?? '');

        return $extension === 'pdf' || $extension === '.pdf';
    }

    /**
     * Parse the pages string and return an array of page numbers
     * Supports formats like: "1,3,5" or "1-5" or "1,3-5,8"
     *
     * @return array<int>
     */
    protected function parsePageNumbers(string $pages): array
    {
        $pageNumbers = [];
        $parts = explode(',', $pages);

        foreach ($parts as $part) {
            $part = trim($part);

            if (str_contains($part, '-')) {
                // Range format: "1-5"
                $range = explode('-', $part);
                if (count($range) === 2) {
                    $start = (int) trim($range[0]);
                    $end = (int) trim($range[1]);

                    if ($start > 0 && $end > 0 && $start <= $end) {
                        for ($i = $start; $i <= $end; $i++) {
                            $pageNumbers[] = $i;
                        }
                    }
                }
            } else {
                // Single page number
                $pageNum = (int) $part;
                if ($pageNum > 0) {
                    $pageNumbers[] = $pageNum;
                }
            }
        }

        // Remove duplicates and sort
        $pageNumbers = array_unique($pageNumbers);
        sort($pageNumbers);

        return $pageNumbers;
    }

    /**
     * Extract specific pages from a PDF file
     * Requires setasign/fpdi package: composer require setasign/fpdi
     *
     * @return string|null The path to the new PDF file or null on failure
     */
    protected function extractPdfPages(
        string $sourcePdfPath,
        string $pages,
        string $folder,
        string $fileName,
        string $attribute
    ): ?string {
        // Check if FPDI is available
        if (! class_exists(Fpdi::class)) {
            // Log warning and return null to fallback to storing original document
            Log::warning(
                'FPDI library not installed. Install with: composer require setasign/fpdi. Storing original document instead.'
            );

            return null;
        }

        try {
            $pageNumbers = $this->parsePageNumbers($pages);

            if (empty($pageNumbers)) {
                return null;
            }

            // Create new PDF with selected pages
            $pdf = new Fpdi;

            // Get total pages from source PDF
            $totalPages = $pdf->setSourceFile($sourcePdfPath);

            // Check for invalid page numbers
            $invalidPages = array_filter($pageNumbers, fn ($page) => $page > $totalPages);

            if (! empty($invalidPages)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid page numbers: %s. The document only has %d page(s).',
                        implode(', ', $invalidPages),
                        $totalPages
                    )
                );
            }

            foreach ($pageNumbers as $pageNumber) {
                // Import the page
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);

                // Add page with the same size as the original
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            // Generate output filename
            $outputFilename = $this->generateUniqueFilename($fileName, 'pdf', $attribute);
            $outputPath = $folder.'/'.$outputFilename;
            $fullOutputPath = public_path($outputPath);

            // Ensure directory exists
            $directory = dirname($fullOutputPath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Delete old file
            $this->deleteOldFile($attribute);

            // Save the new PDF
            $pdf->Output('F', $fullOutputPath);

            return '/'.$outputPath;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error extracting PDF pages: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Store document from temporary file path
     */
    protected function storeDocumentFromTemp(
        string $attribute,
        string $tempFilePath,
        string $folder,
        string $fileName,
        ?string $extension
    ): void {
        $this->deleteOldFile($attribute);

        $extension = $extension ? ltrim($extension, '.') : 'bin';
        $outputFilename = $this->generateUniqueFilename($fileName, $extension, $attribute);
        $outputPath = $folder.'/'.$outputFilename;
        $fullOutputPath = public_path($outputPath);

        // Ensure directory exists
        $directory = dirname($fullOutputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Copy temp file to destination
        copy($tempFilePath, $fullOutputPath);

        $this->attributes[$attribute] = '/'.$outputPath;
    }
}
