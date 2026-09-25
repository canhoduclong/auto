{{-- Tab content mẫu, bạn có thể thay đổi nội dung theo từng tab --}}
<div>
    <h2 class="text-lg font-semibold mb-4">Tổng khách hàng</h2>
    <div class="mb-4">Tổng số khách hàng: <span class="font-bold">{{ $totalCustomers ?? 0 }}</span></div>
    <div class="mb-4">Tổng số nhóm khách hàng: <span class="font-bold">{{ $totalGroups ?? 0 }}</span></div>
    <div class="mb-4">Biểu đồ tăng trưởng khách hàng (demo):</div>
    <canvas id="customerGrowthChart" height="120"></canvas>
    <script>
        // Demo Chart.js, cần include Chart.js ở layout
        if (window.Chart) {
            new Chart(document.getElementById('customerGrowthChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
                    datasets: [{
                        label: 'Khách hàng',
                        data: [10, 20, 30, 40, 60, 80],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,0.1)',
                        fill: true,
                    }]
                },
                options: {responsive: true, plugins: {legend: {display: false}}}
            });
        }
    </script>
</div>
