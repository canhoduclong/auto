@props([
    'tabs', // mảng tab: key, label, icon, value, permission
    'activeTab', // key tab đang chọn
])
<div>
    <div class="flex overflow-x-auto gap-4 pb-2">
        @foreach ($tabs as $tab)
            @can($tab['permission'] ?? null)
            <div class="card cursor-pointer px-6 py-4 rounded-lg shadow transition-all duration-200 select-none
                        {{ $activeTab === $tab['key'] ? 'bg-blue-600 text-white scale-105' : 'bg-white hover:bg-blue-50' }}"
                 data-tab="{{ $tab['key'] }}">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{!! $tab['icon'] !!}</span>
                    <div>
                        <div class="text-xs opacity-80">{{ $tab['label'] }}</div>
                        <div class="text-xl font-bold">{{ $tab['value'] }}</div>
                    </div>
                </div>
            </div>
            @endcan
        @endforeach
    </div>
    <div id="tab-content" class="mt-6 min-h-[200px]">
        <div class="flex justify-center py-10" id="tab-loading" style="display:none">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </div>
        <div id="tab-inner-content">
            {{-- Nội dung tab sẽ được load động ở đây --}}
        </div>
    </div>
</div>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.card[data-tab]').forEach(card => {
            card.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');
                document.getElementById('tab-loading').style.display = 'flex';
                document.getElementById('tab-inner-content').innerHTML = '';
                fetch(`/dashboard/tab-content/${tab}`)
                  .then(res => res.text())
                  .then(html => {
                    document.getElementById('tab-loading').style.display = 'none';
                    document.getElementById('tab-inner-content').innerHTML = html;
                  });
                document.querySelectorAll('.card[data-tab]').forEach(c => c.classList.remove('bg-blue-600', 'text-white', 'scale-105'));
                this.classList.add('bg-blue-600', 'text-white', 'scale-105');
            });
        });
    });
</script>
