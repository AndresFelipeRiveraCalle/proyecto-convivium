/**
 * ============================================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: zonas.js
 * DESCRIPCIÓN: Archivo JavaScript para manejo de eventos de los elemenos de las Zonas comunes.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-08-08
 * ============================================================================================
 */

// Identificar todos los elementos que contienen la clase .btn-eliminar
const botonesEliminar = document.querySelectorAll('.btn-eliminar');

// Ejecutar este bloque de código por cada elemento identificado
botonesEliminar.forEach((boton, indice) => {

    // Escuchar la acción de cada elemento con el evento 'click'
    boton.addEventListener('click', () => {
        const confirmar = confirm('¿Está seguro de que desea eliminar esta zona? Esta acción no se puede deshacer.');

        if (!confirmar) {
            // Detener el flujo del elemento que contiene la clase .btn-eliminar
            event.preventDefault(); 
        }
    })
})

// Identificar todos los elementos que contienen la clase .mensaje
const mensaje = document.querySelector('.zc-mensaje');

// Comprovar que exista el elemento
if (mensaje) {

    // Ejecutar esta instrucción en 5 segundos
    setTimeout(() => {

        // Agregar la clase ocultar al elemento para que entre el CSS 
        mensaje.classList.add('zc-mensaje-ocultar');

        // Esperar a que termine la transición de opacidad
        setTimeout(() => {

            // Eliminar completamente el mensaje del documento
            mensaje.remove();

        }, 500);
    }, 5000);
}

// Identificar el boton btn-agregar-horario
const btnAgregarHorario = document.querySelector('#btn-agregar-horario');

// Campos básicos de la zona
const nombre = document.querySelector('#nombre');
const descripcion = document.querySelector('#descripcion');
const capacidad = document.querySelector('#capacidad');

// Sección de horarios
const seccionHorarios = document.querySelector('#seccion-horarios');
const mensajeHorarios = document.querySelector('#mensaje-horarios');

// console.log('Mensaje horarios:', mensajeHorarios);

/* console.log('Nombre:', nombre);
console.log('Descripción:', descripcion);
console.log('Capacidad:', capacidad);
console.log('Sección horarios:', seccionHorarios);*/

// Determinar si los datos básicos están completos para habilitar o deshabilitar la zona de horarios disponibles
function verificarDatosZona() {

    // Obtener los valores actuales de los campos
    const nombreCompleto = nombre.value.trim();
    const descripcionCompleta = descripcion.value.trim();
    const capacidadCompleta = capacidad.value.trim();

    // Verificar que los tres campos tengan información
    const datosCompletos =
        nombreCompleto !== '' &&
        descripcionCompleta !== '' &&
        capacidadCompleta !== '';

    // Habilitar o deshabilitar la sección de horarios
    seccionHorarios.disabled = !datosCompletos;

    // Mostrar u ocultar el mensaje informativo
    if (datosCompletos) {
        mensajeHorarios.style.display = 'none';
    } else {
        mensajeHorarios.style.display = 'block';
    }
}

// Escuchar los cambios de los tres campos input
nombre.addEventListener('input', verificarDatosZona);
descripcion.addEventListener('input', verificarDatosZona);
capacidad.addEventListener('input', verificarDatosZona);

// Revisar si los datos de la zona están completos 
verificarDatosZona();

// Contenedor donde se mostrarán visualmente los horarios configurados
const horariosConfigurados = document.querySelector('#horarios-configurados');

// Identificar el título de horarios configurados
const tituloHorarios = document.querySelector('#titulo-horarios');

// Ocultar inicialmente el título
tituloHorarios.style.display = 'none';

// Contenedor donde se almacenarán los inputs ocultos para PHP
const horariosInputs = document.querySelector('#horarios-inputs');

/* console.log('Contenedor visual:', horariosConfigurados);
console.log('Contenedor inputs:', horariosInputs);*/ 

// Función para convertir horas en minutos
function convertirAMinutos(hora) {
    const [horas, minutos] = hora.split(':');
    return (parseInt(horas) * 60) + parseInt(minutos);
}

// Almacenar todos los horarios agregados por el usuario
const horariosAgregados = [];

// Cargar los horarios existentes cuando se edita una zona
const inputsHorarios = document.querySelectorAll('#horarios-inputs input[type="hidden"]');

// Recorrer los inputs ocultos de tres en tres (día, inicio y fin)
for (let i = 0; i < inputsHorarios.length; i += 3) {

    horariosAgregados.push({
        dia_semana: parseInt(inputsHorarios[i].value),
        hora_inicio: inputsHorarios[i + 1].value,
        hora_fin: inputsHorarios[i + 2].value
    });
}

// Nombres de los días de la semana
const nombresDias = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo'
};

// Cargar los horarios existentes cuando se está editando una zona
if (typeof horariosExistentes !== 'undefined' && horariosExistentes.length > 0) {

    horariosExistentes.forEach((horario) => {

        horariosAgregados.push({
            dia_semana: Number(horario.dia_semana),
            hora_inicio: horario.hora_inicio.substring(0, 5),
            hora_fin: horario.hora_fin.substring(0, 5)
        });
    });

    // Mostrar los horarios existentes
    actualizarHorarios();
}

// Función actualizarHorarios 
// Toma lo que actualmente existe en horariosAgregados y reconstruir la parte visual y los input hidden.
function actualizarHorarios() {
    
    // Eliminar los horarios visuales anteriores sin eliminar el título "Horarios Configurados"
    horariosConfigurados
        .querySelectorAll('.horario-visual')
        .forEach((elemento) => {
            elemento.remove();
        });
    
    // Eliminar los inputs ocultos anteriores
    horariosInputs.innerHTML = '';

    // Mostrar u ocultar el título según existan horarios configurados
    if (horariosAgregados.length === 0) {
        tituloHorarios.style.display = 'none';
    } else {
        tituloHorarios.style.display = 'block';
    }

    // Recorrer los horarios agregados
    horariosAgregados.forEach((horario, indice) => {

        // ==============================
        // REPRESENTACIÓN VISUAL
        // ==============================

        // Crear elemento visual
        const horarioVisual = document.createElement('div');

        // Identificar el elemento como horario visual
        horarioVisual.classList.add('zc-horario-visual');  

        // Obtener el nombre del día
        const nombreDia = nombresDias[horario.dia_semana];

        // Mostrar la información del horario
        horarioVisual.textContent = `${nombreDia} - ${horario.hora_inicio} - ${horario.hora_fin}`;

        // Crear botón para eliminar el horario 
        const botonEliminar = document.createElement('button');

        // Indicar que es un botón y agregar el texto que verá el usuario
        botonEliminar.type = 'button';
        botonEliminar.textContent = 'Eliminar';          

        // Guardar el índice del horario visual en el botón para asociarlo
        botonEliminar.dataset.indice = indice;

        // Escuchar el click del botón eliminar
        botonEliminar.addEventListener('click', () => {
            
            // Obtener el índice asociado al botón
            const indiceHorario = Number(botonEliminar.dataset.indice);

            /* console.log('Botón eliminar presionado');;
            console.log('Índice del horario:', indiceHorario);;;
            console.log('Horario seleccionado:', horariosAgregados[indiceHorario]);*/
            
            // Eliminar el horario del arreglo
            horariosAgregados.splice(indiceHorario, 1);

            // Reconstruir la interfaz
            actualizarHorarios();

            console.log('Horarios después de eliminar:', horariosAgregados);
        });

        // Agregar el botón al horario visual
        horarioVisual.appendChild(botonEliminar);

        // Agregar al contenedor
        horariosConfigurados.appendChild(horarioVisual);

        // ==============================
        // INPUTS OCULTOS
        // ==============================

        // Crear input oculto para el día
        const inputDia = document.createElement('input');

        inputDia.type = 'hidden';
        inputDia.name = `horarios[${indice}][dia_semana]`;
        inputDia.value = horario.dia_semana;

        // Crear input oculto para la hora de inicio
        const inputInicio = document.createElement('input');

        inputInicio.type = 'hidden';
        inputInicio.name = `horarios[${indice}][hora_inicio]`;
        inputInicio.value = horario.hora_inicio;

        // Crear input oculto para la hora de fin 
        const inputFin = document.createElement('input');

        inputFin.type = 'hidden';
        inputFin.name = `horarios[${indice}][hora_fin]`;
        inputFin.value = horario.hora_fin;

        // Agregar los tres inputs al contenedor
        horariosInputs.appendChild(inputDia);
        horariosInputs.appendChild(inputInicio);
        horariosInputs.appendChild(inputFin);
    });
}

// Obtener el formulario mediante el id
const formularioZona = document.getElementById('form-zona');

// Capturar el evento click del botón registrar zona del formulario
formularioZona.addEventListener('submit', (evento) => {

     // Verificar que exista al menos un horario configurado
    if (horariosAgregados.length === 0) {

        // Evitar que el formulario se envíe
        evento.preventDefault();

        alert('Debe agregar al menos un horario para registrar la zona.');

        return;
    }
});

// Escuchar el evento click del botón
btnAgregarHorario.addEventListener('click', () => {

    // 1. Obtener días seleccionados
    const diasSeleccionados = document.querySelectorAll('input[name="dias[]"]:checked'); // Elementos input que tengan ese name, seleccionados

    // console.log('Días seleccionados:', diasSeleccionados);

    // 2. Obtener hora inicio
    const horaInicio = document.querySelector('#hora_inicio').value;

    // Obtener hora fin
    const horaFin = document.querySelector('#hora_fin').value;

    /* console.log('Hora inicio:', horaInicio);
    console.log('Hora fin:', horaFin);*/ 

    // 3. Validaciones básicas

    // Validar que se haya seleccionado al menos un día
    if (diasSeleccionados.length === 0) {
        alert('Debe seleccionar al menos un día.');
        return;
    }

    // Validar que se haya seleccionado una hora de inicio
    if (horaInicio === '') {
        alert('Debe seleccionar una hora de inicio.');
        return;
    }

    // Validar que se haya seleccionado una hora de fin
    if (horaFin === '') {
        alert('Debe seleccionar una hora de fin.');
        return;
    }

    // Convertir las horas a minutos
    const horaInicioMinutos = convertirAMinutos(horaInicio);
    const horaFinMinutos = convertirAMinutos(horaFin);

    // Validar que la hora de fin sea posterior a la hora de inicio.
    if (horaFinMinutos <= horaInicioMinutos) {
        alert('La hora de fin no debe ser menor o igual a la hora de inicio.');
        return;
    }

    // Crear un arreglo para almacenar temporalmente los nuevos horarios
    const nuevosHorarios = [];

    // Recorrer cada día seleccionado
    diasSeleccionados.forEach((dia) => {

        // Crear el objeto correspondiente al horario
        const horario = {
            dia_semana: Number(dia.value),
            hora_inicio: horaInicio,
            hora_fin: horaFin
        };

        // Agregar el horario al arreglo
        nuevosHorarios.push(horario);
    });

    // Indicar si los nuevos horarios son válidos
    let horariosValidos = true;
    // Almacenar el tipo de conflicto encontrado
    let tipoConflicto = null;
    // Almacenar el día donde ocurrió el conflicto
    let diasConflicto = [];

    // Recorrer cada nuevo horario
    nuevosHorarios.forEach((nuevoHorario) => {

        // Comparar el nuevo horario contra cada horario ya agregado
        horariosAgregados.forEach((horarioAgregado) => {

            /* console.log('Nuevo horario:', nuevoHorario);
            console.log('Horario agregado:', horarioAgregado);*/
            
            // Comprobar si ambos horarios pertenecen al mismo día
            const mismoDia = nuevoHorario.dia_semana === horarioAgregado.dia_semana;

            // console.log('¿Es el mismo día?:', mismoDia);
            
            // Convertir las horas del nuevo horario a minutos
            const nuevoInicioMinutos = convertirAMinutos(nuevoHorario.hora_inicio);
            const nuevoFinMinutos = convertirAMinutos(nuevoHorario.hora_fin);

            // Convertir las horas del horario existente a minutos
            const existenteInicioMinutos = convertirAMinutos(horarioAgregado.hora_inicio);
            const existenteFinMinutos = convertirAMinutos(horarioAgregado.hora_fin);

            /*console.log('Nuevo inicio:', nuevoInicioMinutos);
            console.log('Nuevo fin:', nuevoFinMinutos);

            console.log('Existente inicio:', existenteInicioMinutos);
            console.log('Existente fin:', existenteFinMinutos);*/

            // Primera condición del solapamiento: El nuevo horario debe comenzar antes de que termine el existente
            const nuevoEmpiezaAntesDelFinExistente = nuevoInicioMinutos < existenteFinMinutos;

            // Segunda condición del solapamiento: El nuevo horario debe terminar después de que comience el existente
            const nuevoTerminaDespuesDelInicioExistente = nuevoFinMinutos > existenteInicioMinutos;

            /* console.log('¿Nuevo empieza antes del fin existente?:', nuevoEmpiezaAntesDelFinExistente);
            console.log('¿Nuevo termina después del inicio existente?:', nuevoTerminaDespuesDelInicioExistente);*/

            // Comprobar si el nuevo horario es exactamente igual al existente
            const esDuplicado = mismoDia && nuevoInicioMinutos === existenteInicioMinutos && nuevoFinMinutos === existenteFinMinutos;

            //console.log('¿Es un duplicado?:', esDuplicado);

            // Determinar si existe solapamiento
            const haySolapamiento = mismoDia && nuevoEmpiezaAntesDelFinExistente && nuevoTerminaDespuesDelInicioExistente;

            //console.log('¿Hay solapamiento?:', haySolapamiento);

            // Determinar el tipo de conflicto
            if (esDuplicado) {

                horariosValidos = false;
                tipoConflicto = 'duplicado'
                
                // Evitar días duplicados
                if (!diasConflicto.includes(nuevoHorario.dia_semana)) {
                    diasConflicto.push(nuevoHorario.dia_semana);
                }

            } else if (haySolapamiento) {

                horariosValidos = false;
                tipoConflicto = 'solapamiento';

                // Evitar días duplicados
                if (!diasConflicto.includes(nuevoHorario.dia_semana)) {
                    diasConflicto.push(nuevoHorario.dia_semana);
                }
            }

            /* console.log('Tipo de conflicto: ', tipoConflicto);
            console.log('Día de conflicto: ', diasConflicto);*/
            
        });
    });

    if (!horariosValidos) {

        // Convertir los valores a nombres
        const nombresDiasConflicto = diasConflicto.map((dia) => nombresDias[dia]);
        // Convertir el arreglo en texto
        const diasTexto = nombresDiasConflicto.join(', ');

        if (tipoConflicto === 'duplicado'){
            alert(`Los siguientes horarios ya están configurados: ${diasTexto}.`);
        } else if (tipoConflicto === 'solapamiento') {
            alert(`Los siguientes horarios se solapan con horarios existentes: ${diasTexto}.`);
        }
    }

    if (horariosValidos) {

        // Agregar los nuevos horarios a la colección permanente
        horariosAgregados.push(...nuevosHorarios); // Operador de propagación (spread operator) (...) para insertar cada elemento por separado

        // Ordenar los horarios por día de la semana
        horariosAgregados.sort((a, b) => {

            // Primer criterio: día de la semana
            const diferenciaDia = Number(a.dia_semana) - Number(b.dia_semana);

            // Si son días diferentes, ordenar por día
            if (diferenciaDia !== 0) {
                return diferenciaDia;
            }

            // Segundo criterio: hora de inicio
            return a.hora_inicio.localeCompare(b.hora_inicio);
        });

        // Reconstruir la representación visual y los inputs ocultos
        actualizarHorarios();
    }

    /* console.log('Horarios agregados:', horariosAgregados);
    console.log('Horarios inputs hidden:', horariosInputs);*/
    
});

// Limpiar la URL después de mostrar un mensaje
document.addEventListener("DOMContentLoaded", () => {

    const mensaje = document.getElementById("mensaje-alerta");

    // Limpiar los parámetros de la URL después de mostrar un mensaje de éxito o error,
    // evitando que al recargar la página (F5) el navegador vuelva a mostrar la misma alerta.
    if (mensaje && window.history.replaceState && window.location.search) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}); 