@php
    $raw = strip_tags($getState() ?? '');
    $text = \Illuminate\Support\Str::limit($raw, 100);
@endphp
<div
    class="math-content"
    dir="ltr"
    style="unicode-bidi: plaintext;"
    x-data="{
        waitAndRender() {
            if (typeof renderMathInElement !== 'undefined') {
                this.renderMath();
            } else {
                let interval = setInterval(() => {
                    if (typeof renderMathInElement !== 'undefined') {
                        clearInterval(interval);
                        this.renderMath();
                    }
                }, 200);
                setTimeout(() => clearInterval(interval), 5000);
            }
        },
        renderMath() {
            try {
                renderMathInElement(this.$el, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true },
                        { left: '$', right: '$', display: false },
                        { left: '\\(', right: '\\)', display: false },
                        { left: '\\[', right: '\\]', display: true },
                    ],
                    throwOnError: false,
                });
            } catch(e) {}
        }
    }"
    x-init="waitAndRender()"
>{!! e($text) !!}</div>
