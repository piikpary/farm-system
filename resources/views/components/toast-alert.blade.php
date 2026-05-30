@if (session('success') || session('error'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3500)"
        x-show="show"
        x-transition
        class="toast-alert-wrap"
    >
        @if (session('success'))
            <div class="toast-alert success">
                <div class="toast-icon">✓</div>
                <div class="toast-text">{{ session('success') }}</div>
                <button type="button" class="toast-close" @click="show = false">×</button>
            </div>
        @endif

        @if (session('error'))
            <div class="toast-alert error">
                <div class="toast-icon">!</div>
                <div class="toast-text">{{ session('error') }}</div>
                <button type="button" class="toast-close" @click="show = false">×</button>
            </div>
        @endif
    </div>
@endif