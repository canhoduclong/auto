<?php

namespace Tests\Unit;

use App\Services\CustomerDebtSheetService;
use PHPUnit\Framework\TestCase;

class CustomerDebtSheetServiceTest extends TestCase
{
    public function test_sales_merged_rows_and_payment_direction_produce_correct_balance(): void
    {
        $service = new CustomerDebtSheetService;
        $sales = $service->parse([
            ['Ngày tháng', 'Mã KH', 'Khách hàng', 'Tổng tiền'],
            ['12/11/2026', 'KH01', 'Anh Bình', '1.200.000'],
            ['', '', '', '300.000'],
            ['', '', 'Tổng cộng', '1.500.000'],
        ], 'sale');
        $payments = $service->parse([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Số tiền', 'Thu/Chi'],
            ['12/11/2026', 'KH01', 'Anh Bình', '500.000', 'Thu'],
            ['12/11/2026', 'KH01', 'Anh Bình', '200.000', 'Chi'],
            ['12/11/2026', '', '', '100.000', 'Thu'],
        ], 'payment');
        $groups = $service->summarize(array_merge($sales, $payments));
        self::assertCount(2, $sales);
        self::assertCount(1, $payments);
        self::assertCount(1, $groups);
        self::assertSame(1000000, array_values($groups)[0]['balance']);
    }

    public function test_bank_tab_uses_code_as_receipt_category_not_customer_code(): void
    {
        $issues = [];
        $entries = (new CustomerDebtSheetService)->parse([
            ['NKC TKCN'], ['Ngày tháng', 'Tháng', 'Code', 'Tên KH', 'NVKD', 'Nội Dung', 'Thu', 'Chi'],
            ['08/11/2026', '11', 'TTBH', 'A Mát', 'Duệ', 'Thu tiền vịt', '60.000.000', ''],
            ['09/11/2026', '11', 'CPK', '', '', 'Phí', '', '4.700'],
            ['09/11/2026', '11', 'TUTM', 'HA', '', 'Chuyển nội bộ', '2.000.000', ''],
            ['09/11/2026', '11', 'TTBH', '', '', 'Chưa rõ khách', '1.000.000', ''],
        ], 'payment', $issues, 'TKCNMBBDUE');
        self::assertCount(1, $entries);
        self::assertSame('', $entries[0]['code']);
        self::assertSame('TKCNMBBDUE', $entries[0]['source_sheet']);
        self::assertSame(60000000, $entries[0]['amount']);
        self::assertCount(1, $issues);
        self::assertSame('TKCNMBBDUE', $issues[0]['source_sheet']);
        self::assertSame(6, $issues[0]['row']);
    }

    public function test_both_cash_flow_schemas_match_the_same_customer_and_keep_receipts_from_each_account(): void
    {
        $service = new CustomerDebtSheetService;
        $issues = [];
        $sales = $service->parse([['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', '0964', 'A Mát', '100.000.000']], 'sale');
        $cashFlow = $service->parse([
            ['Ngày tháng', 'Loại', 'Tên KH/NCC', 'Thu', 'Chi'],
            ['13/11/2026', 'TTBH', 'A Mát', '10.000.000', ''],
            ['13/11/2026', 'TUTM', 'A Mát', '5.000.000', ''],
        ], 'payment', $issues, 'Dongtienhoatdong');
        $bank = $service->parse([
            ['Ngày tháng', 'Code', 'Tên KH', 'Thu', 'Chi'],
            ['13/11/2026', 'TTBH', ' a   mát ', '60.000.000', ''],
            ['13/11/2026', 'CPK', 'A Mát', '', '4.700'],
        ], 'payment', $issues, 'TKCNMBBDUE');
        $groups = $service->summarize(array_merge($sales, $cashFlow, $bank));
        self::assertCount(1, $groups);
        $customer = array_values($groups)[0];
        self::assertSame('0964', $customer['code']);
        self::assertSame(70000000, $customer['payments']);
        self::assertSame(30000000, $customer['balance']);
        self::assertSame('', $bank[0]['code']);
        self::assertCount(2, array_filter($customer['entries'], fn ($entry) => $entry['type'] === 'payment'));
    }

    public function test_code_header_outside_the_bank_tab_is_not_assumed_to_be_transaction_category(): void
    {
        $issues = [];
        $entries = (new CustomerDebtSheetService)->parse([['Ngày', 'Code', 'Khách hàng', 'Tiền thu'], ['13/11/2026', 'KH01', 'Chị Bình', '1000']], 'payment', $issues, 'Dongtienhoatdong');
        self::assertCount(1, $entries);
        self::assertSame(1000, $entries[0]['amount']);
    }

    public function test_bank_tab_with_no_customer_receipts_is_a_valid_empty_source(): void
    {
        $issues = [];
        self::assertSame([], (new CustomerDebtSheetService)->parse([['Ngày tháng', 'Code', 'Tên KH', 'Thu', 'Chi'], ['09/11/2026', 'CPK', '', '', '4700']], 'payment', $issues, 'TKCNMBBDUE'));
    }

    public function test_duplicate_names_with_different_codes_never_merge(): void
    {
        $s = new CustomerDebtSheetService;
        $entries = [];
        foreach (['KH01', 'KH02', ''] as $code) {
            $entries[] = ['date' => '2026-11-12', 'name' => 'Anh Bình', 'code' => $code, 'type' => 'sale', 'amount' => 100, 'row' => 1, 'note' => ''];
        }
        $groups = $s->summarize($entries);
        self::assertCount(3, $groups);
        self::assertCount(1, array_filter($groups, fn ($g) => $g['ambiguous']));
    }

    public function test_name_fallback_only_joins_a_unique_code_and_preserves_overpayment(): void
    {
        $s = new CustomerDebtSheetService;
        $base = ['date' => '2026-11-12', 'name' => 'Anh Bình', 'row' => 1, 'note' => ''];
        $groups = $s->summarize([$base + ['code' => 'KH01', 'type' => 'sale', 'amount' => 100], $base + ['code' => '', 'type' => 'payment', 'amount' => 150]]);
        self::assertCount(1, $groups);
        self::assertSame(-50, array_values($groups)[0]['balance']);
    }

    public function test_ambiguous_cashflow_headers_are_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        (new CustomerDebtSheetService)->parse([['Ngày', 'Khách hàng', 'Số tiền'], ['12/11/2026', 'Anh Bình', '100.000']], 'payment');
    }

    public function test_invalid_dates_do_not_silently_drop_debt(): void
    {
        $this->expectException(\RuntimeException::class);
        (new CustomerDebtSheetService)->parse([['Ngày', 'Khách hàng', 'Tổng tiền'], ['31/02/2026', 'Anh Bình', '100.000']], 'sale');
    }

    public function test_malformed_money_is_rejected_instead_of_cast_to_zero(): void
    {
        $this->expectException(\RuntimeException::class);
        (new CustomerDebtSheetService)->money('lỗi công thức');
    }

    public function test_workbook_reads_the_two_named_tabs_with_formatted_cells(): void
    {
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $book->getActiveSheet()->setTitle('nhatkybanhang')->fromArray([['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['12/11/2026', 'KH01', 'Anh Bình', 1500000]]);
        $book->createSheet()->setTitle('Dongtienhoatdong')->fromArray([['Ngày', 'Mã KH', 'Khách hàng', 'Tiền thu'], ['12/11/2026', 'KH01', 'Anh Bình', 500000]]);
        $path = tempnam(sys_get_temp_dir(), 'debt-test-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
            $s = new CustomerDebtSheetService;
            [$sales, $payments] = $s->workbook($path);
            $groups = $s->summarize(array_merge($s->parse($sales, 'sale'), $s->parse($payments, 'payment')));
            self::assertSame(1000000, array_values($groups)[0]['balance']);
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_workbook_preserves_the_additional_bank_payment_tab(): void
    {
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $book->getActiveSheet()->setTitle('nhatkybanhang')->fromArray([['Ngày', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'Chị Bình', 1000]]);
        $book->createSheet()->setTitle('Dongtienhoatdong')->fromArray([['Ngày', 'Khách hàng', 'Tiền thu'], ['13/11/2026', 'Chị Bình', 200]]);
        $book->createSheet()->setTitle('TKCNMBBDUE')->fromArray([['Ngày tháng', 'Code', 'Tên KH', 'Thu'], ['13/11/2026', 'TTBH', 'Chị Bình', 300]]);
        $path = tempnam(sys_get_temp_dir(), 'debt-bank-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
            $tabs = (new CustomerDebtSheetService)->workbook($path);
            self::assertCount(3, $tabs);
            self::assertSame('TTBH', $tabs[2][1][1]);
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_workbook_keeps_all_five_payment_tabs_even_when_bank_tabs_are_out_of_order(): void
    {
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $book->getActiveSheet()->setTitle('nhatkybanhang')->fromArray([['Ngày', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'Chị Bình', 1000]]);
        foreach (array_reverse(CustomerDebtSheetService::PAYMENT_TABS) as $tab) {
            $book->createSheet()->setTitle($tab)->fromArray([['Ngày tháng', 'Code', 'Tên KH', 'Thu'], ['13/11/2026', 'TTBH', $tab, 100]]);
        }
        $path = tempnam(sys_get_temp_dir(), 'debt-five-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
            $tabs = (new CustomerDebtSheetService)->workbook($path);
            self::assertCount(6, $tabs);
            foreach (CustomerDebtSheetService::PAYMENT_TABS as $i => $tab) {
                self::assertSame($tab, $tabs[$i + 1][1][2]);
            }
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_real_sales_headers_use_first_customer_and_date_columns(): void
    {
        $s = new CustomerDebtSheetService;
        $rows = [
            ['Ngày tháng', 'Tháng', 'Mã KH', 'Khách hàng', 'NVKD', 'DVT', 'SL', 'Kg/con', 'Tổng', 'Đơn giá', 'Tổng tiền', 'Thanh toán', 'Tặng', '', 'Date', 'STT', 'Khách hàng'],
            ['24/08/2024', '8', 'KH01', 'Anh Hải', 'Huy', 'Con', '8', '2,75', '22', '65.000', '1.430.000', '', '', '', '45.528', '1', 'Tên trong bảng phụ'],
        ];
        $entries = $s->parse($rows, 'sale');
        self::assertSame('Anh Hải', $entries[0]['name']);
        self::assertSame('2024-08-24', $entries[0]['date']);
    }

    public function test_real_cashflow_only_uses_ttbh_and_reports_unidentified_receipts(): void
    {
        $s = new CustomerDebtSheetService;
        $issues = [];
        $entries = $s->parse([
            ['Ngày tháng', 'Tháng', 'Loại', 'Tên KH/NCC', 'NVKD', 'nội dung', 'Thu', 'Chi'],
            ['22/8/2024', '8', ' TTBH', 'Anh Bình', '', 'Thu tiền vịt', '1.000.000', ''],
            ['22/8/2024', '8', 'CPS', 'Anh Bình', '', 'Thu tiền xốp', '150.000', ''],
            ['22/8/2024', '8', 'TTM', 'Anh Bình', '', 'Vay tiền', '10.000.000', ''],
            ['22/8/2024', '8', 'TTBH', '', '', 'Chưa điền khách', '500.000', ''],
        ], 'payment', $issues);
        self::assertCount(1, $entries);
        self::assertSame('2024-08-22', $entries[0]['date']);
        self::assertSame(1000000, $entries[0]['amount']);
        self::assertCount(1, $issues);
        self::assertSame(500000, $issues[0]['amount']);
        self::assertSame(5, $issues[0]['row']);
    }

    public function test_customer_matching_preserves_vietnamese_accents(): void
    {
        $s = new CustomerDebtSheetService;
        self::assertNotSame($s->customerName('Anh Hà'), $s->customerName('Anh Hạ'));
        self::assertSame($s->customerName(' Chị  Bình '), $s->customerName('chị bình'));
    }

    public function test_company_customer_names_are_not_mistaken_for_total_rows(): void
    {
        $entries = (new CustomerDebtSheetService)->parse([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'],
            ['12/11/2026', 'CT01', 'Công ty Thực Phẩm', '5.000.000'],
            ['', '', 'Tổng cộng', '5.000.000'],
        ], 'sale');
        self::assertCount(1, $entries);
        self::assertSame(5000000, $entries[0]['amount']);
    }

    public function test_gifts_reduce_receivables_without_changing_gross_sales_or_payments(): void
    {
        $service = new CustomerDebtSheetService;
        $sales = $service->parse([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'Tặng'],
            ['12/11/2026', 'KH01', 'Anh Bình', '1.000.000', '200.000'],
            ['12/11/2026', 'KH01', 'Anh Bình', '100.000', '100.000'],
            ['12/11/2026', 'KH01', 'Anh Bình', '50.000', '-'],
        ], 'sale');
        $payments = $service->parse([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Thu'],
            ['12/11/2026', 'KH01', 'Anh Bình', '500.000'],
        ], 'payment');
        $group = array_values($service->summarize(array_merge($sales, $payments)))[0];
        self::assertSame(1150000, $group['sales']);
        self::assertSame(300000, $group['gifts']);
        self::assertSame(500000, $group['payments']);
        self::assertSame(350000, $group['balance']);
        self::assertSame(0, $service->movement($sales[1]));
    }

    public function test_text_in_gift_column_is_preserved_as_a_note_without_a_deduction(): void
    {
        $service = new CustomerDebtSheetService;
        $entries = $service->parse([
            ['Ngày', 'Khách hàng', 'Tổng tiền', 'Tặng'],
            ['12/11/2026', 'Anh Bình', '1.000.000', 'bao gồm 3tr tiền ck'],
        ], 'sale');
        self::assertSame(0, $entries[0]['gift']);
        self::assertSame('bao gồm 3tr tiền ck', $entries[0]['gift_note']);
        self::assertStringContainsString('Ghi chú cột Tặng: bao gồm 3tr tiền ck', $entries[0]['note']);
        self::assertSame(1000000, $service->movement($entries[0]));
    }

    public function test_latest_purchase_and_payment_dates_use_actual_positive_transactions(): void
    {
        $base = ['code' => 'KH01', 'name' => 'Anh Bình', 'row' => 1, 'note' => ''];
        $groups = (new CustomerDebtSheetService)->summarize([
            $base + ['date' => '2026-11-10', 'type' => 'sale', 'amount' => 1000],
            $base + ['date' => '2026-11-01', 'type' => 'sale', 'amount' => 500],
            $base + ['date' => '2026-11-08', 'type' => 'payment', 'amount' => 100],
            $base + ['date' => '2026-11-12', 'type' => 'payment', 'amount' => 0],
            $base + ['date' => '2026-11-11', 'type' => 'sale', 'amount' => -100],
            $base + ['date' => '2026-11-09', 'type' => 'payment', 'amount' => 200],
        ]);
        $group = array_values($groups)[0];
        self::assertSame('2026-11-10', $group['last_purchase_date']);
        self::assertSame('2026-11-09', $group['last_payment_date']);
        $unpaid = (new CustomerDebtSheetService)->summarize([$base + ['date' => '2026-11-10', 'type' => 'sale', 'amount' => 1000]]);
        self::assertNull(array_values($unpaid)[0]['last_payment_date']);
    }

    public function test_source_sales_name_comes_from_the_latest_sales_row(): void
    {
        $service = new CustomerDebtSheetService;
        $entries = $service->parse([
            ['Ngày', 'Mã KH', 'Khách hàng', 'NVKD', 'Tổng tiền'],
            ['10/11/2026', 'KH01', 'Anh Bình', 'Huy', '1.000.000'],
            ['01/11/2026', 'KH01', 'Anh Bình', 'Hà', '500.000'],
            ['11/11/2026', 'KH01', 'Anh Bình', '#N/A', '100.000'],
        ], 'sale');
        self::assertSame('Huy', $entries[0]['sale_name']);
        self::assertSame('Huy', array_values($service->summarize($entries))[0]['sale_name']);
    }
}
