<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\StoredFile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SaveExam
{
    public function handle(Exam $exam, array $attributes, array $uploads): Exam
    {
        foreach (['available_from', 'available_until'] as $field) {
            $attributes[$field] = ! isset($attributes[$field])
                ? null
                : CarbonImmutable::parse($attributes[$field])->utc();
        }

        $files = [];
        try {
            foreach ($uploads as $relation => $upload) {
                $path = 'exams/'.Str::uuid().'.pdf';
                $files[$relation] = ['disk' => StoredFile::DISK, 'path' => $path, 'mime_type' => 'application/pdf', 'size' => $upload->getSize()];
                if ($upload->storeAs($path, ['disk' => StoredFile::DISK]) === false) {
                    throw new RuntimeException('The exam file could not be stored.');
                }
            }

            [$current, $replaced] = DB::transaction(function () use ($exam, $attributes, $files): array {
                $current = $exam->exists ? Exam::query()->lockForUpdate()->findOrFail($exam->id) : $exam->newInstance();
                $current->fill($attributes);
                if ($current->isDirty('unit_id')) {
                    $current->position = null;
                }

                $replaced = [];
                foreach ($files as $relation => $file) {
                    if ($previous = $current->{$relation}) {
                        $replaced[] = $previous;
                    }
                    $current->{$relation}()->associate(StoredFile::create($file));
                }
                $current->save();

                foreach ($replaced as $file) {
                    $file->delete();
                }

                return [$current, $replaced];
            }, 3);
        } catch (Throwable $exception) {
            foreach ($files as $file) {
                $this->deleteFile($file['path']);
            }

            throw $exception;
        }

        if ($replaced !== []) {
            DB::afterCommit(function () use ($replaced): void {
                foreach ($replaced as $file) {
                    $this->deleteFile($file->path);
                }
            });
        }

        return $current;
    }

    private function deleteFile(string $path): void
    {
        try {
            Storage::disk(StoredFile::DISK)->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
