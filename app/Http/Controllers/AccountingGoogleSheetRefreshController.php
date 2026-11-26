<?php

namespace App\Http\Controllers;

use App\Services\CustomerDebtSheetService;
use App\Services\GoogleSheetCustomerImportService;
use App\Services\SupplierSheetDebtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AccountingGoogleSheetRefreshController extends Controller
{
    public function __invoke(
        Request $request,
        CustomerDebtSheetService $sheets,
        GoogleSheetCustomerImportService $customers,
        SupplierSheetDebtService $suppliers,
    ) {
        $actor = $request->user();
        abort_unless($actor->canManageCustomerDebt(), 403);

        $results = Cache::lock('accounting-google-sheet-refresh', 180)->get(function () use ($actor, $sheets, $customers, $suppliers) {
            try {
                $tabs = $sheets->fetchAccountingTabs();
            } catch (Throwable $exception) {
                report($exception);

                return ['connection' => ['ok' => false, 'message' => 'Không đọc được Google Sheet. Vui lòng kiểm tra kết nối và quyền đọc rồi thử lại.']];
            }

            $missing = array_diff(CustomerDebtSheetService::SETTLEMENT_TABS, array_keys($tabs));

            return [
                'payments' => $missing
                    ? $this->missingTabs($missing)
                    : $this->refreshSource(function () use ($tabs, $sheets, $actor) {
                        $batch = $sheets->saveTabs(array_map(fn ($name) => $tabs[$name], CustomerDebtSheetService::SETTLEMENT_TABS), $actor->id, 'google');

                        return [
                            'message' => sprintf('%d khoản mới, %d thay đổi, %d đã bỏ khỏi nguồn.', $batch['new_count'], $batch['updated_count'], $batch['removed_count']),
                            'url' => route('customer-debt.payments', ['batch' => $batch['id'], 'change' => 'all']),
                        ];
                    }, 'Chưa cập nhật được công nợ và thanh toán. Vui lòng kiểm tra dữ liệu nguồn.'),
                'customers' => !isset($tabs['nhatkybanhang'])
                    ? $this->missingTabs(['nhatkybanhang'])
                    : $this->refreshSource(function () use ($tabs, $customers, $actor) {
                        $batch = $customers->refreshCatalog($tabs['nhatkybanhang'], $actor)['batch'];

                        return [
                            'message' => sprintf('%d khách mới, %d thay đổi, %d không đổi.', $batch['added'], $batch['updated'], $batch['unchanged']),
                        ];
                    }, 'Chưa cập nhật được danh mục khách nguồn. Vui lòng kiểm tra dữ liệu nguồn.'),
                'suppliers' => !isset($tabs['nhatkymuahang'])
                    ? $this->missingTabs(['nhatkymuahang'])
                    : $this->refreshSource(function () use ($tabs, $suppliers, $sheets) {
                        $snapshot = $suppliers->refreshFromRows($tabs['nhatkymuahang'], $sheets);

                        return ['message' => 'Đã cập nhật '.count($snapshot['rows']).' dòng mua hàng.', 'url' => route('customer-debt.suppliers.index')];
                    }, 'Chưa cập nhật được công nợ nhà cung cấp. Vui lòng kiểm tra dữ liệu nguồn.'),
            ];
        });

        $results = $results ?: ['connection' => ['ok' => false, 'message' => 'Đang có một lần cập nhật Google Sheet. Vui lòng chờ lần đó hoàn tất.']];

        return redirect()->route('accounting.dashboard')->with('google_sheet_refresh_results', $results);
    }

    private function missingTabs(array $tabs): array
    {
        return ['ok' => false, 'message' => 'Thiếu tab '.implode(', ', $tabs).'. Dữ liệu lần trước được giữ lại.'];
    }

    private function refreshSource(callable $operation, string $fallback): array
    {
        try {
            return ['ok' => true] + $operation();
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'message' => $fallback];
        }
    }
}
