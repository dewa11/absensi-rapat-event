(function () {
    const refreshButton = document.getElementById('refresh-captcha');
    const captchaImage = document.getElementById('captcha-image');

    if (refreshButton && captchaImage) {
        refreshButton.addEventListener('click', function () {
            const captchaUrl = captchaImage.dataset.captchaUrl || '/captcha';
            captchaImage.src = captchaUrl + '?ts=' + Date.now();
        });
    }

    const forms = document.querySelectorAll('.needs-validation');
    const selfieVideo = document.getElementById('selfie-video');
    const selfiePreview = document.getElementById('selfie-preview');
    const selfieCanvas = document.getElementById('selfie-canvas');
    const selfieData = document.getElementById('selfie_data');
    const selfieStatus = document.getElementById('selfie-status');
    const captureSelfieButton = document.getElementById('capture-selfie');
    const retakeSelfieButton = document.getElementById('retake-selfie');
    let selfieStream = null;

    function setSelfieStatus(message) {
        if (selfieStatus) {
            selfieStatus.textContent = message;
        }
    }

    function stopSelfieStream() {
        if (selfieStream) {
            selfieStream.getTracks().forEach(function (track) {
                track.stop();
            });
            selfieStream = null;
        }
    }

    function resetSelfieCapture() {
        if (selfieData) {
            selfieData.value = '';
        }
        if (selfiePreview) {
            selfiePreview.classList.add('d-none');
            selfiePreview.removeAttribute('src');
        }
        if (selfieVideo) {
            selfieVideo.classList.remove('d-none');
        }
        if (captureSelfieButton) {
            captureSelfieButton.classList.remove('d-none');
            captureSelfieButton.disabled = !selfieStream;
        }
        if (retakeSelfieButton) {
            retakeSelfieButton.classList.add('d-none');
        }
    }

    function drawMirroredSquareFrame(context) {
        const frameSize = Math.min(selfieVideo.videoWidth, selfieVideo.videoHeight);
        const offsetX = Math.floor((selfieVideo.videoWidth - frameSize) / 2);
        const offsetY = Math.floor((selfieVideo.videoHeight - frameSize) / 2);

        selfieCanvas.width = frameSize;
        selfieCanvas.height = frameSize;

        context.save();
        context.scale(-1, 1);
        context.drawImage(
            selfieVideo,
            offsetX,
            offsetY,
            frameSize,
            frameSize,
            -frameSize,
            0,
            frameSize,
            frameSize
        );
        context.restore();
    }

    async function startSelfieCamera() {
        if (!selfieVideo || !selfieCanvas || !selfieData) {
            return;
        }

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setSelfieStatus('Browser ini tidak mendukung akses kamera.');
            if (captureSelfieButton) {
                captureSelfieButton.disabled = true;
            }
            return;
        }

        try {
            selfieStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user'
                },
                audio: false
            });

            selfieVideo.srcObject = selfieStream;
            selfieVideo.onloadedmetadata = function () {
                selfieVideo.play().catch(function () {});
                if (captureSelfieButton) {
                    captureSelfieButton.disabled = false;
                }
                setSelfieStatus('Kamera siap. Ambil foto untuk melanjutkan.');
            };
        } catch (error) {
            setSelfieStatus('Akses kamera ditolak atau kamera tidak tersedia.');
            if (captureSelfieButton) {
                captureSelfieButton.disabled = true;
            }
        }
    }

    if (captureSelfieButton && retakeSelfieButton && selfieVideo && selfiePreview && selfieCanvas && selfieData) {
        captureSelfieButton.disabled = true;
        startSelfieCamera();

        captureSelfieButton.addEventListener('click', function () {
            if (!selfieStream || !selfieVideo.videoWidth || !selfieVideo.videoHeight) {
                setSelfieStatus('Kamera belum siap.');
                return;
            }

            const context = selfieCanvas.getContext('2d');
            if (!context) {
                setSelfieStatus('Tidak dapat memproses foto.');
                return;
            }

            drawMirroredSquareFrame(context);

            const dataUrl = selfieCanvas.toDataURL('image/jpeg', 0.92);
            selfieData.value = dataUrl;
            selfiePreview.src = dataUrl;
            selfiePreview.classList.remove('d-none');
            selfieVideo.classList.add('d-none');
            captureSelfieButton.classList.add('d-none');
            retakeSelfieButton.classList.remove('d-none');
            setSelfieStatus('Foto berhasil diambil.');
        });

        retakeSelfieButton.addEventListener('click', function () {
            resetSelfieCapture();
            setSelfieStatus(selfieStream ? 'Kamera aktif. Ambil foto untuk melanjutkan.' : 'Kamera tidak tersedia.');
        });
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (selfieData && selfieData.value.trim() === '') {
                event.preventDefault();
                event.stopPropagation();
                setSelfieStatus('Selfie wajib diambil dari kamera.');
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });

    window.addEventListener('beforeunload', function () {
        stopSelfieStream();
    });
})();
