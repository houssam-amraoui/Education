<?php

namespace App\Http\Controllers;

use App\Support\Prototype;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class PrototypeController extends Controller
{
    public function primitives(): View
    {
        $lessons = collect([
            ['The passive voice', 'Grammar', 15],
            ['Reported speech', 'Grammar', 20],
            ['Making suggestions', 'Functions', 10],
            ['Gifts of youth', 'Reading', 18],
            ['Writing an opinion paragraph', 'Writing', 25],
            ['Words for personal qualities', 'Vocabulary', 12],
            ['Expressing agreement', 'Functions', 14],
        ]);

        $perPage = 3;
        $page = min(LengthAwarePaginator::resolveCurrentPage(), (int) ceil($lessons->count() / $perPage));
        $paginator = new LengthAwarePaginator($lessons->forPage($page, $perPage), $lessons->count(), $perPage, $page, [
            'path' => route('prototype.primitives'),
        ]);

        return view('prototype.fixtures.primitives', compact('paginator'));
    }

    public function show(Request $request): View
    {
        $screens = Prototype::screens();
        $screen = $screens[$request->route('screen')];
        $emptyCategory = false;
        if ($screen['id'] === 'G06') {
            $category = $request->query('category');
            if (is_string($category) && isset(Prototype::UNIT_CATEGORIES[$category]) && $category !== 'grammar') {
                $screen['title'] = Prototype::UNIT_CATEGORIES[$category].' lessons';
                $emptyCategory = true;
            }
        }
        $area = str_starts_with($screen['id'], 'A') ? 'admin' : (str_starts_with($screen['id'], 'S') ? 'student' : 'public');
        if ($area === 'public' && $request->query('preview') === 'student') {
            $area = 'student';
        }
        $breadcrumbs = [];
        $ancestor = $screen['parent'];
        while ($ancestor !== null) {
            if (Route::has(Prototype::routeName($ancestor))) {
                array_unshift($breadcrumbs, $screens[$ancestor]);
            }
            $ancestor = $screens[$ancestor]['parent'];
        }

        $url = fn (string $id, array $parameters = []): string => Prototype::url($id, $parameters);

        return view('prototype.page', compact('screens', 'screen', 'area', 'breadcrumbs', 'url', 'emptyCategory'));
    }
}
