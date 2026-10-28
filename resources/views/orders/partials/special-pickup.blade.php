@push('scripts')
<script src="{{ asset('js/special-warehouse-pickup.js') }}?v=20261028-2"></script>
<script>window.specialPickupEndpoints = { options: @json(route('special-pickup.options')), store: @json(route('special-pickup.store')) };</script>
@endpush
