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