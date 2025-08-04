// Definimos las variables globales que se pasan desde la vista Blade
// Estas se inicializan en el script inline en create.blade.php
// const apiUrlBase = '/admin/turnos'; // Ejemplo: se sobrescribe por el valor real de Blade
// const currentTurnoId = null; // Ejemplo: se sobrescribe por el valor real de Blade
// const currentTurnoHora = ''; // Ejemplo: se sobrescribe por el valor real de Blade
// const apiUrlMedicosBase = '{{ route('api.medicos.by-especialidad') }}'; // Se sobrescribe por el valor real de Blade
// const apiUrlHorariosDisponibles = '{{ route('api.turnos.disponibles') }}'; // Se agrega esta nueva variable

document.addEventListener('DOMContentLoaded', function () {
    const especialidadSelect = document.getElementById('id_especialidad');
    const medicoSelect = document.getElementById('id_medico');
    const fechaInput = document.getElementById('fecha');
    const horaSelect = document.getElementById('hora');

    // --- Funciones de Reseteo ---

    /**
     * Resetea el selector de hora a su estado inicial con un mensaje opcional.
     * @param {string} message - El mensaje a mostrar en el selector.
     */
    function resetearHorario(message = 'Selecciona primero médico y fecha') {
        horaSelect.innerHTML = `<option value="">${message}</option>`;
        horaSelect.disabled = true;
    }

    /**
     * Resetea el selector de fecha y, por cascada, el de hora.
     * @param {string} messageFecha - El mensaje a mostrar en el selector de fecha.
     */
    function resetearFechaYHora(messageFecha = 'Selecciona primero un médico') {
        fechaInput.value = ''; // Limpiar fecha seleccionada
        fechaInput.disabled = true;
        resetearHorario(); // Resetear hora también
    }

    /**
     * Resetea el selector de médicos y, por cascada, el de fecha y hora.
     * @param {string} messageMedicos - El mensaje a mostrar en el selector de médicos.
     */
    function resetearMedicos(messageMedicos = 'Selecciona primero una especialidad') {
        medicoSelect.innerHTML = `<option value="">${messageMedicos}</option>`;
        medicoSelect.disabled = true;
        medicoSelect.selectedIndex = 0;
        resetearFechaYHora(); // Resetear fecha y hora también
    }

    // --- Estado Inicial de los Selectores al Cargar la Página ---
    if (!especialidadSelect.value) {
        resetearMedicos(); // Esto también resetea fecha y hora
    }

    // --- Event Listeners ---
    if (especialidadSelect) {
        especialidadSelect.addEventListener('change', async function() {
            const especialidadId = this.value;
            resetearMedicos();

            if (especialidadId) {
                await cargarMedicosPorEspecialidad(especialidadId);
                if (medicoSelect.value && fechaInput.value) {
                    fechaInput.disabled = false;
                    cargarHorariosDisponibles();
                } else if (medicoSelect.value) {
                    fechaInput.disabled = false;
                }
            }
        });
    }

    if (medicoSelect && fechaInput && horaSelect) {
        medicoSelect.addEventListener('change', function() {
            resetearFechaYHora();
            if (medicoSelect.value) {
                fechaInput.disabled = false;
                if (fechaInput.value) {
                    cargarHorariosDisponibles();
                }
            }
        });
        fechaInput.addEventListener('change', cargarHorariosDisponibles);
    }

    // --- Funciones para Llamadas AJAX y Actualización de UI ---

    /**
     * Carga los médicos según la especialidad seleccionada.
     * @param {string} especialidadId - El ID de la especialidad seleccionada.
     */
    async function cargarMedicosPorEspecialidad(especialidadId) {
        medicoSelect.disabled = true;
        medicoSelect.innerHTML = '<option value="">Cargando médicos...</option>';

        try {
            const response = await fetch(`${apiUrlMedicosBase}?id_especialidad=${especialidadId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const medicos = await response.json();

            medicoSelect.innerHTML = '';
            let defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Selecciona un médico';
            medicoSelect.appendChild(defaultOption);

            if (medicos.length > 0) {
                medicos.forEach(medico => {
                    const option = document.createElement('option');
                    option.value = medico.id_medico;
                    option.textContent = `${medico.nombre} ${medico.apellido}`;
                    medicoSelect.appendChild(option);
                });
                medicoSelect.disabled = false;
            } else {
                resetearMedicos('No hay médicos con esta especialidad');
            }
        } catch (error) {
            console.error('Error al cargar médicos por especialidad:', error);
            resetearMedicos('Error al cargar médicos. Intenta de nuevo.');
        }
    }

    /**
     * Función para cargar los horarios disponibles de un médico para una fecha específica.
     */
    async function cargarHorariosDisponibles() {
        const medicoId = medicoSelect.value;
        const fecha = fechaInput.value;

        if (!medicoId || !fecha) {
            resetearHorario('Selecciona primero un médico y una fecha');
            return;
        }

        horaSelect.disabled = true;
        horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';

        try {
            // AQUI ESTA LA MODIFICACION: Usamos la nueva ruta y pasamos los IDs como query parameters
            const url = currentTurnoId
                ? `${apiUrlHorariosDisponibles}?id_medico=${medicoId}&fecha=${fecha}&except_turno_id=${currentTurnoId}`
                : `${apiUrlHorariosDisponibles}?id_medico=${medicoId}&fecha=${fecha}`;

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const horarios = await response.json();

            horaSelect.innerHTML = '';
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Selecciona una hora';
            horaSelect.appendChild(defaultOption);

            if (horarios.length > 0) {
                horarios.forEach(horario => {
                    const option = document.createElement('option');
                    option.value = horario;
                    option.textContent = horario;
                    if (horario === currentTurnoHora) {
                        option.selected = true;
                    }
                    horaSelect.appendChild(option);
                });
                horaSelect.disabled = false;
            } else {
                resetearHorario('No hay horarios disponibles para este día');
            }
        } catch (error) {
            console.error('Error al cargar horarios:', error);
            resetearHorario('Error al cargar horarios. Intenta de nuevo.');
        }
    }

    // --- Lógica de Carga Inicial para el caso de 'create' y 'edit' ---
    if (especialidadSelect.value) {
        cargarMedicosPorEspecialidad(especialidadSelect.value).then(() => {
            if (medicoSelect.value && fechaInput.value) {
                fechaInput.disabled = false;
                cargarHorariosDisponibles();
            } else if (medicoSelect.value) {
                fechaInput.disabled = false;
            }
        });
    }
});
