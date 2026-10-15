<?php
namespace Tests\Feature;

use App\Models\{AdminEvent, User};
use App\Notifications\AdminEventRecorded;
use PHPUnit\Framework\TestCase;

class AdminEventNotificationSizeTest extends TestCase
{
    public function test_small_notifications_keep_their_metadata(): void
    {
        $event = new AdminEvent(['title'=>'Xóa phiếu','message'=>'Lý do xóa','metadata'=>['order_id'=>123]]);
        $event->id=42;
        $data=(new AdminEventRecorded($event))->toArray(new User);
        self::assertSame(['order_id'=>123],$data['metadata']);
        self::assertSame(42,$data['event_id']);
    }
    public function test_large_cleanup_snapshot_is_referenced_without_losing_audit_data(): void
    {
        $snapshot=['orders'=>array_fill(0,100,['note'=>str_repeat('Đơn cũ 🦆 ',100)])];
        $event=new AdminEvent(['event_type'=>'order_transfer','action'=>'historical_data_deleted',
            'title'=>'Admin xóa phiếu cũ và toàn bộ đơn','message'=>str_repeat('Lý do ',1000),'metadata'=>$snapshot]);
        $event->id=42;
        $data=(new AdminEventRecorded($event))->toArray(new User);
        self::assertLessThan(65535,strlen(json_encode($data,JSON_THROW_ON_ERROR)));
        self::assertTrue($data['metadata']['details_stored_in_admin_event']);
        self::assertSame(42,$data['metadata']['admin_event_id']);
        self::assertSame($snapshot,$event->metadata);
    }
}
