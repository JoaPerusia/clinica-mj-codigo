document.addEventListener('DOMContentLoaded', () => {
    // Busca el elemento de la imagen del carrusel por su ID
    const carouselImage = document.getElementById('carousel-image');

    // **NUEVO: Verificar si el elemento existe antes de continuar**
    if (!carouselImage) {
        return; // Salir del script si no se encuentra el elemento
    }

    // Define las rutas de las imágenes que se usarán en el carrusel
    const images = [
        '/images/carousel-1.jpg',
        '/images/carousel-2.jpg',
        '/images/carousel-3.jpg',
    ];
    let currentImageIndex = 0;

    // Función para cambiar la imagen del carrusel
    function changeImage() {
        // Incrementa el índice de la imagen y lo reinicia si llega al final del array
        currentImageIndex = (currentImageIndex + 1) % images.length;
        // Establece la opacidad a 0 para iniciar el efecto de desvanecimiento
        carouselImage.style.opacity = '0';
        // Espera 1 segundo (mismo tiempo que la transición en CSS) antes de cambiar la imagen
        setTimeout(() => {
            carouselImage.src = images[currentImageIndex];
            // Restablece la opacidad a 1 para hacer que la nueva imagen aparezca
            carouselImage.style.opacity = '1';
        }, 1000);
    }

    // Llama a la función changeImage cada 5 segundos para que el carrusel sea automático
    setInterval(changeImage, 5000);
});