// Create forms keep their original controls so Back preserves text, files and editor state.
export function initPostComposer() {
    document.querySelectorAll('[data-post-composer]').forEach((page) => {
        const form = page.querySelector('[data-content-form]');
        const actions = form?.querySelector(':scope > .profile-form-actions');
        if (!form || !actions) return;
        const saveDraft = form.querySelector('[data-save-draft]');
        const formattedPreview = form.querySelector('[data-article-preview]');
        const previewImage = formattedPreview?.querySelector('[data-article-preview-image]');
        const contentFormat = form.querySelector('[data-rich-format], #article-content-format');
        const details = document.createElement('section');
        details.dataset.composerDetails = '';
        const review = document.createElement('section');
        review.dataset.composerReview = '';
        const heading = document.createElement('h2');
        heading.tabIndex = -1;
        const hint = document.createElement('p');
        hint.className = 'profile-field-help';
        hint.setAttribute('role', 'status');
        const summary = document.createElement('dl');
        summary.className = 'workspace-composer-summary';
        review.append(summary);
        [...form.children].forEach((child) => {
            if (child.matches('input[type="hidden"], .workspace-draft-controls, [data-unsaved-status], [data-save-draft]')) return;
            if (child.matches('[data-content-preview], [data-article-preview], .profile-form-actions')) review.append(child);
            else details.append(child);
        });
        form.prepend(heading, hint, details, review);
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'dashboard-button';
        next.textContent = 'Continue to review';
        const navigation = document.createElement('div');
        navigation.className = 'profile-form-actions';
        navigation.append(next);
        actions.querySelectorAll('a').forEach((link) => {
            if (link.textContent.trim() === 'Back to submissions') link.remove();
            else navigation.prepend(link);
        });
        if (saveDraft) navigation.prepend(saveDraft);
        details.append(navigation);
        const back = document.createElement('button');
        back.type = 'button';
        back.className = 'dashboard-button dashboard-button-secondary';
        back.textContent = 'Back to details';
        actions.prepend(back);
        const submit = actions.querySelector('button[type="submit"]');
        if (submit) submit.textContent = page.dataset.composerSubmit;
        let step = 2;
        const show = (value, focus = true) => {
            step = value;
            details.hidden = step !== 2;
            review.hidden = step !== 3;
            heading.textContent = step === 2 ? 'Add your post details' : 'Review your post';
            hint.textContent = step === 2
                ? 'Step 2 of 3: Add your content and any supporting files.'
                : 'Step 3 of 3: Check the details below. ' + page.dataset.composerHint;
            page.querySelectorAll('[data-composer-step]').forEach((item) => {
                if (Number(item.dataset.composerStep) === step) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
            if (focus) heading.focus();
        };
        const updateSummary = () => {
            // Keep the live preview and its event listeners when rebuilding the summary.
            if (formattedPreview) review.insertBefore(formattedPreview, actions);
            summary.replaceChildren();
            [...form.elements].forEach((control) => {
                if (!control.name || !control.matches('input:not([type="hidden"]), textarea, select') || control.disabled) return;
                if (contentFormat?.value === 'markdown' && previewImage && !previewImage.hidden && control.matches('input[type="file"][data-image-preview]')) return;
                const value = control.type === 'file'
                    ? [...control.files].map((file) => file.name).join(', ')
                    : control.tagName === 'SELECT' ? (control.value ? control.selectedOptions[0]?.textContent : '') : control.value;
                if (!value?.trim()) return;
                const row = document.createElement('div');
                const label = document.createElement('dt');
                label.textContent = (control.labels?.[0]?.textContent || control.name).replace(/\s*\*\s*$/, '').trim();
                const output = document.createElement('dd');
                if (formattedPreview && contentFormat?.value === 'markdown' && control.matches('[data-rich-body], #article-content')) {
                    output.classList.add('workspace-composer-formatted-content');
                    output.append(formattedPreview);
                } else {
                    output.textContent = value;
                }
                row.append(label, output);
                summary.append(row);
            });
        };
        const advance = () => {
            if (!form.reportValidity()) return;
            const body = form.querySelector('textarea[name="content"], textarea[name="description"]');
            if ((body?.value.match(/\S+/gu) || []).length > 300) {
                body.setCustomValidity('Please shorten your content to 300 words or fewer.');
                body.reportValidity();
                body.addEventListener('input', () => body.setCustomValidity(''), { once: true });
                return;
            }
            updateSummary();
            show(3);
            if (contentFormat?.value === 'markdown') formattedPreview?.querySelector('[data-render-article]')?.click();
        };
        next.addEventListener('click', advance);
        back.addEventListener('click', () => show(2));
        form.addEventListener('invalid', () => { if (step === 3) show(2, false); }, true);
        form.addEventListener('submit', (event) => {
            if (event.submitter?.matches('[data-save-draft]')) return;
            if (step === 2) {
                event.preventDefault();
                advance();
            }
        }, true);
        page.classList.add('is-guided-composer');
        show(2, false);
    });
}
