<ul>
    @foreach($children as $child)
        <li>
            {{ $child->name }}
            @if($child->children->count())
                @include('categories.partials.child', ['children' => $child->children])
            @endif
        </li>
    @endforeach
</ul>