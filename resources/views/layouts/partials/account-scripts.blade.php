{{-- Account menus and notification polling run only for the matching session state. --}}
    @guest
        <script>
            const loginToggle = document.getElementById('login-toggle');
            const loginPopover = document.getElementById('login-popover');

            if (loginToggle && loginPopover) {
                loginToggle.addEventListener('click', () => {
                    const isOpen = loginToggle.getAttribute('aria-expanded') === 'true';
                    loginToggle.setAttribute('aria-expanded', String(!isOpen));
                    loginPopover.classList.toggle('hidden', isOpen);
                });

                document.addEventListener('click', (event) => {
                    if (!loginPopover.contains(event.target) && !loginToggle.contains(event.target)) {
                        loginToggle.setAttribute('aria-expanded', 'false');
                        loginPopover.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        loginToggle.setAttribute('aria-expanded', 'false');
                        loginPopover.classList.add('hidden');
                    }
                });
            }
        </script>
    @endguest
    @auth
        <script>
            const accountMenuToggle = document.getElementById('account-menu-toggle');
            const accountMenu = document.getElementById('account-menu');

            // Role shortcuts live beside profile actions here, so shared navigation stays identical for every role.
            // SECURITY: Adviser and posting links are rendered only by their existing Gates in the Blade menu.
            if (accountMenuToggle && accountMenu) {
                const accountMenuItems = () => [...accountMenu.querySelectorAll('[role="menuitem"]')];
                const closeAccountMenu = (returnFocus = false) => {
                    accountMenuToggle.setAttribute('aria-expanded', 'false');
                    accountMenu.classList.add('hidden');
                    if (returnFocus) accountMenuToggle.focus();
                };
                accountMenuToggle.addEventListener('click', () => {
                    const isOpen = accountMenuToggle.getAttribute('aria-expanded') === 'true';
                    accountMenuToggle.setAttribute('aria-expanded', String(!isOpen));
                    accountMenu.classList.toggle('hidden', isOpen);
                    if (!isOpen) accountMenuItems()[0]?.focus();
                });

                document.addEventListener('click', (event) => {
                    if (!accountMenu.contains(event.target) && !accountMenuToggle.contains(event.target)) {
                        closeAccountMenu();
                    }
                });

                accountMenu.addEventListener('keydown', (event) => {
                    const items = accountMenuItems();
                    const index = items.indexOf(document.activeElement);
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        closeAccountMenu(true);
                    } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        const direction = event.key === 'ArrowDown' ? 1 : -1;
                        items[(index + direction + items.length) % items.length]?.focus();
                    }
                });
                accountMenuToggle.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        accountMenuToggle.click();
                        const items = accountMenuItems();
                        (event.key === 'ArrowDown' ? items[0] : items.at(-1))?.focus();
                    } else if (event.key === 'Escape') closeAccountMenu(true);
                });
            }

            const notificationWrapper = document.getElementById('notification-menu-wrapper');
            const notificationToggle = document.getElementById('notifications-toggle');
            const notificationDropdown = document.getElementById('notifications-dropdown');
            const notificationList = document.getElementById('notification-list');
            const notificationBadge = document.getElementById('notification-badge');
            const notificationCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const postLabels = { article: 'Article', activity: 'Activity', achievement: 'Achievement', document: 'Document', liquidation: 'Liquidation' };
            const notificationIconTemplates = new Map([
                ['submitted', 'notification-icon-submitted'],
                ['approved', 'notification-icon-approved'],
                ['published', 'notification-icon-published'],
                ['rejected', 'notification-icon-rejected'],
            ]);
            const notificationPostIconTemplates = new Map([
                ['article', 'notification-post-article'],
                ['activity', 'notification-post-activity'],
                ['achievement', 'notification-post-achievement'],
                ['document', 'notification-post-document'],
                ['liquidation', 'notification-post-liquidation'],
            ]);
            const appendStaticIcon = (container, templateId, className) => {
                const template = document.getElementById(templateId);
                if (!(template instanceof HTMLTemplateElement)) {
                    console.error(`Missing static notification icon template: ${templateId}`);
                    return;
                }
                const source = template.content.firstElementChild;
                if (!(source instanceof SVGSVGElement)) {
                    console.error(`Invalid static notification icon template: ${templateId}`);
                    return;
                }
                const icon = source.cloneNode(true);
                if (!(icon instanceof SVGSVGElement)) return;
                icon.classList.add(className);
                container.append(icon);
            };
            document.querySelectorAll('.notification-actor-avatar').forEach((avatar) => {
                avatar.addEventListener('error', () => avatar.remove(), { once: true });
            });

            const relativeTime = (value) => {
                const seconds = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 1000));
                if (seconds < 60) return 'just now';
                if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
                if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
                return `${Math.floor(seconds / 86400)}d ago`;
            };

            const showNotificationSkeleton = () => {
                notificationList.replaceChildren();
                const status = document.createElement('div');
                status.className = 'notification-loading-skeleton';
                status.setAttribute('role', 'status');
                const label = document.createElement('span');
                label.className = 'visually-hidden';
                label.textContent = 'Loading notifications';
                status.append(label);
                for (let row = 0; row < 3; row++) {
                    const skeletonRow = document.createElement('span');
                    skeletonRow.className = 'skeleton-block notification-skeleton-row';
                    skeletonRow.setAttribute('aria-hidden', 'true');
                    status.append(skeletonRow);
                }
                notificationList.append(status);
            };

            const updateNotificationSummary = async (forceOpen = false) => {
                if (!notificationWrapper || document.hidden || (!forceOpen && notificationToggle?.getAttribute('aria-expanded') === 'true')) return;
                if (forceOpen) showNotificationSkeleton();
                try {
                    const response = await fetch(notificationWrapper.dataset.summaryUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('Notification request failed.');
                    const summary = await response.json();
                    const count = Number(summary.unread_count || 0);
                    const previousCount = Number(notificationBadge.dataset.unreadCount || 0);
                    notificationBadge.textContent = count > 99 ? '99+' : String(count);
                    notificationBadge.dataset.unreadCount = String(count);
                    notificationBadge.classList.toggle('hidden', count === 0);
                    if (count !== previousCount && count > 0) {
                        notificationBadge.dispatchEvent(new Event('ssite:notification-count-change'));
                    }

                    document.querySelectorAll('[data-notification-post-type]').forEach((dot) => {
                        const unread = Number(summary.nav_counts?.[dot.dataset.notificationPostType] || 0) > 0;
                        dot.classList.toggle('hidden', !unread);
                    });

                    if (!forceOpen && notificationToggle?.getAttribute('aria-expanded') === 'true') return;
                    notificationList.replaceChildren();
                    if (!summary.notifications?.length) {
                        const empty = document.createElement('p');
                        empty.className = 'notification-empty';
                        empty.textContent = "You're all caught up";
                        notificationList.append(empty);
                        return;
                    }

                    summary.notifications.forEach((item) => {
                        const data = item.data || {};
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = notificationWrapper.dataset.readUrlTemplate.replace('00000000-0000-4000-8000-000000000000', encodeURIComponent(item.id));
                        form.className = 'notification-entry-form';
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = notificationCsrf;
                        const button = document.createElement('button');
                        button.type = 'submit'; button.className = `notification-entry ${item.read_at ? '' : 'is-unread'}`;
                        const typeIcon = document.createElement('span');
                        typeIcon.className = `notification-type-icon notification-type-${data.type || 'published'}`;
                        typeIcon.setAttribute('aria-hidden', 'true');
                        appendStaticIcon(typeIcon, notificationIconTemplates.get(data.type) || 'notification-icon-default', 'notification-status-icon');
                        const postIcon = document.createElement('small');
                        appendStaticIcon(postIcon, notificationPostIconTemplates.get(data.post_type) || 'notification-post-article', 'notification-post-icon');
                        typeIcon.append(postIcon);
                        const avatar = document.createElement('img');
                        avatar.className = 'notification-actor-avatar';
                        avatar.alt = '';
                        avatar.src = notificationWrapper.dataset.avatarUrlTemplate.replace('00000000-0000-4000-8000-000000000000', encodeURIComponent(item.id));
                        avatar.addEventListener('error', () => avatar.remove(), { once: true });
                        typeIcon.append(avatar);
                        const copy = document.createElement('span'); copy.className = 'notification-entry-copy';
                        const message = document.createElement('span'); message.className = 'notification-entry-message'; message.textContent = data.message || 'You have a new notification.';
                        const title = document.createElement('span'); title.className = 'notification-entry-title'; title.textContent = data.title || '';
                        const meta = document.createElement('span'); meta.className = 'notification-entry-meta';
                        meta.textContent = `${data.actor_name || 'SSITE'} · ${postLabels[data.post_type] || 'Post'} · ${item.created_at ? relativeTime(item.created_at) : ''}`;
                        copy.append(message, title, meta);
                        if (!item.read_at) { const unreadDot = document.createElement('span'); unreadDot.className = 'notification-entry-unread-dot'; unreadDot.setAttribute('aria-label', 'Unread'); button.append(typeIcon, copy, unreadDot); }
                        else button.append(typeIcon, copy);
                        form.append(csrf, button); notificationList.append(form);
                    });
                } catch (_) {
                    // Polling failures after logout or session expiry stay silent and leave the page usable.
                    if (forceOpen) {
                        const unavailable = document.createElement('p');
                        unavailable.className = 'notification-empty';
                        unavailable.textContent = 'Could not load notifications. Open the menu to try again.';
                        notificationList.replaceChildren(unavailable);
                    }
                }
            };

            if (notificationWrapper && notificationToggle && notificationDropdown) {
                notificationToggle.addEventListener('click', () => {
                    const open = notificationToggle.getAttribute('aria-expanded') !== 'true';
                    notificationToggle.setAttribute('aria-expanded', String(open));
                    notificationDropdown.classList.toggle('hidden', !open);
                    if (open) updateNotificationSummary(true);
                });
                document.addEventListener('click', (event) => {
                    if (!notificationWrapper.contains(event.target)) {
                        notificationToggle.setAttribute('aria-expanded', 'false');
                        notificationDropdown.classList.add('hidden');
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        notificationToggle.setAttribute('aria-expanded', 'false');
                        notificationDropdown.classList.add('hidden');
                    }
                });
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) updateNotificationSummary();
                });
                // SECURITY: Hidden tabs and an open dropdown pause polling to avoid wasted requests and flicker.
                window.setInterval(updateNotificationSummary, 60000);
                updateNotificationSummary();
            }
        </script>
    @endauth
