/* Progressive enhancement: the page stays visible and usable without JavaScript. */
(() => {
    const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motionPreference.matches || !Element.prototype.animate) return;

    const activeAnimations = new Set();
    const enter = (element, delay = 0) => {
        const animation = element.animate([
            { opacity: 0, transform: 'translateY(22px)' },
            { opacity: 1, transform: 'translateY(0)' },
        ], { duration: 720, delay, easing: 'cubic-bezier(.22, 1, .36, 1)', fill: 'backwards' });
        activeAnimations.add(animation);
        animation.finished.then(() => activeAnimations.delete(animation)).catch(() => {});
    };

    document.querySelectorAll('.hero-copy > *').forEach((element, index) => enter(element, index * 85));
    const visual = document.querySelector('.hero-visual');
    if (visual) enter(visual, 180);

    let observer;
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const siblings = entry.target.parentElement;
                const index = siblings.classList.contains('feature-grid') ? [...siblings.children].indexOf(entry.target) : 0;
                enter(entry.target, index * 100);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -20px 0px' });
        document.querySelectorAll('.section-heading, .feature-card, .access-art, .access-copy, .faq-section > div, .join-section').forEach(element => observer.observe(element));
    }

    motionPreference.addEventListener('change', event => {
        if (!event.matches) return;
        observer?.disconnect();
        activeAnimations.forEach(animation => animation.cancel());
        activeAnimations.clear();
    });
})();
