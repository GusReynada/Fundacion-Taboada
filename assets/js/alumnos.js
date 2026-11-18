(() => {
    'use strict';

    // Alternar visibilidad de contraseña en la pantalla de acceso
    const passwordInput = document.getElementById('adminPassword');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    if (passwordInput && togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePasswordBtn.textContent = isHidden ? 'Ocultar' : 'Mostrar';
        });
    }

    // Modal de detalle de alumno
    const modal = document.getElementById('studentModal');
    if (!modal) {
        return;
    }

    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalFooterCloseBtn = document.getElementById('modalFooterCloseBtn');
    const modalIdBadge = document.getElementById('modalIdBadge');
    const modalNombre = document.getElementById('modalNombre');
    const modalCarrera = document.getElementById('modalCarrera');
    const modalUniversidad = document.getElementById('modalUniversidad');
    const modalCorreo = document.getElementById('modalCorreo');
    const modalTelefono = document.getElementById('modalTelefono');
    const modalNacimiento = document.getElementById('modalNacimiento');
    const modalFoto = document.getElementById('modalFoto');
    const modalPhotoLoader = document.getElementById('modalPhotoLoader');
    const modalPhotoError = document.getElementById('modalPhotoError');

    let lastFocusedCard = null;
    let activeLoadToken = 0;

    function openModalFromCard(card) {
        lastFocusedCard = card;
        activeLoadToken += 1;
        const currentToken = activeLoadToken;

        const id = card.dataset.id || '';
        const nombre = card.dataset.nombre || '';
        const carrera = card.dataset.carrera || '';
        const universidad = card.dataset.universidad || '';
        const correo = card.dataset.correo || '';
        const telefono = card.dataset.telefono || '';
        const nacimiento = card.dataset.nacimiento || '';
        const fullPhotoUrl = card.dataset.fotoFull || '';

        modalIdBadge.textContent = id ? `Registro #${id}` : 'Registro';
        modalNombre.textContent = nombre;
        modalCarrera.textContent = carrera;
        modalUniversidad.textContent = universidad;
        modalNacimiento.textContent = nacimiento;

        modalCorreo.textContent = correo;
        modalCorreo.href = correo ? `mailto:${correo}` : '#';

        modalTelefono.textContent = telefono;
        modalTelefono.href = telefono ? `tel:${telefono.replace(/[^+\d]/g, '')}` : '#';

        // Cargar aloFoto original solo al abrir el modal
        modalFoto.hidden = true;
        modalFoto.removeAttribute('src');
        modalFoto.alt = nombre ? `Fotografía de ${nombre}` : 'Fotografía del alumno';
        modalPhotoError.hidden = true;
        modalPhotoLoader.hidden = false;

        if (fullPhotoUrl) {
            const preload = new Image();
            preload.onload = () => {
                if (currentToken !== activeLoadToken) {
                    return;
                }
                modalFoto.src = fullPhotoUrl;
                modalFoto.hidden = false;
                modalPhotoLoader.hidden = true;
            };
            preload.onerror = () => {
                if (currentToken !== activeLoadToken) {
                    return;
                }
                modalPhotoLoader.hidden = true;
                modalPhotoError.hidden = false;
            };
            preload.src = fullPhotoUrl;
        } else {
            modalPhotoLoader.hidden = true;
            modalPhotoError.hidden = false;
        }

        modal.hidden = false;
        document.body.classList.add('modal-open');
        if (modalCloseBtn) {
            modalCloseBtn.focus();
        }
    }

    function closeModal() {
        if (modal.hidden) {
            return;
        }
        activeLoadToken += 1;
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        modalFoto.hidden = true;
        modalFoto.removeAttribute('src');

        if (lastFocusedCard && typeof lastFocusedCard.focus === 'function') {
            lastFocusedCard.focus();
            lastFocusedCard = null;
        }
    }

    document.querySelectorAll('.card-thumb img').forEach((img) => {
        img.addEventListener('error', () => {
            img.classList.add('is-broken');
        });
    });

    document.querySelectorAll('.student-card').forEach((card) => {
        card.addEventListener('click', () => openModalFromCard(card));
    });

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    if (modalFooterCloseBtn) {
        modalFooterCloseBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
