// Definimos las variables globales que se pasan desde la vista Blade
// Estas se inicializan en el script inline en create.blade.php
// const apiUrlBase = '/admin/turnos'; // Ejemplo: se sobrescribe por el valor real de Blade
// const currentTurnoId = null; // Ejemplo: se sobrescribe por el valor real de Blade
// const currentTurnoHora = ''; // Ejemplo: se sobrescribe por el valor real de Blade

document.addEventListener('DOMContentLoaded', function () {
    const medicoSelect = document.getElementById('id_medico');
    const fechaInput = document.getElementById('fecha');
    const horaSelect = document.getElementById('hora');

    // Escuchar cambios en los selectores de médico y fecha
    if (medicoSelect && fechaInput && horaSelect) {
        medicoSelect.addEventListener('change', cargarHorariosDisponibles);
        fechaInput.addEventListener('change', cargarHorariosDisponibles);
    }

    /**
     * Función para cargar los horarios disponibles de un médico para una fecha específica.
     */
    async function cargarHorariosDisponibles() {
        const medicoId = medicoSelect.value;
        const fecha = fechaInput.value;

        // Si no hay médico o fecha seleccionados, no hacemos nada.
        if (!medicoId || !fecha) {
            resetearHorario('Selecciona primero un médico y una fecha');
            return;
        }

        horaSelect.disabled = true;
        horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';

        try {
            // Construimos la URL de la API.
            // La URL base viene de la vista Blade y la ruta de la API es '/disponibles'
            const response = await fetch(`${apiUrlBase}/disponibles?id_medico=${medicoId}&fecha=${fecha}`);

            if (!response.ok) {
                const errorData = await response.json();
                console.error('Error del servidor:', errorData);
                throw new Error(`Error en la petición: ${response.statusText}`);
            }

            const horarios = await response.json();
            
            // Habilitamos el selector si tenemos datos
            if (horarios.length > 0) {
                horaSelect.disabled = false;
                horaSelect.innerHTML = ''; // Limpiamos las opciones
                
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Selecciona una hora';
                horaSelect.appendChild(defaultOption);

                horarios.forEach(horario => {
                    const option = document.createElement('option');
                    option.value = horario;
                    option.textContent = horario;
                    
                    // Si estamos en la vista de edición, seleccionamos la hora actual
                    if (horario === currentTurnoHora) {
                        option.selected = true;
                    }
                    horaSelect.appendChild(option);
                });
            } else {
                resetearHorario('No hay horarios disponibles para este día');
            }

        } catch (error) {
            console.error('Error al cargar horarios:', error);
            resetearHorario('Error al cargar horarios. Intenta de nuevo.'); // En caso de error, reseteamos el campo
        }
    }

    /**
     * Resetea el selector de hora a su estado inicial con un mensaje opcional.
     * @param {string} message - El mensaje a mostrar en el selector.
     */
    function resetearHorario(message = 'Selecciona primero médico y fecha') {
        horaSelect.innerHTML = `<option value="">${message}</option>`;
        horaSelect.disabled = true;
    }

    // Llama a la función al cargar la página si ya hay un médico y una fecha seleccionados
    if (medicoSelect.value && fechaInput.value) {
        cargarHorariosDisponibles();
    }
});
