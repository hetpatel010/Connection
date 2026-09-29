<div
    x-data="{
        open: false,
        query: '',
        activeIndex: 0,
        items: @js($items),
        get filtered() {
            const q = this.query.trim().toLowerCase();

            if (q === '') {
                return this.items;
            }

            return this.items.filter((item) => {
                const haystack = [item.label, item.group, ...(item.keywords ?? [])]
                    .join(' ')
                    .toLowerCase();

                return haystack.includes(q);
            });
        },
        openPalette() {
            this.open = true;
            this.query = '';
            this.activeIndex = 0;
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
                this.scrollActiveIntoView();
            });
        },
        closePalette() {
            this.open = false;
            this.query = '';
            this.activeIndex = 0;
        },
        moveActive(delta) {
            if (this.filtered.length === 0) {
                this.activeIndex = 0;

                return;
            }

            this.activeIndex = Math.max(0, Math.min(this.activeIndex + delta, this.filtered.length - 1));
            this.scrollActiveIntoView();
        },
        scrollActiveIntoView() {
            this.$nextTick(() => {
                this.$refs.results?.querySelector('[data-global-search-active=true]')?.scrollIntoView({ block: 'nearest' });
            });
        },
        activateCurrent() {
            this.$refs.results?.querySelector('[data-global-search-active=true]')?.click();
        },
        onKeydown(event) {
            if (! this.open) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.moveActive(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.moveActive(-1);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                this.activateCurrent();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.closePalette();
            }
        },
    }"
    x-on:open-global-search.window="openPalette()"
    x-on:keydown.window="onKeydown($event)"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[60] bg-slate-900/50 backdrop-blur-sm"
        x-on:click="closePalette()"
    ></div>

    <div
        x-show="open"
        x-transition
        class="fixed inset-x-0 top-[12vh] z-[70] mx-auto w-full max-w-xl px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="global-search-title"
        x-on:click.stop
    >
        <div class="overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
            <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
                <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    x-ref="searchInput"
                    x-model="query"
                    type="search"
                    id="global-search-title"
                    class="w-full border-0 bg-transparent p-0 text-base text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                    placeholder="{{ __('Jump to page or action…') }}"
                    autocomplete="off"
                    spellcheck="false"
                    x-on:input="activeIndex = 0"
                >
                <kbd class="kbd hidden sm:inline-flex">Esc</kbd>
            </div>

            <div x-ref="results" class="max-h-[min(24rem,50vh)] overflow-y-auto p-2">
                <template x-if="filtered.length === 0">
                    <p class="px-3 py-8 text-center text-sm text-slate-500">{{ __('No matching pages found.') }}</p>
                </template>

                <template x-for="(item, index) in filtered" :key="item.id">
                    <a
                        :href="item.url"
                        wire:navigate
                        x-on:click="closePalette()"
                        :data-global-search-active="index === activeIndex"
                        :class="index === activeIndex ? 'bg-indigo-50 text-indigo-900 ring-1 ring-indigo-100' : 'text-slate-700 hover:bg-slate-50'"
                        class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm transition"
                    >
                        <span class="font-medium" x-text="item.label"></span>
                        <span
                            class="shrink-0 text-xs"
                            :class="index === activeIndex ? 'text-indigo-500' : 'text-slate-400'"
                            x-text="item.group"
                        ></span>
                    </a>
                </template>
            </div>

            <div class="flex items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/80 px-4 py-2.5 text-xs text-slate-500">
                <span class="inline-flex items-center gap-2">
                    <kbd class="kbd">↑</kbd>
                    <kbd class="kbd">↓</kbd>
                    {{ __('Navigate') }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <kbd class="kbd">↵</kbd>
                    {{ __('Open') }}
                </span>
            </div>
        </div>
    </div>
</div>
