(() => {
    const regions = [...document.querySelectorAll('[data-page-data][aria-busy="true"]')];
    const fallback = document.querySelector('[data-page-data-fallback]');
    if (!regions.length || !fallback) return;

    const load = async () => {
        try {
            const response = await fetch(location.href, {
                headers: { Accept: 'text/html', 'X-SSITE-Page-Data': '1' },
                cache: 'no-store',
            });
            if (response.redirected) {
                location.replace(response.url);
                return;
            }
            if (!response.ok) throw new Error('Page data request failed');

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const loaded = new Map([...page.querySelectorAll('[data-page-data]')]
                .map(region => [region.dataset.pageData, region]));
            if (regions.some(region => {
                const content = loaded.get(region.dataset.pageData);
                return !content || content.getAttribute('aria-busy') === 'true';
            })) {
                throw new Error('Page data response was incomplete');
            }

            regions.forEach(region => {
                const content = loaded.get(region.dataset.pageData);
                region.replaceChildren(...content.childNodes);
                region.removeAttribute('aria-busy');
            });
            document.dispatchEvent(new CustomEvent('page-data-loaded', { detail: { regions } }));
        } catch {
            regions.forEach(region => {
                region.replaceChildren();
                region.removeAttribute('aria-busy');
            });
            fallback.hidden = false;
        }
    };

    load();
})();
