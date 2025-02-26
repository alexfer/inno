import './utils';
import messages from "./i18n";
import i18next from "i18next";

i18next.init(messages);

const el = {
    form: document.getElementById('form-message'),
    search: document.getElementById('order-search'),
    danger: document.getElementById('toast-danger'),
    success: document.getElementById('toast-success'),
    modal: document.getElementById('modal-message'),
    formAnswer: document.getElementById('form-answer'),
    invoices: document.querySelectorAll('.send-invoice'),
};

if(el.invoices.length) {
    [...el.invoices].forEach((invoice, i) => {
        invoice.addEventListener('click', async e => {
            e.preventDefault();
            const request = await fetch(invoice.attributes.href.nodeValue, {
                method: 'POST',
            });
            request.json().then(data => {
                if(!data.success) {
                    window.showToast(document.getElementById('toast-danger'), data.message);
                    return;
                }
                window.showToast(document.getElementById('toast-success'), data.message);
            });
            document.body.click();
        });
    });
}

if (el.formAnswer !== null) {
    const form = el.formAnswer;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = form.getAttribute('action');
        const inputs = form.querySelectorAll('input[type="hidden"], textarea');
        const radios = document.getElementsByName('priority');
        let message = {};

        for (let i = 0; i < radios.length; i++) {
            if (radios[i].checked) {
                message['priority'] = radios[i].value;
            }
        }

        [...inputs].forEach((input, i) => {
            message[input.getAttribute('name')] = input.value;
        });

        const response = await fetch(url, {
            method: 'POST',
            body: JSON.stringify(message)
        }).catch((e) => {
            console.log(e);
        });
        const data = await response.json();
        document.getElementById('response').innerHTML = data.template;
        form.remove();
    });
}

if (el.form !== null) {
    el.form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const url = el.form.getAttribute('action')
        const message = el.form.querySelector('textarea');
        let order = null;

        if (el.modal) {
            order = el.form.querySelector('input[name="order"]');
            if (order && !order.value) {
                showToast(el.danger, i18next.t('orderNotFound'));
                return false;
            }
        }

        await fetch(url, {
            method: "POST",
            body: JSON.stringify({
                message: message.value,
                _token: el.form.querySelector('input[name="_token"]').value,
                product: el.form.querySelector('input[name="product"]').value,
                store: el.form.querySelector('input[name="store"]').value,
                order: order ? order.value : order
            }),
            headers: {'Content-type': 'application/json; charset=utf-8'}
        })
            .then((response) => response.json())
            .then((json) => {
                const response = json;
                if (response.success === false) {
                    showToast(el.danger, response.error);
                }
                if (response.success === true) {
                    showToast(el.success, response.message);
                    if (el.modal) {
                        if (order) {
                            order.value = null;
                            el.form.querySelector('input[type="search"]').value = null;
                        }
                        el.modal.querySelector('button[data-modal-hide="modal-message"]').click();
                    }
                }
                message.value = null;
            }).catch(err => {
                console.log(err);
            });
    });
}
if (el.form && el.search) {
    const icon = el.search.previousElementSibling.previousElementSibling.children[0];
    const input = el.form.querySelector('input[type="search"]');
    document.getElementById('open-message').onclick = function () {
        icon.classList.replace('text-green-500', 'text-gray-500');
    }
    el.search.addEventListener('click', async () => {
        const url = el.search.getAttribute('data-url');
        await fetch(url, {
            method: 'POST',
            body: JSON.stringify({query: input.value}),
            headers: {'Content-type': 'application/json; charset=utf-8'}
        }).then((response) => response.json()).then((json) => {
            const response = json;
            if (response.order !== null) {
                el.form.querySelector('input[name="store"]').value = response.order.store;
                el.form.querySelector('input[name="order"]').value = response.order.id;
                el.form.querySelector('textarea').focus();
                showToast(el.success, i18next.t('orderFound'));
            } else {
                showToast(el.danger, i18next.t('orderNotFound'));
            }
        }).catch(err => {
            console.log(err);
        });
    });
}