/**
 * Fallback QR renderer.
 *
 * The primary QR is generated in PHP (bacon/bacon-qr-code) and needs no
 * JavaScript. This only runs when the PHP path produced nothing, and is
 * bundled locally so an unreachable CDN cannot cost an operator their 2FA
 * enrolment QR.
 */
import QRCode from 'qrcode';

window.QRCode = QRCode;

window.renderLinduQr = function (el, text, width) {
    return new Promise((resolve) => {
        if (!el) {
            resolve(false);
            return;
        }
        QRCode.toCanvas(el, text, { width: width || 180 })
            .then(() => resolve(true))
            .catch(() => {
                el.innerHTML =
                    '<span class="text-danger">QR could not be rendered — enter the setup key above by hand.</span>';
                resolve(false);
            });
    });
};
