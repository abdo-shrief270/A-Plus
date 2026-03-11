/**
 * KaTeX Auto-Render Initialization
 * Renders LaTeX math expressions ($...$, $$...$$) across all Filament pages.
 */
function renderAllMath() {
    if (typeof renderMathInElement === 'undefined') return;

    document.querySelectorAll('.math-content, .prose, .mathjax-content, .fi-in-text-entry').forEach(function (el) {
        if (el.dataset.mathRendered === 'true') return;
        try {
            renderMathInElement(el, {
                delimiters: [
                    { left: '$$', right: '$$', display: true },
                    { left: '$', right: '$', display: false },
                    { left: '\\(', right: '\\)', display: false },
                    { left: '\\[', right: '\\]', display: true },
                ],
                throwOnError: false,
                ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
            });
            el.dataset.mathRendered = 'true';
        } catch (e) {
            // silently ignore rendering errors
        }
    });
}

// Reset rendered flags before re-rendering (for Livewire updates)
function resetMathFlags() {
    document.querySelectorAll('[data-math-rendered]').forEach(function (el) {
        el.removeAttribute('data-math-rendered');
    });
}

// Initial render when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(renderAllMath, 300);
});

// Re-render after Livewire SPA navigation
document.addEventListener('livewire:navigated', function () {
    resetMathFlags();
    setTimeout(renderAllMath, 300);
});

// Re-render after Livewire component updates (modals, tabs, etc.)
document.addEventListener('livewire:morph.updated', function () {
    resetMathFlags();
    setTimeout(renderAllMath, 300);
});

// Also listen for generic Livewire updates
document.addEventListener('livewire:update', function () {
    resetMathFlags();
    setTimeout(renderAllMath, 300);
});

// MutationObserver to catch dynamically inserted content (modals, slide-overs)
var mathObserver = new MutationObserver(function (mutations) {
    var shouldRender = false;
    for (var i = 0; i < mutations.length; i++) {
        if (mutations[i].addedNodes.length > 0) {
            shouldRender = true;
            break;
        }
    }
    if (shouldRender) {
        setTimeout(renderAllMath, 200);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    mathObserver.observe(document.body, { childList: true, subtree: true });
});