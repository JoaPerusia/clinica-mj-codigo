// logica para filtrar por DNI
document.addEventListener('DOMContentLoaded', function () {
    const estadoFiltroSelect = document.getElementById('estado_filtro');
    
    estadoFiltroSelect.addEventListener('change', function () {
        const selectedEstado = this.value;
        const currentUrl = new URL(window.location.href);

        // Actualizar el parámetro 'estado_filtro' en la URL
        currentUrl.searchParams.set('estado_filtro', selectedEstado);
        
        // Asegurarse de que el parámetro de página se resetee a 1 cuando se cambia el filtro de estado
        currentUrl.searchParams.set('page', 1);

        // Redirigir a la nueva URL
        window.location.href = currentUrl.toString();
    });
});
