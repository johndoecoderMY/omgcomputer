import QRCode from 'qrcode';

const qrTextInput = document.getElementById('qr-text');
const qrColorInput = document.getElementById('qr-color');
const qrBgInput = document.getElementById('qr-bg');
const qrSizeInput = document.getElementById('qr-size');
const qrMarginInput = document.getElementById('qr-margin');
const logoSizeInput = document.getElementById('logo-size');
const logoInput = document.getElementById('logo-input');
const qrCanvas = document.getElementById('qr-canvas');
const qrError = document.getElementById('qr-error');
const sizeLabel = document.getElementById('qr-size-label');
const marginLabel = document.getElementById('qr-margin-label');
const logoSizeLabel = document.getElementById('logo-size-label');
const defaultLogo = new Image();
const defaultLogoSrc = document.querySelector('[data-qr-default-logo]').dataset.qrDefaultLogo;

let selectedLogoImage = null;

function roundedRectPath(context, x, y, width, height, radius) {
    const r = Math.min(radius, width / 2, height / 2);
    context.beginPath();
    context.moveTo(x + r, y);
    context.lineTo(x + width - r, y);
    context.quadraticCurveTo(x + width, y, x + width, y + r);
    context.lineTo(x + width, y + height - r);
    context.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
    context.lineTo(x + r, y + height);
    context.quadraticCurveTo(x, y + height, x, y + height - r);
    context.lineTo(x, y + r);
    context.quadraticCurveTo(x, y, x + r, y);
    context.closePath();
}

function showLabels() {
    sizeLabel.textContent = `${qrSizeInput.value} px`;
    marginLabel.textContent = `${qrMarginInput.value} modules`;
    logoSizeLabel.textContent = `${logoSizeInput.value}%`;
}

function showQrError(message, error) {
    console.error(message, error);
    qrError.textContent = message;
    qrError.classList.remove('d-none');
}

function clearQrError() {
    qrError.textContent = '';
    qrError.classList.add('d-none');
}

function applyLogoOverlay() {
    if (!selectedLogoImage) {
        return;
    }

    const context = qrCanvas.getContext('2d');
    const logoSize = Math.round(qrCanvas.width * Number(logoSizeInput.value) / 100);
    const x = (qrCanvas.width - logoSize) / 2;
    const y = (qrCanvas.height - logoSize) / 2;
    const pad = 6;
    const logoScale = Math.min(logoSize / selectedLogoImage.naturalWidth, logoSize / selectedLogoImage.naturalHeight);
    const logoWidth = selectedLogoImage.naturalWidth * logoScale;
    const logoHeight = selectedLogoImage.naturalHeight * logoScale;
    const logoX = x + (logoSize - logoWidth) / 2;
    const logoY = y + (logoSize - logoHeight) / 2;

    context.save();
    context.fillStyle = '#ffffff';
    roundedRectPath(context, x - pad, y - pad, logoSize + (pad * 2), logoSize + (pad * 2), Math.min(14, (logoSize + (pad * 2)) * 0.2));
    context.fill();

    context.strokeStyle = 'rgba(23, 32, 51, 0.12)';
    context.lineWidth = 2;
    roundedRectPath(context, x - pad, y - pad, logoSize + (pad * 2), logoSize + (pad * 2), Math.min(14, (logoSize + (pad * 2)) * 0.2));
    context.stroke();

    context.beginPath();
    roundedRectPath(context, x, y, logoSize, logoSize, Math.min(10, logoSize * 0.2));
    context.clip();
    context.drawImage(selectedLogoImage, logoX, logoY, logoWidth, logoHeight);
    context.restore();
}

async function generateQr() {
    const value = (qrTextInput.value || 'https://omgcomputer.my').trim();
    const options = {
        width: Number(qrSizeInput.value),
        margin: Number(qrMarginInput.value),
        color: {
            dark: qrColorInput.value,
            light: qrBgInput.value,
        },
        errorCorrectionLevel: 'H',
    };

    qrCanvas.width = options.width;
    qrCanvas.height = options.width;

    try {
        await QRCode.toCanvas(qrCanvas, value, options);
        applyLogoOverlay();
        clearQrError();
    } catch (error) {
        showQrError('Could not generate the QR code. Check the content and try again.', error);
    }
}

function loadLogoFromFile(file) {
    if (!file) {
        selectedLogoImage = defaultLogo.complete && defaultLogo.naturalWidth > 0 ? defaultLogo : null;
        generateQr();
        return;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
        const image = new Image();
        image.onload = function () {
            selectedLogoImage = image;
            generateQr();
        };
        image.onerror = function (error) {
            showQrError('Could not load that logo image. Please choose another file.', error);
        };
        image.src = event.target.result;
    };
    reader.onerror = function (error) {
        showQrError('Could not read that logo image. Please choose another file.', error);
    };
    reader.readAsDataURL(file);
}

function downloadQr() {
    const link = document.createElement('a');
    link.download = 'omg-computer-qr-code.png';
    link.href = qrCanvas.toDataURL('image/png');
    link.click();
}

defaultLogo.onload = function () {
    selectedLogoImage = defaultLogo;
    generateQr();
};
defaultLogo.onerror = function (error) {
    showQrError('The default logo could not be loaded. You can still generate a QR code without it.', error);
    generateQr();
};
defaultLogo.src = defaultLogoSrc;

qrTextInput.addEventListener('input', generateQr);
qrColorInput.addEventListener('input', generateQr);
qrBgInput.addEventListener('input', generateQr);
qrSizeInput.addEventListener('input', () => {
    showLabels();
    generateQr();
});
qrMarginInput.addEventListener('input', () => {
    showLabels();
    generateQr();
});
logoSizeInput.addEventListener('input', () => {
    showLabels();
    generateQr();
});
logoInput.addEventListener('change', function (event) {
    loadLogoFromFile(event.target.files && event.target.files[0]);
});
document.getElementById('generate-btn').addEventListener('click', generateQr);
document.getElementById('download-btn').addEventListener('click', downloadQr);

showLabels();
generateQr();
