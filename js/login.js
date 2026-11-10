'use strict';

const formLogin = document.getElementById('formLogin');
const alerta = document.getElementById('alerta');
const btnLogin = document.getElementById('btnLogin');
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function mostrarError(texto) {
    alerta.className = 'alert alert-danger';
    alerta.textContent = texto;
}

formLogin.addEventListener('submit', async (e) => {
    e.preventDefault();
    alerta.className = 'd-none';

    const usuario = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value;
    if (!usuario || !password) {
        mostrarError('Ingresa usuario y contraseña.');
        return;
    }

    btnLogin.disabled = true;
    btnLogin.textContent = 'Verificando...';
    try {
        const resp = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ usuario, password }),
        });
        const data = await resp.json();
        if (data.ok) {
            window.location.href = 'panel.php';
            return;
        }
        mostrarError(data.mensaje || 'No se pudo iniciar sesión.');
    } catch (err) {
        mostrarError('No se pudo conectar con el servidor.');
    }
    btnLogin.disabled = false;
    btnLogin.textContent = 'Ingresar';
});
