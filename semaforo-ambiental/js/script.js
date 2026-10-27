/* ================================================================
   PROYECTO: Semáforo Ambiental de la Huerta
   ARCHIVO: script.js
   DESCRIPCIÓN: Login, registro y cuentas de demostración
================================================================ */

// PARTE 1. REFERENCIAS DEL DOM

const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passInput = document.getElementById('password');
const loginAlert = document.getElementById('loginAlert');
const emailError = document.getElementById('emailError');
const passError = document.getElementById('passError');
const toggleBtn = document.getElementById('togglePass');

const registerForm = document.getElementById('registerForm');
const regNombre = document.getElementById('regNombre');
const regEmail = document.getElementById('regEmail');
const regPassword = document.getElementById('regPassword');
const regPasswordConf = document.getElementById('regPasswordConf');
const registerAlert = document.getElementById('registerAlert');
const nombreError = document.getElementById('nombreError');
const regEmailError = document.getElementById('regEmailError');
const regPassError = document.getElementById('regPassError');
const regPassConfError = document.getElementById('regPassConfError');
const toggleRegBtn = document.getElementById('toggleRegPass');
const toggleRegConfBtn = document.getElementById('toggleRegPassConf');


// PARTE 2. USUARIOS PREDETERMINADOS ACTUALIZADOS

const USUARIOS_DEFAULT = [
    {
        nombre: 'Profesor',
        email: 'elprofe@gmail.com',
        password: 'ProfeDemo2026!'
    },
    {
        nombre: 'Estudiante',
        email: 'estudiante@correo.com',
        password: 'EstudianteDemo2026!'
    }
];


// PARTE 3. FUNCIONES DE LOCALSTORAGE

function obtenerUsuarios() {
    const guardados = JSON.parse(
        localStorage.getItem('usuarios') || '[]'
    );

    return [...USUARIOS_DEFAULT, ...guardados];
}

function guardarUsuario(nuevoUsuario) {
    const guardados = JSON.parse(
        localStorage.getItem('usuarios') || '[]'
    );

    guardados.push(nuevoUsuario);
    localStorage.setItem('usuarios', JSON.stringify(guardados));
}

function emailExiste(email) {
    return obtenerUsuarios().some(
        u => u.email === email.toLowerCase()
    );
}


// PARTE 4. VALIDACIONES

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validarPassword(pass) {
    const errores = [];

    if (pass.length < 8) errores.push('Mínimo 8 caracteres');
    if (!/[A-Z]/.test(pass)) errores.push('Al menos una mayúscula');
    if (!/[0-9]/.test(pass)) errores.push('Al menos un número');

    return errores;
}


// PARTE 5. ACCESO RÁPIDO

function llenarCredenciales(correoDemo, passDemo) {
    emailInput.value = correoDemo;
    passInput.value = passDemo;

    emailError.textContent = '';
    passError.textContent = '';
    loginAlert.className = 'alerta';
    loginAlert.textContent = '';

    [emailInput, passInput].forEach(input => {
        input.style.borderColor = 'var(--verde-claro)';

        setTimeout(() => {
            input.style.borderColor = '';
        }, 700);
    });

    const botonEnviar = document.querySelector(
        '#loginForm button[type="submit"]'
    );

    if (botonEnviar) botonEnviar.focus();
}

window.llenarCredenciales = llenarCredenciales;


// PARTE 6. MOSTRAR Y OCULTAR CONTRASEÑAS

function crearToggle(btn, input) {
    if (!btn || !input) return;

    btn.addEventListener('click', () => {
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
        } else {
            input.type = 'password';
            btn.textContent = '👁️';
        }
    });
}

crearToggle(toggleBtn, passInput);
crearToggle(toggleRegBtn, regPassword);
crearToggle(toggleRegConfBtn, regPasswordConf);


// PARTE 7. MEDIDOR DE FORTALEZA

const barraFuerza = document.getElementById('barraFuerza');
const textoFuerza = document.getElementById('textoFuerza');

if (regPassword && barraFuerza && textoFuerza) {
    regPassword.addEventListener('input', () => {
        const errores = validarPassword(regPassword.value);
        const puntaje = regPassword.value.length === 0
            ? 0
            : 3 - errores.length;

        const config = [
            { pct: '0%', color: '#e0e0e0', label: '' },
            { pct: '33%', color: '#e53935', label: 'Débil' },
            { pct: '66%', color: '#ffb300', label: 'Regular' },
            { pct: '100%', color: '#43a047', label: 'Fuerte' }
        ][puntaje];

        barraFuerza.style.width = config.pct;
        barraFuerza.style.background = config.color;
        textoFuerza.textContent = config.label;
    });
}


// PARTE 8. CAMBIAR ENTRE LOGIN Y REGISTRO

function mostrarTab(tab) {
    const panelLogin = document.getElementById('panelLogin');
    const panelRegistro = document.getElementById('panelRegistro');
    const tabLogin = document.getElementById('tabLogin');
    const tabRegister = document.getElementById('tabRegister');

    if (!panelLogin || !panelRegistro || !tabLogin || !tabRegister) {
        return;
    }

    if (tab === 'login') {
        panelLogin.style.display = 'block';
        panelRegistro.style.display = 'none';
        tabLogin.classList.add('active');
        tabRegister.classList.remove('active');
    } else {
        panelLogin.style.display = 'none';
        panelRegistro.style.display = 'block';
        tabLogin.classList.remove('active');
        tabRegister.classList.add('active');
    }

    limpiarAlertas();
}

window.mostrarTab = mostrarTab;

function limpiarAlertas() {
    [
        emailError,
        passError,
        nombreError,
        regEmailError,
        regPassError,
        regPassConfError
    ].forEach(el => {
        if (el) el.textContent = '';
    });

    if (loginAlert) {
        loginAlert.className = 'alerta';
        loginAlert.textContent = '';
    }

    if (registerAlert) {
        registerAlert.className = 'alerta';
        registerAlert.textContent = '';
    }
}


// PARTE 9. INICIO DE SESIÓN

if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        limpiarAlertas();

        const email = emailInput.value.trim().toLowerCase();
        const password = passInput.value;

        let hayError = false;

        if (!email) {
            emailError.textContent = 'El correo es obligatorio';
            hayError = true;
        } else if (!validarEmail(email)) {
            emailError.textContent = 'El formato del correo no es válido';
            hayError = true;
        }

        if (!password) {
            passError.textContent = 'La contraseña es obligatoria';
            hayError = true;
        }

        if (hayError) return;

        const usuario = obtenerUsuarios().find(
            u => u.email.toLowerCase() === email &&
                 u.password === password
        );

        if (usuario) {
            loginAlert.className = 'alerta success';
            loginAlert.textContent =
                `¡Bienvenido, ${usuario.nombre}! Redirigiendo...`;

            setTimeout(() => {
                window.location.href = 'indexprincipal.html';
            }, 1500);
        } else {
            loginAlert.className = 'alerta error';
            loginAlert.textContent =
                'Correo o contraseña incorrectos';
        }
    });
}


// PARTE 10. REGISTRO DE USUARIOS

if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        limpiarAlertas();

        const nombre = regNombre.value.trim();
        const email = regEmail.value.trim().toLowerCase();
        const password = regPassword.value;
        const passwordC = regPasswordConf.value;

        let hayError = false;

        if (!nombre || nombre.length < 2) {
            nombreError.textContent = 'Ingresa un nombre válido';
            hayError = true;
        }

        if (!email) {
            regEmailError.textContent = 'El correo es obligatorio';
            hayError = true;
        } else if (!validarEmail(email)) {
            regEmailError.textContent = 'El correo no es válido';
            hayError = true;
        } else if (emailExiste(email)) {
            regEmailError.textContent = 'Este correo ya está registrado';
            hayError = true;
        }

        const erroresPass = validarPassword(password);

        if (!password) {
            regPassError.textContent = 'La contraseña es obligatoria';
            hayError = true;
        } else if (erroresPass.length > 0) {
            regPassError.textContent = erroresPass.join(' · ');
            hayError = true;
        }

        if (!passwordC) {
            regPassConfError.textContent = 'Confirma tu contraseña';
            hayError = true;
        } else if (password !== passwordC) {
            regPassConfError.textContent = 'Las contraseñas no coinciden';
            hayError = true;
        }

        if (hayError) return;

        guardarUsuario({ nombre, email, password });

        registerAlert.className = 'alerta success';
        registerAlert.textContent =
            `¡Cuenta creada! Bienvenido, ${nombre}. Ahora puedes iniciar sesión.`;

        registerForm.reset();

        if (barraFuerza) barraFuerza.style.width = '0%';
        if (textoFuerza) textoFuerza.textContent = '';

        renderizarUsuarios();

        setTimeout(() => mostrarTab('login'), 2000);
    });
}


// PARTE 11. LISTA DE USUARIOS REGISTRADOS

function renderizarUsuarios() {
    const lista = document.getElementById('listaUsuarios');

    if (!lista) return;

    const guardados = JSON.parse(
        localStorage.getItem('usuarios') || '[]'
    );

    lista.innerHTML = '';

    if (guardados.length === 0) {
        lista.innerHTML =
            '<li class="no-users">Sin usuarios registrados aún</li>';
        return;
    }

    guardados.forEach((u, i) => {
        const li = document.createElement('li');

        const info = document.createElement('span');
        info.className = 'user-info';

        const nombre = document.createElement('strong');
        nombre.textContent = u.nombre;

        const correo = document.createElement('small');
        correo.textContent = u.email;

        info.appendChild(nombre);
        info.appendChild(correo);

        const boton = document.createElement('button');
        boton.className = 'btn-del';
        boton.dataset.index = i;
        boton.title = 'Eliminar';
        boton.textContent = '✕';

        li.appendChild(info);
        li.appendChild(boton);
        lista.appendChild(li);
    });

    lista.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = Number.parseInt(btn.dataset.index, 10);
            const guardados = JSON.parse(
                localStorage.getItem('usuarios') || '[]'
            );

            guardados.splice(idx, 1);
            localStorage.setItem('usuarios', JSON.stringify(guardados));
            renderizarUsuarios();
        });
    });
}


// PARTE 12. ELIMINAR USUARIOS REGISTRADOS

const btnLimpiar = document.getElementById('btnLimpiar');

if (btnLimpiar) {
    btnLimpiar.addEventListener('click', () => {
        if (confirm('¿Eliminar todos los usuarios registrados?')) {
            localStorage.removeItem('usuarios');
            renderizarUsuarios();
        }
    });
}


// INICIALIZAR

renderizarUsuarios();