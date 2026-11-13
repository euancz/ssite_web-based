(() => {
    const overlay = document.getElementById('login-success');
    if (!overlay) return;
    const started = performance.now();
    const minimumDuration = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 900;
    let finished = false;
    const finish = () => {
        if (finished) return;
        finished = true;
        window.setTimeout(() => {
            overlay.classList.add('is-finished');
            window.setTimeout(() => overlay.remove(), 300);
        }, Math.max(0, minimumDuration - (performance.now() - started)));
    };
    if (document.readyState === 'complete') finish();
    else window.addEventListener('load', finish, { once: true });
    window.setTimeout(finish, 4000);
    window.addEventListener('pageshow', event => {
        if (event.persisted) overlay.remove();
    });
})();
