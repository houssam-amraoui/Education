<?php

namespace App\Support;

use App\Models\StoredFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class DocumentResponse
{
    public function available(?StoredFile $file): bool
    {
        return $this->readablePath($file) !== null;
    }

    public function make(?StoredFile $file, string $title, string $disposition): BinaryFileResponse
    {
        $path = $this->readablePath($file);
        abort_if($path === null, 404);

        try {
            $response = new BinaryFileResponse($path, 200, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ], public: false);
        } catch (FileException) {
            abort(404);
        }

        return $response->setContentDisposition($disposition, (Str::slug($title) ?: 'document').'.pdf');
    }

    private function readablePath(?StoredFile $file): ?string
    {
        if ($file?->disk !== StoredFile::DISK || $file->mime_type !== 'application/pdf') {
            return null;
        }

        $disk = Storage::disk(StoredFile::DISK);
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($file->path));

        return $root !== false && $path !== false
            && str_starts_with($path, $root.DIRECTORY_SEPARATOR)
            && is_file($path) && is_readable($path) ? $path : null;
    }
}
