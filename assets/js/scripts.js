/**
 * SOLUFEED - Scripts JavaScript
 * Funciones de interacción básicas
 */

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Confirmación antes de eliminar
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-ocultar mensajes después de 5 segundos
    const mensajes = document.querySelectorAll('.mensaje');
    mensajes.forEach(mensaje => {
        setTimeout(() => {
            mensaje.style.transition = 'opacity 0.5s';
            mensaje.style.opacity = '0';
            setTimeout(() => {
                mensaje.remove();
            }, 500);
        }, 5000);
    });
    
});

/**
 * Valida que un campo no esté vacío
 */
function validarCampoRequerido(campo) {
    if (campo.value.trim() === '') {
        alert('Este campo es obligatorio');
        campo.focus();
        return false;
    }
    return true;
}

/**
 * Valida que un número sea positivo
 */
function validarNumeroPositivo(campo) {
    const valor = parseFloat(campo.value);
    if (isNaN(valor) || valor <= 0) {
        alert('Debe ingresar un número mayor a 0');
        campo.focus();
        return false;
    }
    return true;
}

/**
 * Formatea un número con separador de miles
 */
function formatearNumero(numero, decimales = 2) {
    return Number(numero).toLocaleString('es-AR', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}

/**
 * Calcula el total de un array de inputs
 */
function calcularTotal(inputs) {
    let total = 0;
    inputs.forEach(input => {
        const valor = parseFloat(input.value) || 0;
        total += valor;
    });
    return total;
}