import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import zhLocale from '@fullcalendar/core/locales/zh-cn';

import '@fullcalendar/core/main.css';
import '@fullcalendar/daygrid/main.css';
// import '@fullcalendar/timegrid/main.css';
// import '@fullcalendar/list/main.css';

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin],
        locale: zhLocale,
        eventTimeFormat: { // like '14:30'
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        displayEventEnd: true,
        eventLimit: 6,
        customButtons: {
            reserve: {
                text: reserveText,
                click: function() {
                    window.location.href = './reserve';
                }
            }
        },
        header: {
            right:  'reserve prev,next'
        }
    });

    calendar.addEventSource(reservations);
    calendar.render();
});