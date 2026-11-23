import './bootstrap';

const warmupUrl = document.querySelector('meta[name="ai-warmup-url"]')?.content;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

if (warmupUrl && csrfToken) {
    let lastWarmup = 0;
    const warmAiOnVisit = () => {
        if (document.visibilityState !== 'visible' || Date.now() - lastWarmup < 30_000) {
            return;
        }
        lastWarmup = Date.now();
        fetch(warmupUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            keepalive: true,
        }).catch(() => {});
    };

    window.addEventListener('pageshow', warmAiOnVisit);
    document.addEventListener('visibilitychange', warmAiOnVisit);
}

// Konfirmasi hapus (form[data-confirm]) dan indikator loading (form[data-loading]) tanpa library tambahan
document.addEventListener('submit', (event) => {
    const form = event.target;

    const consentDialog = form.dataset.consentDialog ? document.getElementById(form.dataset.consentDialog) : null;
    if (consentDialog && !form.dataset.consented) {
        event.preventDefault();
        consentDialog.returnValue = '';
        consentDialog.addEventListener('close', () => {
            if (consentDialog.returnValue === 'confirm') {
                form.dataset.consented = 'true';
                form.querySelector('input[name="privacy_consent"]').value = '1';
                form.requestSubmit();
            }
        }, { once: true });
        consentDialog.showModal();
        return;
    }

    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }

    if (form.hasAttribute('data-loading')) {
        form.querySelectorAll('[data-loading-indicator]').forEach((indicator) => {
            indicator.hidden = false;
        });
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
        });
    }
});

// Saat kembali lewat tombol Back, aktifkan lagi tombol dan sembunyikan loading
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
        return;
    }
    document.querySelectorAll('form[data-loading] button[type="submit"]').forEach((button) => {
        button.disabled = false;
    });
    document.querySelectorAll('[data-loading-indicator]').forEach((indicator) => {
        indicator.hidden = true;
    });
});
