(() => {
    const formatTwelveHourValue = (value, includeDate) => {
        const match = String(value || '').replace('T', ' ').match(/^(?:(\d{4}-\d{2}-\d{2})\s+)?(\d{1,2}):(\d{2})/);
        if (!match) return value;

        const hour = Number(match[2]);
        const meridiem = hour >= 12 ? 'PM' : 'AM';
        const hour12 = String(hour % 12 || 12).padStart(2, '0');
        const time = `${hour12}:${match[3]} ${meridiem}`;
        return includeDate && match[1] ? `${match[1]} ${time}` : time;
    };

    const initializeDatePickers = () => {
        if (!window.tempusDominus?.TempusDominus) return;

        document.querySelectorAll('input[type="date"], input[type="time"], input[type="datetime-local"]').forEach(input => {
            if (input.dataset.tempusBound === 'true') return;

            const isDateTime = input.type === 'datetime-local';
            const isTime = input.type === 'time';
            const format = isDateTime ? 'yyyy-MM-dd hh:mm T' : isTime ? 'hh:mm T' : 'yyyy-MM-dd';
            const originalType = input.type;
            const originalValue = input.value;
            const wrapper = document.createElement('div');
            const toggle = document.createElement('button');

            wrapper.className = 'sicms-date-picker-group';
            wrapper.id = `sicms-picker-${input.id || input.name || 'field'}`.replace(/[^A-Za-z0-9_-]/g, '-');
            toggle.type = 'button';
            toggle.className = 'sicms-date-picker-toggle';
            toggle.setAttribute('aria-label', 'Open date picker');
            toggle.innerHTML = '<i class="bi bi-calendar3" aria-hidden="true"></i>';

            input.type = 'text';
            input.value = isTime || isDateTime
                ? formatTwelveHourValue(originalValue, isDateTime)
                : originalValue;
            input.classList.add('sicms-date-picker-input');
            input.dataset.tempusBound = 'true';
            input.dataset.originalInputType = originalType;
            input.autocomplete = 'off';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(toggle);

            const restrictions = {};
            if (input.min) restrictions.minDate = new Date(input.min.replace('T', ' '));
            if (input.max) restrictions.maxDate = new Date(input.max.replace('T', ' '));

            const picker = new window.tempusDominus.TempusDominus(wrapper, {
                localization: { format },
                restrictions,
                display: {
                    icons: {
                        type: 'icons',
                        time: 'bi bi-clock',
                        date: 'bi bi-calendar3',
                        up: 'bi bi-chevron-up',
                        down: 'bi bi-chevron-down',
                        previous: 'bi bi-chevron-left',
                        next: 'bi bi-chevron-right',
                        today: 'bi bi-calendar-check',
                        clear: 'bi bi-trash3',
                        close: 'bi bi-x-lg',
                    },
                    buttons: { today: !isTime, clear: true, close: true },
                    components: {
                        calendar: !isTime,
                        date: !isTime,
                        month: !isTime,
                        year: !isTime,
                        decades: !isTime,
                        hours: true,
                        minutes: true,
                        seconds: false,
                    },
                },
            });
            toggle.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                picker.show();
            });
            input.addEventListener('click', event => {
                event.stopPropagation();
                picker.show();
            });
        });
    };

    const start = () => {
        initializeDatePickers();
        document.addEventListener('daris:ajax-success', initializeDatePickers);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
