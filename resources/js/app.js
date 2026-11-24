import './bootstrap';
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const passwordResetWaiting = document.querySelector('[data-password-reset-waiting]');
if (passwordResetWaiting) {
    const pollInterval = Number(passwordResetWaiting.dataset.pollInterval) || 5000;
    window.setTimeout(() => window.location.reload(), pollInterval);
}

const registrationSuccessNotice = document.querySelector('[data-registration-success]');
if (registrationSuccessNotice) {
    const countdown = registrationSuccessNotice.querySelector('[data-registration-countdown]');
    const loginUrl = registrationSuccessNotice.dataset.loginUrl;
    let remainingSeconds = 5;

    const countdownTimer = window.setInterval(() => {
        remainingSeconds -= 1;
        if (countdown) {
            countdown.textContent = String(remainingSeconds);
        }

        if (remainingSeconds <= 0) {
            window.clearInterval(countdownTimer);
            window.location.assign(loginUrl);
        }
    }, 1000);
}

document.querySelectorAll('[data-live-clock]').forEach((clock) => {
    const updateClock = () => {
        const now = new Date();
        clock.dateTime = now.toISOString();
        clock.textContent = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
        }).format(now);
    };

    updateClock();
    window.setInterval(updateClock, 1000);
});

const databaseNavigation = document.querySelector('.database-navigation');
const activeDatabaseLink = databaseNavigation?.querySelector('[aria-current="page"]');

const portalShell = document.querySelector('.database-dashboard-portal');
const portalHeader = document.querySelector('.portal-dashboard-header');
const portalMain = portalShell?.querySelector('.database-main');
const mobileDrawerToggle = document.querySelector('[data-mobile-drawer-toggle]');
const mobileDrawerBackdrop = document.querySelector('[data-mobile-drawer-backdrop]');
const mobileDrawerClose = document.querySelector('[data-mobile-drawer-close]');

if (portalShell && mobileDrawerToggle) {
    const syncHeaderHeight = () => {
        if (portalHeader) {
            portalShell.style.setProperty('--mobile-header-height', `${portalHeader.offsetHeight}px`);
        }
    };

    const setDrawerOpen = (open) => {
        syncHeaderHeight();
        portalShell.classList.toggle('is-mobile-drawer-open', open);
        mobileDrawerToggle.setAttribute('aria-expanded', String(open));
        if (mobileDrawerBackdrop) {
            mobileDrawerBackdrop.hidden = !open;
        }
    };

    syncHeaderHeight();
    window.addEventListener('resize', syncHeaderHeight);

    mobileDrawerToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setDrawerOpen(!portalShell.classList.contains('is-mobile-drawer-open'));
    });

    portalMain?.addEventListener('click', (event) => {
        if (window.innerWidth <= 768
            && portalShell.classList.contains('is-mobile-drawer-open')
            && !event.target.closest('[data-mobile-drawer-toggle]')) {
            setDrawerOpen(false);
        }
    });

    mobileDrawerBackdrop?.addEventListener('click', () => setDrawerOpen(false));
    mobileDrawerClose?.addEventListener('click', () => setDrawerOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && portalShell.classList.contains('is-mobile-drawer-open')) {
            setDrawerOpen(false);
        }
    });

    databaseNavigation?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                setDrawerOpen(false);
            }
        });
    });
}

if (databaseNavigation && activeDatabaseLink) {
    if (getComputedStyle(databaseNavigation).flexDirection === 'row') {
        const navBounds = databaseNavigation.getBoundingClientRect();
        const linkBounds = activeDatabaseLink.getBoundingClientRect();
        databaseNavigation.scrollLeft += linkBounds.left - navBounds.left
            - (databaseNavigation.clientWidth - activeDatabaseLink.clientWidth) / 2;
    } else {
        const navBounds = databaseNavigation.getBoundingClientRect();
        const linkBounds = activeDatabaseLink.getBoundingClientRect();
        const linkTop = linkBounds.top - navBounds.top + databaseNavigation.scrollTop;
        databaseNavigation.scrollTop = Math.max(
            0,
            linkTop - (databaseNavigation.clientHeight - activeDatabaseLink.clientHeight) / 2,
        );
    }
}

const messageNotifications = document.querySelector('[data-message-notifications]');

if (messageNotifications) {
    const toggle = messageNotifications.querySelector('[data-message-notifications-toggle]');
    const panel = messageNotifications.querySelector('[data-message-notification-panel]');
    const list = messageNotifications.querySelector('[data-message-notification-items]');
    const badge = messageNotifications.querySelector('[data-message-unread-badge]');

    const setPanelOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };

    const renderNotifications = ({ count, messages }) => {
        badge.hidden = count < 1;
        badge.textContent = count > 99 ? '99+' : String(count);
        list.replaceChildren();

        if (messages.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'message-notification-empty';
            empty.textContent = 'Belum ada pesan baru.';
            list.append(empty);
            return;
        }

        messages.forEach((message) => {
            const link = document.createElement('a');
            const sender = document.createElement('strong');
            const preview = document.createElement('span');

            link.className = 'message-notification-item';
            link.href = message.url;
            sender.textContent = message.sender;
            preview.textContent = message.preview;
            link.append(sender, preview);
            list.append(link);
        });
    };

    const refreshNotifications = async () => {
        const response = await fetch(messageNotifications.dataset.notificationsUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Notifikasi pesan gagal dimuat (${response.status}).`);
        }

        renderNotifications(await response.json());
    };

    toggle.addEventListener('click', () => {
        setPanelOpen(panel.hidden);
    });

    document.addEventListener('click', (event) => {
        if (!messageNotifications.contains(event.target)) {
            setPanelOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setPanelOpen(false);
        }
    });

    refreshNotifications().catch((error) => {
        console.error('Gagal memuat notifikasi pesan.', error);
    });
    window.setInterval(() => {
        refreshNotifications().catch((error) => {
            console.error('Gagal memperbarui notifikasi pesan.', error);
        });
    }, 30000);
}

document.querySelectorAll('[data-role-notifications]').forEach((notifications) => {
    const toggle = notifications.querySelector('[data-role-notifications-toggle]');
    const panel = notifications.querySelector('[data-role-notification-panel]');
    const badge = notifications.querySelector('[data-role-notification-badge]');
    const markAllButton = notifications.querySelector('[data-role-notifications-mark-all]');
    const items = [...notifications.querySelectorAll('[data-role-notification-item]')];
    const storageKey = `sinar-role-notifications:${notifications.dataset.roleNotifications}`;
    const readIds = new Set((localStorage.getItem(storageKey) ?? '').split('|').filter(Boolean));

    const renderReadState = () => {
        const unreadItems = items.filter((item) => !readIds.has(item.dataset.notificationId));
        badge.hidden = unreadItems.length === 0;
        badge.textContent = unreadItems.length > 99 ? '99+' : String(unreadItems.length);
        badge.setAttribute('aria-label', `${unreadItems.length} notifikasi belum dibaca`);
        markAllButton.hidden = unreadItems.length === 0;
        items.forEach((item) => {
            item.classList.toggle('is-read', readIds.has(item.dataset.notificationId));
        });
    };

    const saveReadState = () => {
        localStorage.setItem(storageKey, [...readIds].join('|'));
        renderReadState();
    };

    const setPanelOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };

    renderReadState();

    markAllButton.addEventListener('click', () => {
        items.forEach((item) => readIds.add(item.dataset.notificationId));
        saveReadState();
    });

    items.forEach((item) => {
        item.addEventListener('click', () => {
            readIds.add(item.dataset.notificationId);
            saveReadState();
        });
    });

    toggle.addEventListener('click', () => {
        setPanelOpen(panel.hidden);
    });

    document.addEventListener('click', (event) => {
        if (!notifications.contains(event.target)) {
            setPanelOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setPanelOpen(false);
        }
    });
});

document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-profile-menu-toggle]');
    const panel = menu.querySelector('[data-profile-menu-panel]');

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => {
        setOpen(panel.hidden);
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
});

const dashboardSearch = document.querySelector('[data-dashboard-search]');

if (dashboardSearch) {
    const searchableLinks = document.querySelectorAll('[data-dashboard-searchable]');
    const searchableNavigation = document.querySelector('.database-navigation, .role-nav');
    const navigationLinks = searchableNavigation?.querySelectorAll('[data-dashboard-searchable]') ?? [];
    const navigationLinkSet = new Set(navigationLinks);
    const navigationSearchEmpty = document.querySelector('[data-dashboard-search-empty]');
    const globalSearchUrl = dashboardSearch.dataset.globalSearchUrl;
    const globalSearchResults = document.querySelector('[data-global-search-results]');
    let globalSearchTimer = null;
    let globalSearchController = null;

    const renderGlobalResults = (items, query) => {
        if (!globalSearchResults) {
            return;
        }
        if (query.length < 2) {
            globalSearchResults.hidden = true;
            globalSearchResults.innerHTML = '';
            return;
        }

        globalSearchResults.innerHTML = '';
        if (!items || items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'portal-global-search-empty';
            empty.textContent = `Tidak ada data yang cocok untuk "${query}".`;
            globalSearchResults.appendChild(empty);
            globalSearchResults.hidden = false;
            return;
        }

        items.forEach((item) => {
            const link = document.createElement('a');
            link.className = 'portal-global-search-item';
            link.href = item.url;

            const badge = document.createElement('span');
            badge.className = 'portal-global-search-category';
            badge.textContent = item.category;

            const body = document.createElement('span');
            body.className = 'portal-global-search-body';

            const title = document.createElement('strong');
            title.textContent = item.label;

            const meta = document.createElement('small');
            meta.textContent = item.meta || '';

            body.appendChild(title);
            if (item.meta) {
                body.appendChild(meta);
            }

            link.appendChild(badge);
            link.appendChild(body);
            globalSearchResults.appendChild(link);
        });

        globalSearchResults.hidden = false;
    };

    dashboardSearch.addEventListener('input', () => {
        const rawQuery = dashboardSearch.value.trim();
        const query = rawQuery.toLocaleLowerCase();
        let navigationMatches = 0;

        searchableLinks.forEach((link) => {
            const matches = link.textContent.toLocaleLowerCase().includes(query);
            link.classList.toggle('is-search-hidden', !matches);

            if (matches && navigationLinkSet.has(link)) {
                navigationMatches += 1;
            }
        });

        document.querySelectorAll('[data-navigation-group], [data-module-group]').forEach((group) => {
            const visibleLinks = Array.from(group.querySelectorAll('[data-dashboard-searchable]'))
                .some((link) => !link.classList.contains('is-search-hidden'));
            group.classList.toggle('is-search-hidden', !visibleLinks);
        });

        navigationSearchEmpty?.classList.toggle('is-visible', query !== '' && navigationMatches === 0);

        if (globalSearchUrl && globalSearchResults) {
            window.clearTimeout(globalSearchTimer);
            if (globalSearchController) {
                globalSearchController.abort();
            }
            if (rawQuery.length < 2) {
                renderGlobalResults([], rawQuery);
                return;
            }

            globalSearchTimer = window.setTimeout(async () => {
                globalSearchController = new AbortController();
                try {
                    const response = await fetch(`${globalSearchUrl}?q=${encodeURIComponent(rawQuery)}`, {
                        headers: { Accept: 'application/json' },
                        signal: globalSearchController.signal,
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderGlobalResults(payload.items ?? [], rawQuery);
                } catch (error) {
                    if (error?.name !== 'AbortError') {
                        globalSearchResults.hidden = true;
                    }
                }
            }, 220);
        }
    });

    document.addEventListener('click', (event) => {
        if (globalSearchResults && !event.target.closest('.portal-dashboard-search')) {
            globalSearchResults.hidden = true;
        }
    });

    dashboardSearch.addEventListener('focus', () => {
        if (globalSearchResults && dashboardSearch.value.trim().length >= 2 && globalSearchResults.children.length > 0) {
            globalSearchResults.hidden = false;
        }
    });
}

document.querySelectorAll('[data-record-search]').forEach((input) => {
    const section = input.closest('.database-list-section');
    const form = input.closest('form');
    const heading = section?.querySelector('.database-section-heading');
    const countBadge = section?.querySelector('.database-result-count');
    let fetchTimer = null;
    let fetchController = null;

    const filterCurrentRows = (query) => {
        const rows = Array.from(section?.querySelectorAll('[data-record-row]') ?? []);
        const emptyState = section?.querySelector('[data-record-search-empty]');
        let visibleRows = 0;

        rows.forEach((row) => {
            const matches = row.textContent.toLocaleLowerCase().includes(query);
            row.hidden = !matches;
            visibleRows += Number(matches);
        });

        if (emptyState) {
            emptyState.hidden = visibleRows !== 0;
        }
    };

    const syncLiveResults = async (rawQuery) => {
        if (!form || !section || !heading) {
            return;
        }

        if (fetchController) {
            fetchController.abort();
        }
        fetchController = new AbortController();

        const targetUrl = new URL(form.action || window.location.href, window.location.origin);
        if (rawQuery !== '') {
            targetUrl.searchParams.set('q', rawQuery);
        } else {
            targetUrl.searchParams.delete('q');
        }
        targetUrl.searchParams.delete('page');

        try {
            const response = await fetch(targetUrl.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: fetchController.signal,
            });
            if (!response.ok) {
                return;
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const freshSection = doc.querySelector('.database-list-section');
            if (!freshSection) {
                return;
            }

            const freshCount = freshSection.querySelector('.database-result-count');
            if (countBadge && freshCount) {
                countBadge.textContent = freshCount.textContent;
            }

            while (heading.nextSibling) {
                heading.nextSibling.remove();
            }

            const freshHeading = freshSection.querySelector('.database-section-heading');
            let node = freshHeading?.nextSibling ?? null;
            while (node) {
                const next = node.nextSibling;
                section.appendChild(document.importNode(node, true));
                node = next;
            }

            window.history.replaceState({}, '', targetUrl.toString());
        } catch (error) {
            if (error?.name !== 'AbortError') {
                // Keep client-side filter state if network fails
            }
        }
    };

    input.addEventListener('input', () => {
        const rawQuery = input.value.trim();
        filterCurrentRows(rawQuery.toLocaleLowerCase());

        if (form) {
            window.clearTimeout(fetchTimer);
            fetchTimer = window.setTimeout(() => {
                syncLiveResults(rawQuery);
            }, 220);
        }
    });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(fetchTimer);
        syncLiveResults(input.value.trim());
    });

    section?.addEventListener('click', (event) => {
        const resetLink = event.target.closest('.database-record-search-form a');
        if (!resetLink) {
            return;
        }
        event.preventDefault();
        input.value = '';
        window.clearTimeout(fetchTimer);
        syncLiveResults('');
        input.focus();
    });
});

/* Pencarian otomatis untuk semua <select> panjang (>= 5 opsi) */
document.querySelectorAll('.database-action-panel select, .registration-form select').forEach((select) => {
    if (select.dataset.searchableInitialized === '1' || select.multiple || select.options.length < 5) {
        return;
    }
    select.dataset.searchableInitialized = '1';

    const originalOptions = Array.from(select.options).map((option) => ({
        value: option.value,
        text: option.textContent ?? '',
        disabled: option.disabled,
        dataset: { ...option.dataset },
    }));

    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-select-wrap';
    select.parentNode.insertBefore(wrapper, select);

    const searchRow = document.createElement('div');
    searchRow.className = 'searchable-select-bar';

    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'searchable-select-input';
    searchInput.placeholder = 'Ketik untuk mencari pilihan...';
    searchInput.setAttribute('aria-label', 'Cari pilihan');

    const countBadge = document.createElement('span');
    countBadge.className = 'searchable-select-count';
    countBadge.textContent = `${originalOptions.filter((opt) => opt.value !== '').length} opsi`;

    searchRow.appendChild(searchInput);
    searchRow.appendChild(countBadge);
    wrapper.appendChild(searchRow);
    wrapper.appendChild(select);

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLocaleLowerCase();
        const previousValue = select.value;

        const filtered = originalOptions.filter((opt) => {
            if (opt.value === '') {
                return true;
            }
            return query === '' || opt.text.toLocaleLowerCase().includes(query);
        });

        select.innerHTML = '';
        filtered.forEach((opt) => {
            const optionEl = document.createElement('option');
            optionEl.value = opt.value;
            optionEl.textContent = opt.text;
            optionEl.disabled = opt.disabled;
            Object.entries(opt.dataset).forEach(([k, v]) => {
                optionEl.dataset[k] = v;
            });
            select.appendChild(optionEl);
        });

        const selectableMatches = filtered.filter((opt) => opt.value !== '' && !opt.disabled);
        countBadge.textContent = query === ''
            ? `${originalOptions.filter((opt) => opt.value !== '').length} opsi`
            : `${selectableMatches.length} cocok`;

        if (query !== '' && selectableMatches.length > 0) {
            if (previousValue !== '' && selectableMatches.some((opt) => opt.value === previousValue)) {
                select.value = previousValue;
            } else {
                select.value = selectableMatches[0].value;
            }
        } else if (filtered.some((opt) => opt.value === previousValue)) {
            select.value = previousValue;
        } else if (selectableMatches.length > 0) {
            select.value = selectableMatches[0].value;
        } else {
            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = 'Tidak ada pilihan yang cocok';
            emptyOpt.disabled = true;
            select.appendChild(emptyOpt);
        }

        if (select.value !== previousValue) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
});

/* Pencarian instan pada daftar kartu/editor/absensi di semua panel aksi */
const attachInlineListSearch = (container, items, insertBeforeNode, placeholder = 'Cari di daftar ini...') => {
    if (items.length < 4) {
        return;
    }

    const searchBar = document.createElement('div');
    searchBar.className = 'panel-inline-search';

    const input = document.createElement('input');
    input.type = 'search';
    input.placeholder = placeholder;
    input.setAttribute('aria-label', placeholder);

    const count = document.createElement('span');
    count.className = 'panel-inline-search-count';
    count.textContent = `${items.length} data`;

    const empty = document.createElement('p');
    empty.className = 'database-filter-empty';
    empty.hidden = true;
    empty.textContent = 'Tidak ada data pada daftar ini yang cocok.';

    searchBar.appendChild(input);
    searchBar.appendChild(count);

    container.insertBefore(searchBar, insertBeforeNode);
    container.insertBefore(empty, insertBeforeNode);

    input.addEventListener('input', () => {
        const query = input.value.trim().toLocaleLowerCase();
        let visible = 0;

        items.forEach((item) => {
            const matches = query === '' || item.textContent.toLocaleLowerCase().includes(query);
            item.hidden = !matches;
            visible += Number(matches);
        });

        count.textContent = query === '' ? `${items.length} data` : `${visible} / ${items.length} cocok`;
        empty.hidden = visible !== 0;
    });
};

const ACTION_ITEM_SELECTOR = '.database-student-editor, .database-action-details, .database-action-row, .database-inline-card, .submission-card';

document.querySelectorAll('.database-action-panel').forEach((panel) => {
    let currentGroup = [];

    const flushGroup = () => {
        if (currentGroup.length >= 4) {
            attachInlineListSearch(panel, currentGroup, currentGroup[0]);
        }
        currentGroup = [];
    };

    Array.from(panel.children).forEach((child) => {
        if (child.matches(ACTION_ITEM_SELECTOR)) {
            currentGroup.push(child);
        } else {
            flushGroup();
        }
    });
    flushGroup();
});

document.querySelectorAll('.database-attendance-list').forEach((list) => {
    const rows = Array.from(list.querySelectorAll(':scope > .database-attendance-row'));
    const form = list.closest('form');
    const header = form?.querySelector('.database-attendance-header');
    if (form && header) {
        attachInlineListSearch(form, rows, header, 'Cari nama siswa di daftar absensi...');
    } else {
        attachInlineListSearch(list, rows, rows[0], 'Cari nama siswa di daftar absensi...');
    }
});

/* Format otomatis pemisah ribuan titik (100.000) untuk semua input nominal/rupiah */
const formatRupiahDigits = (digits) => digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

document.querySelectorAll(
    'input[type="number"][name="jumlah"], input[type="number"][name="nominal"], input[type="number"][name="biaya"], input[type="number"][name="diskon"], input[type="number"][name="amount"]',
).forEach((input) => {
    if (input.dataset.currencyInitialized === '1') {
        return;
    }
    input.dataset.currencyInitialized = '1';

    const fieldName = input.getAttribute('name');
    const minVal = input.getAttribute('min') !== null ? Number(input.getAttribute('min')) : null;
    const maxVal = input.getAttribute('max') !== null ? Number(input.getAttribute('max')) : null;

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = fieldName;

    input.removeAttribute('name');
    input.type = 'text';
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('autocomplete', 'off');
    if (!input.placeholder) {
        input.placeholder = 'Contoh: 100.000';
    }
    input.insertAdjacentElement('afterend', hiddenInput);

    const normalizeInitial = (rawValue) => {
        if (rawValue === '' || rawValue === null || rawValue === undefined) {
            return '';
        }
        const parsed = Number(rawValue);
        if (!Number.isNaN(parsed)) {
            return String(Math.round(parsed));
        }
        return String(rawValue).replace(/\D+/g, '');
    };

    const syncValue = (digits) => {
        const cleaned = digits.replace(/^0+(?=\d)/, '');
        hiddenInput.value = cleaned;
        input.value = cleaned === '' ? '' : formatRupiahDigits(cleaned);

        if (cleaned !== '') {
            const numeric = Number(cleaned);
            if (minVal !== null && numeric < minVal) {
                input.setCustomValidity(`Nilai minimal ${formatRupiahDigits(String(Math.ceil(minVal)))}.`);
            } else if (maxVal !== null && numeric > maxVal) {
                input.setCustomValidity(`Nilai maksimal ${formatRupiahDigits(String(Math.floor(maxVal)))}.`);
            } else {
                input.setCustomValidity('');
            }
        } else {
            input.setCustomValidity('');
        }
    };

    syncValue(normalizeInitial(input.value));

    input.addEventListener('input', () => {
        const cursorPos = input.selectionStart ?? input.value.length;
        const digitsBeforeCursor = input.value.slice(0, cursorPos).replace(/\D+/g, '').length;
        const allDigits = input.value.replace(/\D+/g, '');

        syncValue(allDigits);

        let seenDigits = 0;
        let nextCursor = input.value.length;
        for (let i = 0; i < input.value.length; i += 1) {
            if (/\d/.test(input.value[i])) {
                seenDigits += 1;
            }
            if (seenDigits >= digitsBeforeCursor) {
                nextCursor = i + 1;
                break;
            }
        }
        if (digitsBeforeCursor === 0) {
            nextCursor = 0;
        }
        input.setSelectionRange(nextCursor, nextCursor);
    });
});

document.querySelectorAll('.password-toggle').forEach((passwordToggle) => {
    const passwordInput = document.getElementById(passwordToggle.getAttribute('aria-controls'))
        ?? passwordToggle.closest('.input-wrap, .registration-password-wrap')?.querySelector('input');

    if (!passwordInput) {
        return;
    }

    passwordToggle.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        passwordToggle.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        passwordToggle.classList.toggle('is-visible', isPassword);
        passwordToggle.setAttribute('aria-pressed', String(isPassword));
    });
});

document.querySelectorAll('.database-copy-button').forEach((button) => {
    const target = document.getElementById(button.dataset.copyTarget);
    const status = document.getElementById(button.dataset.copyStatus);

    if (!target || !status) {
        console.error('Payment account copy control is missing its target or status element.');
        return;
    }

    button.addEventListener('click', async () => {
        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            status.textContent = 'Tidak dapat menyalin otomatis. Silakan salin nomor rekening secara manual.';
            return;
        }

        try {
            await navigator.clipboard.writeText(target.textContent.trim());
            status.textContent = 'Nomor rekening berhasil disalin.';
        } catch (error) {
            console.error('Unable to copy the payment account number.', error);
            status.textContent = 'Tidak dapat menyalin otomatis. Silakan salin nomor rekening secara manual.';
        }
    });
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form) {
        return;
    }
    if (!window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-open-editor]');
    if (!button) {
        return;
    }
    const editor = document.getElementById(button.dataset.openEditor);
    if (!editor) {
        return;
    }
    editor.open = true;
    editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
    editor.querySelector('input, textarea, select')?.focus();
});

const uploadValidatorsByForm = new Map();

const ensureFilePreviewModal = () => {
    let modal = document.getElementById('sinar-file-preview-modal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'sinar-file-preview-modal';
    modal.className = 'file-preview-modal';
    modal.hidden = true;
    modal.innerHTML = `
        <div class="file-preview-modal-backdrop" data-close-preview></div>
        <div class="file-preview-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sinar-file-preview-title">
            <div class="file-preview-modal-head">
                <strong id="sinar-file-preview-title">Preview File</strong>
                <div class="file-preview-modal-actions">
                    <a class="file-preview-open-tab" href="#" target="_blank" rel="noopener">Buka di tab baru</a>
                    <button type="button" class="file-preview-close" data-close-preview aria-label="Tutup preview">✕</button>
                </div>
            </div>
            <div class="file-preview-modal-body"></div>
        </div>
    `;
    document.body.appendChild(modal);

    modal.addEventListener('click', (event) => {
        if (event.target.closest('[data-close-preview]')) {
            modal.hidden = true;
            modal.querySelector('.file-preview-modal-body').innerHTML = '';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
            modal.querySelector('.file-preview-modal-body').innerHTML = '';
        }
    });

    return modal;
};

const openFilePreviewModal = (file, objectUrl) => {
    const modal = ensureFilePreviewModal();
    const title = modal.querySelector('#sinar-file-preview-title');
    const openTab = modal.querySelector('.file-preview-open-tab');
    const body = modal.querySelector('.file-preview-modal-body');

    title.textContent = file.name;
    openTab.href = objectUrl;
    body.innerHTML = '';

    const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp|gif)$/i.test(file.name);
    const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

    if (isImage) {
        const img = document.createElement('img');
        img.src = objectUrl;
        img.alt = file.name;
        img.className = 'file-preview-modal-image';
        body.appendChild(img);
    } else if (isPdf) {
        const frame = document.createElement('iframe');
        frame.src = objectUrl;
        frame.title = file.name;
        frame.className = 'file-preview-modal-pdf';
        body.appendChild(frame);
    } else {
        const fallback = document.createElement('p');
        fallback.className = 'file-preview-modal-fallback';
        fallback.textContent = 'Pratinjau langsung tidak tersedia untuk format ini. Klik "Buka di tab baru" di atas.';
        body.appendChild(fallback);
    }

    modal.hidden = false;
};

document.addEventListener('click', (event) => {
    const docLink = event.target.closest('[data-doc-preview]');
    if (!docLink) {
        return;
    }
    event.preventDefault();
    const ext = (docLink.dataset.docExt || '').toLowerCase();
    const mime = ext === 'pdf'
        ? 'application/pdf'
        : (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext) ? `image/${ext === 'jpg' ? 'jpeg' : ext}` : '');
    openFilePreviewModal(
        { name: `${docLink.dataset.docName || 'Dokumen'}${ext ? '.' + ext : ''}`, type: mime },
        docLink.href,
    );
});

const formatPickerBytes = (bytes) => {
    const megabytes = bytes / (1024 * 1024);
    const formatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: megabytes >= 1 ? 2 : 0,
        maximumFractionDigits: megabytes >= 1 ? 2 : 1,
    });

    return megabytes >= 1
        ? `${formatter.format(megabytes)} MB`
        : `${formatter.format(bytes / 1024)} KB`;
};

const initSingleFilePicker = (fileInput) => {
    if (!fileInput || fileInput.multiple || fileInput.dataset.pickerInitialized === '1') {
        return;
    }
    fileInput.dataset.pickerInitialized = '1';

    const wrapper = document.createElement('div');
    wrapper.className = 'file-picker';
    fileInput.parentNode.insertBefore(wrapper, fileInput);
    wrapper.appendChild(fileInput);
    fileInput.classList.add('file-picker-native');

    const trigger = document.createElement('div');
    trigger.className = 'file-picker-trigger';
    trigger.innerHTML = `
        <button type="button" class="file-picker-btn">Pilih File</button>
        <span class="file-picker-placeholder">Belum ada file dipilih</span>
    `;
    wrapper.appendChild(trigger);

    const preview = document.createElement('div');
    preview.className = 'file-picker-preview';
    preview.hidden = true;
    preview.innerHTML = `
        <div class="file-picker-thumb" role="button" tabindex="0" title="Klik untuk melihat preview"></div>
        <div class="file-picker-meta">
            <strong class="file-picker-name"></strong>
            <span class="file-picker-size"></span>
        </div>
        <div class="file-picker-actions">
            <button type="button" class="file-picker-action is-view">Lihat</button>
            <button type="button" class="file-picker-action is-change">Ganti</button>
            <button type="button" class="file-picker-action is-remove" aria-label="Hapus file">Hapus</button>
        </div>
    `;
    wrapper.appendChild(preview);

    const thumbEl = preview.querySelector('.file-picker-thumb');
    const nameEl = preview.querySelector('.file-picker-name');
    const sizeEl = preview.querySelector('.file-picker-size');
    const viewBtn = preview.querySelector('.file-picker-action.is-view');
    const changeBtn = preview.querySelector('.file-picker-action.is-change');
    const removeBtn = preview.querySelector('.file-picker-action.is-remove');
    const pickBtn = trigger.querySelector('.file-picker-btn');

    let currentObjectUrl = null;
    let currentFile = null;
    const clearDraftInput = fileInput.closest('.registration-upload-field')?.querySelector('[data-draft-clear]') ?? null;

    const revokeCurrentUrl = () => {
        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }
    };

    const openChooser = (event) => {
        event.preventDefault();
        event.stopPropagation();
        fileInput.click();
    };

    const renderPickerState = () => {
        revokeCurrentUrl();
        const file = fileInput.files?.[0] ?? null;

        if (!file) {
            const draftName = fileInput.dataset.draftName;
            const draftUrl = fileInput.dataset.draftUrl;
            if (draftName && draftUrl) {
                const draftSize = Number(fileInput.dataset.draftSize || 0);
                const draftMime = fileInput.dataset.draftMime || '';
                currentFile = { name: draftName, type: draftMime, size: draftSize };
                currentObjectUrl = null;
                trigger.hidden = true;
                preview.hidden = false;

                const isImage = draftMime.startsWith('image/') || /\.(jpe?g|png|webp|gif)$/i.test(draftName);
                const isPdf = draftMime === 'application/pdf' || /\.pdf$/i.test(draftName);

                thumbEl.innerHTML = '';
                if (isImage) {
                    const img = document.createElement('img');
                    img.src = draftUrl;
                    img.alt = draftName;
                    thumbEl.appendChild(img);
                } else {
                    const badge = document.createElement('span');
                    badge.className = `file-picker-badge ${isPdf ? 'is-pdf' : 'is-doc'}`;
                    badge.textContent = isPdf ? 'PDF' : 'FILE';
                    thumbEl.appendChild(badge);
                }

                nameEl.textContent = draftName;
                nameEl.title = draftName;
                sizeEl.textContent = `${formatPickerBytes(draftSize)} · Tersimpan`;
                return;
            }

            currentFile = null;
            trigger.hidden = false;
            preview.hidden = true;
            thumbEl.innerHTML = '';
            nameEl.textContent = '';
            sizeEl.textContent = '';
            return;
        }

        if (clearDraftInput) {
            clearDraftInput.value = '0';
        }
        delete fileInput.dataset.draftName;
        delete fileInput.dataset.draftSize;
        delete fileInput.dataset.draftMime;
        delete fileInput.dataset.draftUrl;
        currentFile = file;

        trigger.hidden = true;
        preview.hidden = false;
        currentObjectUrl = URL.createObjectURL(file);

        const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp|gif)$/i.test(file.name);
        const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

        thumbEl.innerHTML = '';
        if (isImage) {
            const img = document.createElement('img');
            img.src = currentObjectUrl;
            img.alt = file.name;
            thumbEl.appendChild(img);
        } else {
            const badge = document.createElement('span');
            badge.className = `file-picker-badge ${isPdf ? 'is-pdf' : 'is-doc'}`;
            badge.textContent = isPdf ? 'PDF' : 'FILE';
            thumbEl.appendChild(badge);
        }

        nameEl.textContent = file.name;
        nameEl.title = file.name;
        sizeEl.textContent = formatPickerBytes(file.size);
    };

    pickBtn.addEventListener('click', openChooser);
    trigger.addEventListener('click', openChooser);
    changeBtn.addEventListener('click', openChooser);

    const handlePreviewOpen = (event) => {
        event.preventDefault();
        event.stopPropagation();
        const previewUrl = currentObjectUrl || fileInput.dataset.draftUrl;
        if (currentFile && previewUrl) {
            openFilePreviewModal(currentFile, previewUrl);
        }
    };
    viewBtn.addEventListener('click', handlePreviewOpen);
    thumbEl.addEventListener('click', handlePreviewOpen);
    thumbEl.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            handlePreviewOpen(event);
        }
    });

    removeBtn.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        fileInput.value = '';
        if (clearDraftInput) {
            clearDraftInput.value = '1';
        }
        delete fileInput.dataset.draftName;
        delete fileInput.dataset.draftSize;
        delete fileInput.dataset.draftMime;
        delete fileInput.dataset.draftUrl;
        if (fileInput.dataset.wasRequired === '1') {
            fileInput.required = true;
        }
        renderPickerState();
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    fileInput.addEventListener('change', renderPickerState);
    renderPickerState();
};

document.querySelectorAll('[data-max-bytes]').forEach((fileInput) => {
    initSingleFilePicker(fileInput);
    const feedback = document.getElementById(fileInput.getAttribute('aria-describedby'));
    const form = fileInput.form;

    if (!feedback || !form) {
        return;
    }

    const maxBytes = Number(fileInput.dataset.maxBytes);

    const formatFileSize = (bytes) => {
        const megabytes = bytes / (1024 * 1024);
        const number = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: megabytes >= 1 ? 2 : 0,
            maximumFractionDigits: megabytes >= 1 ? 2 : 1,
        });

        return megabytes >= 1
            ? `${number.format(megabytes)} MB`
            : `${number.format(bytes / 1024)} KB`;
    };

    const showFeedback = (message, state = '') => {
        feedback.textContent = message;
        feedback.classList.toggle('is-error', state === 'error');
        feedback.classList.toggle('is-success', state === 'success');
        fileInput.setCustomValidity(state === 'error' ? message : '');
        fileInput.setAttribute('aria-invalid', String(state === 'error'));
    };

    const validateFile = (file) => {
        if (!file) {
            showFeedback('File maksimal 5 MB. Resolusi gambar maksimal 20 megapiksel.');
            return true;
        }

        if (file.size > maxBytes) {
            showFeedback('Ukuran file melebihi batas 5 MB. Pilih file yang lebih kecil.', 'error');
            return false;
        }

        const fileSize = formatFileSize(file.size);

        const extension = file.name.split('.').pop().toLowerCase();
        if (!['pdf', 'jpg', 'jpeg', 'png'].includes(extension)) {
            showFeedback('Format tidak didukung. Pilih file PDF, JPG, atau PNG.', 'error');
            return false;
        }

        showFeedback(`File ${fileSize} siap diunggah. Batas resolusi gambar 20 megapiksel akan diperiksa saat dikirim.`, 'success');
        return true;
    };

    fileInput.addEventListener('change', () => {
        validateFile(fileInput.files?.[0]);
    });

    const validators = uploadValidatorsByForm.get(form) ?? [];
    validators.push({ fileInput, validateFile });
    uploadValidatorsByForm.set(form, validators);
});

uploadValidatorsByForm.forEach((validators, form) => {
    form.addEventListener('submit', (event) => {
        for (const { fileInput, validateFile } of validators) {
            if (!validateFile(fileInput.files?.[0])) {
                event.preventDefault();
                fileInput.reportValidity();
                return;
            }
        }
    });
});

document.querySelectorAll('[data-activity-photo-input]').forEach((fileInput) => {
    const form = fileInput.form;
    const previewGrid = form?.querySelector('[data-activity-photo-previews]');
    const photoCount = form?.querySelector('[data-activity-photo-count]');
    const maxFiles = Number(fileInput.dataset.maxFiles ?? 10);
    let previewUrls = [];

    if (!form || !previewGrid || !photoCount) {
        return;
    }

    const renderPreviews = () => {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
        previewGrid.replaceChildren();

        const files = Array.from(fileInput.files ?? []);
        previewGrid.hidden = files.length === 0;
        fileInput.setCustomValidity(files.length > maxFiles ? `Pilih maksimal ${maxFiles} foto.` : '');
        photoCount.classList.toggle('is-error', files.length > maxFiles);
        if (files.length > maxFiles) {
            photoCount.textContent = `Maksimal ${maxFiles} foto per unggahan.`;
        } else if (files.length > 0) {
            photoCount.textContent = `${files.length} foto dipilih. Maksimal ${maxFiles} foto, 5 MB per foto.`;
        } else {
            photoCount.textContent = `Pilih hingga ${maxFiles} foto sekaligus (maksimal 5 MB per foto).`;
        }

        files.forEach((file, index) => {
            const card = document.createElement('figure');
            const image = document.createElement('img');
            const removeButton = document.createElement('button');
            const caption = document.createElement('figcaption');
            const objectUrl = URL.createObjectURL(file);

            card.className = 'activity-photo-preview-card';
            image.src = objectUrl;
            image.alt = `Pratinjau ${file.name}`;
            previewUrls.push(objectUrl);
            removeButton.className = 'activity-photo-preview-remove';
            removeButton.type = 'button';
            removeButton.setAttribute('aria-label', `Hapus foto ${file.name}`);
            removeButton.title = 'Hapus foto';
            removeButton.textContent = '×';
            caption.textContent = file.name;

            removeButton.addEventListener('click', () => {
                const remainingFiles = new DataTransfer();
                Array.from(fileInput.files ?? []).forEach((selectedFile, selectedIndex) => {
                    if (selectedIndex !== index) {
                        remainingFiles.items.add(selectedFile);
                    }
                });
                fileInput.files = remainingFiles.files;
                renderPreviews();
            });

            card.append(image, removeButton, caption);
            previewGrid.append(card);
        });
    };

    fileInput.addEventListener('change', renderPreviews);
    window.addEventListener('pagehide', () => {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
    }, { once: true });
});

document.querySelector('#google-login')?.addEventListener('click', () => {
    document.querySelector('#login-feedback').textContent = 'Login Google belum dihubungkan.';
});

document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-open');
});

const jakartaFormat = (options) => new Intl.DateTimeFormat('id-ID', {
    ...options,
    timeZone: 'Asia/Jakarta',
});

const jakartaDateParts = () => {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date());

    return Object.fromEntries(parts.map(({ type, value }) => [type, value]));
};

const currentJakartaDate = () => {
    const { year, month, day } = jakartaDateParts();

    return new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), 12));
};

const dateKey = (date) => date.toISOString().slice(0, 10);

const updateLiveClock = () => {
    const now = new Date();
    const dateText = jakartaFormat({
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(now);
    const timeText = jakartaFormat({
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(now);

    document.querySelectorAll('[data-live-date]').forEach((element) => {
        element.textContent = dateText;
    });
    document.querySelectorAll('[data-live-clock]').forEach((element) => {
        element.textContent = timeText;
    });
};

updateLiveClock();
window.setInterval(updateLiveClock, 1000);

document.querySelectorAll('[data-schedule-widget]').forEach((widget) => {
    let selectedDate = currentJakartaDate();
    const daysContainer = widget.querySelector('[data-date-days]');
    const monthLabel = widget.querySelector('[data-month-label]');

    const renderCalendar = () => {
        monthLabel.textContent = jakartaFormat({
            month: 'long',
            year: 'numeric',
        }).format(selectedDate);

        const selectedWeekday = selectedDate.getUTCDay();
        const mondayOffset = selectedWeekday === 0 ? -6 : 1 - selectedWeekday;
        const monday = new Date(selectedDate);
        monday.setUTCDate(monday.getUTCDate() + mondayOffset);

        daysContainer.replaceChildren();

        for (let index = 0; index < 7; index += 1) {
            const date = new Date(monday);
            date.setUTCDate(monday.getUTCDate() + index);

            const button = document.createElement('button');
            const weekday = document.createElement('span');
            const day = document.createElement('span');
            const isSelected = dateKey(date) === dateKey(selectedDate);

            button.type = 'button';
            button.className = `role-date-option${isSelected ? ' is-selected' : ''}`;
            button.dataset.date = dateKey(date);
            button.setAttribute('aria-pressed', String(isSelected));
            button.setAttribute('aria-label', jakartaFormat({
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).format(date));
            weekday.textContent = jakartaFormat({ weekday: 'short' }).format(date);
            day.textContent = jakartaFormat({ day: 'numeric', month: 'short' }).format(date);
            button.append(weekday, day);
            daysContainer.append(button);
        }
    };

    daysContainer.addEventListener('click', (event) => {
        const button = event.target.closest('[data-date]');

        if (!button || !daysContainer.contains(button)) {
            return;
        }

        const [year, month, day] = button.dataset.date.split('-').map(Number);
        selectedDate = new Date(Date.UTC(year, month - 1, day, 12));
        renderCalendar();
    });

    widget.querySelectorAll('[data-date-step]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedDate.setUTCDate(selectedDate.getUTCDate() + Number(button.dataset.dateStep));
            renderCalendar();
        });
    });

    widget.querySelectorAll('[data-month-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const dayOfMonth = selectedDate.getUTCDate();
            const targetMonth = new Date(Date.UTC(
                selectedDate.getUTCFullYear(),
                selectedDate.getUTCMonth() + Number(button.dataset.monthStep),
                1,
                12,
            ));
            const lastDay = new Date(Date.UTC(
                targetMonth.getUTCFullYear(),
                targetMonth.getUTCMonth() + 1,
                0,
                12,
            )).getUTCDate();

            targetMonth.setUTCDate(Math.min(dayOfMonth, lastDay));
            selectedDate = targetMonth;
            renderCalendar();
        });
    });

    widget.querySelector('[data-date-today]')?.addEventListener('click', () => {
        selectedDate = currentJakartaDate();
        renderCalendar();
    });

    renderCalendar();
});

document.querySelectorAll('[data-iqro-roster-select]').forEach((rosterSelect) => {
    const form = rosterSelect.closest('form');
    const indicatorSelect = form?.querySelector('[data-iqro-indicator-select]');
    if (!indicatorSelect) {
        return;
    }

    const syncIndicators = () => {
        const selectedOption = rosterSelect.options[rosterSelect.selectedIndex];
        const kelompok = selectedOption?.dataset.kelompok ?? '';
        let firstVisibleValue = null;

        Array.from(indicatorSelect.options).forEach((option) => {
            const matches = !kelompok || !option.dataset.kelompok || option.dataset.kelompok === kelompok;
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches && firstVisibleValue === null) {
                firstVisibleValue = option.value;
            }
        });

        const currentOption = indicatorSelect.options[indicatorSelect.selectedIndex];
        if (currentOption?.disabled && firstVisibleValue !== null) {
            indicatorSelect.value = firstVisibleValue;
        }
    };

    rosterSelect.addEventListener('change', syncIndicators);
    syncIndicators();
});

document.querySelectorAll('[data-attendance-date-input]').forEach((dateInput) => {
    dateInput.addEventListener('change', () => {
        dateInput.closest('form')?.requestSubmit();
    });
});

document.querySelectorAll('[data-attendance-select-all]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetStatus = button.dataset.attendanceSelectAll;
        const panel = button.closest('.database-action-panel') ?? document;
        panel.querySelectorAll(`input[type="radio"][value="${targetStatus}"]`).forEach((radio) => {
            radio.checked = true;
        });
    });
});

/* --------------------------------------------------------------------------
 * Validasi unggahan instan: begitu file dipilih, langsung tampil
 * "melebihi batas" bila ukuran/resolusi terlalu besar, dan form tidak dikirim.
 * ------------------------------------------------------------------------ */
const MAX_IMAGE_MEGAPIXELS = 50;

const formatBytes = (bytes) => {
    const megabytes = bytes / (1024 * 1024);
    const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: megabytes >= 1 ? 1 : 0 });

    return megabytes >= 1 ? `${number.format(megabytes)} MB` : `${number.format(bytes / 1024)} KB`;
};

const readImageSize = (file) => new Promise((resolve) => {
    if (!file.type.startsWith('image/')) {
        resolve(null);
        return;
    }

    const url = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
        resolve({ width: image.naturalWidth, height: image.naturalHeight });
        URL.revokeObjectURL(url);
    };
    image.onerror = () => {
        resolve(null);
        URL.revokeObjectURL(url);
    };
    image.src = url;
});

const acceptedExtensions = (input) => (input.getAttribute('accept') ?? '')
    .split(',')
    .map((item) => item.trim().toLowerCase())
    .filter(Boolean)
    .flatMap((item) => {
        if (item.startsWith('.')) return [item.slice(1)];
        if (item === 'image/jpeg') return ['jpg', 'jpeg'];
        if (item === 'image/png') return ['png'];
        if (item === 'image/webp') return ['webp'];
        if (item === 'application/pdf') return ['pdf'];
        if (item === 'image/*') return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        return [];
    });

document.querySelectorAll('input[type="file"][data-max-mb], input[type="file"][data-max-bytes]:not([aria-describedby])')
    .forEach((input) => {
        initSingleFilePicker(input);
        const maxBytes = input.dataset.maxMb
            ? Number(input.dataset.maxMb) * 1024 * 1024
            : Number(input.dataset.maxBytes);
        const maxLabel = formatBytes(maxBytes);
        const extensions = acceptedExtensions(input);

        let feedback = input.parentElement.querySelector(':scope > .upload-feedback');
        if (!feedback) {
            feedback = document.createElement('small');
            feedback.className = 'upload-feedback';
            feedback.setAttribute('aria-live', 'polite');
            input.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = `Maksimal ${maxLabel} per file.`;

        const setState = (message, state) => {
            feedback.textContent = message;
            feedback.classList.toggle('is-error', state === 'error');
            feedback.classList.toggle('is-success', state === 'success');
            input.classList.toggle('is-invalid', state === 'error');
            input.setAttribute('aria-invalid', String(state === 'error'));
            input.setCustomValidity(state === 'error' ? message : '');
        };

        const validate = async () => {
            const files = Array.from(input.files ?? []);
            if (files.length === 0) {
                setState(`Maksimal ${maxLabel} per file.`, '');
                return true;
            }

            const problems = [];
            for (const file of files) {
                const extension = file.name.split('.').pop().toLowerCase();
                if (extensions.length > 0 && !extensions.includes(extension)) {
                    problems.push(`${file.name}: format .${extension} tidak didukung (gunakan ${extensions.join(', ').toUpperCase()}).`);
                    continue;
                }
                if (file.size > maxBytes) {
                    problems.push(`${file.name} berukuran ${formatBytes(file.size)} — melebihi batas maksimal ${maxLabel}.`);
                    continue;
                }
                const size = await readImageSize(file);
                if (size && (size.width * size.height) / 1_000_000 > MAX_IMAGE_MEGAPIXELS) {
                    const megapixels = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 })
                        .format((size.width * size.height) / 1_000_000);
                    problems.push(`${file.name} beresolusi ${size.width}×${size.height} (${megapixels} MP) — melebihi batas ${MAX_IMAGE_MEGAPIXELS} MP.`);
                }
            }

            if (problems.length > 0) {
                setState(`✕ ${problems.join(' ')}`, 'error');
                return false;
            }

            const totalSize = files.reduce((sum, file) => sum + file.size, 0);
            setState(
                files.length === 1
                    ? `✓ ${files[0].name} (${formatBytes(totalSize)}) siap diunggah.`
                    : `✓ ${files.length} file (${formatBytes(totalSize)}) siap diunggah.`,
                'success',
            );
            return true;
        };

        input.addEventListener('change', () => {
            validate();
        });

        input.form?.addEventListener('submit', (event) => {
            if (input.classList.contains('is-invalid')) {
                event.preventDefault();
                event.stopImmediatePropagation();
                input.closest('details')?.setAttribute('open', '');
                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                input.reportValidity();
            }
        }, true);
    });

/* --------------------------------------------------------------------------
 * Isian form tidak hilang saat server menolak (validasi gagal):
 * nilai disimpan sementara saat submit, lalu dikembalikan bila ada error.
 * ------------------------------------------------------------------------ */
const FORM_DRAFT_KEY = 'sinar:last-submitted-form';

const formSignature = (form) => {
    const method = (form.getAttribute('method') ?? 'get').toLowerCase();
    const sameForms = Array.from(document.querySelectorAll('form'))
        .filter((candidate) => candidate.action === form.action
            && (candidate.getAttribute('method') ?? 'get').toLowerCase() === method);

    return `${window.location.pathname}|${form.action}|${sameForms.indexOf(form)}`;
};

const isDraftableField = (field) => field.name
    && !['_token', '_method'].includes(field.name)
    && !['file', 'password', 'hidden', 'submit', 'button', 'reset'].includes(field.type);

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || (form.getAttribute('method') ?? 'get').toLowerCase() !== 'post') {
        return;
    }

    const nameCounters = {};
    const fields = [];
    Array.from(form.elements).filter(isDraftableField).forEach((field) => {
        const position = nameCounters[field.name] ?? 0;
        nameCounters[field.name] = position + 1;
        fields.push({
            name: field.name,
            position,
            value: field.value,
            checked: field.type === 'checkbox' || field.type === 'radio' ? field.checked : null,
        });
    });

    try {
        sessionStorage.setItem(FORM_DRAFT_KEY, JSON.stringify({ signature: formSignature(form), fields }));
    } catch (error) {
        console.error('Isian form tidak dapat disimpan sementara.', error);
    }
});

(() => {
    let draft = null;
    try {
        draft = JSON.parse(sessionStorage.getItem(FORM_DRAFT_KEY) ?? 'null');
        sessionStorage.removeItem(FORM_DRAFT_KEY);
    } catch (error) {
        console.error('Isian form sementara tidak dapat dibaca.', error);
    }

    const errorBox = document.querySelector('.database-form-errors, .registration-errors');
    if (!draft || !errorBox) {
        return;
    }

    const form = Array.from(document.querySelectorAll('form'))
        .find((candidate) => formSignature(candidate) === draft.signature);
    if (!form) {
        return;
    }

    const groups = {};
    Array.from(form.elements).filter(isDraftableField).forEach((field) => {
        (groups[field.name] ??= []).push(field);
    });

    draft.fields.forEach(({ name, position, value, checked }) => {
        const field = groups[name]?.[position];
        if (!field) return;

        if (checked !== null) {
            field.checked = checked;
        } else {
            field.value = value;
        }
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });

    form.closest('details')?.setAttribute('open', '');
    form.querySelectorAll('details').forEach((details) => details.setAttribute('open', ''));

    const inlineErrors = errorBox.cloneNode(true);
    inlineErrors.classList.add('is-inline');
    form.prepend(inlineErrors);
    form.querySelectorAll('input[type="file"]').forEach((fileInput) => {
        const feedback = fileInput.parentElement.querySelector(':scope > .upload-feedback');
        if (feedback) {
            feedback.textContent = 'Isian lain sudah dikembalikan. Silakan pilih ulang file-nya (browser tidak mengizinkan file disimpan otomatis).';
            feedback.classList.remove('is-success', 'is-error');
            feedback.classList.add('is-warning');
        }
    });
    inlineErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
})();

/* Pencarian & filter peran kontak pada halaman Pesan (ala WhatsApp) */
document.querySelectorAll('.message-contact-panel').forEach((panel) => {
    const searchInput = panel.querySelector('[data-contact-search]');
    const roleButtons = Array.from(panel.querySelectorAll('[data-contact-role]'));
    const items = Array.from(panel.querySelectorAll('[data-contact-item]'));
    const emptyState = panel.querySelector('[data-contact-search-empty]');
    let activeRole = 'all';

    const applyFilter = () => {
        const query = (searchInput?.value ?? '').trim().toLocaleLowerCase();
        let visibleCount = 0;

        items.forEach((item) => {
            const matchesQuery = query === '' || item.textContent.toLocaleLowerCase().includes(query);
            const matchesRole = activeRole === 'all' || item.dataset.roleGroup === activeRole;
            const visible = matchesQuery && matchesRole;
            item.hidden = !visible;
            visibleCount += Number(visible);
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0 || items.length === 0;
        }
    };

    searchInput?.addEventListener('input', applyFilter);
    roleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeRole = button.dataset.contactRole ?? 'all';
            roleButtons.forEach((btn) => btn.classList.toggle('is-active', btn === button));
            applyFilter();
        });
    });
});

/* Auto-scroll ke pesan terbaru, Enter untuk kirim, dan sinkronisasi pesan otomatis */
const messageThread = document.querySelector('[data-message-thread]');
if (messageThread) {
    messageThread.scrollTop = messageThread.scrollHeight;

    let lastSnapshot = '';
    const formatDateDivider = (dateStr) => {
        const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
        const yesterdayDate = new Date(Date.now() - 86400000).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
        if (dateStr === today) return 'Hari ini';
        if (dateStr === yesterdayDate) return 'Kemarin';
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    };

    const pollThread = async () => {
        try {
            const response = await fetch(messageThread.dataset.threadUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const { messages } = await response.json();
            const snapshot = JSON.stringify(messages);
            if (lastSnapshot === '') {
                lastSnapshot = snapshot;
                return;
            }
            if (snapshot === lastSnapshot) return;
            lastSnapshot = snapshot;

            const wasAtBottom = messageThread.scrollHeight - messageThread.scrollTop - messageThread.clientHeight < 60;
            const csrfToken = messageThread.dataset.csrfToken ?? '';
            messageThread.replaceChildren();

            if (messages.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'message-thread-empty';
                empty.textContent = 'Belum ada percakapan. Kirim pesan pertama.';
                messageThread.append(empty);
                return;
            }

            let lastDate = null;
            messages.forEach((msg) => {
                if (lastDate !== msg.date) {
                    const divider = document.createElement('div');
                    divider.className = 'message-date-divider';
                    const span = document.createElement('span');
                    span.textContent = formatDateDivider(msg.date);
                    divider.append(span);
                    messageThread.append(divider);
                    lastDate = msg.date;
                }

                const article = document.createElement('article');
                article.className = `message-bubble ${msg.is_own ? 'is-own' : 'is-incoming'}`;

                const p = document.createElement('p');
                p.textContent = msg.body;

                const meta = document.createElement('div');
                meta.className = 'message-bubble-meta';

                const time = document.createElement('time');
                time.textContent = msg.time;
                meta.append(time);

                if (msg.is_own) {
                    const tick = document.createElement('span');
                    tick.className = `message-read-tick ${msg.read ? 'is-read' : ''}`;
                    tick.title = msg.read ? 'Sudah dibaca' : 'Terkirim';
                    tick.textContent = msg.read ? '✓✓' : '✓';
                    meta.append(tick);

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = msg.delete_url;
                    form.className = 'message-delete-form';
                    form.addEventListener('submit', (e) => {
                        if (!window.confirm('Hapus pesan ini?')) e.preventDefault();
                    });

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = csrfToken;

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';

                    const delBtn = document.createElement('button');
                    delBtn.type = 'submit';
                    delBtn.className = 'message-delete-button';
                    delBtn.title = 'Hapus pesan';
                    delBtn.setAttribute('aria-label', 'Hapus pesan');
                    delBtn.textContent = '×';

                    form.append(tokenInput, methodInput, delBtn);
                    meta.append(form);
                }

                article.append(p, meta);
                messageThread.append(article);
            });

            if (wasAtBottom) {
                messageThread.scrollTop = messageThread.scrollHeight;
            }
        } catch (error) {
            console.error('Gagal menyinkronkan percakapan.', error);
        }
    };

    pollThread();
    window.setInterval(pollThread, 5000);
}

const composeForm = document.querySelector('[data-message-compose]');
const composeTextarea = composeForm?.querySelector('textarea');
composeTextarea?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        if (composeTextarea.value.trim() !== '') {
            composeForm.requestSubmit();
        }
    }
});

document.querySelectorAll('[data-payment-mode-root]').forEach((root) => {
    const buttons = Array.from(root.querySelectorAll('[data-payment-mode-btn]'));
    const panels = Array.from(root.querySelectorAll('[data-payment-mode-panel]'));

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.paymentModeBtn;
            buttons.forEach((item) => {
                const active = item.dataset.paymentModeBtn === target;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach((panel) => {
                panel.hidden = panel.dataset.paymentModePanel !== target;
            });
        });
    });
});

(() => {
    const EXCLUDED_TYPES = new Set(['hidden', 'search', 'submit', 'button', 'reset']);

    const findFieldLabelElement = (field) => {
        if (field.id && field.form) {
            const byFor = field.form.querySelector(`label[for="${CSS.escape(field.id)}"]`);
            if (byFor) {
                return byFor;
            }
        }
        return field.closest('label');
    };

    const getCleanLabelText = (field) => {
        if (field.dataset.fieldLabel) {
            return field.dataset.fieldLabel.trim();
        }
        const labelEl = findFieldLabelElement(field);
        if (labelEl) {
            const clone = labelEl.cloneNode(true);
            clone.querySelectorAll(
                'input, select, textarea, button, .required-marker, .searchable-select-wrap, .registration-upload-feedback, .field-validation-error, .password-toggle, .registration-password-wrap'
            ).forEach((el) => el.remove());
            const text = clone.textContent
                .replace(/\s+/g, ' ')
                .replace(/\s*\(opsional[^)]*\)/gi, '')
                .replace(/\s*\(JPG\/PNG[^)]*\)/gi, '')
                .replace(/\s*\(PDF\/JPG[^)]*\)/gi, '')
                .replace(/\s*\*+\s*$/, '')
                .trim();
            if (text !== '') {
                return text;
            }
        }
        return (
            field.getAttribute('aria-label')
            || field.getAttribute('placeholder')
            || 'Kolom ini'
        ).replace(/\s*\(opsional\)/gi, '').trim();
    };

    const syncRequiredMarker = (field) => {
        if (EXCLUDED_TYPES.has(field.type) || field.disabled) {
            return;
        }
        const labelEl = findFieldLabelElement(field);
        if (!labelEl || labelEl.classList.contains('check-label')) {
            return;
        }
        const isRequired = field.required || field.dataset.wasRequired === '1';
        const existingMarker = labelEl.querySelector('.required-marker');
        if (!isRequired) {
            if (existingMarker && existingMarker.dataset.autoRequired === '1') {
                existingMarker.remove();
            }
            return;
        }
        if (existingMarker || labelEl.textContent.includes('*')) {
            return;
        }

        const marker = document.createElement('span');
        marker.className = 'required-marker';
        marker.dataset.autoRequired = '1';
        marker.setAttribute('aria-hidden', 'true');
        marker.textContent = '*';

        const textNode = Array.from(labelEl.childNodes).find(
            (node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== ''
        );
        if (textNode) {
            textNode.after(marker);
            return;
        }

        const firstCaption = labelEl.querySelector(':scope > span:not(.required-marker):not(.registration-password-wrap):not(.searchable-select-wrap), :scope > strong');
        if (firstCaption) {
            firstCaption.append(marker);
            return;
        }

        labelEl.append(marker);
    };

    const buildIndonesianValidationMessage = (field) => {
        const label = getCleanLabelText(field);
        const v = field.validity;

        if (v.valueMissing) {
            if (field.type === 'file') {
                return `${label} wajib diunggah.`;
            }
            if (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'radio') {
                return `${label} wajib dipilih.`;
            }
            return `${label} wajib diisi.`;
        }
        if (v.typeMismatch) {
            return field.type === 'email'
                ? `Format ${label} tidak valid (contoh: nama@email.com).`
                : `Format ${label} tidak valid.`;
        }
        if (v.patternMismatch) {
            if (field.maxLength === 16) {
                return `${label} harus terdiri dari 16 digit angka.`;
            }
            if (field.maxLength === 10) {
                return `${label} harus terdiri dari 10 digit angka.`;
            }
            return `Format ${label} tidak sesuai.`;
        }
        if (v.tooShort) {
            return `${label} minimal ${field.minLength} karakter.`;
        }
        if (v.tooLong) {
            return `${label} maksimal ${field.maxLength} karakter.`;
        }
        if (v.rangeUnderflow) {
            return `${label} minimal ${field.min}.`;
        }
        if (v.rangeOverflow) {
            return `${label} maksimal ${field.max}.`;
        }
        return `${label} belum valid.`;
    };

    const showInlineError = (field, message) => {
        field.classList.add('is-field-invalid');
        const container = field.closest('.password-field, .registration-password-wrap, .searchable-select-wrap') || field;
        let errorEl = container.nextElementSibling;
        if (!errorEl || !errorEl.classList.contains('field-validation-error')) {
            errorEl = document.createElement('small');
            errorEl.className = 'field-validation-error';
            errorEl.setAttribute('role', 'alert');
            container.after(errorEl);
        }
        errorEl.textContent = message;
    };

    const clearInlineError = (field) => {
        field.classList.remove('is-field-invalid');
        const container = field.closest('.password-field, .registration-password-wrap, .searchable-select-wrap') || field;
        const nextEl = container.nextElementSibling;
        if (nextEl && nextEl.classList.contains('field-validation-error')) {
            nextEl.remove();
        }
    };

    const syncConditionalFields = (form) => {
        const methodSelect = form.querySelector('select[name="metode"]');
        const proofInput = form.querySelector('input[data-required-when-method]');
        if (methodSelect && proofInput) {
            proofInput.required = methodSelect.value === proofInput.dataset.requiredWhenMethod;
            syncRequiredMarker(proofInput);
            if (!proofInput.required) {
                proofInput.setCustomValidity('');
                clearInlineError(proofInput);
            }
        }
    };

    document.querySelectorAll('form').forEach((form) => {
        syncConditionalFields(form);
        form.querySelectorAll('input, select, textarea').forEach(syncRequiredMarker);
    });

    document.addEventListener('invalid', (event) => {
        const field = event.target;
        if (!(field instanceof HTMLElement) || !field.matches('input, select, textarea')) {
            return;
        }
        if (EXCLUDED_TYPES.has(field.type)) {
            return;
        }
        const message = buildIndonesianValidationMessage(field);
        field.setCustomValidity(message);
        showInlineError(field, message);
    }, true);

    const handleFieldUpdate = (event) => {
        const field = event.target;
        if (!(field instanceof HTMLElement) || !field.matches('input, select, textarea')) {
            return;
        }
        field.setCustomValidity('');
        if (field.form) {
            syncConditionalFields(field.form);
        }
        if (field.checkValidity()) {
            clearInlineError(field);
        }
    };

    document.addEventListener('input', handleFieldUpdate, true);
    document.addEventListener('change', handleFieldUpdate, true);
})();
