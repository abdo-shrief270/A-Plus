<div
    wire:ignore
    x-data="{
        content: $wire.$entangle('{{ $getStatePath() }}'),
        renderMath() {
            let el = this.$refs.preview;
            if (!el || typeof renderMathInElement === 'undefined') return;
            el.innerHTML = this.content || '';
            try {
                renderMathInElement(el, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true },
                        { left: '$', right: '$', display: false },
                        { left: '\\\\(', right: '\\\\)', display: false },
                        { left: '\\\\[', right: '\\\\]', display: true },
                    ],
                    throwOnError: false,
                    ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                });
            } catch(e) {}
        }
    }"
    x-effect="content; $nextTick(() => renderMath())"
    x-init="$nextTick(() => renderMath())"
>
    <div x-show="content && content.length > 0">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">معاينة الرياضيات:</div>
        <div
            x-ref="preview"
            class="math-content p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 prose dark:prose-invert max-w-none text-sm"
            dir="ltr"
            style="unicode-bidi: plaintext;"
        ></div>
    </div>
</div>
