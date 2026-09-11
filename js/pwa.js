(function () {
    'use strict';

    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('service-worker.js', { scope: './' })
            .catch(function () {
                // A instalacao PWA e opcional; o sistema continua no navegador.
            });
    });
})();
