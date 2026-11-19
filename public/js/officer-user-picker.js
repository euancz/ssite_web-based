(() => {
    const field = document.querySelector('[data-officer-user-picker]');
    if (!field) return;
    const select = field.querySelector('select');
    const picker = field.querySelector('.officer-user-picker');
    const input = picker.querySelector('input');
    const list = picker.querySelector('[role="listbox"]');
    const status = field.querySelector('[role="status"]');
    const users = [...select.options].filter(option => option.value).map(option => ({
        id: option.value,
        name: option.dataset.name,
        email: option.dataset.email,
        search: `${option.dataset.name} ${option.dataset.email}`.toLocaleLowerCase(),
    }));
    let visible = [];
    let active = -1;

    const selectedLabel = () => select.value ? select.selectedOptions[0].textContent.trim() : '';
    const close = () => {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        input.value = selectedLabel();
        status.textContent = '';
    };
    const highlight = (index) => {
        active = index;
        [...list.children].forEach((node, i) => node.classList.toggle('is-active', i === active));
        if (active < 0) input.removeAttribute('aria-activedescendant');
        else {
            input.setAttribute('aria-activedescendant', list.children[active].id);
            window.searchPopover.reveal(list, list.children[active]);
        }
    };
    const choose = (user) => {
        select.value = user.id;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    };
    const render = () => {
        const words = input.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
        const matches = users.filter(user => words.every(word => user.search.includes(word)));
        // Limit rendered rows while searching the entire account list.
        visible = [{ id: '', name: 'No linked user', email: '' }, ...matches.slice(0, 50)];
        list.replaceChildren();
        visible.forEach((user, index) => {
            const option = document.createElement('div');
            option.id = `officer-user-option-${index}`;
            option.className = 'officer-user-option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', String(select.value === user.id));
            const name = document.createElement('span');
            name.textContent = user.name;
            option.append(name);
            if (user.email) {
                const email = document.createElement('small');
                email.textContent = user.email;
                option.append(email);
            }
            option.addEventListener('mousedown', event => event.preventDefault());
            option.addEventListener('click', () => { choose(user); input.focus({ preventScroll: true }); close(); });
            list.append(option);
        });
        active = -1;
        input.removeAttribute('aria-activedescendant');
        list.hidden = false;
        list.scrollTop = 0;
        input.setAttribute('aria-expanded', 'true');
        status.textContent = !matches.length ? 'No matching users. Try another name or email.'
            : matches.length > 50 ? `${matches.length} users found. Showing the first 50; type more to narrow the results.`
                : `${matches.length} ${matches.length === 1 ? 'user' : 'users'} found.`;
    };
    const open = () => {
        if (list.hidden) { input.value = ''; render(); }
    };
    select.addEventListener('change', () => {
        if (select.value) document.getElementById('name').value = select.selectedOptions[0].dataset.name || '';
    });
    input.addEventListener('focus', open);
    input.addEventListener('click', open);
    input.addEventListener('input', render);
    input.addEventListener('blur', close);
    input.addEventListener('keydown', event => {
        if (event.isComposing) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            open();
            highlight(event.key === 'ArrowDown' ? (active + 1) % visible.length
                : (active <= 0 ? visible.length : active) - 1);
        } else if (event.key === 'Enter' && !list.hidden) {
            event.preventDefault();
            if (active >= 0) choose(visible[active]);
        } else if (event.key === 'Escape' && !list.hidden) {
            event.preventDefault();
            event.stopPropagation();
            close();
        } else if (event.key === 'Tab') close();
    });
    field.querySelector('label').htmlFor = input.id;
    select.hidden = true;
    picker.hidden = false;
    input.value = selectedLabel();
    select.form?.addEventListener('reset', () => requestAnimationFrame(close));
})();
