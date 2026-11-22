import './bootstrap';

// Konfirmasi hapus (form[data-confirm]) dan indikator loading (form[data-loading]) tanpa library tambahan
document.addEventListener('submit', (event) => {
    const form = event.target;

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
