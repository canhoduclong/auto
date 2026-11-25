<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CompanyAssetReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'mode' => 'nullable|in:assets,payments',
            'q' => 'nullable|string|max:150',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : null]),
            'partner_id' => 'nullable|integer|min:1',
            'contract_id' => 'nullable|integer|min:1',
            'category' => ['nullable', Rule::in(array_keys(CompanyAssetController::CATEGORIES))],
            'status' => ['nullable', Rule::in(array_keys(CompanyAssetController::ASSET_STATUSES))],
            'kind' => 'nullable|in:deposit,installment,unassigned',
            'export' => 'nullable|in:xlsx',
        ]);
        $mode = $filters['mode'] ?? 'assets';
        $query = $mode === 'assets' ? $this->assets($filters) : $this->payments($filters);
        $title = $mode === 'assets' ? 'Giá trị tài sản và khoản chi' : 'Thực chi theo hợp đồng';
        $total = (int) (clone $query)->sum($mode === 'assets' ? 'a.value' : 'p.amount');
        if (isset($filters['export'])) {
            return $this->export($query, $title, $total);
        }

        $rows = $query->paginate(25)->withQueryString();
        $partners = DB::table('asset_partners')->orderBy('name')->get(['id', 'name']);
        $contracts = DB::table('asset_contracts')->orderBy('number')->get(['id', 'number', 'title']);

        return view('accounting.assets.report', compact('filters', 'mode', 'title', 'total', 'rows', 'partners', 'contracts'));
    }

    private function assets(array $filters): Builder
    {
        $q = trim($filters['q'] ?? '');
        return DB::table('company_assets as a')
            ->leftJoin('asset_contracts as c', 'c.id', '=', 'a.contract_id')
            ->leftJoin('asset_partners as partner', 'partner.id', '=', 'c.partner_id')
            ->select('a.id', 'a.name as content', 'a.code as reference', 'a.value as amount', 'a.created_at as report_date',
                'a.category', 'a.status', 'c.id as contract_id', 'c.number as contract_number', 'partner.name as partner_name')
            ->when($q !== '', fn ($query) => $query->where(fn ($match) =>
                $match->whereLike('a.name', "%{$q}%")->orWhereLike('a.code', "%{$q}%")->orWhereLike('a.note', "%{$q}%")
                    ->orWhereLike('c.number', "%{$q}%")->orWhereLike('partner.name', "%{$q}%")))
            ->when($filters['category'] ?? null, fn ($query, $value) => $query->where('a.category', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('a.status', $value))
            ->when($filters['partner_id'] ?? null, fn ($query, $id) => $query->where('c.partner_id', $id))
            ->when($filters['contract_id'] ?? null, fn ($query, $id) => $query->where('a.contract_id', $id))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->where('a.created_at', '>=', $date.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->where('a.created_at', '<', \Carbon\CarbonImmutable::parse($date)->addDay()->toDateString()))
            ->orderBy('a.created_at')->orderBy('a.id');
    }

    private function payments(array $filters): Builder
    {
        $q = trim($filters['q'] ?? '');

        return DB::table('asset_contract_payments as p')
            ->join('asset_contracts as c', 'c.id', '=', 'p.contract_id')
            ->join('asset_partners as partner', 'partner.id', '=', 'c.partner_id')
            ->leftJoin('asset_contract_installments as i', 'i.id', '=', 'p.installment_id')
            ->select('p.id', 'p.reference', 'p.amount', 'p.paid_on as report_date', 'p.contract_id',
                'c.number as contract_number', 'partner.name as partner_name')
            ->selectRaw("COALESCE(NULLIF(p.note, ''), i.label, c.title) as content")
            ->when($q !== '', fn ($query) => $query->where(fn ($match) =>
                $match->whereLike('p.note', "%{$q}%")->orWhereLike('p.reference', "%{$q}%")
                    ->orWhereLike('i.label', "%{$q}%")->orWhereLike('c.title', "%{$q}%")
                    ->orWhereLike('c.number', "%{$q}%")->orWhereLike('partner.name', "%{$q}%")))
            ->when($filters['partner_id'] ?? null, fn ($query, $id) => $query->where('c.partner_id', $id))
            ->when($filters['contract_id'] ?? null, fn ($query, $id) => $query->where('p.contract_id', $id))
            ->when($filters['kind'] ?? null, fn ($query, $kind) => $kind === 'unassigned'
                ? $query->whereNull('p.installment_id') : $query->where('i.kind', $kind))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->where('p.paid_on', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->where('p.paid_on', '<=', $date))
            ->orderBy('p.paid_on')->orderBy('p.id');
    }

    private function export(Builder $query, string $title, int $total)
    {
        return response()->streamDownload(function () use ($query, $title, $total) {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Báo cáo tài sản');
            $sheet->mergeCells('A1:B1')->setCellValue('A1', $title)->setCellValue('C1', $total);
            $sheet->fromArray(['STT', 'Nội dung', 'Số tiền (VNĐ)'], null, 'A2');
            $sheet->getStyle('A1:C2')->getFont()->setBold(true);
            $sheet->getStyle('A1:C1')->getFill()->setFillType('solid')->getStartColor()->setRGB('D9E8FF');
            $sheet->getStyle('A2:C2')->getFill()->setFillType('solid')->getStartColor()->setRGB('F0F3F8');
            $sheet->getColumnDimension('A')->setWidth(9);
            $sheet->getColumnDimension('B')->setWidth(78);
            $sheet->getColumnDimension('C')->setWidth(23);
            $line = 3;
            foreach ($query->cursor() as $row) {
                $sheet->setCellValue('A'.$line, $line - 2);
                $sheet->setCellValueExplicit('B'.$line, $row->content, DataType::TYPE_STRING);
                if ($row->amount !== null) {
                    $sheet->setCellValue('C'.$line, (int) $row->amount);
                }
                $line++;
            }
            $sheet->getStyle('C1:C'.max(2, $line - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('B3:B'.max(3, $line - 1))->getAlignment()->setWrapText(true);
            $sheet->freezePane('A3');
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'bao-cao-tai-san-'.today()->toDateString().'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
