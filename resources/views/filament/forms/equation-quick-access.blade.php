@php
    $formats = \App\Models\LatexFormat::active()->ordered()->get();
    $grouped = $formats->groupBy('category');
@endphp

<div
    x-data="{
        openCat: null,
        copied: null,
        formats: {{ Js::from($formats->mapWithKeys(fn ($f) => [
            $f->key => [
                'label' => $f->name,
                'icon' => $f->icon,
                'inputs' => $f->inputs ?? [],
                'template' => $f->template,
                'category' => $f->category,
            ]
        ])) }},
        categories: {{ Js::from($grouped->map(fn ($items) => $items->pluck('key')->values())) }},

        toggleCat(cat) {
            this.openCat = this.openCat === cat ? null : cat;
        },

        generateCode(key) {
            let f = this.formats[key];
            if (!f) return '';
            let result = f.template;
            // Replace placeholders with defaults from inputs
            (f.inputs || []).forEach(inp => {
                result = result.split('%' + inp.k + '%').join(inp.p || inp.k);
            });
            return '$' + result + '$';
        },

        copyCode(key) {
            let code = this.generateCode(key);
            navigator.clipboard.writeText(code).then(() => {
                this.copied = key;
                setTimeout(() => this.copied = null, 1500);
            });
        },

        renderIcon(key) {
            this.$nextTick(() => {
                let el = document.getElementById('qa-icon-' + key);
                if (!el || typeof katex === 'undefined' || el.dataset.rendered === '1') return;
                let raw = this.formats[key].icon.replace(/^\\\(/, '').replace(/\\\)$/, '');
                try { katex.render(raw, el, { throwOnError: false, displayMode: false }); el.dataset.rendered = '1'; } catch(e) {}
            });
        },
    }"
    class="mb-2"
>
    {{-- شريط الوصول السريع --}}
    <div class="flex items-center gap-1 flex-wrap">
        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 me-1 whitespace-nowrap">إدراج سريع:</span>

        <template x-for="(keys, catName) in categories" :key="catName">
            <div class="relative">
                <button
                    type="button"
                    @click="toggleCat(catName); $nextTick(() => { if(openCat === catName) categories[catName].forEach(k => renderIcon(k)) })"
                    :class="openCat === catName
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700'
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="px-2 py-1 text-[10px] font-medium rounded-md border transition-all whitespace-nowrap"
                    x-text="catName"
                ></button>

                {{-- القائمة المنسدلة --}}
                <div
                    x-show="openCat === catName"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click.outside="openCat = null"
                    class="absolute z-50 top-full mt-1 start-0 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-2 min-w-[200px] max-w-[320px]"
                    dir="rtl"
                    style="display:none;"
                >
                    <div class="grid grid-cols-2 gap-1">
                        <template x-for="key in categories[catName] || []" :key="key">
                            <button
                                type="button"
                                @click="copyCode(key)"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-md text-start transition-all"
                                :class="copied === key
                                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                    : 'hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300'"
                            >
                                <span
                                    class="text-xs min-w-[24px] text-center flex-shrink-0"
                                    :id="'qa-icon-' + key"
                                    x-html="formats[key]?.icon || ''"
                                    dir="ltr"
                                ></span>
                                <span class="text-[10px] font-medium truncate" x-text="copied === key ? 'تم النسخ!' : formats[key]?.label || ''"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
