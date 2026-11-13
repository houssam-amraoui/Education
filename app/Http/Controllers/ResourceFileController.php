<?php

namespace App\Http\Controllers;

use App\Actions\Analytics\RecordAnalyticsEvent;
use App\Models\AnalyticsEvent;
use App\Models\Lesson;
use App\Models\Resource;
use App\Support\DocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceFileController extends Controller
{
    public function __construct(private RecordAnalyticsEvent $analytics) {}

    public function show(Resource $resource, string $disposition, DocumentResponse $documents): BinaryFileResponse
    {
        Gate::authorize('view', $resource);

        $response = $documents->make($resource->storedFile, $resource->title, $disposition);

        if (request()->isMethod('GET')) {
            $this->analytics->content(request(), AnalyticsEvent::RESOURCE_OPEN, $resource->id);
        }

        return $response;
    }

    public function lesson(Lesson $lesson, Resource $resource, string $disposition, DocumentResponse $documents): BinaryFileResponse
    {
        abort_unless(Lesson::visibleTo(request()->user())->whereKey($lesson->getKey())->exists(), 404);

        return $this->show($resource, $disposition, $documents);
    }
}
