<div class="hide-sm text-primary">
@if((float)$stat->kg_basis > 0)<div>{{ number_format($stat->kg_amount / $stat->kg_basis,0,',','.') }}đ/kg</div>@endif
@if((float)$stat->unit_basis > 0)<div>{{ number_format($stat->unit_amount / $stat->unit_basis,0,',','.') }}đ/{{ \App\Enums\ProductUnit::tryFrom($stat->product_unit ?? '')?->label() ?? 'Cái' }}</div>@endif
@if((float)$stat->kg_basis <= 0 && (float)$stat->unit_basis <= 0)—@endif
</div>
