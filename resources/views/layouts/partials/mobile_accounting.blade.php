@if(session('mobile_accounting'))
<script src="{{ asset('js/mobile-accounting.js') }}?v={{ filemtime(public_path('js/mobile-accounting.js')) }}" defer></script>
@endif
