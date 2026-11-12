<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Resource;
use App\Support\DocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceFileController extends Controller
{
    public function show(Resource $resource, string $disposition, DocumentResponse $documents): BinaryFileResponse
    {
        Gate::authorize('view', $resource);

        return $documents->make($resource->storedFile, $resource->title, $disposition);
    }

    public function lesson(Lesson $lesson, Resource $resource, string $disposition, DocumentResponse $documents): BinaryFileResponse
    {
        abort_unless(Lesson::visibleToPublic()->whereKey($lesson->getKey())->exists(), 404);

        return $this->show($resource, $disposition, $documents);
    }
}
