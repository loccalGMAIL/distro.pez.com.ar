<div
    x-data="{
        stream: null,
        supported: 'BarcodeDetector' in window,
        error: null,
        scanning: false,
        async init() {
            if (! this.supported) {
                this.error = 'Tu navegador no soporta el escaneo de códigos de barras. Probá desde Chrome en Android.';

                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.scanning = true;
                this.loop();
            } catch (e) {
                this.error = 'No se pudo acceder a la cámara del dispositivo.';
            }
        },
        async loop() {
            if (! this.scanning) {
                return;
            }

            try {
                const detector = new BarcodeDetector({
                    formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'codabar', 'itf'],
                });
                const codes = await detector.detect(this.$refs.video);

                if (codes.length > 0) {
                    this.scanning = false;
                    this.stopCamera();
                    $wire.callMountedAction({ barcode: codes[0].rawValue });

                    return;
                }
            } catch (e) {}

            requestAnimationFrame(() => this.loop());
        },
        stopCamera() {
            this.stream?.getTracks().forEach((track) => track.stop());
        },
        destroy() {
            this.scanning = false;
            this.stopCamera();
        },
    }"
>
    <template x-if="error">
        <p class="text-sm text-danger-600 dark:text-danger-400" x-text="error"></p>
    </template>

    <template x-if="! error">
        <div class="overflow-hidden rounded-lg bg-black">
            <video x-ref="video" class="aspect-video w-full object-cover" muted playsinline></video>
        </div>
    </template>

    <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400" x-show="! error">
        Apuntá la cámara al código de barras del producto.
    </p>
</div>
