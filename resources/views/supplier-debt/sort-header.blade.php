@php($activeSort = $sort === $field)
<th scope="col" class="{{ $field === 'name' ? '' : 'text-end' }}" rowspan="{{ $rowspan ?? 1 }}" @if($activeSort) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a class="text-reset text-decoration-none" href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $activeSort ? ($direction === 'asc' ? 'desc' : 'asc') : ($field === 'name' ? 'asc' : 'desc'), 'page' => null]) }}">
        {{ $label }} <span aria-hidden="true">{{ $activeSort ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
    </a>
</th>
