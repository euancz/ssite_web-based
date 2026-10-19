import { animate, inView } from 'motion';

export function initMotion(root = typeof document === 'undefined' ? null : document) {
    if (typeof window === 'undefined' || !root || typeof root.querySelectorAll !== 'function') {
        return () => {};
    }

    const revealElements = [...root.querySelectorAll('[data-motion="reveal"]')].slice(0, 12);
    const counterElements = [...root.querySelectorAll('[data-motion="count-up"]')];
    const badgeElements = [...root.querySelectorAll('[data-motion="badge"]')];
    const readingProgressElements = [...root.querySelectorAll('[data-motion="reading-progress"]')];
    const elements = [...revealElements, ...counterElements];

    if (!elements.length && !badgeElements.length && !readingProgressElements.length) {
        return () => {};
    }

    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)') ?? null;
    const observers = new Map();
    const animations = new Map();
    const completed = new WeakSet();
    const counterValues = new Map();

    elements.forEach((element) => {
        if (element.dataset.motion !== 'count-up') return;

        const originalText = element.textContent;
        const valueText = originalText.trim();
        const value = Number(valueText);
        if (!/^(?:0|[1-9]\d*)$/.test(valueText) || !Number.isSafeInteger(value)) {
            console.error('Count-up requires a non-negative safe integer; leaving the rendered value unchanged.', element);
            completed.add(element);
            return;
        }

        counterValues.set(element, { originalText, value });
    });

    const show = (element) => {
        if (element.dataset.motion !== 'reveal') return;

        element.style.opacity = '1';
        element.style.transform = 'none';
    };

    const restoreCounters = () => {
        counterValues.forEach(({ originalText }, element) => {
            element.textContent = originalText;
        });
    };

    const stopMotion = () => {
        observers.forEach((stopObserving) => stopObserving());
        observers.clear();
        animations.forEach((animation) => animation.stop());
        animations.clear();
    };

    const showAll = () => {
        elements.forEach((element) => {
            completed.add(element);
            show(element);
        });
        restoreCounters();
    };

    // A badge pop runs only after its existing unread count changes.
    const popBadge = (event) => {
        const badge = event.currentTarget;
        if (reducedMotion?.matches) return;

        animations.get(badge)?.stop();
        badge.style.transform = '';

        let animation;
        try {
            const duration = Number.parseFloat(getComputedStyle(badge).getPropertyValue('--motion-badge')) || 180;
            animation = animate(badge, { scale: [1, 1.14, 1] }, {
                duration: duration / 1000,
                ease: [0.16, 1, 0.3, 1],
            });
        } catch (error) {
            badge.style.transform = '';
            console.error('Notification badge animation failed; restored its normal state.', error);
            return;
        }

        animations.set(badge, animation);
        const clearAnimation = () => {
            if (animations.get(badge) !== animation) return;
            badge.style.transform = '';
            animations.delete(badge);
        };
        animation.finished.then(clearAnimation, clearAnimation);
    };

    // Only long article bodies get a scroll-linked indicator; no content depends on it.
    const setupReadingProgress = (indicator) => {
        const content = root.querySelector('[data-reading-content]');
        if (!content) return () => {};

        let frame = 0;
        const update = () => {
            if (frame) return;
            frame = window.requestAnimationFrame(() => {
                frame = 0;
                const bounds = content.getBoundingClientRect();
                if (bounds.height <= window.innerHeight * 1.35) {
                    indicator.hidden = true;
                    return;
                }

                indicator.hidden = false;
                const contentTop = window.scrollY + bounds.top;
                const scrollDistance = Math.max(bounds.height - window.innerHeight, 1);
                const progress = Math.max(0, Math.min(1, (window.scrollY - contentTop) / scrollDistance));
                indicator.firstElementChild.style.transform = `scaleX(${progress})`;
            });
        };

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update, { passive: true });
        update();

        return () => {
            window.removeEventListener('scroll', update);
            window.removeEventListener('resize', update);
            if (frame) window.cancelAnimationFrame(frame);
        };
    };

    const readingProgressCleanup = readingProgressElements.map(setupReadingProgress);

    badgeElements.forEach((badge) => badge.addEventListener('ssite:notification-count-change', popBadge));

    const observeBelowFold = () => {
        if (reducedMotion?.matches || typeof IntersectionObserver === 'undefined') {
            showAll();
            return;
        }

        // Reveal only the first twelve feed cards; bounded delay keeps long lists responsive.
        revealElements.forEach((element, revealIndex) => {
            if (completed.has(element)) return;

            const bounds = element.getBoundingClientRect();
            if (bounds.top < window.innerHeight && bounds.bottom > 0) {
                completed.add(element);
                show(element);
                return;
            }

            element.style.opacity = '0';
            element.style.transform = 'translateY(12px)';
            let stopObserving = () => {};
            try {
                stopObserving = inView(element, (target) => {
                    if (completed.has(target)) return;
                    completed.add(target);

                    let animation;
                    try {
                        animation = animate(target, { opacity: 1, y: 0 }, {
                            duration: 0.28,
                            ease: [0.16, 1, 0.3, 1],
                            delay: Math.min(revealIndex * 0.035, 0.35),
                        });
                    } catch (error) {
                        target.style.opacity = '1';
                        target.style.transform = 'none';
                        console.error('Reveal animation failed; restored visible content.', error);
                        return;
                    }
                    animations.set(target, animation);

                    const clearAnimation = () => {
                        if (animations.get(target) === animation) animations.delete(target);
                    };
                    animation.finished.then(clearAnimation, clearAnimation);
                    queueMicrotask(() => {
                        stopObserving();
                        observers.delete(target);
                    });
                }, {
                    amount: 0.15,
                    margin: '0px 0px -20px 0px',
                });
            } catch (error) {
                element.style.opacity = '1';
                element.style.transform = 'none';
                completed.add(element);
                console.error('Reveal setup failed; restored visible content.', error);
                return;
            }

            observers.set(element, stopObserving);
        });

        counterElements.forEach((element) => {
            if (completed.has(element)) return;

            const bounds = element.getBoundingClientRect();
            if (bounds.top < window.innerHeight && bounds.bottom > 0) {
                completed.add(element);
                return;
            }

            let stopObserving = () => {};
            try {
                stopObserving = inView(element, (target) => {
                    if (completed.has(target)) return;
                    completed.add(target);
                    const counter = counterValues.get(target);
                    if (!counter || counter.value === 0) return;

                    let animation;
                    try {
                        animation = animate(0, counter.value, {
                            duration: 0.7,
                            ease: [0.16, 1, 0.3, 1],
                            onUpdate: (value) => {
                                target.textContent = String(Math.min(counter.value, Math.floor(value)));
                            },
                        });
                    } catch (error) {
                        target.textContent = counter.originalText;
                        console.error('Count-up animation failed; restored the rendered value.', error);
                        return;
                    }

                    animations.set(target, animation);
                    const clearAnimation = () => {
                        target.textContent = counter.originalText;
                        if (animations.get(target) === animation) animations.delete(target);
                    };
                    animation.finished.then(clearAnimation, clearAnimation);
                    queueMicrotask(() => {
                        stopObserving();
                        observers.delete(target);
                    });
                }, { amount: 0.15, margin: '0px 0px -20px 0px' });
            } catch (error) {
                element.textContent = counterValues.get(element)?.originalText ?? element.textContent;
                completed.add(element);
                console.error('Count-up setup failed; restored the rendered value.', error);
                return;
            }

            observers.set(element, stopObserving);
        });
    };

    observeBelowFold();

    const updateReducedMotion = () => {
        stopMotion();
        badgeElements.forEach((badge) => { badge.style.transform = ''; });
        if (reducedMotion?.matches) {
            showAll();
            return;
        }
        observeBelowFold();
    };

    reducedMotion?.addEventListener('change', updateReducedMotion);
    const restoreOnPageHide = () => {
        stopMotion();
        badgeElements.forEach((badge) => { badge.style.transform = ''; });
        showAll();
    };
    window.addEventListener('pagehide', restoreOnPageHide, { once: true });

    return () => {
        reducedMotion?.removeEventListener('change', updateReducedMotion);
        window.removeEventListener('pagehide', restoreOnPageHide);
        badgeElements.forEach((badge) => badge.removeEventListener('ssite:notification-count-change', popBadge));
        readingProgressCleanup.forEach((cleanup) => cleanup());
        stopMotion();
        badgeElements.forEach((badge) => { badge.style.transform = ''; });
        showAll();
    };
}

export default { initMotion };
