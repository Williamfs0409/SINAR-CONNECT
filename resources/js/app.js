import './bootstrap';
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const databaseNavigation = document.querySelector('.database-navigation');
const activeDatabaseLink = databaseNavigation?.querySelector('[aria-current="page"]');

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

    dashboardSearch.addEventListener('input', () => {
        const query = dashboardSearch.value.trim().toLocaleLowerCase();
        let navigationMatches = 0;

        searchableLinks.forEach((link) => {
            const matches = link.textContent.toLocaleLowerCase().includes(query);
            link.classList.toggle('is-search-hidden', !matches);

            if (matches && navigationLinkSet.has(link)) {
                navigationMatches += 1;
            }
        });

        navigationSearchEmpty?.classList.toggle('is-visible', query !== '' && navigationMatches === 0);
    });
}

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

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-open-editor]').forEach((button) => {
    const editor = document.getElementById(button.dataset.openEditor);

    if (!editor) {
        console.error('Student profile editor was not found.');
        return;
    }

    button.addEventListener('click', () => {
        editor.open = true;
        editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        editor.querySelector('input, textarea, select')?.focus();
    });
});

document.querySelectorAll('[data-max-bytes][data-max-pixels]').forEach((fileInput) => {
    const feedback = document.getElementById(fileInput.getAttribute('aria-describedby'));
    const form = fileInput.form;

    if (!feedback || !form) {
        return;
    }

    const maxBytes = Number(fileInput.dataset.maxBytes);
    const maxPixels = Number(fileInput.dataset.maxPixels);
    let submitAfterValidation = false;

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

    const readImageDimensions = (file) => new Promise((resolve) => {
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        const finish = (dimensions) => {
            URL.revokeObjectURL(objectUrl);
            resolve(dimensions);
        };

        image.addEventListener('load', () => {
            finish({ width: image.naturalWidth, height: image.naturalHeight });
        }, { once: true });
        image.addEventListener('error', () => finish(null), { once: true });
        image.src = objectUrl;
    });

    const validateFile = async (file) => {
        if (!file) {
            showFeedback('File maksimal 5 MB. Gambar maksimal 20 megapiksel.');
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

        if (['jpg', 'jpeg', 'png'].includes(extension)) {
            showFeedback('Sedang memeriksa ukuran gambar...');
            const dimensions = await readImageDimensions(file);

            if (fileInput.files?.[0] !== file) {
                return false;
            }

            if (!dimensions) {
                showFeedback('Gambar tidak dapat dibaca. Pilih file gambar lain.', 'error');
                return false;
            }

            if (dimensions.width * dimensions.height > maxPixels) {
                showFeedback(
                    `Resolusi gambar ${dimensions.width} × ${dimensions.height} melebihi batas 20 megapiksel. Pilih gambar lain.`,
                    'error',
                );
                return false;
            }
        }

        showFeedback(`File ${fileSize} siap diunggah.`, 'success');
        return true;
    };

    fileInput.addEventListener('change', () => {
        void validateFile(fileInput.files?.[0]);
    });

    form.addEventListener('submit', async (event) => {
        if (submitAfterValidation) {
            submitAfterValidation = false;
            return;
        }

        const file = fileInput.files?.[0];
        if (!file) {
            return;
        }

        event.preventDefault();
        if (! await validateFile(file)) {
            fileInput.reportValidity();
            return;
        }

        submitAfterValidation = true;
        if (event.submitter) {
            form.requestSubmit(event.submitter);
        } else {
            form.requestSubmit();
        }
    });
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
