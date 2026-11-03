import { Passkeys } from '@laravel/passkeys';

async function configurePasskeys() {
    const response = await fetch('/passkeys/bridge-csrf', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
    });

    if (!response.ok) {
        throw new Error('Could not initialize secure passkey authentication.');
    }

    const data = await response.json();

    Passkeys.configure({
        fetch: {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': data.csrf_token
            }
        }
    });
}

function showStatus(message, type = 'info') {
    const status = document.getElementById('loginStatus');

    if (status) {
        status.className = `status show ${type}`;
        status.textContent = message;
    }
}

// Passkey registration after successful OTP login.
window.schoolOsRegisterPasskey = async function () {
    if (!Passkeys.isSupported()) {
        showStatus('Passkeys are not supported by this browser or device.', 'error');
        return;
    }

    if (!window.schoolOsPasskeySessionReady) {
        showStatus('Please log in again using email OTP before registering a passkey.', 'error');
        return;
    }

    try {
        await configurePasskeys();

        const name = window.prompt(
            'Give this passkey a name:',
            'My device'
        );

        if (!name || !name.trim()) {
            return;
        }

        const result = await Passkeys.register({
            name: name.trim()
        });

        showStatus(`Passkey "${result.name}" registered successfully.`, 'info');

        window.alert('Your passkey has been registered successfully.');
    } catch (error) {
        console.error('Passkey registration error:', error);
        showStatus(error.message || 'Passkey registration failed.', 'error');
    }
};

// Passkey login.
const loginButton = document.getElementById('passkeyLogin');

if (loginButton) {
    loginButton.addEventListener('click', async () => {
        if (!Passkeys.isSupported()) {
            showStatus('Passkeys are not supported by this browser or device.', 'error');
            return;
        }

        loginButton.disabled = true;
        showStatus('Waiting for passkey verification...');

        try {
            await configurePasskeys();

            const result = await Passkeys.verify();

            if (!result || !result.token || !result.user) {
                throw new Error('The server did not return the expected login response.');
            }

            window.dispatchEvent(
                new CustomEvent('schoolos:passkey-login', {
                    detail: result
                })
            );
        } catch (error) {
            console.error('Passkey login error:', error);
            showStatus(
                error.message || 'Passkey login failed. You can use email OTP.',
                'error'
            );
        } finally {
            loginButton.disabled = false;
        }
    });
}

// Add a registration action after OTP login, without depending on
// the login form remaining visible.
window.addEventListener('schoolos:passkey-session-ready', function () {
    if (document.getElementById('schoolOsRegisterPasskeyAction')) {
        return;
    }

    const button = document.createElement('button');

    button.id = 'schoolOsRegisterPasskeyAction';
    button.type = 'button';
    button.textContent = 'Register Passkey';
    button.style.cssText =
        'position:fixed;right:20px;bottom:20px;z-index:9999;' +
        'padding:12px 18px;border:0;border-radius:8px;' +
        'background:#174ea6;color:white;font-weight:600;cursor:pointer;' +
        'box-shadow:0 3px 12px rgba(0,0,0,.2)';

    button.addEventListener('click', window.schoolOsRegisterPasskey);

    document.body.appendChild(button);
});
