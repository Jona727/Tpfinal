/**
 * SOLUFEED - Scripts JavaScript
 * Funciones de interacción básicas
 */

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function () {

    // Confirmación antes de eliminar
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function (e) {
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

    // Mobile Menu Logic
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const menuOverlay = document.getElementById('menuOverlay');

    if (menuToggle && sidebar && menuOverlay) {
        function toggleMenu() {
            sidebar.classList.toggle('open');
            menuOverlay.classList.toggle('active');

            // Cambiar ícono y bloquear scroll
            const isOpen = sidebar.classList.contains('open');
            menuToggle.textContent = isOpen ? '✕' : '☰'; // Cambia el icono pero mantiene el estilo Material Icon si es texto

            if (isOpen) {
                document.body.style.overflow = 'hidden'; // Bloquear scroll
                // Si usamos Material Icons y texto plano, quizas esto rompa el icono si estaba en span. 
                // Revisemos el HTML: <button><span class="material-icon">☰</span></button>
                // Al hacer textContent override, borramos el span.
                // Corrección: Manipular el innerHTML o el span.
                menuToggle.innerHTML = '<span class="material-icon">✕</span>';
            } else {
                document.body.style.overflow = ''; // Restaurar scroll
                menuToggle.innerHTML = '<span class="material-icon">☰</span>';
            }
        }

        function closeMenu() {
            sidebar.classList.remove('open');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = ''; // Restaurar scroll
            menuToggle.innerHTML = '<span class="material-icon">☰</span>';
        }

        menuToggle.addEventListener('click', toggleMenu);
        menuOverlay.addEventListener('click', closeMenu);

        // Cerrar menú con tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeMenu();
            }
        });
    }
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