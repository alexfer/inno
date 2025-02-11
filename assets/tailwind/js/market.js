import i18next from "i18next";
import messages from "./i18n";
import './utils';

await i18next.init(messages);

const cart = document.getElementById('show-cart');
const attributes = document.querySelectorAll('#attributes');
const forms = document.querySelectorAll('.shopping-cart');
const wishlists = document.querySelectorAll('.add-wishlist');
const comparison = document.querySelectorAll('.compare');
const headers = {'Content-type': 'application/json; charset=utf-8'};
const bulkRemoveWishlist = document.getElementById('bulk-remove');
const target = document.getElementById('dropdown');
const trigger = document.getElementById('dropdown-search');
const success = document.getElementById('toast-success');

if (typeof comparison != 'undefined') {
    [...comparison].forEach((form) => {
        const url = form.getAttribute('action');
        let product = form.querySelector('input[name="product"]');
        let button = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            fetch(url, {
                method: 'POST',
                body: JSON.stringify({product: product.value})
            })
                .then((response) => {
                    if (response.status === 401) {
                        return false;
                    }
                    button.classList.remove('text-gray-600');
                    button.classList.add('pointer-events-none', 'text-blue-500', 'animate__animated', 'animate__heartBeat');
                    return response.json();
                })
                .then((data) => {
                    document.getElementById('comparison').innerText = data.count;
                })
                .catch(err => {
                    console.log(err);
                });
        });
    });
}

if (target !== null) {
    [...target.getElementsByTagName('button')].forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById('search');
            let text = document.createTextNode(btn.textContent.trim());
            trigger.textContent = null;
            trigger.prepend(text);
            input.previousElementSibling.value = btn.getAttribute('data-slug');
            input.value = null;
            input.focus();
        });
    });
}

if (bulkRemoveWishlist !== null) {
    bulkRemoveWishlist.addEventListener('click', (event) => {
        event.preventDefault();
        let url = bulkRemoveWishlist.getAttribute('data-url');
        let bulks = document.getElementsByClassName('bulk-item');
        let items = [], els = [];

        [...bulks].forEach((bulk) => {
            if (bulk.querySelector('input').checked) {
                items.push(bulk.querySelector('input').value);
                els.push(bulk.querySelector('input').parentElement.parentElement);
            }
        });

        if (items.length > 0) {
            fetch(url, {
                method: 'POST',
                body: JSON.stringify({items: items}),
                headers: headers
            })
                .then((response) => {
                    if (response.status === 204) {
                        for (let i in els) {
                            els[i].remove();
                        }
                    }
                })
                .catch(err => {
                    console.log(err);
                });
        }
    });
}

if (cart !== null) {
    cart.addEventListener('click', () => {
        let url = cart.getAttribute('data-url');
        console.log(url);
        let body = document.getElementById('order-body');
        if (!body) return;
        body.innerHTML = '';
        fetch(url, {
            headers: headers
        })
            .then((response) => response.json())
            .then((json) => {
                body.innerHTML = json.template;
                if (parseInt(json.quantity) !== 0) {
                    body.children[0].classList.remove('visually-hidden');
                } else {
                    body.children[0].classList.toggle('visually-hidden');
                }
            })
            .catch(err => {
                console.log(err);
            });
    });
}

if (attributes.length) {
    Array.from(attributes).forEach((el) => {
        if (el.children !== null) {
            Array.from(el.children).forEach((item) => {
                if (item.hasAttribute('id')) {
                    Array.from(item.children).forEach((wrapper) => {
                        Array.from(wrapper.children).forEach((input) => {
                            let json = [];
                            input.addEventListener("change", (event) => {
                                if (event.currentTarget.checked) {
                                    let root = input.getAttribute('data-root-name');
                                    let value = input.getAttribute('data-name');
                                    let extra = input.getAttribute('data-extra');
                                    if (root === 'color') {
                                        json = {color: value, extra: extra};
                                    } else {
                                        json = {size: value, extra: extra};
                                    }
                                    el.querySelector('input[name="' + root + '"]').value = JSON.stringify(json);
                                    input.parentElement.parentElement.previousElementSibling.innerHTML = value;
                                }
                            });
                        });
                    });
                }
            });
        }
    });
}

if (typeof wishlists != 'undefined') {
    [...wishlists].forEach((form) => {
        const url = form.getAttribute('action');
        let store = form.querySelector('input[name="store"]');
        let button = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            fetch(url, {
                method: 'POST',
                body: JSON.stringify({store: store.value})
            })
                .then((response) => {
                    if (response.status === 401) {
                        return false;
                    }
                    button.classList.remove('text-gray-600');
                    button.classList.add('pointer-events-none', 'text-red-500', 'animate__animated', 'animate__heartBeat');
                    return response.json();
                })
                .catch(err => {
                    console.log(err);
                });
        });
    });
}

if (typeof forms != 'undefined') {
    Array.from(forms).forEach((form) => {
        const url = form.getAttribute('action');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            let color = form.querySelector('input[name="color"]');
            let size = form.querySelector('input[name="size"]');
            fetch(url, {
                method: 'POST',
                body: JSON.stringify({
                    quantity: 1,
                    color: color ? color.value : null,
                    size: size ? size.value : null,
                }),
                headers: headers
            })
                .then((response) => response.json())
                .then((json) => {
                    let qty = document.getElementById('qty');
                    if (qty) {
                        qty.innerHTML = json.store.quantity;
                        if (success !== undefined) {
                            showToast(success, json.store.message);
                        }
                        if (json.store.url !== null) {
                            fetch(json.store.url, {
                                method: 'OPTIONS',
                                body: JSON.stringify({session: json.store.session})
                            }).then((response) => response.text());
                        }
                    }

                })
                .catch(err => {
                    console.log(err);
                });
        }, true);
    });
}