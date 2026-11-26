<details style="padding-block: 6px; border-bottom: 1px solid var(--line)">
    <summary class="row between" style="cursor: pointer; list-style: none">
        <span @class(['display' => $isFamily]) style="{{ $isFamily ? 'font-size: .85rem' : '' }}">{{ $category->name }}</span>
        <span class="mono small muted">{{ $category->samples_count }} son(s) · #{{ $category->position }}</span>
    </summary>
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="fields" style="margin-top: 10px">
        @csrf
        @method('PUT')
        <div class="field"><label>Nom</label><input name="name" value="{{ $category->name }}" required maxlength="80"></div>
        <div class="field">
            <label>Famille</label>
            <select name="parent_id">
                <option value="">— Famille —</option>
                @foreach ($families as $family)
                    @continue($family->id === $category->id)
                    <option value="{{ $family->id }}" @selected($category->parent_id === $family->id)>{{ $family->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Description</label><input name="description" value="{{ $category->description }}" maxlength="255"></div>
        <div class="field"><label>Ordre</label><input name="position" type="number" min="0" max="999" value="{{ $category->position }}"></div>
        <div class="row"><button class="btn btn-sm">Enregistrer</button></div>
    </form>
    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Supprimer « {{ $category->name }} » ?" style="margin-top: 8px">
        @csrf
        @method('DELETE')
        <button class="link-btn danger small">Supprimer</button>
    </form>
</details>
