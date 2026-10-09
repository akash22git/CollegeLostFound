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

    // Toggle Password Visibility
    document.querySelectorAll('.password-toggle-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
                btn.setAttribute('aria-label', 'Show password');
            }
            input.focus();
        });
    });

    // Smart Email Validation & Typo Suggestion
    initSmartEmailValidation();

    // Initialize date inputs to current local date
    initDateInputs();
});

/**
 * Sync date inputs with user's local timezone date
 */
function initDateInputs() {
    const dateInputs = document.querySelectorAll('input[type="date"]#item_date, input[type="date"][data-today-max]');
    if (!dateInputs.length) return;

    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const localToday = `${yyyy}-${mm}-${dd}`;

    dateInputs.forEach((input) => {
        input.max = localToday;
        if (!input.value) {
            input.value = localToday;
        }
    });
}

/**
 * Smart Email Validator with Real-time Green Checkmark & Typo Suggestions
 */
function initSmartEmailValidation() {
    const knownDomainTypos = {
        // Gmail typos
        'gmai.com': 'gmail.com',
        'gmaill.com': 'gmail.com',
        'gmial.com': 'gmail.com',
        'gamil.com': 'gmail.com',
        'gmal.com': 'gmail.com',
        'gemail.com': 'gmail.com',
        'gmail.con': 'gmail.com',
        'gmai.con': 'gmail.com',
        'gmail.co': 'gmail.com',
        'gmail.cm': 'gmail.com',
        'gmeil.com': 'gmail.com',
        'gmaik.com': 'gmail.com',
        'gmaul.com': 'gmail.com',
        'gmai.co': 'gmail.com',
        'gmaio.com': 'gmail.com',

        // Yahoo typos
        'yaho.com': 'yahoo.com',
        'yahooo.com': 'yahoo.com',
        'yhaoo.com': 'yahoo.com',
        'yahoo.con': 'yahoo.com',
        'ymail.com': 'yahoo.com',
        'yahoo.co': 'yahoo.com',

        // Outlook & Hotmail typos
        'outlok.com': 'outlook.com',
        'outllok.com': 'outlook.com',
        'outlook.con': 'outlook.com',
        'otlook.com': 'outlook.com',
        'hotmial.com': 'hotmail.com',
        'hotmai.com': 'hotmail.com',
        'hotmaill.com': 'hotmail.com',
        'hotmail.con': 'hotmail.com',

        // iCloud typos
        'iclud.com': 'icloud.com',
        'icoud.com': 'icloud.com',
        'icloud.con': 'icloud.com'
    };

    const emailInputs = document.querySelectorAll('input[type="email"]');

    emailInputs.forEach((input) => {
        // Ensure parent wrapper exists
        let wrapper = input.closest('.email-field-wrapper');
        let container = input.parentElement;

        if (!container.classList.contains('email-input-container')) {
            const newContainer = document.createElement('div');
            newContainer.className = 'email-input-container';
            input.parentNode.insertBefore(newContainer, input);
            newContainer.appendChild(input);
            container = newContainer;
        }

        // Create or get status icon
        let icon = container.querySelector('.email-validation-icon');
        if (!icon) {
            icon = document.createElement('span');
            icon.className = 'email-validation-icon';
            icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            container.appendChild(icon);
        }

        // Create or get typo container
        let typoBox = container.parentElement.querySelector('.email-typo-suggestion');
        if (!typoBox) {
            typoBox = document.createElement('div');
            typoBox.className = 'email-typo-suggestion';
            typoBox.style.display = 'none';
            container.parentElement.appendChild(typoBox);
        }

        function validateEmailState() {
            const rawVal = input.value.trim();
            typoBox.style.display = 'none';
            typoBox.innerHTML = '';
            icon.className = 'email-validation-icon';

            if (rawVal === '') {
                input.classList.remove('is-valid', 'is-invalid');
                return;
            }

            const atIndex = rawVal.lastIndexOf('@');
            if (atIndex > 0 && atIndex < rawVal.length - 1) {
                const domain = rawVal.substring(atIndex + 1).toLowerCase();
                const username = rawVal.substring(0, atIndex);

                // Check for known typos
                if (knownDomainTypos[domain]) {
                    const correctedDomain = knownDomainTypos[domain];
                    const suggestedEmail = `${username}@${correctedDomain}`;

                    icon.className = 'email-validation-icon is-warning';
                    icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
                    input.classList.remove('is-valid');

                    typoBox.innerHTML = `
                        <i class="bi bi-lightbulb-fill"></i>
                        <span>Did you mean</span>
                        <button type="button" class="btn-typo-fix" data-fix="${suggestedEmail}">
                            ${suggestedEmail}
                        </button>?
                    `;
                    typoBox.style.display = 'flex';

                    const fixBtn = typoBox.querySelector('.btn-typo-fix');
                    if (fixBtn) {
                        fixBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            input.value = fixBtn.getAttribute('data-fix');
                            validateEmailState();
                            input.focus();
                        });
                    }
                    return;
                }
            }

            // Standard regex format check
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (emailRegex.test(rawVal)) {
                icon.className = 'email-validation-icon is-valid';
                icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
            } else if (rawVal.includes('@') && rawVal.length > 5) {
                icon.className = 'email-validation-icon is-invalid';
                icon.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
            } else {
                icon.className = 'email-validation-icon';
                input.classList.remove('is-valid', 'is-invalid');
            }
        }

        input.addEventListener('input', validateEmailState);
        input.addEventListener('blur', validateEmailState);

        // Run initially if input has a pre-filled value
        if (input.value.trim() !== '') {
            validateEmailState();
        }
    });
}

