(() => {
    'use strict';

    const form = document.getElementById('registroForm');
    if (!form) {
        return;
    }

    const alertBox = document.getElementById('formAlert');
    const submitBtn = document.getElementById('submitBtn');
    const submitLabel = submitBtn.querySelector('.btn-label');
    const csrfInput = document.getElementById('csrf_token');

    const photoInput = document.getElementById('foto');
    const photoDropzone = document.getElementById('photoDropzone');
    const photoPreviewImg = document.getElementById('photoPreviewImg');
    const photoPlaceholder = photoDropzone.querySelector('.photo-placeholder-icon');
    const photoFileName = document.getElementById('photoFileName');
    const removePhotoBtn = document.getElementById('removePhotoBtn');

    const maxPhotoBytes = Number(form.dataset.maxPhotoBytes || 5 * 1024 * 1024);
    const allowedMimeTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
    const fields = ['nombre', 'foto', 'carrera', 'universidad', 'correo', 'telefono', 'fecha_cumpleanos'];

    let activePreviewUrl = null;

    function formatBytes(bytes) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }
        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }
        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    }

    function showAlert(type, message) {
        alertBox.className = `alert alert--${type} is-visible`;
        alertBox.textContent = message;
    }

    function hideAlert() {
        alertBox.className = 'alert';
        alertBox.textContent = '';
    }

    function setFieldError(fieldName, message) {
        const errorEl = form.querySelector(`[data-error-for="${fieldName}"]`);
        const inputEl = document.getElementById(fieldName);
        const groupEl = errorEl ? errorEl.closest('.form-group') : null;

        if (errorEl) {
            errorEl.textContent = message;
        }
        if (groupEl) {
            groupEl.classList.add('has-error');
        }
        if (inputEl) {
            inputEl.setAttribute('aria-invalid', 'true');
        }
    }

    function clearFieldError(fieldName) {
        const errorEl = form.querySelector(`[data-error-for="${fieldName}"]`);
        const inputEl = document.getElementById(fieldName);
        const groupEl = errorEl ? errorEl.closest('.form-group') : null;

        if (errorEl) {
            errorEl.textContent = '';
        }
        if (groupEl) {
            groupEl.classList.remove('has-error');
        }
        if (inputEl) {
            inputEl.setAttribute('aria-invalid', 'false');
        }
    }

    function clearAllErrors() {
        fields.forEach(clearFieldError);
    }

    function resetPhotoPreview() {
        if (activePreviewUrl) {
            URL.revokeObjectURL(activePreviewUrl);
            activePreviewUrl = null;
        }
        photoInput.value = '';
        photoPreviewImg.hidden = true;
        photoPreviewImg.removeAttribute('src');
        if (photoPlaceholder) {
            photoPlaceholder.style.display = 'block';
        }
        photoFileName.textContent = 'Ninguna foto seleccionada';
        removePhotoBtn.hidden = true;
    }

    function validateSelectedPhoto(file) {
        if (!file) {
            return 'La foto es obligatoria.';
        }
        const hasValidMime = file.type ? allowedMimeTypes.has(file.type) : /\.(jpe?g|png|webp)$/i.test(file.name);
        if (!hasValidMime) {
            return 'Formato no permitido. Selecciona una imagen JPG, PNG o WEBP.';
        }
        if (file.size <= 0) {
            return 'El archivo de imagen está vacío.';
        }
        if (file.size > maxPhotoBytes) {
            const maxMb = (maxPhotoBytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '');
            return `La foto no debe superar los ${maxMb} MB.`;
        }
        return '';
    }

    function applyPhotoFile(file) {
        clearFieldError('foto');

        if (!file) {
            resetPhotoPreview();
            return;
        }

        const error = validateSelectedPhoto(file);
        if (error) {
            resetPhotoPreview();
            setFieldError('foto', error);
            return;
        }

        if (activePreviewUrl) {
            URL.revokeObjectURL(activePreviewUrl);
        }
        activePreviewUrl = URL.createObjectURL(file);
        photoPreviewImg.src = activePreviewUrl;
        photoPreviewImg.hidden = false;
        if (photoPlaceholder) {
            photoPlaceholder.style.display = 'none';
        }
        photoFileName.textContent = `${file.name} (${formatBytes(file.size)})`;
        removePhotoBtn.hidden = false;
    }

    function validateFormClientSide() {
        const errors = {};

        const nombre = document.getElementById('nombre').value.trim();
        const carrera = document.getElementById('carrera').value.trim();
        const universidad = document.getElementById('universidad').value.trim();
        const correo = document.getElementById('correo').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const fecha = document.getElementById('fecha_cumpleanos').value.trim();
        const foto = photoInput.files[0] || null;

        if (!nombre) {
            errors.nombre = 'El nombre es obligatorio.';
        } else if (nombre.length < 2 || nombre.length > 150) {
            errors.nombre = 'El nombre debe tener entre 2 y 150 caracteres.';
        }

        const photoError = validateSelectedPhoto(foto);
        if (photoError) {
            errors.foto = photoError;
        }

        if (!carrera) {
            errors.carrera = 'La carrera es obligatoria.';
        } else if (carrera.length < 2 || carrera.length > 150) {
            errors.carrera = 'La carrera debe tener entre 2 y 150 caracteres.';
        }

        if (!universidad) {
            errors.universidad = 'La universidad es obligatoria.';
        } else if (universidad.length < 2 || universidad.length > 150) {
            errors.universidad = 'La universidad debe tener entre 2 y 150 caracteres.';
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!correo) {
            errors.correo = 'El correo electrónico es obligatorio.';
        } else if (!emailPattern.test(correo) || correo.length > 180) {
            errors.correo = 'Ingresa un correo electrónico válido.';
        }

        const digits = telefono.replace(/\D+/g, '');
        if (!telefono) {
            errors.telefono = 'El teléfono es obligatorio.';
        } else if (!/^[0-9+\-\s().]+$/.test(telefono) || digits.length < 7 || digits.length > 15) {
            errors.telefono = 'Ingresa un teléfono válido (entre 7 y 15 dígitos).';
        }

        if (!fecha) {
            errors.fecha_cumpleanos = 'La fecha de cumpleaños es obligatoria.';
        } else {
            const today = new Date();
            const todayStr = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
                .toISOString()
                .slice(0, 10);
            if (fecha < '1900-01-01' || fecha > todayStr) {
                errors.fecha_cumpleanos = 'La fecha debe estar entre 1900 y la fecha actual.';
            }
        }

        return errors;
    }

    function setLoading(isLoading) {
        submitBtn.disabled = isLoading;
        submitBtn.classList.toggle('is-loading', isLoading);
        if (submitLabel) {
            submitLabel.textContent = isLoading ? 'Guardando...' : 'Enviar registro';
        }
    }

    // Eventos de vista previa de fotografía
    photoInput.addEventListener('change', () => {
        applyPhotoFile(photoInput.files[0] || null);
    });

    removePhotoBtn.addEventListener('click', () => {
        resetPhotoPreview();
        clearFieldError('foto');
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        photoDropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            photoDropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        photoDropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            photoDropzone.classList.remove('is-dragover');
        });
    });

    photoDropzone.addEventListener('drop', (event) => {
        const droppedFiles = event.dataTransfer ? event.dataTransfer.files : null;
        if (!droppedFiles || droppedFiles.length === 0) {
            return;
        }
        const file = droppedFiles[0];
        if (typeof DataTransfer !== 'undefined') {
            const dt = new DataTransfer();
            dt.items.add(file);
            photoInput.files = dt.files;
        }
        applyPhotoFile(file);
    });

    // Limpiar errores al escribir en cada campo
    fields.forEach((fieldName) => {
        if (fieldName === 'foto') {
            return;
        }
        const input = document.getElementById(fieldName);
        if (!input) {
            return;
        }
        input.addEventListener('input', () => clearFieldError(fieldName));
        input.addEventListener('change', () => clearFieldError(fieldName));
    });

    // Envío asíncrono del formulario
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert();
        clearAllErrors();

        const clientErrors = validateFormClientSide();
        const errorKeys = Object.keys(clientErrors);
        if (errorKeys.length > 0) {
            errorKeys.forEach((key) => setFieldError(key, clientErrors[key]));
            showAlert('error', 'Por favor completa correctamente todos los campos requeridos.');
            const firstInvalid = document.getElementById(errorKeys[0]);
            if (firstInvalid) {
                firstInvalid.focus({ preventScroll: false });
            }
            return;
        }

        setLoading(true);

        try {
            const formData = new FormData(form);
            const response = await fetch(form.getAttribute('action') || 'procesar.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (_parseError) {
                payload = null;
            }

            if (payload && payload.csrf_token && csrfInput) {
                csrfInput.value = payload.csrf_token;
            }

            if (response.ok && payload && payload.ok) {
                form.reset();
                resetPhotoPreview();
                showAlert('success', payload.message || '¡Registro guardado correctamente!');
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }

            const serverErrors = (payload && payload.errors && typeof payload.errors === 'object')
                ? payload.errors
                : {};
            const serverErrorKeys = Object.keys(serverErrors);

            if (serverErrorKeys.length > 0) {
                serverErrorKeys.forEach((key) => setFieldError(key, serverErrors[key]));
                const firstInvalid = document.getElementById(serverErrorKeys[0]);
                if (firstInvalid) {
                    firstInvalid.focus({ preventScroll: false });
                }
            }

            const message = (payload && payload.message)
                ? payload.message
                : 'No se pudo procesar tu registro. Inténtalo nuevamente.';
            showAlert('error', message);
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (_networkError) {
            showAlert('error', 'Error de conexión con el servidor. Verifica tu red e inténtalo nuevamente.');
        } finally {
            setLoading(false);
        }
    });
})();
