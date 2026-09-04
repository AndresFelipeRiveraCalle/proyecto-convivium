const tasaAnual = document.getElementById('tasa_anual');
const tasaMensual = document.getElementById('tasa_mensual');

function calcularTasaMensual() {

    const anual = parseFloat(tasaAnual.value);

    if (isNaN(anual) || anual < 0) {

        tasaMensual.value = '';

        return;
    }

    const tasaDecimal = anual / 100;

    const mensualDecimal =
        Math.pow(1 + tasaDecimal, 1 / 12) - 1;

    const mensualPorcentaje =
        mensualDecimal * 100;

    tasaMensual.value =
        mensualPorcentaje.toFixed(6);
}

tasaAnual.addEventListener(
    'input',
    calcularTasaMensual
);