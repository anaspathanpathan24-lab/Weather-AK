import './bootstrap';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function getCsrfHeaders() {
    return csrfToken ? { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' } : { 'X-Requested-With': 'XMLHttpRequest' };
}

function setMessage(message, type = 'info') {
    const alert = document.getElementById('form-message');
    if (!alert) return;

    alert.className = `alert alert-${type} d-block`;
    alert.textContent = message;
}

function clearMessage() {
    const alert = document.getElementById('form-message');
    if (!alert) return;

    alert.className = 'alert d-none';
    alert.textContent = '';
}

function setSubmitState(button, isLoading) {
    if (!button) return;

    button.disabled = isLoading;
    button.innerHTML = isLoading
        ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...'
        : button.dataset.defaultText || button.textContent;
}

async function requestJson(url, body, { method = 'POST', button = null } = {}) {
    if (button) setSubmitState(button, true);

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...getCsrfHeaders(),
            },
            body: JSON.stringify(body),
        });

        const data = await response.json().catch(() => ({ success: false, message: 'Unexpected server response.', errors: {} }));

        if (!response.ok) {
            const message = data.message || 'Request failed.';
            const errorMessages = Object.values(data.errors || {}).flat();
            throw { message: errorMessages[0] || message, errors: errorMessages };
        }

        return data;
    } finally {
        if (button) setSubmitState(button, false);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const forgotForm = document.getElementById('forgot-password-form');
    const verifyForm = document.getElementById('verify-otp-form');
    const resetForm = document.getElementById('reset-password-form');

    if (forgotForm) {
        const button = document.getElementById('submit-btn');
        if (button) button.dataset.defaultText = button.textContent;

        forgotForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearMessage();
            const formData = new FormData(forgotForm);

            try {
                const data = await requestJson('/forgot-password', { email: formData.get('email') }, { button });
                setMessage(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    window.location.href = '/verify-otp';
                }
            } catch (error) {
                setMessage(error.message || 'Unable to send OTP.', 'danger');
            }
        });
    }

    if (verifyForm) {
        const button = document.getElementById('submit-btn');
        if (button) button.dataset.defaultText = button.textContent;

        verifyForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearMessage();
            const formData = new FormData(verifyForm);

            try {
                const data = await requestJson('/verify-otp', { email: formData.get('email'), otp: formData.get('otp') }, { button });
                setMessage(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    window.location.href = '/reset-password';
                }
            } catch (error) {
                setMessage(error.message || 'Unable to verify OTP.', 'danger');
            }
        });
    }

    if (resetForm) {
        const button = document.getElementById('submit-btn');
        if (button) button.dataset.defaultText = button.textContent;

        resetForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearMessage();
            const formData = new FormData(resetForm);

            try {
                const data = await requestJson('/reset-password', {
                    email: formData.get('email'),
                    password: formData.get('password'),
                    password_confirmation: formData.get('password_confirmation'),
                }, { button });

                setMessage(data.message, data.success ? 'success' : 'danger');
                if (data.success) {
                    window.location.href = '/password-changed';
                }
            } catch (error) {
                setMessage(error.message || 'Unable to reset password.', 'danger');
            }
        });
    }
});
