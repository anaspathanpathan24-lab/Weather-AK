import './bootstrap';

const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function headers() {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    };
}

function showMessage(message, type = 'info') {
    const box = document.getElementById('form-message');
    if (!box) return;

    box.className = `alert alert-${type}`;
    box.classList.remove('d-none');
    box.textContent = message;
}

function clearMessage() {
    const box = document.getElementById('form-message');
    if (!box) return;

    box.className = 'alert d-none';
    box.textContent = '';
}

function loading(button, state) {

    if (!button) return;

    if (!button.dataset.text) {
        button.dataset.text = button.innerHTML;
    }

    button.disabled = state;

    button.innerHTML = state
        ? `<span class="spinner-border spinner-border-sm me-2"></span>Processing...`
        : button.dataset.text;
}

async function api(url, payload, button = null) {

    loading(button, true);

    try {

        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: headers(),
            body: JSON.stringify(payload)
        });

        let data = {};

        try {
            data = await response.json();
        } catch (_) {}

        switch (response.status) {

            case 200:
            case 201:
                return data;

            case 401:
                throw new Error(data.message || "Unauthorized.");

            case 403:
                throw new Error("Access denied.");

            case 404:
                throw new Error("Requested page not found.");

            case 422:

                if (data.errors) {

                    const first = Object.values(data.errors)[0];

                    throw new Error(Array.isArray(first) ? first[0] : first);
                }

                throw new Error(data.message || "Validation failed.");

            case 500:
                throw new Error("Internal Server Error.");

            default:
                throw new Error(data.message || "Unexpected error.");
        }

    } catch (e) {

        if (e instanceof TypeError) {
            throw new Error("Network Error. Please check your internet connection.");
        }

        throw e;

    } finally {

        loading(button, false);

    }

}

document.addEventListener('DOMContentLoaded', () => {

    /*
    |--------------------------------------------------------------------------
    | Forgot Password
    |--------------------------------------------------------------------------
    */

    const forgot = document.getElementById('forgot-password-form');

    if (forgot) {

        forgot.addEventListener('submit', async function (e) {

            e.preventDefault();

            clearMessage();

            const btn = document.getElementById('submit-btn');

            const email = document.getElementById('email').value.trim();

            try {

                const result = await api('/forgot-password', {
                    email
                }, btn);

                showMessage(result.message, 'success');

                setTimeout(() => {

                    window.location.href = '/verify-otp';

                }, 800);

            } catch (err) {

                showMessage(err.message, 'danger');

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */

    const verify = document.getElementById('verify-otp-form');

    if (verify) {

        verify.addEventListener('submit', async function (e) {

            e.preventDefault();

            clearMessage();

            const btn = document.getElementById('submit-btn');

            try {

                const result = await api('/verify-otp', {

                    email: document.getElementById('email').value,

                    otp: document.getElementById('otp').value

                }, btn);

                showMessage(result.message, 'success');

                setTimeout(() => {

                    window.location.href = '/reset-password';

                }, 800);

            } catch (err) {

                showMessage(err.message, 'danger');

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Reset Password
    |--------------------------------------------------------------------------
    */

    const reset = document.getElementById('reset-password-form');

    if (reset) {

        reset.addEventListener('submit', async function (e) {

            e.preventDefault();

            clearMessage();

            const btn = document.getElementById('submit-btn');

            try {

                const result = await api('/reset-password', {

                    email: document.getElementById('email').value,

                    password: document.getElementById('password').value,

                    password_confirmation: document.getElementById('password_confirmation').value

                }, btn);

                showMessage(result.message, 'success');

                setTimeout(() => {

                    window.location.href = '/password-changed';

                }, 800);

            } catch (err) {

                showMessage(err.message, 'danger');

            }

        });

    }

});