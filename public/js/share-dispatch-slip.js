(function () {
    'use strict';

    function downloadFile(file) {
        var link = document.createElement('a');
        link.href = URL.createObjectURL(file);
        link.download = file.name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
    }

    async function shareDispatchSlipImage(button) {
        var target = document.querySelector(button.dataset.shareTarget || '#dispatchSlipShareContent');
        if (!target || typeof window.html2canvas !== 'function') {
            window.alert('Không thể tạo ảnh phiếu. Vui lòng tải lại trang và thử lại.');
            return;
        }

        var originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span>Đang tạo ảnh...</span>';

        try {
            var canvas = await window.html2canvas(target, {
                backgroundColor: '#ffffff',
                scale: Math.min(2, Math.max(1, window.devicePixelRatio || 1)),
                useCORS: true,
                logging: false,
                windowWidth: Math.max(target.scrollWidth, 794),
                ignoreElements: function (element) { return element.matches('[data-share-exclude]'); },
            });
            var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/png', 0.96); });
            if (!blob) throw new Error('Không tạo được dữ liệu ảnh.');

            var filename = (button.dataset.shareFilename || 'phieu-kho') + '.png';
            var file = new File([blob], filename, {type: 'image/png'});
            var shareData = {
                files: [file],
                title: button.dataset.shareTitle || 'Phiếu kho',
                text: button.dataset.shareText || 'Phiếu kho Hoàng Long TNT',
            };

            if (navigator.share && (!navigator.canShare || navigator.canShare(shareData))) {
                await navigator.share(shareData);
                return;
            }

            downloadFile(file);
            window.alert('Trình duyệt chưa hỗ trợ chia sẻ ảnh trực tiếp. Ảnh đã được tải xuống; hãy chọn ảnh này khi gửi qua Zalo.');
        } catch (error) {
            if (error && error.name === 'AbortError') return;
            window.alert(error && error.message ? error.message : 'Không thể chia sẻ ảnh phiếu.');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-share-dispatch-slip]');
        if (!button) return;
        event.preventDefault();
        shareDispatchSlipImage(button);
    });
})();
