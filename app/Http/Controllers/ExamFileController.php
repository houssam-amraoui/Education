<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Support\DocumentResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamFileController extends Controller
{
    public function __invoke(Exam $exam, string $document, string $disposition, DocumentResponse $documents): BinaryFileResponse
    {
        Gate::authorize('viewFile', $exam);

        $file = $document === 'paper' ? $exam->examFile : $exam->correctionFile;

        return $documents->make($file, $exam->title.' '.$document, $disposition);
    }
}
