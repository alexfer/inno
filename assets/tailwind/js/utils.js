window.showToast = (toast, message, timeout) => {
    toast.querySelector('.toast-body').innerText = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, timeout ? timeout : 5000);
};

window.closeModal = (id) => {
    document.getElementById(id).style.display = 'none';
    document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden');
};

window.focusNextInput = (el, prevId, nextId) => {
    if (el.value.length === 0) {
        if (prevId) {
            document.getElementById(prevId).focus();
        }
    } else {
        if (nextId) {
            document.getElementById(nextId).focus();
        }
    }
};

window.bindForm = (form) => {
    return [...form].reduce((previousValue, currentValue) => {
        const [i, prop] = currentValue.name.split(/\[(.*?)]/g).filter(Boolean)
        if (!previousValue[i]) {
            previousValue[i] = {};
        }
        if (currentValue.type === 'checkbox') {
            previousValue[i][prop] = !!currentValue.checked;
        } else {
            previousValue[i][prop] = currentValue.value;
        }
        return previousValue;
    }, []);
};

window.SetCookie = (name, value, time, secure = false) => {
    let date = new Date();
    date.setTime(date.getTime() + time);
    let expires = "; expires=" + date.toUTCString();
    document.cookie = name + "=" + (value || '') + expires + ";SameSite=Lax;Path=/;";
};

window.getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop().split(';').shift();
    }
};

const inputs = document.querySelectorAll('input[data-showcounter="true"], textarea[data-showcounter="true"]');

if (inputs.length) {
    [...inputs].forEach((input, index) => {
        const max = input.getAttribute('max');
        const counter = document.createElement('div');
        const spanFirst = document.createElement('span');
        const spanLast = document.createElement('span');
        spanFirst.setAttribute('id', `chars-${index}`);
        spanFirst.textContent = input.value.length || '0';
        spanLast.textContent = max || '0';
        counter.classList.add('counter-helper');
        counter.appendChild(spanFirst);
        counter.appendChild(spanLast);
        input.insertAdjacentElement('afterend', counter);
        spanFirst.insertAdjacentText('afterend', '/');

        const chars = document.getElementById(`chars-${index}`);

        input.onkeyup = function (e) {
            chars.innerHTML = e.target.value.length;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // open
    const burger = document.querySelectorAll('.navbar-burger');
    const menu = document.querySelectorAll('.navbar-menu');

    if (burger.length && menu.length) {
        for (let i = 0; i < burger.length; i++) {
            burger[i].addEventListener('click', function () {
                for (let j = 0; j < menu.length; j++) {
                    menu[j].classList.toggle('hidden');
                }
            });
        }
    }

    // close
    const close = document.querySelectorAll('.navbar-close');
    const backdrop = document.querySelectorAll('.navbar-backdrop');

    if (close.length) {
        for (let i = 0; i < close.length; i++) {
            close[i].addEventListener('click', function () {
                for (let j = 0; j < menu.length; j++) {
                    menu[j].classList.toggle('hidden');
                }
            });
        }
    }

    if (backdrop.length) {
        for (let i = 0; i < backdrop.length; i++) {
            backdrop[i].addEventListener('click', function () {
                for (let j = 0; j < menu.length; j++) {
                    menu[j].classList.toggle('hidden');
                }
            });
        }
    }
});

const any = document.getElementById('any');
const discount = document.querySelector('[id$="_discount"]');

if (discount) {
    const output = document.querySelector(".discount-output");
    if (output) {
        output.textContent = discount.value + '%';

        discount.addEventListener("input", () => {
            output.textContent = discount.value + '%';
        });
    }
}

if (any) {
    any.addEventListener('click', (e) => {
        let chx = false,
            checks = document.querySelectorAll('.checks');
        if (any.checked) {
            chx = true;
        }
        for (let i in checks) {
            if (typeof checks[i] !== "function" && typeof checks[i] !== "number" && !checks[i].hasAttribute('disabled')) {
                checks[i].checked = chx;
                checks[i].addEventListener('click', () => {
                    any.checked = false;
                });
            }
        }
    });
}