import Swal from "sweetalert2";
import i18next from "i18next";
import './utils';
import customCss from "./customCss";
import swalOptions from "./swalOptions";

document.addEventListener('DOMContentLoaded', () => {
    const formData = new FormData();
    const headers = {'Content-type': 'application/json'};
    const eventOptions = {
        capture: true,
        once: true
    };

    const elements = {
        uploadInput: document.querySelector('input[id="picture"]'),
        confirmDelete: document.getElementsByClassName('confirm-delete'),
        confirmChange: document.getElementsByClassName('confirm-change'),
        toastSuccess: document.getElementById('toast-success'),
        toastDanger: document.getElementById('toast-danger'),
        loadCategories: document.getElementById('load-categories'),
        deleteEntry: document.querySelectorAll('.delete-entry'),
        tabList: document.querySelector('ul[role="tablist"]'),
        addEntry: document.querySelectorAll('.add-entry'),
        storeButton: document.getElementById('store-search'),
        coupons: document.querySelectorAll('.coupons'),
        search: document.getElementById('search'),
    };


    if (elements.search !== null) {
        const input = elements.search;
        let ms = 0;
        const autocomplete = document.getElementById('autocomplete');

        input.addEventListener('input', (e) => {
            clearTimeout(ms);
            ms = setTimeout(() => {
                const t = e.target;
                if (input.value.length > 2) {
                    fetch(t.dataset.url + '?term=' + input.value)
                        .then(data => data.json())
                        .then(data => {
                            if (data) {
                                autocomplete.innerHTML = data.template;
                            }
                        });
                }
            }, 500);
        });

        document.body.addEventListener('click', (e) => {
            if(autocomplete) {
                autocomplete.innerHTML = '';
            }
        });
    }

    if (elements.coupons.length > 0) {
        const coupons = elements.coupons;
        const parent = coupons.item(0).parentElement.parentElement;
        const url = parent.getAttribute('data-url');
        const token = parent.getAttribute('data-token');

        const products = [];

        for (let i in coupons) {
            if (typeof coupons[i] !== "function" && typeof coupons[i] !== "number") {
                const id = coupons[i].getAttribute('data-id');
                const handler = coupons[i];
                handler.addEventListener('click', async (e) => {
                    e.preventDefault();

                    const form = document.getElementById('products');
                    const inputs = document.querySelectorAll('input[type="checkbox"]');

                    [...inputs].forEach((input) => {
                        if (input.checked === true) {
                            products.push(input.value);
                            input.disabled = true;
                            input.classList.remove('checks');
                        }
                    });

                    if (products.length === 0) {
                        showToast(elements.toastDanger, 'Choose at least one item from the list..');
                        return false;
                    }

                    const response = await fetch(url, {
                            method: 'POST',
                            body: JSON.stringify({
                                '_token': token,
                                products: products,
                                id: id
                            }),
                            headers: headers
                        }
                    );
                    const data = await response.json();
                    form.reset();
                    showToast(elements.toastSuccess, data.message);
                });
            }
        }
    }

    if (elements.storeButton) {
        elements.storeButton.addEventListener('click', () => {
            let search = document.getElementById('input-store-search');
            let url = elements.storeButton.dataset.url;
            let list = document.getElementById('search-list');
            let ms = 0;
            search.value = null;

            search.addEventListener('input', () => {
                clearTimeout(ms);
                ms = setTimeout(() => {
                    fetch(url + '/' + search.value)
                        .then(data => data.json())
                        .then(data => {

                            let stores = Object.entries(data.result);

                            if (stores.length > 0) {
                                let classList = list.children[0].children.item(0).getAttribute('class');
                                let li = [];

                                list.innerHTML = null;
                                for (const [key, item] of stores) {
                                    li[key] = document.createElement('li');
                                    let link = document.createElement('a');
                                    link.setAttribute('class', classList);
                                    link.text = item.name;
                                    link.href = item.url;
                                    li[key].appendChild(link);
                                    list.appendChild(li[key]);
                                }
                            }
                        });
                }, 500);
            });
        });
    }

    if (elements.addEntry && elements.addEntry.length > 0) {
        elements.addEntry.forEach((el, i) => {
            el.addEventListener('click', async (event) => {
                event.preventDefault();
                let url = el.getAttribute('data-url');
                let xhr = el.getAttribute('data-xhr');
                let owner = el.getAttribute('data-modal-target');
                let option = document.createElement('option');
                let form = document.getElementById('form[' + owner + ']');
                let input = form.querySelector('input[type="text"]');
                const span = document.createElement('span');

                if (xhr !== null) {
                    fetch(xhr)
                        .then(data => data.json())
                        .then(data => {
                            let countries = data.countries;
                            Object.keys(countries).forEach((key) => {
                                form.querySelector('#suppler_country').appendChild(new Option(countries[key], key));
                            });
                        });
                }

                form.querySelector('button[type="submit"]').addEventListener('click', async (ev) => {
                    ev.preventDefault();
                    const response = await fetch(url, {method: 'POST', body: new FormData(form)});
                    const data = await response.json();
                    input.value = null;
                    let json = data.json;

                    if (json.constraints !== undefined) {
                        span.classList = input.nextElementSibling.classList;
                        span.classList.replace('hidden', 'block');
                        span.textContent = json.constraints.invalid;
                        input.after(span);
                        return false;
                    }

                    option.value = json.option.id;
                    option.text = json.option.name;
                    option.selected = true;
                    span.remove();
                    document.querySelector(data.json.id).append(option);
                    setTimeout(() => {
                        input.value = null;
                        form.querySelector('button[data-modal-name="modal"]').click();
                    }, 200);
                });
            }, eventOptions);
        });

    }

    if (elements.tabList) {
        Array.from(elements.tabList.children).forEach((el) => {
            Array.from(el.children).forEach((handler) => {
                handler.addEventListener('click', (event) => {
                    event.stopPropagation();
                    let location = handler.getAttribute('aria-controls');
                    window.history.replaceState({}, '', location);
                });
            });
        });
    }

    if (elements.deleteEntry) {
        Array.from(elements.deleteEntry).forEach((entry) => {
            entry.addEventListener('click', () => {
                let url = entry.getAttribute('data-url');
                let token = entry.getAttribute('data-token');
                Swal.fire(swalOptions).then(async (result) => {
                    if (result.isConfirmed) {
                        const response = await fetch(url, {
                                method: 'POST',
                                body: JSON.stringify({'_token': token}),
                                headers: headers
                            }
                        );
                        const data = await response.json();
                        document.location.href = data.redirect;
                    }
                });
            });
        });
    }

    if (elements.loadCategories) {
        elements.loadCategories.addEventListener('click', (e) => {
            e.preventDefault();
            let children = elements.loadCategories.parentElement.children;
            for (let el of Array.from(children)) {
                el.classList.remove('sr-only');
            }
            elements.loadCategories.remove();
        });
    }

    if (elements.toastSuccess) {
        let flash = document.querySelector('input[name="flash-success"]');
        if (flash) {
            let messages = JSON.parse(flash.value);
            if (typeof messages.message !== 'undefined') {
                showToast(elements.toastSuccess, messages.message);
            }
        }
    }

    if (elements.toastDanger) {
        let flash = document.querySelector('input[name="flash-danger"]');
        if (flash) {
            let messages = JSON.parse(flash.value);
            if (typeof messages.message !== 'undefined') {
                showToast(elements.toastDanger, messages.message);
            }
        }
    }

    if (elements.uploadInput) {
        if (elements.uploadInput.classList.contains('skip')) {
            return false;
        }
        elements.uploadInput.addEventListener('change', (event) => {
            let file = event.target.files[0];
            let input = elements.uploadInput;
            let url = input.getAttribute('data-url');
            let max = +input.getAttribute('max');
            let status = document.querySelector('[role="status"]');
            const attachments = document.getElementById('attachments');

            if (file.size > max) {
                input.value = null;
                file = null;
                showToast(elements.toastDanger, i18next.t('fileTooLarge'));
                return;
            }

            formData.append('file', file);
            attachments.parentElement.classList.add('invisible');
            status.classList.remove('hidden');

            fetch(url, {
                method: 'POST',
                body: formData
            }).then(async response => {
                const isJson = response.headers.get('content-type')?.includes('application/json') || undefined;
                const data = isJson && await response.json();
                showToast(elements.toastSuccess, data.message);

                const wrap = document.createElement('li');
                const img = document.createElement('img');
                const inner = document.createElement('div');

                wrap.className = attachments.querySelector('li').getAttribute('class');
                img.className = attachments.querySelector('img').getAttribute('class');
                inner.className = attachments.querySelector('div').getAttribute('class');
                img.src = data.picture;

                wrap.appendChild(img);
                wrap.appendChild(inner);
                attachments.prepend(wrap);

                attachments.parentElement.classList.remove('invisible');
                status.classList.add('hidden');
                input.value = null;
                file = null;
            });
        });
    }

    if (elements.confirmChange.length > 0) {
        Array.from(elements.confirmChange).forEach((el) => {
            el.addEventListener('click', (event) => {
                event.preventDefault();
                let confirmModal = document.getElementById('changeConfirm');
                confirmModal.style.display = 'block';
                confirmModal.querySelector('a[role="button"]').addEventListener('click', async (event) => {
                    event.preventDefault();
                    await handleClick(el, confirmModal, elements);
                    document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden');
                }, eventOptions);
            });
        });
    }

    if (elements.confirmDelete.length > 0) {
        Array.from(elements.confirmDelete).forEach((el) => {
            el.addEventListener('click', (event) => {
                event.preventDefault();
                let confirmModal = document.getElementById('deleteConfirm');
                confirmModal.style.display = 'block';
                confirmModal.querySelector('a[role="button"]').addEventListener('click', async (event) => {
                    event.preventDefault();
                    await window.handleClick(el, confirmModal, elements);
                    el.parentElement.parentElement.remove();
                    document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden');
                }, eventOptions);
            });
        });
    }
});

window.handleClick = async (el, confirmModal, elements) => {
    let id = el.parentElement.getAttribute('data-id');
    let url = el.getAttribute('href');
    const response = await fetch(url, {
        method: 'post',
        body: JSON.stringify({id: id})
    });
    const data = await response.json();
    showToast(elements.toastSuccess, data.message);
    confirmModal.style.display = 'none';
};
