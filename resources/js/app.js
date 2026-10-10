import Alpine from '@alpinejs/csp';
import pos from './components/pos.js';
import {
    appVersion,
    bulkProducts,
    confirmSubmit,
    copyable,
    dropdown,
    flash,
    modal,
    printable,
    rowEditor,
    tableTools,
} from './components/ui.js';

window.Alpine = Alpine;

// Alpine.data() diperlukan agar halaman bisa memakai CSP build: komponen
// didefinisikan di JS (bukan fungsi global di dalam ekspresi x-data).
Alpine.data('pos', pos);
Alpine.data('confirmSubmit', confirmSubmit);
Alpine.data('printable', printable);
Alpine.data('copyable', copyable);
Alpine.data('rowEditor', rowEditor);
Alpine.data('tableTools', tableTools);
Alpine.data('dropdown', dropdown);
Alpine.data('modal', modal);
Alpine.data('flash', flash);
Alpine.data('bulkProducts', bulkProducts);
Alpine.data('appVersion', appVersion);

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