// Source stays in the normal textarea; only server-rendered, restricted HTML enters the preview.
export function initArticleFormatting() {
    document.querySelectorAll('[data-content-form]').forEach((form) => initRichForm(form));
}

function initRichForm(form) {
    const format = form.querySelector('[data-rich-format], #article-content-format');
    const body = form.querySelector('[data-rich-body], #article-content');
    const tools = form.querySelector('[data-article-tools]');
    if (!format || !body || !tools) return;
    const editor = form.querySelector('[data-article-editor]');
    const modes = [...form.querySelectorAll('[data-editor-mode]')];
    const panel = form.querySelector('[data-article-preview]');
    const plainPreview = form.querySelector('[data-content-preview]');
    const output = panel?.querySelector('[data-article-preview-output]');
    const status = panel?.querySelector('[data-article-preview-status]');
    const previewButton = panel?.querySelector('[data-render-article]');
    let revision = 0;
    let pending = null;

    const setEditorMode = (mode) => {
        if (!editor) return;
        editor.dataset.editorMode = mode;
        body.classList.toggle('is-markdown-mode', mode === 'markdown');
        modes.forEach((tab) => {
            const active = tab.dataset.editorMode === mode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
        });
    };

    const invalidate = () => {
        revision += 1;
        pending?.abort();
        tools.hidden = !tools.dataset.visualReady && !editor && format.value !== 'markdown';
        if (panel) panel.hidden = format.value !== 'markdown';
        if (plainPreview) plainPreview.hidden = Boolean(panel) && format.value === 'markdown';
        if (output) {
            output.hidden = true;
            output.replaceChildren();
        }
        if (status) status.textContent = 'Refresh preview to check your current text.';
        if (previewButton) previewButton.disabled = false;
        panel?.removeAttribute('aria-busy');
    };
    format.addEventListener('change', invalidate);
    body.addEventListener('input', invalidate);
    modes.forEach((tab) => tab.addEventListener('click', () => {
        const mode = tab.dataset.editorMode;
        if (format.value !== 'markdown') {
            format.value = 'markdown';
            format.dispatchEvent(new Event('change', { bubbles: true }));
        }
        setEditorMode(mode);
        body.focus();
    }));
    tools.addEventListener('click', (event) => {
        const button = event.target.closest('[data-format-command]');
        if (!button || tools.dataset.visualReady || format.value !== 'markdown') return;
        let start = body.selectionStart;
        let end = body.selectionEnd;
        const command = button.dataset.formatCommand;
        let selected = body.value.slice(start, end) || 'Text';
        if (['heading', 'list', 'ordered-list', 'quote'].includes(command)) {
            start = start === 0 ? 0 : body.value.lastIndexOf('\n', start - 1) + 1;
            const nextLine = body.value.indexOf('\n', end);
            end = nextLine < 0 ? body.value.length : nextLine;
            const prefix = { heading: '## ', list: '- ', quote: '> ' }[command];
            selected = (body.value.slice(start, end) || 'Text').split('\n').map((line, index) => (command === 'ordered-list' ? `${index + 1}. ` : prefix) + line).join('\n');
        } else {
            const wrappers = { strike: ['~~', '~~'], 'code-block': ['\n\n```\n', '\n```\n\n'], divider: ['\n\n---\n\n', ''], table: ['\n\n| Column 1 | Column 2 |\n| --- | --- |\n| ', ' | Cell |\n\n'], bold: ['**', '**'], italic: ['*', '*'], code: ['`', '`'], link: ['[', '](https://example.com)'] };
            const pair = wrappers[command];
            if (!pair) return;
            selected = pair[0] + selected + pair[1];
        }
        body.focus();
        body.setRangeText(selected, start, end, 'select');
        body.dispatchEvent(new Event('input', { bubbles: true }));
    });
    previewButton?.addEventListener('click', async () => {
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
    setEditorMode('visual');
    invalidate();
    import('./article-rich-editor').then(({ mountArticleEditor }) => {
        mountArticleEditor(body, format, tools);
    }).catch(() => {
        // Keep the original textarea usable if the editor bundle cannot load.
        tools.hidden = format.value !== 'markdown';
        tools.querySelectorAll('[data-format-command]').forEach(button => {
            button.disabled = ['undo', 'redo', 'indent', 'outdent', 'clear'].includes(button.dataset.formatCommand);
        });
    });
}
