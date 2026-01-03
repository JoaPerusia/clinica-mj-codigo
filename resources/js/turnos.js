document.addEventListener('DOMContentLoaded', function () {
    const especialidadSelect = document.getElementById('id_especialidad');
    const medicoSelect = document.getElementById('id_medico');
    const fechaInput = document.getElementById('fecha'); // Input controlado por Flatpickr
    const horaSelect = document.getElementById('hora');
    const refColores = document.getElementById('referencia-colores'); // La leyenda de colores
    
    let calendario; // Instancia de Flatpickr
    let agendaCache = {}; // Caché para evitar recargas innecesarias de colores

    // -------------------------------------------------------------------
    // 1. INICIALIZACIÓN DE FLATPICKR (El Calendario Visual)
    // -------------------------------------------------------------------
    calendario = flatpickr(fechaInput, {
        locale: {
            ...flatpickr.l10ns.es, // Carga todas las traducciones de Español
            firstDayOfWeek: 0      // 0 = Domingo, 1 = Lunes
        },
        minDate: "today",
        dateFormat: "Y-m-d",
        disableMobile: "true",
        
        onMonthChange: function(selectedDates, dateStr, instance) {
            actualizarColoresCalendario(instance.currentYear, instance.currentMonth + 1);
        },
        onYearChange: function(selectedDates, dateStr, instance) {
            actualizarColoresCalendario(instance.currentYear, instance.currentMonth + 1);
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
             if (typeof agendaActual !== 'undefined' && agendaActual.length > 0) {
                const fechaDia = dObj.getFullYear() + "-" + 
                               String(dObj.getMonth() + 1).padStart(2, '0') + "-" + 
                               String(dObj.getDate()).padStart(2, '0');
                
                const infoDia = agendaActual.find(item => item.fecha === fechaDia);
                if (infoDia) {
                    if (infoDia.estado === 'disponible') dayElem.classList.add('dia-disponible');
                    else if (infoDia.estado === 'bloqueado') dayElem.classList.add('dia-bloqueado');
                }
            }
        },
        onChange: function(selectedDates, dateStr, instance) {
            if (dateStr) cargarHorariosDisponibles(dateStr);
            else resetearHorario();
        }
    });

    // -------------------------------------------------------------------
    // 2. FUNCIONES DE RESETEO
    // -------------------------------------------------------------------

    function resetearHorario(message = 'Selecciona una hora') {
        horaSelect.innerHTML = `<option value="">${message}</option>`;
        horaSelect.disabled = true;
    }

    function resetearFecha() {
        calendario.clear(); // Limpiar visualmente
        fechaInput.disabled = true;
        if(refColores) refColores.classList.add('hidden');
    }

    function resetearMedicos(messageMedicos = 'Selecciona primero una especialidad') {
        medicoSelect.innerHTML = `<option value="">${messageMedicos}</option>`;
        medicoSelect.disabled = true;
        resetearFecha();
        resetearHorario();
    }

    // -------------------------------------------------------------------
    // 3. LÓGICA DE COLORES (AGENDA VISUAL)
    // -------------------------------------------------------------------

    async function actualizarColoresCalendario(year, month) {
        const medicoId = medicoSelect.value;
        if (!medicoId) return;

        // Clave única para caché (ej: "5-10-2025")
        const cacheKey = `${medicoId}-${month}-${year}`;

        try {
            let datos;
            
            // Usar caché si ya consultamos este mes
            if (agendaCache[cacheKey]) {
                datos = agendaCache[cacheKey];
            } else {
                // Consultar API (apiUrlAgenda viene de create.blade.php)
                const response = await fetch(`${apiUrlAgenda}?id_medico=${medicoId}&mes=${month}&anio=${year}`);
                if (!response.ok) throw new Error('Error al obtener agenda');
                datos = await response.json();
                agendaCache[cacheKey] = datos;
            }

            pintarDias(datos);

        } catch (error) {
            console.error('Error cargando agenda visual:', error);
        }
    }

    function pintarDias(estados) {
        // Limpiar clases previas
        const diasDOM = document.querySelectorAll('.flatpickr-day');
        diasDOM.forEach(dia => dia.classList.remove('dia-disponible', 'dia-bloqueado'));

        // Pintar según respuesta de la API
        estados.forEach(item => {
            // Buscamos el día en el DOM comparando fechas
            diasDOM.forEach(diaElem => {
                if (!diaElem.dateObj) return;

                // Formateamos la fecha del elemento DOM a Y-m-d para comparar
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

    // -------------------------------------------------------------------
    // 4. EVENT LISTENERS
    // -------------------------------------------------------------------

    // A. Cambio de Especialidad
    if (especialidadSelect) {
        especialidadSelect.addEventListener('change', function () {
            const especialidadId = this.value;
            resetearMedicos();
            agendaCache = {}; // Limpiar caché al cambiar especialidad

            if (especialidadId) {
                cargarMedicosPorEspecialidad(especialidadId);
            }
        });
    }

    // B. Cambio de Médico
    if (medicoSelect) {
        medicoSelect.addEventListener('change', function () {
            resetearFecha();
            resetearHorario();
            agendaCache = {}; // Limpiar caché al cambiar médico

            if (this.value) {
                fechaInput.disabled = false; // Habilitar calendario
                if(refColores) refColores.classList.remove('hidden');
                
                // Cargar colores del mes actual
                actualizarColoresCalendario(calendario.currentYear, calendario.currentMonth + 1);
            }
        });
    }

    // -------------------------------------------------------------------
    // 5. FUNCIONES AJAX (CARGA DE DATOS)
    // -------------------------------------------------------------------

    async function cargarMedicosPorEspecialidad(especialidadId) {
        medicoSelect.disabled = true;
        medicoSelect.innerHTML = '<option value="">Cargando médicos...</option>';

        try {
            const response = await fetch(`${apiUrlMedicosBase}?id_especialidad=${especialidadId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
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
                    
                    option.textContent = `${medico.apellido}, ${medico.nombre}`;
                    
                    medicoSelect.appendChild(option);
                });
                medicoSelect.disabled = false;
            } else {
                resetearMedicos('No hay médicos con esta especialidad');
            }
        } catch (error) {
            console.error('Error al cargar médicos por especialidad:', error);
            resetearMedicos('Error al cargar médicos.');
        }
    }

    // Se llama desde Flatpickr onChange
    async function cargarHorariosDisponibles(fechaStr) {
        const medicoId = medicoSelect.value;
        if (!medicoId || !fechaStr) return;

        horaSelect.innerHTML = '<option>Cargando horarios...</option>';
        horaSelect.disabled = true;

        try {
            // Construir URL (considerando si es edición)
            let url = `${apiUrlHorariosDisponibles}?id_medico=${medicoId}&fecha=${fechaStr}`;
            if (typeof currentTurnoId !== 'undefined' && currentTurnoId) {
                url += `&except_turno_id=${currentTurnoId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Error HTTP');
            const data = await response.json();

            horaSelect.innerHTML = ''; // Limpiar

            if (data.mensaje) {
                // Mensaje específico del backend (ej: "Médico no atiende")
                resetearHorario(data.mensaje);
                return;
            }

            if (data.horarios && data.horarios.length > 0) {
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = 'Selecciona una hora';
                horaSelect.appendChild(defaultOpt);

                data.horarios.forEach(hora => {
                    const opt = document.createElement('option');
                    opt.value = hora;
                    opt.textContent = hora;
                    
                    // Preseleccionar si estamos editando
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

    // -------------------------------------------------------------------
    // 6. CARGA INICIAL (PARA EDICIÓN O RECARGA CON ERRORES)
    // -------------------------------------------------------------------
    if (especialidadSelect.value) {
        cargarMedicosPorEspecialidad(especialidadSelect.value).then(() => {
            
            const medicoPreseleccionado = medicoSelect.getAttribute('data-selected') || medicoSelect.value; 
            
            if (medicoPreseleccionado) {
                 medicoSelect.value = medicoPreseleccionado;
                 medicoSelect.dispatchEvent(new Event('change')); // Disparar lógica de calendario
                 
                 // Si hay fecha preseleccionada
                 if (fechaInput.value) {
                     // Flatpickr ya tiene el valor por el input value, solo cargamos horarios
                     cargarHorariosDisponibles(fechaInput.value);
                 }
            }
        });
    }
});