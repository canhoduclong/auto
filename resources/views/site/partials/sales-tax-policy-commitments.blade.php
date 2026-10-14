@php
    $commitments = [
        'read' => 'Tôi đã được cung cấp và có cơ hội đọc đầy đủ nội dung quy định sử dụng hệ thống.',
        'understand' => 'Tôi hiểu quyền hạn, trách nhiệm và các nguyên tắc bảo mật, xử lý dữ liệu, giao dịch và tuân thủ nghiệp vụ liên quan đến công việc của mình.',
        'integrity' => 'Tôi cam kết sử dụng hệ thống trung thực, đúng phân quyền, không cố ý làm sai lệch dữ liệu hoặc lợi dụng thông tin doanh nghiệp.',
        'audit' => 'Tôi hiểu rằng các thao tác nghiệp vụ của mình có thể được ghi nhận, kiểm tra và đối soát theo các mục đích đã thông báo.',
        'feedback' => 'Tôi hiểu mình có quyền phản ánh, yêu cầu giải thích và đề nghị điều chỉnh khi phát hiện sai lệch.',
        'comply' => 'Tôi đồng ý tuân thủ các quy định hợp lệ nêu trên trong quá trình sử dụng hệ thống.',
    ];
@endphp
<div class="{{ !empty($prompt) ? 'tax-policy-commitments' : 'policy-commitments' }}">
@foreach($commitments as $key => $label)
    @continue(!empty($excludeFinal) && $key === 'comply')
    <label class="{{ !empty($prompt) ? 'tax-policy-consent' : 'policy-consent' }}">
        <input type="checkbox" name="commitments[{{ $key }}]" value="1" required>
        <span class="{{ $key === 'comply' ? 'fw-bold' : '' }}">{{ $label }}</span>
    </label>
@endforeach
</div>
