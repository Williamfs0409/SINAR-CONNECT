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

        document.querySelectorAll('[data-navigation-group], [data-module-group]').forEach((group) => {
            const visibleLinks = Array.from(group.querySelectorAll('[data-dashboard-searchable]'))
                .some((link) => !link.classList.contains('is-search-hidden'));
            group.classList.toggle('is-search-hidden', !visibleLinks);
        });

        navigationSearchEmpty?.classList.toggle('is-visible', query !== '' && navigationMatches === 0);
    });
}

document.querySelectorAll('[data-record-search]').forEach((input) => {
    const section = input.closest('.database-list-section');
    const rows = Array.from(section?.querySelectorAll('[data-record-row]') ?? []);
    const emptyState = section?.querySelector('[data-record-search-empty]');

    input.addEventListener('input', () => {
        const query = input.value.trim().toLocaleLowerCase();
        let visibleRows = 0;

        rows.forEach((row) => {
            const matches = row.textContent.toLocaleLowerCase().includes(query);
            row.hidden = !matches;
            visibleRows += Number(matches);
        });

        if (emptyState) {
            emptyState.hidden = visibleRows !== 0;
        }
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

const uploadValidatorsByForm = new Map();

document.querySelectorAll('[data-max-bytes]').forEach((fileInput) => {
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
