<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Arborescence des catégories (familles et sous-catégories).
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories', [
            'families' => Category::query()->roots()->withCount('samples')->with(['children' => fn ($q) => $q->withCount('samples')])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Category::query()->create([
            ...$data,
            'slug' => $this->slug($data['name'], $data['parent_id'] ?? null),
            'position' => (int) ($data['position'] ?? 0),
        ]);

        return back()->with('status', 'Catégorie « '.$data['name'].' » créée.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);

        if (($data['parent_id'] ?? null) !== null && $category->children()->exists()) {
            return back()->with('error', 'Une famille qui a des sous-catégories ne peut pas devenir elle-même une sous-catégorie.');
        }

        $category->update([...$data, 'position' => (int) ($data['position'] ?? 0)]);

        return back()->with('status', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->samples()->withTrashed()->exists() || $category->children()->exists()) {
            return back()->with('error', 'Cette catégorie contient des sons ou des sous-catégories : déplacez-les d’abord.');
        }

        $category->delete();

        return back()->with('status', 'Catégorie supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                $category ? Rule::notIn([$category->getKey()]) : 'nullable',
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    private function slug(string $name, ?int $parentId): string
    {
        $parent = $parentId ? Category::query()->find($parentId) : null;
        $base = Str::slug(($parent ? $parent->slug.' ' : '').$name) ?: 'categorie';
        $slug = $base;
        $i = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
