'use strict';

/* ============================================================
 * Utilidades AJAX
 * ============================================================ */
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function api(url, metodo = 'GET', cuerpo = null) {
    const opciones = { method: metodo, headers: { 'X-CSRF-Token': CSRF } };
    if (cuerpo !== null) {
        opciones.headers['Content-Type'] = 'application/json';
        opciones.body = JSON.stringify(cuerpo);
    }
    let resp;
    try {
        resp = await fetch(url, opciones);
    } catch (e) {
        throw new Error('No se pudo conectar con el servidor.');
    }
    let data;
    try {
        data = await resp.json();
    } catch (e) {
        throw new Error('Respuesta inválida del servidor.');
    }
    if (resp.status === 401) {
        window.location.href = 'index.php';
        throw new Error(data.mensaje || 'Sesión expirada.');
    }
    if (!resp.ok || !data.ok) {
        throw new Error(data.mensaje || 'Ocurrió un error.');
    }
    return data;
}

/** Crea un elemento con texto seguro (textContent, nunca innerHTML). */
function el(tag, clases = '', texto = '') {
    const nodo = document.createElement(tag);
    if (clases) nodo.className = clases;
    if (texto !== '') nodo.textContent = texto;
    return nodo;
}

function celda(texto, clases = '') {
    return el('td', clases, texto ?? '');
}

function moneda(valor) {
    return Number(valor).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fechaLocal(iso) {
    if (!iso) return '';
    const [a, m, d] = String(iso).slice(0, 10).split('-');
    return `${d}/${m}/${a}`;
}

function hoyISO() {
    const h = new Date();
    const mm = String(h.getMonth() + 1).padStart(2, '0');
    const dd = String(h.getDate()).padStart(2, '0');
    return `${h.getFullYear()}-${mm}-${dd}`;
}

function mensaje(texto, tipo = 'success') {
    const caja = document.getElementById('mensajes');
    const alerta = el('div', `alert alert-${tipo} alert-dismissible fade show`, texto);
    alerta.setAttribute('role', 'alert');
    const cerrar = el('button', 'btn-close');
    cerrar.type = 'button';
    cerrar.setAttribute('data-bs-dismiss', 'alert');
    cerrar.setAttribute('aria-label', 'Cerrar');
    alerta.appendChild(cerrar);
    caja.replaceChildren(alerta);
    setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerta).close(), 5000);
}

function mostrarError(e) {
    mensaje(e.message || 'Error inesperado.', 'danger');
}

function botonAccion(texto, clase, handler) {
    const b = el('button', `btn btn-sm ${clase} ms-1`, texto);
    b.type = 'button';
    b.addEventListener('click', handler);
    return b;
}

/* Confirmación con modal de Bootstrap */
let accionConfirmada = null;
const modalConfirmar = new bootstrap.Modal(document.getElementById('modalConfirmar'));
function confirmar(texto, callback) {
    document.getElementById('textoConfirmar').textContent = texto;
    accionConfirmada = callback;
    modalConfirmar.show();
}
document.getElementById('btnConfirmarOk').addEventListener('click', async () => {
    modalConfirmar.hide();
    if (accionConfirmada) {
        try {
            await accionConfirmada();
        } catch (e) {
            mostrarError(e);
        }
        accionConfirmada = null;
    }
});

/* ============================================================
 * Navegación entre módulos
 * ============================================================ */
const modulos = ['inicio', 'pacientes', 'procedimientos', 'historia'];

function irA(modulo, idPaciente = null) {
    modulos.forEach((m) => {
        document.getElementById(`mod-${m}`).classList.toggle('d-none', m !== modulo);
    });
    document.querySelectorAll('#navModulos .nav-link').forEach((a) => {
        a.classList.toggle('active', a.dataset.modulo === modulo);
    });
    document.getElementById('mensajes').replaceChildren();

    if (modulo === 'pacientes') cargarPacientes();
    if (modulo === 'procedimientos') cargarProcedimientos();
    if (modulo === 'historia') prepararHistoria(idPaciente);
}

document.querySelectorAll('[data-modulo]').forEach((a) => {
    a.addEventListener('click', (e) => {
        e.preventDefault();
        irA(a.dataset.modulo);
    });
});
document.querySelectorAll('[data-ir]').forEach((a) => {
    a.addEventListener('click', (e) => {
        e.preventDefault();
        irA(a.dataset.ir);
    });
});

document.getElementById('btnSalir').addEventListener('click', async () => {
    try {
        await api('api/logout.php', 'POST', {});
    } catch (e) { /* si falla igual salimos */ }
    window.location.href = 'index.php';
});

/* ============================================================
 * Módulo PACIENTES
 * ============================================================ */
const modalPaciente = new bootstrap.Modal(document.getElementById('modalPaciente'));
const formPaciente = document.getElementById('formPaciente');
const tbodyPacientes = document.getElementById('tbodyPacientes');
let temporizadorBusqueda = null;

async function cargarPacientes() {
    try {
        const q = document.getElementById('buscarPaciente').value.trim();
        const r = await api('api/pacientes.php?q=' + encodeURIComponent(q));
        tbodyPacientes.replaceChildren();
        if (r.datos.length === 0) {
            const tr = el('tr');
            const td = celda('No hay pacientes para mostrar.', 'text-center text-muted py-4');
            td.colSpan = 6;
            tr.appendChild(td);
            tbodyPacientes.appendChild(tr);
            return;
        }
        r.datos.forEach((p) => {
            const tr = el('tr');
            tr.append(
                celda(p.documento),
                celda(`${p.apellidos}, ${p.nombres}`),
                celda(p.telefono),
                celda(p.email),
                celda(fechaLocal(p.fecha_nacimiento)),
            );
            const acc = el('td', 'text-end text-nowrap');
            acc.append(
                botonAccion('Editar', 'btn-outline-primary', () => editarPaciente(p.id_paciente)),
                botonAccion('Historia', 'btn-outline-success', () => abrirHistoriaDe(p.id_paciente)),
                botonAccion('Eliminar', 'btn-outline-danger', () =>
                    confirmar(`¿Eliminar al paciente ${p.nombres} ${p.apellidos}?`, async () => {
                        const res = await api('api/pacientes.php?id=' + p.id_paciente, 'DELETE');
                        mensaje(res.mensaje);
                        cargarPacientes();
                    })),
            );
            tr.appendChild(acc);
            tbodyPacientes.appendChild(tr);
        });
    } catch (e) {
        mostrarError(e);
    }
}

document.getElementById('buscarPaciente').addEventListener('input', () => {
    clearTimeout(temporizadorBusqueda);
    temporizadorBusqueda = setTimeout(cargarPacientes, 300);
});

document.getElementById('btnNuevoPaciente').addEventListener('click', () => {
    formPaciente.reset();
    document.getElementById('pId').value = '';
    document.getElementById('tituloModalPac').textContent = 'Nuevo paciente';
    modalPaciente.show();
});

async function editarPaciente(id) {
    try {
        const r = await api('api/pacientes.php?id=' + id);
        const p = r.datos;
        formPaciente.reset();
        document.getElementById('pId').value = p.id_paciente;
        document.getElementById('pDocumento').value = p.documento ?? '';
        document.getElementById('pNombres').value = p.nombres ?? '';
        document.getElementById('pApellidos').value = p.apellidos ?? '';
        document.getElementById('pTelefono').value = p.telefono ?? '';
        document.getElementById('pEmail').value = p.email ?? '';
        document.getElementById('pNacimiento').value = (p.fecha_nacimiento ?? '').slice(0, 10);
        document.getElementById('pDireccion').value = p.direccion ?? '';
        document.getElementById('tituloModalPac').textContent = 'Editar paciente';
        modalPaciente.show();
    } catch (e) {
        mostrarError(e);
    }
}

formPaciente.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('pId').value;
    const datos = {
        documento: document.getElementById('pDocumento').value,
        nombres: document.getElementById('pNombres').value,
        apellidos: document.getElementById('pApellidos').value,
        telefono: document.getElementById('pTelefono').value,
        email: document.getElementById('pEmail').value,
        fecha_nacimiento: document.getElementById('pNacimiento').value,
        direccion: document.getElementById('pDireccion').value,
    };
    try {
        let r;
        if (id) {
            datos.id_paciente = Number(id);
            r = await api('api/pacientes.php', 'PUT', datos);
        } else {
            r = await api('api/pacientes.php', 'POST', datos);
        }
        modalPaciente.hide();
        mensaje(r.mensaje);
        cargarPacientes();
    } catch (err) {
        mostrarError(err);
    }
});

/* ============================================================
 * Módulo PROCEDIMIENTOS
 * ============================================================ */
const modalProc = new bootstrap.Modal(document.getElementById('modalProc'));
const formProc = document.getElementById('formProc');
const tbodyProcs = document.getElementById('tbodyProcs');
let catalogoProcs = [];

async function cargarProcedimientos() {
    try {
        const r = await api('api/procedimientos.php');
        catalogoProcs = r.datos;
        tbodyProcs.replaceChildren();
        if (r.datos.length === 0) {
            const tr = el('tr');
            const td = celda('No hay procedimientos registrados.', 'text-center text-muted py-4');
            td.colSpan = 4;
            tr.appendChild(td);
            tbodyProcs.appendChild(tr);
            return;
        }
        r.datos.forEach((p) => {
            const tr = el('tr');
            tr.append(
                celda(p.nombre_procedimiento, 'fw-semibold'),
                celda(p.descripcion),
                celda('$ ' + moneda(p.costo), 'text-end'),
            );
            const acc = el('td', 'text-end text-nowrap');
            acc.append(
                botonAccion('Editar', 'btn-outline-primary', () => editarProc(p)),
                botonAccion('Eliminar', 'btn-outline-danger', () =>
                    confirmar(`¿Eliminar el procedimiento "${p.nombre_procedimiento}"?`, async () => {
                        const res = await api('api/procedimientos.php?id=' + p.id_procedimiento, 'DELETE');
                        mensaje(res.mensaje);
                        cargarProcedimientos();
                    })),
            );
            tr.appendChild(acc);
            tbodyProcs.appendChild(tr);
        });
    } catch (e) {
        mostrarError(e);
    }
}

document.getElementById('btnNuevoProc').addEventListener('click', () => {
    formProc.reset();
    document.getElementById('prId').value = '';
    document.getElementById('tituloModalProc').textContent = 'Nuevo procedimiento';
    modalProc.show();
});

function editarProc(p) {
    formProc.reset();
    document.getElementById('prId').value = p.id_procedimiento;
    document.getElementById('prNombre').value = p.nombre_procedimiento ?? '';
    document.getElementById('prDesc').value = p.descripcion ?? '';
    document.getElementById('prCosto').value = p.costo ?? '';
    document.getElementById('tituloModalProc').textContent = 'Editar procedimiento';
    modalProc.show();
}

formProc.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('prId').value;
    const datos = {
        nombre_procedimiento: document.getElementById('prNombre').value,
        descripcion: document.getElementById('prDesc').value,
        costo: document.getElementById('prCosto').value,
    };
    try {
        let r;
        if (id) {
            datos.id_procedimiento = Number(id);
            r = await api('api/procedimientos.php', 'PUT', datos);
        } else {
            r = await api('api/procedimientos.php', 'POST', datos);
        }
        modalProc.hide();
        mensaje(r.mensaje);
        cargarProcedimientos();
    } catch (err) {
        mostrarError(err);
    }
});

/* ============================================================
 * Módulo HISTORIA CLÍNICA
 * ============================================================ */
const selPaciente = document.getElementById('selPacienteHist');
const panelHistoria = document.getElementById('panelHistoria');
const formAtencion = document.getElementById('formAtencion');
const modalRegHist = new bootstrap.Modal(document.getElementById('modalRegHist'));
let pacienteActual = null;
let temporizadorFiltro = null;

async function llenarSelectPacientes(filtro = '', seleccionar = null) {
    const r = await api('api/pacientes.php?q=' + encodeURIComponent(filtro));
    selPaciente.replaceChildren();
    const vacio = el('option', '', r.datos.length ? 'Selecciona un paciente...' : 'Sin resultados');
    vacio.value = '';
    selPaciente.appendChild(vacio);
    r.datos.forEach((p) => {
        const o = el('option', '', `${p.documento} — ${p.apellidos}, ${p.nombres}`);
        o.value = p.id_paciente;
        selPaciente.appendChild(o);
    });
    if (seleccionar) selPaciente.value = String(seleccionar);
}

async function pintarChecksProcs() {
    const r = await api('api/procedimientos.php');
    catalogoProcs = r.datos;
    const caja = document.getElementById('checksProcs');
    caja.replaceChildren();
    if (r.datos.length === 0) {
        caja.appendChild(el('div', 'col text-muted', 'Primero registra procedimientos en su módulo.'));
        return;
    }
    r.datos.forEach((p) => {
        const col = el('div', 'col');
        const wrap = el('div', 'form-check');
        const input = el('input', 'form-check-input');
        input.type = 'checkbox';
        input.value = p.id_procedimiento;
        input.id = 'chk-proc-' + p.id_procedimiento;
        const label = el('label', 'form-check-label', `${p.nombre_procedimiento} ($ ${moneda(p.costo)})`);
        label.htmlFor = input.id;
        wrap.append(input, label);
        col.appendChild(wrap);
        caja.appendChild(col);
    });
}

async function prepararHistoria(idPaciente = null) {
    try {
        document.getElementById('filtroHistPac').value = '';
        await llenarSelectPacientes('', idPaciente);
        await pintarChecksProcs();
        document.getElementById('fechaAtencion').value = hoyISO();
        document.getElementById('fechaAtencion').max = hoyISO();
        if (idPaciente) {
            await cargarHistoria(idPaciente);
        } else {
            pacienteActual = null;
            panelHistoria.classList.add('d-none');
        }
    } catch (e) {
        mostrarError(e);
    }
}

function abrirHistoriaDe(idPaciente) {
    irA('historia', idPaciente);
}

document.getElementById('filtroHistPac').addEventListener('input', (e) => {
    clearTimeout(temporizadorFiltro);
    temporizadorFiltro = setTimeout(async () => {
        try {
            await llenarSelectPacientes(e.target.value.trim());
        } catch (err) {
            mostrarError(err);
        }
    }, 300);
});

selPaciente.addEventListener('change', () => {
    if (selPaciente.value) {
        cargarHistoria(selPaciente.value);
    } else {
        pacienteActual = null;
        panelHistoria.classList.add('d-none');
    }
});

async function cargarHistoria(idPaciente) {
    try {
        const r = await api('api/historias.php?id_paciente=' + encodeURIComponent(idPaciente));
        pacienteActual = r.paciente;
        panelHistoria.classList.remove('d-none');

        // Datos del paciente
        const p = r.paciente;
        const caja = document.getElementById('datosPaciente');
        caja.replaceChildren();
        caja.appendChild(el('h3', 'h5 mb-1', `${p.nombres} ${p.apellidos}`));
        const detalle = [
            `Documento: ${p.documento}`,
            p.telefono ? `Tel.: ${p.telefono}` : '',
            p.email ? `Correo: ${p.email}` : '',
            p.fecha_nacimiento ? `Nacimiento: ${fechaLocal(p.fecha_nacimiento)}` : '',
        ].filter(Boolean).join(' · ');
        caja.appendChild(el('div', 'text-muted', detalle));
        if (p.direccion) caja.appendChild(el('div', 'text-muted', `Dirección: ${p.direccion}`));

        // Historial
        const tbody = document.getElementById('tbodyHistoria');
        tbody.replaceChildren();
        if (r.historia.length === 0) {
            const tr = el('tr');
            const td = celda('Este paciente aún no tiene atenciones registradas.', 'text-center text-muted py-4');
            td.colSpan = 6;
            tr.appendChild(td);
            tbody.appendChild(tr);
        }
        r.historia.forEach((h) => {
            const tr = el('tr');
            tr.append(
                celda(fechaLocal(h.fecha_atencion), 'text-nowrap'),
                celda(h.nombre_procedimiento, 'fw-semibold'),
                celda('$ ' + moneda(h.costo), 'text-end'),
                celda(h.observaciones),
                celda(h.atendido_por),
            );
            const acc = el('td', 'text-end text-nowrap');
            acc.append(
                botonAccion('Editar', 'btn-outline-primary', () => {
                    document.getElementById('rhId').value = h.id_historia;
                    document.getElementById('rhFecha').value = String(h.fecha_atencion).slice(0, 10);
                    document.getElementById('rhFecha').max = hoyISO();
                    document.getElementById('rhObs').value = h.observaciones ?? '';
                    modalRegHist.show();
                }),
                botonAccion('Eliminar', 'btn-outline-danger', () =>
                    confirmar('¿Eliminar este registro de la historia clínica?', async () => {
                        const res = await api('api/historias.php?id=' + h.id_historia, 'DELETE');
                        mensaje(res.mensaje);
                        cargarHistoria(idPaciente);
                    })),
            );
            tr.appendChild(acc);
            tbody.appendChild(tr);
        });
        document.getElementById('totalHistoria').textContent = '$ ' + moneda(r.total);
    } catch (e) {
        mostrarError(e);
    }
}

formAtencion.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!pacienteActual) {
        mostrarError(new Error('Selecciona un paciente.'));
        return;
    }
    const seleccionados = [...document.querySelectorAll('#checksProcs input:checked')].map((c) => Number(c.value));
    try {
        const r = await api('api/historias.php', 'POST', {
            id_paciente: Number(pacienteActual.id_paciente),
            fecha_atencion: document.getElementById('fechaAtencion').value,
            observaciones: document.getElementById('obsAtencion').value,
            procedimientos: seleccionados,
        });
        mensaje(r.mensaje);
        document.getElementById('obsAtencion').value = '';
        document.querySelectorAll('#checksProcs input').forEach((c) => { c.checked = false; });
        cargarHistoria(pacienteActual.id_paciente);
    } catch (err) {
        mostrarError(err);
    }
});

document.getElementById('formRegHist').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        const r = await api('api/historias.php', 'PUT', {
            id_historia: Number(document.getElementById('rhId').value),
            fecha_atencion: document.getElementById('rhFecha').value,
            observaciones: document.getElementById('rhObs').value,
        });
        modalRegHist.hide();
        mensaje(r.mensaje);
        cargarHistoria(pacienteActual.id_paciente);
    } catch (err) {
        mostrarError(err);
    }
});

/* Botón PDF: envía el id del paciente a reporte/historia_pdf.php (FPDF) */
document.getElementById('btnPDF').addEventListener('click', () => {
    if (!pacienteActual) return;
    window.location.href = 'reporte/historia_pdf.php?id_paciente=' + encodeURIComponent(pacienteActual.id_paciente);
});
