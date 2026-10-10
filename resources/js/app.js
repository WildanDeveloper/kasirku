import Alpine from 'alpinejs';

window.Alpine = Alpine;

let qrLoader = null;

/**
 * Lazy-load pustaka pemindai barcode (dipisah di chunk sendiri agar
 * tidak membebani halaman yang tidak memakai kamera).
 */
window.loadQrScanner = () => {
    qrLoader ??= import('html5-qrcode').then((module) => module.Html5Qrcode);
    return qrLoader;
};

Alpine.start();