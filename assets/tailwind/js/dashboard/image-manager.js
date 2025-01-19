window.addEventListener('load', function () {
    const images = document.getElementById('image');
    const btn = document.getElementById('images');
    const url = btn.getAttribute('data-url');
    const loadMore = document.getElementById('load-more');
    const manageContent = document.getElementById('image-manager-content');
    const injectBtn = document.getElementById('inject');
    const attachments = document.getElementById('attachments');
    let pictures = NodeList, ids = [];

    const loadContent = async (url) => {
        const response = await fetch(url);
        const data = await response.json();
        manageContent.innerHTML = data.html;
        loadMore.setAttribute('href', `${data.url}/?page=${data.nextPage}`);
    }

    loadMore.addEventListener('click', async (e) => {
        e.preventDefault();
        const response = await fetch(loadMore.getAttribute('href'));
        const data = await response.json();
        manageContent.insertAdjacentHTML('beforeend', data.html);
        if (data.nextPage > data.pages) {
            loadMore.classList.add('pointer-events-none');
        }
        loadMore.setAttribute('href', `${data.url}/?page=${data.nextPage}`);
    });

    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        await loadContent(url);
        pictures = await document.querySelectorAll('.pictures');
    });

    injectBtn.addEventListener('click', async () => {
        [...pictures].forEach((picture) => {
            if (picture.children[1].children[0].checked) {
                ids.push(parseInt(picture.children[1].children[0].value));
            }
        });
        if (ids.length > 0) {
            const response = await fetch(injectBtn.getAttribute('data-url'), {
                method: 'POST',
                body: JSON.stringify({
                    ids: ids,
                    id: injectBtn.getAttribute('data-id'),
                    target: injectBtn.getAttribute('data-target'),
                }),
                headers: new Headers({'Content-Type': 'application/json'}),
            });

            const data = await response.json();
            if (Object.keys(data.pictures).length > 0) {
                for (const [key, value] of Object.entries(data.pictures)) {
                    const li = document.createElement('li');
                    const img = document.createElement('img');
                    const div = document.createElement('div');
                    li.classList.add('relative', 'my-1', 'mx-0.5', 'overflow-hidden', 'bg-cover', 'bg-no-repeat');
                    img.classList.add('h-auto', 'w-full', 'md:max-w-xs', 'rounded-lg', 'bg-white');
                    img.setAttribute('src', value);
                    div.classList.add('overlay');
                    div.setAttribute('id', 'overlay');
                    li.appendChild(img);
                    li.appendChild(div);
                    attachments.appendChild(li);
                }
                document.getElementById('close').click();
            }
        }
    });

    images.addEventListener('change', async (e) => {
        e.preventDefault();
        const selectedFiles = images.files;

        if (selectedFiles.length === 0) {
            return;
        }

        const formData = new FormData();

        for (let i = 0; i < selectedFiles.length; i++) {
            formData.append("images[]", selectedFiles[i]);
        }

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            await loadContent(data.url);
        }
    });
});