/* Progressive enhancement: the page stays visible and usable without JavaScript. */
(() => {
    const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motionPreference.matches || !Element.prototype.animate) return;

    const activeAnimations = new Set();
    const enter = (element, delay = 0) => {
        const animation = element.animate([
            { opacity: 0, transform: 'translateY(8px)' },
            { opacity: 1, transform: 'translateY(0)' },
        ], { duration: 400, delay, easing: 'ease-out', fill: 'backwards' });
        activeAnimations.add(animation);
        animation.finished.then(() => activeAnimations.delete(animation)).catch(() => {});
    };

    const copy = document.querySelector('.hero-copy');
    if (copy) enter(copy);
    const visual = document.querySelector('.hero-visual');
    if (visual) enter(visual, 80);

    let observer;
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                enter(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -20px 0px' });
        document.querySelectorAll('.app-art, .section-heading').forEach(element => observer.observe(element));
    }

    motionPreference.addEventListener('change', event => {
        if (!event.matches) return;
        observer?.disconnect();
        activeAnimations.forEach(animation => animation.cancel());
        activeAnimations.clear();
    });
})();
