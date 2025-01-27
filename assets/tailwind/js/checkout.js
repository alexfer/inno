import './phone-number-placeholder';

const loginForm = document.getElementById('login-xhr');
const registerForm = document.getElementById('register-xhr');
const phone = document.getElementById('phone');
const createAccount = document.getElementById('create-account');

if (createAccount !== null) {
    createAccount.addEventListener('click', e => {
        e.preventDefault();
        document.getElementById('sign-in').getElementsByTagName('button')[0].click();
        document.getElementById('modal-register').click();
    })
}
console.log(loginForm);
if (loginForm !== null) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const response = await fetch(loginForm.getAttribute('action'), {
            method: 'POST',
            headers: new Headers({'content-type': 'application/json'}),
            body: JSON.stringify({
                email: loginForm.querySelector('input[name="login[email]"]').value,
                password: loginForm.querySelector('input[name="login[password]"]').value,
                _csrf_token: loginForm.querySelector('input[name="_csrf_token"]').value
            })
        });
        const data = await response.json();
        if (data.success === false) {
            const alert = document.getElementById('alert');
            alert.classList.remove('hidden');
            alert.innerHTML = data.errors;
        } else {
            window.location = data.redirect;
        }
    });
}

if (registerForm !== null) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = {};
        const selectors = registerForm.querySelectorAll('input, select');

        [...selectors].forEach((element) => {
            formData[element.name] = element.value;
        });

        const response = await fetch(registerForm.getAttribute('action'), {
            method: 'POST',
            headers: new Headers({'content-type': 'application/json'}),
            body: JSON.stringify(formData),
        });

        const data = await response.json();

        if (data.success === false) {
            const alert = document.getElementById('alert-register');
            alert.classList.remove('hidden');
            alert.innerHTML = data.error;
        } else {
            window.location = data.redirect;
        }
    });

    phone.addEventListener('keydown', enforceFormat);
    phone.addEventListener('keyup', formatToPhone);
}