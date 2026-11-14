import './bootstrap';
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const passwordInput = document.querySelector('#password');
const passwordToggle = document.querySelector('.password-toggle');

passwordToggle?.addEventListener('click', () => {
    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';
    passwordToggle.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    passwordToggle.classList.toggle('is-visible', isPassword);
    passwordToggle.setAttribute('aria-pressed', String(isPassword));
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
