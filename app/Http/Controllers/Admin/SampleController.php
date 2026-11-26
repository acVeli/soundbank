<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modération du catalogue : bloquer un son le retire de la vente.
 */
class SampleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->value());
        $status = $request->string('statut')->value();

        return view('admin.samples', [
            'samples' => Sample::query()
                ->with(['artist.artistProfile', 'category.parent', 'pack'])
                ->when(in_array($status, [Sample::STATUS_DRAFT, Sample::STATUS_PUBLISHED, Sample::STATUS_BLOCKED], true), fn (Builder $q) => $q->where('status', $status))
                ->when($search !== '', function (Builder $q) use ($search): void {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                    $q->where(fn (Builder $inner) => $inner->where('title', 'like', $like)
                        ->orWhereHas('artist.artistProfile', fn (Builder $p) => $p->where('stage_name', 'like', $like)));
                })
                ->latest()
                ->paginate(50)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function status(Sample $sample): RedirectResponse
    {
        $blocked = $sample->status === Sample::STATUS_BLOCKED;
        $sample->update(['status' => $blocked ? Sample::STATUS_DRAFT : Sample::STATUS_BLOCKED]);

        return back()->with('status', $blocked
            ? '« '.$sample->title.' » débloqué (repassé en brouillon chez l’artiste).'
            : '« '.$sample->title.' » bloqué et retiré de la vente.');
    }
}
