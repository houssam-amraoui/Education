<?php

namespace App\Http\Controllers;

use App\Support\Prototype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class PrototypeController extends Controller
{
    public function show(Request $request): View
    {
        $screens = Prototype::screens();
        $screen = $screens[$request->route('screen')];
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

        return view('prototype.page', compact('screens', 'screen', 'area', 'breadcrumbs', 'url'));
    }
}
