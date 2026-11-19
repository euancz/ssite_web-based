import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from '@tiptap/markdown';
import { TableKit } from '@tiptap/extension-table';

export function mountArticleEditor(body, format, tools) {
    const shell = tools.closest('[data-rich-editor]');
    const heading = tools.querySelector('[data-rich-heading]');
    const tableTools = shell.querySelector('[data-table-tools]');
    const dialog = shell.querySelector('[data-rich-link]');
    const urlInput = dialog.querySelector('input');
    urlInput.disabled = true;
    const linkError = dialog.querySelector('[role="alert"]');
    const mount = document.createElement('div');
    mount.className = 'article-visual-editor';
    body.before(mount);
    let linkSelection = null;
    const initialContent = () => format.value === 'markdown' ? body.value : {
        type: 'doc', content: body.value.split(/\r?\n/).map(text => ({
            type: 'paragraph', ...(text ? { content: [{ type: 'text', text }] } : {}),
        })),
    };
    const activeNames = { bold: 'bold', italic: 'italic', strike: 'strike', quote: 'blockquote', code: 'code',
        'code-block': 'codeBlock', link: 'link', list: 'bulletList', 'ordered-list': 'orderedList' };
    const updateButtons = (editor) => {
        tools.querySelectorAll('[data-format-command]').forEach(button => {
            const command = button.dataset.formatCommand;
            if (activeNames[command]) button.setAttribute('aria-pressed', String(editor.isActive(activeNames[command])));
            const can = editor.can();
            const available = { undo: () => can.undo(), redo: () => can.redo(),
                indent: () => can.sinkListItem('listItem'), outdent: () => can.liftListItem('listItem') };
            button.disabled = available[command] ? !available[command]() : false;
        });
        heading.value = editor.isActive('heading', { level: 2 }) ? '2' : editor.isActive('heading', { level: 3 }) ? '3' : 'paragraph';
        tableTools.hidden = !editor.isActive('table');
    };
    const validate = () => {
        const count = (body.value.match(/\S+/gu) || []).length;
        body.setCustomValidity(body.value.length > 20000 ? 'Keep your content within 20,000 characters.'
            : count > 300 ? 'Keep your content within 300 words.' : '');
    };
    const sync = (editor) => {
        body.value = editor.isEmpty ? '' : editor.getMarkdown();
        format.value = 'markdown';
        validate();
        body.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const editor = new Editor({
        element: mount,
        extensions: [StarterKit.configure({
            heading: { levels: [2, 3] }, underline: false,
            link: { openOnClick: false, autolink: true, defaultProtocol: 'https', protocols: ['http', 'https'] },
        }), TableKit.configure({ table: { resizable: false } }), Markdown],
        content: initialContent(),
        ...(format.value === 'markdown' ? { contentType: 'markdown' } : {}),
        editorProps: { attributes: {
            role: 'textbox', 'aria-multiline': 'true', 'aria-required': 'true',
            'aria-labelledby': body.getAttribute('aria-labelledby'),
            'aria-describedby': body.getAttribute('aria-describedby') || '',
            'aria-invalid': body.getAttribute('aria-invalid') || 'false',
            'data-placeholder': 'Start your story here\u2026',
        }, handleKeyDown: (_view, event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault(); openLink(); return true;
            }
            return false;
        } },
        onUpdate: ({ editor }) => sync(editor),
        onSelectionUpdate: ({ editor }) => updateButtons(editor),
        onTransaction: ({ editor }) => updateButtons(editor),
    });
    body.classList.add('article-editor-source');
    body.tabIndex = -1;
    tools.hidden = false;
    tools.dataset.visualReady = 'true';
    heading.hidden = false;
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
        validate();
        error.hidden = body.validity.valid;
        error.textContent = body.validationMessage;
        editor.view.dom.setAttribute('aria-invalid', String(!body.validity.valid));
    });
    body.form.querySelector(`label[for="${body.id}"]`)?.addEventListener('click', (event) => {
        event.preventDefault();
        editor.commands.focus();
    });
    // Preserve the document selection for pointer clicks, while keeping selects and keyboard navigation native.
    const keepSelection = event => { if (event.target.closest('button')) event.preventDefault(); };
    tools.addEventListener('mousedown', keepSelection);
    tableTools.addEventListener('mousedown', keepSelection);
    heading.addEventListener('change', () => {
        const chain = editor.chain().focus();
        if (heading.value === 'paragraph') chain.setParagraph().run();
        else chain.setHeading({ level: Number(heading.value) }).run();
    });
    const openLink = () => {
        linkSelection = { from: editor.state.selection.from, to: editor.state.selection.to };
        urlInput.disabled = false;
        urlInput.value = editor.getAttributes('link').href || '';
        linkError.hidden = true;
        urlInput.removeAttribute('aria-invalid');
        dialog.querySelector('[data-link-remove]').disabled = !editor.isActive('link');
        dialog.showModal();
        urlInput.focus();
    };
    const closeLink = () => { dialog.close(); editor.commands.focus(); };
    dialog.addEventListener('close', () => { urlInput.disabled = true; });
    const applyLink = () => {
        try {
            const url = new URL(urlInput.value.trim());
            if (!['https:', 'http:'].includes(url.protocol)) throw new Error();
            const chain = editor.chain().focus().setTextSelection(linkSelection).extendMarkRange('link');
            if (linkSelection.from === linkSelection.to && !editor.isActive('link')) {
                chain.insertContent({ type: 'text', text: url.href, marks: [{ type: 'link', attrs: { href: url.href } }] }).run();
            } else chain.setLink({ href: url.href }).run();
            closeLink();
        } catch {
            linkError.textContent = 'Enter a complete website URL starting with https:// or http://.';
            linkError.hidden = false;
            urlInput.setAttribute('aria-invalid', 'true');
            urlInput.focus();
        }
    };
    dialog.querySelector('[data-link-save]').addEventListener('click', applyLink);
    dialog.querySelector('[data-link-cancel]').addEventListener('click', closeLink);
    dialog.querySelector('[data-link-remove]').addEventListener('click', () => {
        editor.chain().focus().setTextSelection(linkSelection).extendMarkRange('link').unsetLink().run(); closeLink();
    });
    dialog.addEventListener('cancel', () => queueMicrotask(() => editor.commands.focus()));
    urlInput.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); applyLink(); } });
    const runCommand = event => {
        const button = event.target.closest('[data-format-command]');
        if (!button || button.disabled) return;
        const chain = editor.chain().focus();
        const actions = {
            bold: () => chain.toggleBold().run(), italic: () => chain.toggleItalic().run(), strike: () => chain.toggleStrike().run(),
            quote: () => chain.toggleBlockquote().run(), code: () => chain.toggleCode().run(),
            'code-block': () => chain.toggleCodeBlock().run(), divider: () => chain.setHorizontalRule().run(),
            list: () => chain.toggleBulletList().run(), 'ordered-list': () => chain.toggleOrderedList().run(),
            indent: () => chain.sinkListItem('listItem').run(), outdent: () => chain.liftListItem('listItem').run(),
            undo: () => chain.undo().run(), redo: () => chain.redo().run(), clear: () => chain.unsetAllMarks().clearNodes().run(),
            link: openLink, table: () => chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
            'add-row': () => chain.addRowAfter().run(), 'delete-row': () => chain.deleteRow().run(),
            'add-column': () => chain.addColumnAfter().run(), 'delete-column': () => chain.deleteColumn().run(),
            'delete-table': () => chain.deleteTable().run(),
        };
        actions[button.dataset.formatCommand]?.();
    };
    tools.addEventListener('click', runCommand);
    tableTools.addEventListener('click', runCommand);
    body.form.addEventListener('reset', () => queueMicrotask(() => {
        editor.commands.setContent(initialContent(), { contentType: format.value === 'markdown' ? 'markdown' : 'json', emitUpdate: false });
        validate(); error.hidden = true; updateButtons(editor);
    }));
    validate(); updateButtons(editor);
    return editor;
}
