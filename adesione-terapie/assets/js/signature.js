// Signature handling helpers for Adesione Terapie module.

export function initializeSignaturePad({ dom, state }) {
    if (!dom.signatureCanvas) return;

    if (state.signaturePadInitialized) {
        clearSignature({ dom, state });
        return;
    }

    const canvas = dom.signatureCanvas;
    const context = canvas.getContext('2d');
    const wrapper = canvas.parentElement;

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = wrapper?.getBoundingClientRect();
        const displayWidth = Math.max(rect?.width || wrapper?.offsetWidth || canvas.offsetWidth || 600, 1);
        const displayHeight = Math.max(rect?.height || wrapper?.offsetHeight || canvas.offsetHeight || 200, 1);

        let existingImage = null;
        if (state.signaturePadDirty) {
            existingImage = new Image();
            existingImage.src = canvas.toDataURL('image/png');
        }

        canvas.width = displayWidth * ratio;
        canvas.height = displayHeight * ratio;
        canvas.style.width = displayWidth + 'px';
        canvas.style.height = displayHeight + 'px';

        context.setTransform(1, 0, 0, 1, 0, 0);
        context.scale(ratio, ratio);

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, displayWidth, displayHeight);

        context.strokeStyle = '#000000';
        context.lineWidth = 2;
        context.lineCap = 'round';
        context.lineJoin = 'round';

        if (existingImage && state.signaturePadDirty) {
            existingImage.onload = () => {
                context.drawImage(existingImage, 0, 0, displayWidth, displayHeight);
            };
        }
    }

    resizeCanvas();

    let drawing = false;
    let lastX = 0;
    let lastY = 0;

    function getCanvasPosition(event) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = rect.width ? (canvas.width / rect.width) : 1;
        const scaleY = rect.height ? (canvas.height / rect.height) : 1;

        const clientX = event.clientX ?? (event.touches && event.touches[0]?.clientX);
        const clientY = event.clientY ?? (event.touches && event.touches[0]?.clientY);

        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function startDrawing(event) {
        drawing = true;
        const pos = getCanvasPosition(event);
        lastX = pos.x;
        lastY = pos.y;
        state.signaturePadDirty = true;
    }

    function draw(event) {
        if (!drawing) return;
        event.preventDefault();

        const pos = getCanvasPosition(event);

        context.beginPath();
        context.moveTo(lastX, lastY);
        context.lineTo(pos.x, pos.y);
        context.stroke();

        lastX = pos.x;
        lastY = pos.y;
    }

    function stopDrawing() {
        drawing = false;
    }

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);

    canvas.addEventListener('touchstart', e => {
        e.preventDefault();
        startDrawing(e.touches[0]);
    }, { passive: false });

    canvas.addEventListener('touchmove', e => {
        e.preventDefault();
        draw(e.touches[0]);
    }, { passive: false });

    canvas.addEventListener('touchend', e => {
        e.preventDefault();
        stopDrawing();
    }, { passive: false });

    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(resizeCanvas, 250);
    });

    if (wrapper && typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(() => resizeCanvas());
        observer.observe(wrapper);
    }

    state.signaturePadInitialized = true;
}

export function saveSignature({ dom, state, showAlert }) {
    if (!dom.signatureCanvas || !dom.signatureImageInput) return;

    if (dom.signatureTypeSelect.value === 'digital') {
        const digital = dom.therapyForm.querySelector('[name="digital_signature"]').value.trim();
        if (!digital) {
            showAlert?.('Inserisci la firma digitale', 'warning');
            return;
        }
        dom.signatureImageInput.value = '';
        showAlert?.('Firma digitale registrata', 'success');
        return;
    }

    if (!state.signaturePadDirty) {
        showAlert?.('Disegna la firma prima di salvarla', 'warning');
        return;
    }

    captureSignatureImage({ dom, state, force: true });
    if (dom.signatureImageInput.value) {
        showAlert?.('Firma salvata', 'success');
    }
}

export function clearSignature({ dom, state }) {
    if (!dom.signatureCanvas) return;

    const canvas = dom.signatureCanvas;
    const context = canvas.getContext('2d');

    context.setTransform(1, 0, 0, 1, 0, 0);
    context.clearRect(0, 0, canvas.width, canvas.height);

    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);

    context.strokeStyle = '#000000';
    context.lineWidth = 2;
    context.lineCap = 'round';
    context.lineJoin = 'round';

    state.signaturePadDirty = false;
    dom.signatureImageInput.value = '';
}

export function updateSignatureMode({ dom }) {
    const mode = dom.signatureTypeSelect ? dom.signatureTypeSelect.value : 'graphical';
    if (mode === 'digital') {
        dom.signatureCanvasWrapper.classList.add('d-none');
        dom.digitalSignatureWrapper.classList.remove('d-none');
    } else {
        dom.signatureCanvasWrapper.classList.remove('d-none');
        dom.digitalSignatureWrapper.classList.add('d-none');
    }
}

export function captureSignatureImage({ dom, state, force = false, showAlert }) {
    if (!dom.signatureCanvas || !dom.signatureImageInput) return;

    const mode = dom.signatureTypeSelect ? dom.signatureTypeSelect.value : 'graphical';
    if (mode === 'digital') {
        dom.signatureImageInput.value = '';
        return;
    }

    if (!state.signaturePadDirty && !force) return;

    try {
        const dataUrl = dom.signatureCanvas.toDataURL('image/png');
        if (dataUrl && dataUrl !== 'data:,') {
            dom.signatureImageInput.value = dataUrl;
        }
    } catch (e) {
        console.error('Errore cattura firma:', e);
        showAlert?.('Errore nel salvataggio della firma. Riprova.', 'danger');
    }
}
