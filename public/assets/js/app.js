/**
 * College Lost & Found - Client-side helper scripts
 */
document.addEventListener('DOMContentLoaded', () => {
    // Enhance POST form submissions with double-submission prevention
    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]:not([disabled])');
            if (submitBtn) {
                setTimeout(() => {
                    submitBtn.disabled = true;
                    if (submitBtn.classList.contains('btn-sm') && submitBtn.children.length > 0 && submitBtn.textContent.trim() === '') {
                        submitBtn.style.opacity = '0.7';
                    } else if (submitBtn.querySelector('.bi')) {
                        const originalHtml = submitBtn.innerHTML;
                        submitBtn.setAttribute('data-original-text', originalHtml);
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Please wait...';
                    }
                }, 0);
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds if desired or keep interactive
    const alerts = document.querySelectorAll('.alert-custom');
    alerts.forEach((alert) => {
        alert.setAttribute('tabindex', '0');
    });
});
