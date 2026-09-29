<div
    x-data="{
        toasts: [],
        init() {
            @if (session('status'))
                this.push({ message: @js(session('status')), type: 'success' });
            @endif
            @if (session('error'))
                this.push({ message: @js(session('error')), type: 'error' });
            @endif
        },
        push(detail) {
            const payload = Array.isArray(detail) ? (detail[0] ?? {}) : (detail ?? {});
            const message = payload.message ?? payload[0] ?? '';

            if (message === '') {
                return;
            }

            const toast = {
                id: Date.now() + Math.random(),
                message,
                type: payload.type ?? 'success',
            };

            this.toasts.push(toast);

            setTimeout(() => this.dismiss(toast.id), 5000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-cloak
    @toast.window="push($event.detail)"
    class="pointer-events-none fixed inset-x-4 top-4 z-[100] flex flex-col items-end gap-3 sm:inset-x-auto sm:right-6 sm:top-6"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-x-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto w-full max-w-sm rounded-xl border px-4 py-3 text-sm shadow-lg shadow-slate-900/10"
            :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-900': toast.type === 'success',
                'border-red-200 bg-red-50 text-red-900': toast.type === 'error',
                'border-amber-200 bg-amber-50 text-amber-900': toast.type === 'warning',
                'border-indigo-200 bg-indigo-50 text-indigo-900': toast.type === 'info',
            }"
            role="alert"
        >
            <div class="flex items-start gap-3">
                <p class="flex-1 font-medium leading-5" x-text="toast.message"></p>
                <button
                    type="button"
                    class="shrink-0 rounded-md p-0.5 text-current/60 transition hover:bg-black/5 hover:text-current"
                    @click="dismiss(toast.id)"
                >
                    <span class="sr-only">{{ __('Dismiss') }}</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>
