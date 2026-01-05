document.addEventListener('DOMContentLoaded', function () {
    const especialidadSelect = document.getElementById('id_especialidad');
    const medicoSelect = document.getElementById('id_medico');
    const fechaInput = document.getElementById('fecha');
    const horaSelect = document.getElementById('hora');
    const refColores = document.getElementById('referencia-colores');
    
    let calendario;
    let agendaCache = {}; 

    // 1. INICIALIZAR FLATPICKR (Versión Clásica Estable)
    calendario = flatpickr(fechaInput, {
        locale: 'es',
        minDate: "today",
        dateFormat: "Y-m-d", // Formato aaaa-mm-dd (Estándar para BD)
        disableMobile: "true",
        
        // Eventos para recargar colores al cambiar de mes
        onMonthChange: function(selectedDates, dateStr, instance) {
            actualizarColoresCalendario(instance.currentYear, instance.currentMonth + 1);
        },
        onYearChange: function(selectedDates, dateStr, instance) {
            actualizarColoresCalendario(instance.currentYear, instance.currentMonth + 1);
        },
        // Al abrir el calendario, intentamos pintar (por si acaso)
        onOpen: function(selectedDates, dateStr, instance) {
            if (medicoSelect.value) {
                actualizarColoresCalendario(instance.currentYear, instance.currentMonth + 1);
            }
        },
        onChange: function(selectedDates, dateStr, instance) {
            if (dateStr) {
                cargarHorariosDisponibles(dateStr);
            } else {
                resetearHorario();
            }
        }
    });

    // --- FUNCIONES DE RESETEO ---
    function resetearHorario(message = 'Selecciona una hora') {
        horaSelect.innerHTML = `<option value="">${message}</option>`;
        horaSelect.disabled = true;
    }

    function resetearFecha() {
        calendario.clear(); 
        if(refColores) refColores.classList.add('hidden');
    }

    function resetearMedicos(messageMedicos = 'Selecciona primero una especialidad') {
        medicoSelect.innerHTML = `<option value="">${messageMedicos}</option>`;
        medicoSelect.disabled = true;
        resetearFecha();
        resetearHorario();
    }

    // --- LÓGICA DE COLORES (AGENDA) ---
    async function actualizarColoresCalendario(year, month) {
        const medicoId = medicoSelect.value;
        if (!medicoId) return;

        const cacheKey = `${medicoId}-${month}-${year}`;

        try {
            let datos;
            
            if (agendaCache[cacheKey]) {
                datos = agendaCache[cacheKey];
            } else {
                // apiUrlAgenda viene del blade
                const response = await fetch(`${apiUrlAgenda}?id_medico=${medicoId}&mes=${month}&anio=${year}`);
                if (!response.ok) throw new Error('Error API Agenda');
                
                datos = await response.json();
                agendaCache[cacheKey] = datos;
            }

            // Llamamos directamente a la función de pintar
            pintarDias(datos);

        } catch (error) {
            console.error('Error cargando agenda visual:', error);
        }
    }

    function pintarDias(estados) {
        // 1. Limpiamos clases anteriores
        const diasDOM = document.querySelectorAll('.flatpickr-day');
        diasDOM.forEach(dia => dia.classList.remove('dia-disponible', 'dia-bloqueado'));

        // 2. Pintamos los días correspondientes
        estados.forEach(item => {
            // Buscamos el día en el calendario por su atributo 'aria-label' o fecha interna
            diasDOM.forEach(diaElem => {
                if (!diaElem.dateObj) return;

                // Convertimos la fecha del elemento a string Y-m-d para comparar
                const d = diaElem.dateObj;
                const diaStr = d.getFullYear() + "-" + 
                               String(d.getMonth() + 1).padStart(2, '0') + "-" + 
                               String(d.getDate()).padStart(2, '0');

                if (diaStr === item.fecha) {
                    if (item.estado === 'disponible') {
                        diaElem.classList.add('dia-disponible');
                    } else if (item.estado === 'bloqueado') {
                        diaElem.classList.add('dia-bloqueado');
                    }
                }
            });
        });
    }

    // --- EVENTOS DEL DOM ---

    if (especialidadSelect) {
        especialidadSelect.addEventListener('change', function () {
            const especialidadId = this.value;
            resetearMedicos();
            agendaCache = {}; 
            
            if (especialidadId) {
                cargarMedicosPorEspecialidad(especialidadId);
            }
        });
    }

    if (medicoSelect) {
        medicoSelect.addEventListener('change', function () {
            resetearFecha();
            resetearHorario();
            agendaCache = {};

            if (this.value) {
                // Habilitamos calendario
                calendario._input.disabled = false; 
                if(refColores) refColores.classList.remove('hidden');
                
                // Cargar colores iniciales
                actualizarColoresCalendario(calendario.currentYear, calendario.currentMonth + 1);
            } else {
                calendario._input.disabled = true;
            }
        });
    }

    // --- CARGA DE DATOS ---

    async function cargarMedicosPorEspecialidad(especialidadId) {
        medicoSelect.disabled = true;
        medicoSelect.innerHTML = '<option value="">Cargando médicos...</option>';

        try {
            const response = await fetch(`${apiUrlMedicosBase}?id_especialidad=${especialidadId}`);
            if (!response.ok) throw new Error('Error HTTP');
            
            const medicos = await response.json();

            medicoSelect.innerHTML = '';
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Selecciona un médico';
            medicoSelect.appendChild(defaultOption);

            if (medicos.length > 0) {
                medicos.forEach(medico => {
                    const option = document.createElement('option');
                    option.value = medico.id_medico;
                    option.textContent = `${medico.apellido}, ${medico.nombre}`;
                    medicoSelect.appendChild(option);
                });
                medicoSelect.disabled = false;
            } else {
                resetearMedicos('No hay médicos con esta especialidad');
            }
        } catch (error) {
            console.error(error);
            resetearMedicos('Error al cargar médicos');
        }
    }

    async function cargarHorariosDisponibles(fechaStr) {
        const medicoId = medicoSelect.value;
        if (!medicoId || !fechaStr) return;

        horaSelect.innerHTML = '<option>Cargando...</option>';
        horaSelect.disabled = true;

        try {
            let url = `${apiUrlHorariosDisponibles}?id_medico=${medicoId}&fecha=${fechaStr}`;
            if (typeof currentTurnoId !== 'undefined' && currentTurnoId) {
                url += `&except_turno_id=${currentTurnoId}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            horaSelect.innerHTML = '';

            if (data.mensaje) {
                resetearHorario(data.mensaje);
                return;
            }

            if (data.horarios && data.horarios.length > 0) {
                const def = document.createElement('option');
                def.value = '';
                def.textContent = 'Selecciona una hora';
                horaSelect.appendChild(def);

                data.horarios.forEach(hora => {
                    const opt = document.createElement('option');
                    opt.value = hora;
                    opt.textContent = hora;
                    if (typeof currentTurnoHora !== 'undefined' && hora === currentTurnoHora) {
                        opt.selected = true;
                    }
                    horaSelect.appendChild(opt);
                });
                horaSelect.disabled = false;
            } else {
                resetearHorario('No hay horarios disponibles');
            }
        } catch (error) {
            console.error(error);
            resetearHorario('Error al cargar horarios');
        }
    }
});