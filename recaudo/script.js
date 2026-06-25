const isLeapYear = (year) => {

    return (
        (year % 4 === 0 &&
        year % 100 !== 0) ||

        (year % 400 === 0)
    );
};

const getFebDays = (year) => {

    return isLeapYear(year)
        ? 29
        : 28;
};

const month_names = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre'
];

let month_picker =
    document.querySelector('#month-picker');

const timeFormate =
    document.querySelector('.time-formate');

const dateFormate =
    document.querySelector('.date-formate');

/* GENERAR CALENDARIO */
const generateCalendar = (month, year) => {

    let calendar_days =
        document.querySelector('.calendar-days');

    calendar_days.innerHTML = '';

    let days_of_month = [
        31,
        getFebDays(year),
        31,
        30,
        31,
        30,
        31,
        31,
        30,
        31,
        30,
        31
    ];

    let currentDate = new Date();

    /* MES */
    month_picker.innerHTML =
        month_names[month];

    /* AÑO */
    document.querySelector('#year').innerHTML =
        year;

    let first_day =
        new Date(year, month);

    for (
        let i = 0;
        i <= days_of_month[month] +
        first_day.getDay() - 1;
        i++
    ) {

        let day =
            document.createElement('div');

        if (i >= first_day.getDay()) {

            day.innerHTML =
                i -
                first_day.getDay() +
                1;

            /* DIA ACTUAL */
            if (
                i - first_day.getDay() + 1 ===
                currentDate.getDate() &&

                year ===
                currentDate.getFullYear() &&

                month ===
                currentDate.getMonth()
            ) {

                day.classList.add(
                    'current-date'
                );
            }
        }

        calendar_days.appendChild(day);
    }
};

/* FECHA ACTUAL */
let currentDate = new Date();

let currentMonth = {
    value: currentDate.getMonth()
};

let currentYear = {
    value: currentDate.getFullYear()
};

/* MOSTRAR CALENDARIO */
generateCalendar(
    currentMonth.value,
    currentYear.value
);

/* MES ANTERIOR */
document.querySelector('#prev-month').onclick = () => {

    currentMonth.value--;

    if (currentMonth.value < 0) {

        currentMonth.value = 11;

        currentYear.value--;
    }

    generateCalendar(
        currentMonth.value,
        currentYear.value
    );
};

/* SIGUIENTE MES */
document.querySelector('#next-month').onclick = () => {

    currentMonth.value++;

    if (currentMonth.value > 11) {

        currentMonth.value = 0;

        currentYear.value++;
    }

    generateCalendar(
        currentMonth.value,
        currentYear.value
    );
};

/* AÑO ANTERIOR */
document.querySelector('#prev-year').onclick = () => {

    currentYear.value--;

    generateCalendar(
        currentMonth.value,
        currentYear.value
    );
};

/* SIGUIENTE AÑO */
document.querySelector('#next-year').onclick = () => {

    currentYear.value++;

    generateCalendar(
        currentMonth.value,
        currentYear.value
    );
};

/* RELOJ */
setInterval(() => {

    const timer = new Date();

    let time =
        `${timer.getHours()}`.padStart(2, '0')
        + ':'
        + `${timer.getMinutes()}`.padStart(2, '0')
        + ':'
        + `${timer.getSeconds()}`.padStart(2, '0');

    timeFormate.textContent = time;

}, 1000);

/* FECHA */
const showDate =
    new Intl.DateTimeFormat(
        'es-ES',
        {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }
    ).format(currentDate);

dateFormate.textContent = showDate;