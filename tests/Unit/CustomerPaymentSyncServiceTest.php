<?php

namespace Tests\Unit;

use App\Services\CustomerPaymentSyncService;
use PHPUnit\Framework\TestCase;

class CustomerPaymentSyncServiceTest extends TestCase
{
    private function receipt(array $overrides = []): array
    {
        return $overrides + ['type' => 'payment', 'date' => '2026-11-13', 'code' => 'KH01', 'name' => 'Chị Bình', 'amount' => 1000, 'note' => 'Thu tiền', 'row' => 10, 'source_sheet' => 'Dongtienhoatdong'];
    }

    private function snapshot(array $entries, array $issues = []): array
    {
        return ['entries' => $entries, 'issues' => $issues, 'source' => 'google', 'loaded_at' => '2026-11-13T08:00:00+07:00', 'loaded_by' => 7, 'payment_tabs' => ['Dongtienhoatdong', 'TKCNMBBDUE']];
    }

    public function test_row_moves_are_not_new_payments_but_duplicate_occurrences_are_counted(): void
    {
        $service = new CustomerPaymentSyncService;
        $old = $this->snapshot([$this->receipt()]);
        $next = $this->snapshot([$this->receipt(['row' => 11]), $this->receipt(['row' => 12])]);
        $result = $service->track($old, $next, 'batch-1', '2026-11-13T09:00:00+07:00');
        self::assertSame(1, $result['batch']['summary']['new_count']);
        self::assertSame(0, $result['batch']['summary']['updated_count']);
        self::assertSame(1000, $result['batch']['summary']['delta']);
        $again = $service->track($result['snapshot'], $result['snapshot'], 'batch-2', '2026-11-13T10:00:00+07:00');
        self::assertCount(0, $again['batch']['changes']);
        self::assertSame($result['snapshot']['entries'][1]['payment_id'], $again['snapshot']['entries'][1]['payment_id']);
    }

    public function test_edits_and_deletions_are_reported_as_changes_not_new_receipts(): void
    {
        $service = new CustomerPaymentSyncService;
        $old = $this->snapshot([$this->receipt(), $this->receipt(['row' => 11, 'name' => 'Anh Hải', 'amount' => 500])]);
        $result = $service->track($old, $this->snapshot([$this->receipt(['amount' => 1200])]), 'batch-1', '2026-11-13T09:00:00+07:00');
        $summary = $result['batch']['summary'];
        self::assertSame(0, $summary['new_count']);
        self::assertSame(1, $summary['updated_count']);
        self::assertSame(1, $summary['removed_count']);
        self::assertSame(-300, $summary['delta']);
        self::assertSame(1000, $result['batch']['changes'][0]['before']['amount']);
        self::assertSame(1200, $result['batch']['changes'][0]['after']['amount']);
    }

    public function test_bank_sources_remain_distinct_and_resolved_payer_is_a_correction(): void
    {
        $service = new CustomerPaymentSyncService;
        $old = $this->snapshot([], [['type' => 'payment', 'date' => '13/11/2026', 'row' => 10, 'amount' => 1000, 'note' => 'Thu tiền', 'source_sheet' => 'Dongtienhoatdong']]);
        $next = $this->snapshot([$this->receipt(), $this->receipt(['source_sheet' => 'TKCNMBBDUE'])]);
        $result = $service->track($old, $next, 'batch-1', '2026-11-13T09:00:00+07:00');
        self::assertSame(1, $result['batch']['summary']['updated_count']);
        self::assertSame(1, $result['batch']['summary']['new_count']);
        self::assertSame(1000, $result['batch']['summary']['delta']);
        self::assertTrue($result['batch']['changes'][0]['before']['unidentified']);
        self::assertFalse($result['batch']['changes'][0]['after']['unidentified']);
    }

    public function test_an_inserted_row_does_not_turn_the_shifted_existing_receipt_into_an_edit(): void
    {
        $service = new CustomerPaymentSyncService;
        $old = $this->snapshot([$this->receipt()]);
        $next = $this->snapshot([$this->receipt(['row' => 10, 'amount' => 200, 'name' => 'Khách mới']), $this->receipt(['row' => 11])]);
        $result = $service->track($old, $next, 'batch-1', '2026-11-13T09:00:00+07:00');
        self::assertSame(1, $result['batch']['summary']['new_count']);
        self::assertSame(0, $result['batch']['summary']['updated_count']);
        self::assertSame('Khách mới',$result['batch']['changes'][0]['after']['name']);
    }
}
