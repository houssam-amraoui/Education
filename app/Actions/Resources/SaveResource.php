<?php

namespace App\Actions\Resources;

use App\Models\Resource;
use App\Models\StoredFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SaveResource
{
    public function handle(Resource $resource, array $metadata, ?UploadedFile $upload, array $links = []): Resource
    {
        $path = $upload ? 'resources/'.Str::uuid().'.pdf' : null;
        try {
            if ($upload && $upload->storeAs($path, ['disk' => StoredFile::DISK]) === false) {
                throw new RuntimeException('The resource file could not be stored.');
            }

            [$saved, $previous] = DB::transaction(function () use ($resource, $metadata, $upload, $path, $links): array {
                $current = $resource->exists
                    ? Resource::query()->lockForUpdate()->findOrFail($resource->getKey())
                    : $resource->newInstance();

                $current->fill($metadata);
                $previous = null;
                if ($upload) {
                    $previous = $current->storedFile;
                    $current->storedFile()->associate(StoredFile::create([
                        'disk' => StoredFile::DISK,
                        'path' => $path,
                        'mime_type' => 'application/pdf',
                        'size' => $upload->getSize(),
                    ]));
                }

                $current->save();
                if (array_key_exists('level_ids', $links)) {
                    $current->levels()->sync($links['level_ids']);
                }
                if (array_key_exists('lesson_ids', $links)) {
                    $current->lessons()->sync($links['lesson_ids']);
                }
                $previous?->delete();

                return [$current, $previous];
            }, 3);
        } catch (Throwable $exception) {
            if ($path !== null) {
                $this->deleteFile(StoredFile::DISK, $path);
            }

            throw $exception;
        }

        if ($previous !== null) {
            DB::afterCommit(fn () => $this->deleteFile($previous->disk, $previous->path));
        }

        return $saved->load('storedFile');
    }

    private function deleteFile(string $disk, string $path): void
    {
        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
