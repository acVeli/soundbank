<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('account.favorites', [
            'samples' => $user->favorites()->published()->with(['artist.artistProfile', 'category'])->latest('favorites.created_at')->paginate(30),
            'owned' => $user->ownedSampleIds(),
        ]);
    }

    public function toggle(Request $request, Sample $sample): RedirectResponse|JsonResponse
    {
        $result = $request->user()->favorites()->toggle($sample->getKey());
        $added = in_array($sample->getKey(), $result['attached'], false);

        if ($request->expectsJson()) {
            return response()->json(['favorite' => $added]);
        }

        return back()->with('status', $added ? 'Ajouté à vos favoris.' : 'Retiré de vos favoris.');
    }
}
