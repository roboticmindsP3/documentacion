// script.js

// Función para alternar la visibilidad de los subdirectorios
function toggleDirectory(id) {
    var element = document.getElementById(id);
    if (element) {
        element.classList.toggle('show');
    }
}
