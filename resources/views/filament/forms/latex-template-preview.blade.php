<div
    wire:ignore
    x-data="{
        template: $wire.$entangle('data.template'),
        renderPreview() {
            let el = this.$refs.templatePreview;
            if (!el || typeof katex === 'undefined') return;
            let tpl = this.template || '';
            // Replace %placeholders% with placeholder names for preview
            let preview = tpl.replace(/%([^%]+)%/g, '$1');
            try {
                katex.render(preview, el, { throwOnError: false, displayMode: true });
            } catch(e) {
                el.textContent = preview;
            }
        }
    }"
    x-init="$nextTick(() => renderPreview())"
    x-effect="template; $nextTick(() => renderPreview())"
>
    <div x-show="template && template.length > 0">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">معاينة القالب:</div>
        <div
            x-ref="templatePreview"
            class="math-content p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-center min-h-[40px] flex items-center justify-center"
            dir="ltr"
        ></div>
    </div>
</div>
