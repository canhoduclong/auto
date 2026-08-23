@php
    $feeDefinitions = [
        'vat' => ['label' => 'Phí VAT', 'unit' => '%'],
        'shipping' => ['label' => 'Phí Ship', 'unit' => 'đ'],
        'discount' => ['label' => 'Chiết khấu đơn', 'unit' => 'đ'],
        'foam_box' => ['label' => 'Phí thùng xốp', 'unit' => 'đ'],
    ];
    $changedFees = collect((array) ($adjustment->fee_changes ?? []))->filter(function ($change): bool {
        $original = (array) ($change['original'] ?? []);
        $adjusted = (array) ($change['adjusted'] ?? []);

        return (bool) ($original['enabled'] ?? false) !== (bool) ($adjusted['enabled'] ?? false)
            || abs((float) ($original['value'] ?? 0) - (float) ($adjusted['value'] ?? 0)) > 0.001;
    });
    $formatFeeValue = static function (array $state, string $unit): string {
        if (! (bool) ($state['enabled'] ?? false)) {
            return 'Không áp dụng';
        }

        $value = (float) ($state['value'] ?? 0);
        if ($unit === '%') {
            return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
        }

        return number_format($value, 0, ',', '.') . 'đ';
    };
@endphp

@if($changedFees->isNotEmpty())
    <div class="border rounded p-3 mb-3 bg-warning-subtle">
        <div class="fw-bold mb-2"><i class="bi bi-receipt me-1"></i>Phí và chiết khấu đề nghị thay đổi</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr><th>Khoản mục</th><th class="text-end">Hiện tại</th><th class="text-end">Sau điều chỉnh</th></tr>
                </thead>
                <tbody>
                    @foreach($changedFees as $key => $change)
                        @php
                            $definition = $feeDefinitions[$key] ?? ['label' => ucfirst((string) $key), 'unit' => 'đ'];
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $definition['label'] }}</td>
                            <td class="text-end text-muted">{{ $formatFeeValue((array) ($change['original'] ?? []), $definition['unit']) }}</td>
                            <td class="text-end text-danger fw-bold">{{ $formatFeeValue((array) ($change['adjusted'] ?? []), $definition['unit']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
