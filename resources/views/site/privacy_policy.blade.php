@extends('layouts.site')

@section('title', 'Chính sách quyền riêng tư')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width: 920px;">
        <h1 class="mb-3">CHÍNH SÁCH QUYỀN RIÊNG TƯ</h1>
        <p class="text-muted">Ngày có hiệu lực: 15 tháng 6 năm 2026</p>

        <p>
            Hoàng Long TNT tôn trọng và cam kết bảo vệ quyền riêng tư của khách hàng khi truy cập
            website, liên hệ tư vấn, đăng ký tài khoản hoặc đặt hàng. Chính sách này giải thích
            thông tin chúng tôi thu thập, mục đích sử dụng và quyền của bạn đối với dữ liệu cá nhân.
        </p>

        <h2 class="mt-5 mb-3">1. Thông tin chúng tôi thu thập</h2>
        <p>Tùy theo cách bạn sử dụng website, chúng tôi có thể thu thập:</p>
        <ul>
            <li>Họ tên, số điện thoại, email, địa chỉ giao hàng và thông tin tài khoản.</li>
            <li>Thông tin đơn hàng, sản phẩm quan tâm, nội dung trao đổi và yêu cầu hỗ trợ.</li>
            <li>Thông tin thanh toán cần thiết để xác nhận giao dịch. Chúng tôi không lưu trữ đầy đủ thông tin thẻ thanh toán.</li>
            <li>Dữ liệu kỹ thuật như địa chỉ IP, loại trình duyệt, thiết bị, thời gian truy cập và các trang đã xem.</li>
            <li>Cookie và dữ liệu tương tự nhằm duy trì phiên đăng nhập, giỏ hàng và cải thiện trải nghiệm website.</li>
        </ul>

        <h2 class="mt-5 mb-3">2. Mục đích sử dụng thông tin</h2>
        <p>Thông tin được sử dụng để:</p>
        <ul>
            <li>Xử lý đơn hàng, giao hàng, thanh toán, đổi trả và hỗ trợ sau bán hàng.</li>
            <li>Liên hệ xác nhận thông tin, tư vấn sản phẩm và phản hồi yêu cầu của khách hàng.</li>
            <li>Quản lý tài khoản, giỏ hàng và lịch sử giao dịch.</li>
            <li>Cải thiện nội dung, hiệu năng, tính bảo mật và trải nghiệm sử dụng website.</li>
            <li>Ngăn chặn gian lận, hành vi trái phép và tuân thủ yêu cầu của pháp luật.</li>
            <li>Gửi thông tin khuyến mại khi bạn đã đồng ý nhận thông tin và cho phép bạn hủy đăng ký bất kỳ lúc nào.</li>
        </ul>

        <h2 class="mt-5 mb-3">3. Chia sẻ thông tin</h2>
        <p>
            Chúng tôi không bán hoặc cho thuê dữ liệu cá nhân. Thông tin chỉ có thể được chia sẻ
            với đơn vị vận chuyển, đối tác thanh toán, nhà cung cấp hạ tầng hoặc cơ quan có thẩm
            quyền khi cần thiết để cung cấp dịch vụ, thực hiện giao dịch hoặc tuân thủ pháp luật.
            Các bên nhận dữ liệu phải bảo mật và chỉ sử dụng thông tin đúng mục đích được giao.
        </p>

        <h2 class="mt-5 mb-3">4. Cookie và công nghệ theo dõi</h2>
        <p>
            Website sử dụng cookie cần thiết để duy trì phiên làm việc, ghi nhớ giỏ hàng và hỗ trợ
            các chức năng cốt lõi. Bạn có thể điều chỉnh trình duyệt để từ chối cookie, tuy nhiên
            một số tính năng của website có thể không hoạt động đầy đủ.
        </p>

        <h2 class="mt-5 mb-3">5. Bảo mật và thời gian lưu trữ</h2>
        <p>
            Chúng tôi áp dụng các biện pháp kỹ thuật và quản lý phù hợp để hạn chế truy cập, mất
            mát hoặc sử dụng dữ liệu trái phép. Dữ liệu được lưu trong thời gian cần thiết để xử lý
            giao dịch, hỗ trợ khách hàng, giải quyết tranh chấp và đáp ứng nghĩa vụ pháp lý.
        </p>

        <h2 class="mt-5 mb-3">6. Quyền của bạn</h2>
        <p>Bạn có thể liên hệ với chúng tôi để:</p>
        <ul>
            <li>Yêu cầu xem, cập nhật hoặc chỉnh sửa thông tin cá nhân.</li>
            <li>Yêu cầu xóa hoặc hạn chế xử lý dữ liệu khi pháp luật cho phép.</li>
            <li>Rút lại sự đồng ý nhận thông tin tiếp thị.</li>
            <li>Đặt câu hỏi hoặc khiếu nại về việc xử lý dữ liệu cá nhân.</li>
        </ul>

        <h2 class="mt-5 mb-3">7. Thay đổi chính sách</h2>
        <p>
            Chính sách này có thể được cập nhật để phù hợp với hoạt động của website và quy định
            pháp luật. Phiên bản mới nhất luôn được công bố tại trang này cùng ngày có hiệu lực.
        </p>

        <h2 class="mt-5 mb-3">8. Liên hệ</h2>
        <p>Nếu có câu hỏi về Chính sách quyền riêng tư, vui lòng liên hệ:</p>
        <ul>
            <li>Email: <a href="mailto:thucphamhoanglongtnt@gmail.com">thucphamhoanglongtnt@gmail.com</a></li>
            <li>Điện thoại: <a href="tel:0931453666">093 145 36 66</a></li>
            <li>Địa chỉ: 177C Chiến Lược, Bình Trị Đông, TP. Hồ Chí Minh, Việt Nam</li>
        </ul>
    </div>
</div>
@endsection
