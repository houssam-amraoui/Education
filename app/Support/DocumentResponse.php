<?php

namespace App\Support;

use App\Models\StoredFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentResponse
{
    public function available(?StoredFile $file): bool
    {
        return $file?->disk === StoredFile::DISK && $file->mime_type === 'application/pdf'
            && Storage::disk(StoredFile::DISK)->fileExists($file->path);
    }

    public function make(?StoredFile $file, string $title, string $disposition): BinaryFileResponse
    {
        abort_unless($this->available($file), 404);

        $response = new BinaryFileResponse(Storage::disk(StoredFile::DISK)->path($file->path), 200, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], public: false);

        return $response->setContentDisposition($disposition, (Str::slug($title) ?: 'document').'.pdf');
    }
}
