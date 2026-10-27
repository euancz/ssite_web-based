// Source stays in the normal textarea; only server-rendered, restricted HTML enters the preview.
export function initArticleFormatting() {
    const format = document.getElementById('article-content-format');
    const body = document.getElementById('article-content');
    if (!format || !body) return;
    const form = body.form;
    const tools = form.querySelector('[data-article-tools]');
    const panel = form.querySelector('[data-article-preview]');
    const plainPreview = form.querySelector('[data-content-preview]');
    const output = panel.querySelector('[data-article-preview-output]');
    const status = panel.querySelector('[data-article-preview-status]');
    const previewButton = panel.querySelector('[data-render-article]');
    let revision = 0;
    let pending = null;

    const invalidate = () => {
        revision += 1;
        pending?.abort();
        tools.hidden = format.value !== 'markdown';
        panel.hidden = format.value !== 'markdown';
        if (plainPreview) plainPreview.hidden = format.value === 'markdown';
        output.hidden = true;
        output.replaceChildren();
        status.textContent = 'Refresh preview to check your current text.';
        previewButton.disabled = false;
        panel.removeAttribute('aria-busy');
    };
    format.addEventListener('change', invalidate);
    body.addEventListener('input', invalidate);
    tools.addEventListener('click', (event) => {
        const button = event.target.closest('[data-format-command]');
        if (!button || format.value !== 'markdown') return;
        let start = body.selectionStart;
        let end = body.selectionEnd;
        const command = button.dataset.formatCommand;
        let selected = body.value.slice(start, end) || 'Text';
        if (['heading', 'list', 'quote'].includes(command)) {
            start = body.value.lastIndexOf('\n', start - 1) + 1;
            const nextLine = body.value.indexOf('\n', end);
            end = nextLine < 0 ? body.value.length : nextLine;
            const prefix = { heading: '## ', list: '- ', quote: '> ' }[command];
            selected = (body.value.slice(start, end) || 'Text').split('\n').map(line => prefix + line).join('\n');
        } else {
            const wrappers = { bold: ['**', '**'], italic: ['*', '*'], link: ['[', '](https://example.com)'] };
            const pair = wrappers[command];
            if (!pair) return;
            selected = pair[0] + selected + pair[1];
        }
        body.focus();
        body.setRangeText(selected, start, end, 'select');
        body.dispatchEvent(new Event('input', { bubbles: true }));
    });
    previewButton.addEventListener('click', async () => {
        pending?.abort();
        const controller = new AbortController();
        pending = controller;
        const requestedRevision = revision;
        previewButton.disabled = true;
        panel.setAttribute('aria-busy', 'true');
        status.textContent = 'Rendering preview…';
        try {
            const response = await fetch(panel.dataset.previewUrl, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.elements.namedItem('_token').value },
                body: JSON.stringify({ content: body.value, content_format: format.value }),
            });
            if (!response.ok) throw new Error(response.status === 419 ? 'Your session expired. Reload before previewing.' : 'Preview unavailable. Check the 20,000-character limit or try again.');
            const result = await response.json();
            if (requestedRevision !== revision || controller.signal.aborted) return;
            if (typeof result.html !== 'string') throw new Error('Preview unavailable. Try again.');
            output.innerHTML = result.html;
            output.hidden = false;
            status.textContent = 'Preview updated. Your article has not been saved.';
        } catch (error) {
            if (error.name !== 'AbortError' && requestedRevision === revision) {
                output.hidden = true;
                status.textContent = error.message || 'Preview unavailable. Try again.';
            }
        } finally {
            if (requestedRevision === revision) {
                previewButton.disabled = false;
                panel.removeAttribute('aria-busy');
            }
        }
    });
    window.addEventListener('pagehide', () => pending?.abort());
    window.addEventListener('pageshow', invalidate);
    invalidate();
}
