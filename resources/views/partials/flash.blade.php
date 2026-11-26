@if (session('status'))
    <div class="flash" role="status"><x-icon name="check" size="16" /> {{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="flash flash-error" role="alert"><x-icon name="x" size="16" /> {{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="flash flash-error" role="alert">
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
