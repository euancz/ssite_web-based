import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from '@tiptap/markdown';

export function mountArticleEditor(body, format, tools) {
    const mount = document.createElement('div');
    mount.className = 'article-visual-editor';
    body.before(mount);
    const initialContent = () => format.value === 'markdown'
        ? body.value
        : { type: 'doc', content: body.value.split(/\r?\n/).map(text => ({
            type: 'paragraph', ...(text ? { content: [{ type: 'text', text }] } : {}),
        })) };
    const activeNames = { bold: 'bold', italic: 'italic', heading: 'heading', quote: 'blockquote', code: 'code', link: 'link', list: 'bulletList', 'ordered-list': 'orderedList' };
    const updateButtons = (editor) => {
        tools.querySelectorAll('[data-format-command]').forEach(button => {
            button.setAttribute('aria-pressed', String(editor.isActive(activeNames[button.dataset.formatCommand])));
        });
    };
    const editor = new Editor({
        element: mount,
        extensions: [StarterKit.configure({
            heading: { levels: [2, 3] }, underline: false, strike: false,
            link: { openOnClick: false, autolink: false, defaultProtocol: 'https' },
        }), Markdown],
        content: initialContent(),
        ...(format.value === 'markdown' ? { contentType: 'markdown' } : {}),
        editorProps: { attributes: {
            role: 'textbox', 'aria-multiline': 'true', 'aria-required': 'true',
            'aria-labelledby': 'article-content-label',
            'aria-describedby': body.getAttribute('aria-describedby') || '',
            'aria-invalid': body.getAttribute('aria-invalid') || 'false',
        } },
        onUpdate: ({ editor }) => {
            body.value = editor.isEmpty ? '' : editor.getMarkdown();
            format.value = 'markdown';
            const tooLong = body.value.length > 20000;
            const tooManyWords = (body.value.match(/\S+/gu) || []).length > 300;
            body.setCustomValidity(tooLong ? 'Keep your content within 20,000 characters.' : tooManyWords ? 'Keep your content within 300 words.' : '');
            body.dispatchEvent(new Event('input', { bubbles: true }));
        },
        onSelectionUpdate: ({ editor }) => updateButtons(editor),
        onTransaction: ({ editor }) => updateButtons(editor),
    });
    body.classList.add('article-editor-source');
    body.tabIndex = -1;
    tools.hidden = false;
    tools.dataset.visualReady = 'true';
    const error = document.createElement('p');
    error.className = 'profile-field-error';
    error.setAttribute('role', 'alert');
    error.hidden = true;
    mount.after(error);
    body.addEventListener('invalid', (event) => {
        event.preventDefault();
        error.textContent = body.validationMessage;
        error.hidden = false;
        editor.view.dom.setAttribute('aria-invalid', 'true');
        editor.commands.focus();
    });
    body.addEventListener('input', () => {
        error.hidden = body.validity.valid;
        error.textContent = body.validationMessage;
        editor.view.dom.setAttribute('aria-invalid', String(!body.validity.valid));
    });
    document.getElementById('article-content-label').addEventListener('click', (event) => {
        event.preventDefault();
        editor.commands.focus();
    });
    tools.addEventListener('mousedown', event => event.preventDefault());
    tools.addEventListener('click', (event) => {
        const command = event.target.closest('[data-format-command]')?.dataset.formatCommand;
        if (!command) return;
        const chain = editor.chain().focus();
        const actions = {
            bold: () => chain.toggleBold().run(), italic: () => chain.toggleItalic().run(),
            heading: () => chain.toggleHeading({ level: 2 }).run(),
            quote: () => chain.toggleBlockquote().run(), code: () => chain.toggleCode().run(),
            list: () => chain.toggleBulletList().run(), 'ordered-list': () => chain.toggleOrderedList().run(),
            link: () => {
                const href = window.prompt('Link URL (https://example.com). Leave blank to remove the link.', editor.getAttributes('link').href || 'https://');
                if (href === null) return;
                if (!href.trim()) { chain.extendMarkRange('link').unsetLink().run(); return; }
                try {
                    const url = new URL(href.trim());
                    if (!['https:', 'http:'].includes(url.protocol)) throw new Error();
                    chain.extendMarkRange('link').setLink({ href: url.href }).run();
                } catch {
                    error.textContent = 'Enter a complete link starting with https:// or http://.';
                    error.hidden = false;
                }
            },
        };
        actions[command]?.();
    });
    body.form.addEventListener('reset', () => queueMicrotask(() => {
        editor.commands.setContent(initialContent(), { contentType: format.value === 'markdown' ? 'markdown' : 'json', emitUpdate: false });
        body.setCustomValidity('');
        error.hidden = true;
    }));
    updateButtons(editor);
    return editor;
}
