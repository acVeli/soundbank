{{-- Liste déroulante des catégories (familles en groupes). --}}
<select name="{{ $name }}" @isset($id) id="{{ $id }}" @endisset @isset($field) data-field="{{ $field }}" @endisset>
    <option value="">{{ $placeholder ?? '— Catégorie —' }}</option>
    @foreach ($tree as $family)
        <optgroup label="{{ $family->name }}">
            @foreach ($family->children as $child)
                <option value="{{ $child->id }}" @selected((string) ($selected ?? '') === (string) $child->id)>{{ $child->name }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
