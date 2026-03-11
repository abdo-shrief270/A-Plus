<div
    wire:ignore
    x-data="{
        content: $wire.$entangle('{{ $getStatePath() }}'),
        renderMath() {
            let el = this.$refs.mdPreview;
            if (!el || typeof renderMathInElement === 'undefined') return;
            let raw = this.content || '';
            if (!raw || raw.length === 0) return;
            // Convert basic markdown to HTML for display
            let html = raw
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/~~(.+?)~~/g, '<del>$1</del>')
                .replace(/\n/g, '<br>');
            el.innerHTML = html;
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
    x-init="$nextTick(() => renderMath())"
    x-effect="content; $nextTick(() => renderMath())"
>
    <div x-show="content && content.length > 0">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">معاينة الرياضيات:</div>
        <div
            x-ref="mdPreview"
            class="math-content p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 prose dark:prose-invert max-w-none text-sm"
            dir="ltr"
            style="unicode-bidi: plaintext;"
        ></div>
    </div>
</div>
