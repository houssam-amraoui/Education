<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Support\Prototype;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $request->session()->put('locale', $data['locale']);

        parse_str($data['query'] ?? '', $query);
        $path = route(Prototype::routeName($data['screen']), absolute: false);
        $queryString = http_build_query($query);

        return redirect()->to($queryString === '' ? $path : $path.'?'.$queryString, 303);
    }
}
