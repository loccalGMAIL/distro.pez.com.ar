{{--
    Registro del service worker y la barra de aviso que comparte dos estados
    excluyentes: "hay versión nueva" (prioritario) e "instalá la app".

    Se inyecta con `PanelsRenderHook::SCRIPTS_AFTER`, así que también aparece en
    el login — que es justo donde conviene ofrecer instalar.

    Visibilidad por `style.display` y no por la clase `hidden` ni por el atributo
    `[hidden]`: las dos compiten en especificidad con el `flex` del contenedor y
    cuál gana depende del orden en que Tailwind emita las utilidades, no del
    estado real.
--}}
<div
    id="pwa-banner"
    style="display: none; padding-bottom: calc(1rem + env(safe-area-inset-bottom));"
    class="fixed inset-x-0 bottom-0 z-50 bg-white px-4 pt-4 shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
>
    <div class="mx-auto flex max-w-2xl items-center gap-3">
        <p id="pwa-banner-text" class="flex-1 text-sm text-gray-950 dark:text-white"></p>

        <button
            type="button"
            data-pwa-action
            style="display: none;"
            class="shrink-0 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500"
        ></button>

        <button
            type="button"
            data-pwa-dismiss
            class="shrink-0 rounded-lg p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            aria-label="Cerrar"
        >
            <x-filament::icon
                :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
                class="h-5 w-5"
            />
        </button>
    </div>
</div>

<script>
    (function () {
        const INSTALL_DISMISSED_KEY = 'pwa-install-dismissed';

        const banner = () => document.getElementById('pwa-banner');

        /** @param kind 'update' | 'install' — qué hace el botón cuando se toca. */
        const showBanner = (kind, message, actionLabel) => {
            const element = banner();

            if (! element) {
                return;
            }

            element.dataset.pwaKind = kind;
            element.querySelector('#pwa-banner-text').textContent = message;

            const action = element.querySelector('[data-pwa-action]');
            action.textContent = actionLabel ?? '';
            action.style.display = actionLabel ? '' : 'none';

            element.style.display = '';
        };

        const hideBanner = () => {
            const element = banner();

            if (element) {
                element.style.display = 'none';
            }
        };

        const isStandalone = () =>
            window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        /** Safari de iOS no tiene `beforeinstallprompt`: solo queda explicar el gesto. */
        const isIos = () => /iphone|ipad|ipod/i.test(window.navigator.userAgent);

        const installDismissed = () => {
            try {
                return window.localStorage.getItem(INSTALL_DISMISSED_KEY) === '1';
            } catch (error) {
                return false;
            }
        };

        const rememberInstallDismissed = () => {
            try {
                window.localStorage.setItem(INSTALL_DISMISSED_KEY, '1');
            } catch (error) {
                // Modo privado o storage bloqueado: se vuelve a ofrecer, nada más.
            }
        };

        const offerInstall = () => {
            if (isStandalone() || installDismissed()) {
                return;
            }

            if (window.deferredInstallPrompt) {
                showBanner('install', 'Instalá la app para abrirla sin el navegador.', 'Instalar');
            } else if (isIos()) {
                showBanner('install', 'Para instalarla: tocá Compartir y después "Agregar a inicio".', null);
            }
        };

        const offerUpdate = () => showBanner('update', 'Hay una versión nueva de la app.', 'Actualizar');

        /**
         * Corre en cada navegación de `wire:navigate`: el markup del banner se
         * vuelve a renderizar con el body nuevo, así que hay que volver a
         * decidir qué mostrar. La versión nueva tiene prioridad sobre instalar.
         */
        const refreshBanner = () => {
            if (! ('serviceWorker' in navigator)) {
                offerInstall();

                return;
            }

            navigator.serviceWorker.getRegistration().then((registration) => {
                if (registration && registration.waiting) {
                    offerUpdate();
                } else {
                    offerInstall();
                }
            });
        };

        refreshBanner();

        if (window.pwaBooted) {
            return;
        }

        window.pwaBooted = true;

        // `document` sobrevive a `wire:navigate`, así que estos listeners
        // delegados se registran una sola vez para toda la sesión.
        document.addEventListener('click', (event) => {
            const kind = banner()?.dataset.pwaKind;

            if (event.target.closest('[data-pwa-dismiss]')) {
                // Cerrar el aviso de instalación se recuerda; el de versión
                // nueva vuelve a aparecer, porque hay algo pendiente de aplicar.
                if (kind === 'install') {
                    rememberInstallDismissed();
                }

                hideBanner();

                return;
            }

            if (! event.target.closest('[data-pwa-action]')) {
                return;
            }

            if (kind === 'update') {
                navigator.serviceWorker?.getRegistration().then((registration) => {
                    // El `controllerchange` que dispara esto recarga la página.
                    registration?.waiting?.postMessage({ type: 'SKIP_WAITING' });
                });

                return;
            }

            const prompt = window.deferredInstallPrompt;

            if (! prompt) {
                return;
            }

            window.deferredInstallPrompt = null;
            hideBanner();
            prompt.prompt();
        });

        window.addEventListener('pwa:installable', offerInstall);
        window.addEventListener('appinstalled', () => {
            window.deferredInstallPrompt = null;
            hideBanner();
        });

        if (! ('serviceWorker' in navigator)) {
            return;
        }

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (window.pwaReloading) {
                return;
            }

            window.pwaReloading = true;
            window.location.reload();
        });

        window.addEventListener('load', () => {
            navigator.serviceWorker.register(@json(route('pwa.service-worker', absolute: false)), { scope: '/' })
                .then((registration) => {
                    if (registration.waiting) {
                        offerUpdate();
                    }

                    registration.addEventListener('updatefound', () => {
                        const installing = registration.installing;

                        if (! installing) {
                            return;
                        }

                        installing.addEventListener('statechange', () => {
                            // Sin `controller` es la primera instalación, no una
                            // actualización: ahí no hay nada que avisar.
                            if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                                offerUpdate();
                            }
                        });
                    });
                })
                .catch(() => {
                    // Sin HTTPS o con el SW bloqueado por política: la app sigue
                    // funcionando como sitio normal.
                });
        });
    })();
</script>
