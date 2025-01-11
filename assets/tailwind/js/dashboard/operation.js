import './../utils';
import templateRow from "./template-row";
import Swal from "sweetalert2";
import i18next from "i18next";
import customCss from "./../customCss";

const entry = document.getElementById('file-upload'),
    dropzone = document.getElementById('dropzone'),
    upload = document.getElementById('upload'),
    preview = document.getElementById('preview'),
    progressArea = document.querySelector('.progress-area'),
    toastSuccess = document.getElementById('toast-success'),
    toastDanger = document.getElementById('toast-danger'),
    uploadedArea = document.querySelector('.uploaded-area'),
    imports = document.getElementById('imports');

let drops = imports.querySelectorAll('.remove');
let sync = imports.querySelectorAll('.sync');
let allowed = entry.getAttribute('accept').split(',');
let xhr = new XMLHttpRequest();
const formData = new FormData();

if(sync.length > 0) {
    [...sync].forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            Swal.fire({
                title: i18next.t('proceed'),
                text: i18next.t('operationText'),
                customClass: customCss,
                showCancelButton: true,
                confirmButtonText: i18next.t('proceed'),
                cancelButtonText: i18next.t('cancel'),
                icon: "info",
            }).then(async (result) => {
                if(result.isConfirmed) {
                    const response = await fetch(el.dataset.url);
                    const data = await response.json();
                    console.log(data);
                }
            });
        });
    });
}

dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('bg-yellow-50');
});

dropzone.addEventListener('dragleave', e => {
    e.preventDefault();
    dropzone.classList.remove('bg-yellow-50');
});

dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('bg-yellow-50');
    const file = e.dataTransfer.files[0];
    displayPreview(file);
    formData.append('file', file);
});

entry.addEventListener('change', e => {
    let file = e.target.files[0];

    if (!allowed.includes(file.type)) {
        upload.classList.add('pointer-events-none');
        dropzone.classList.remove('bg-yellow-50');
        return false;
    }
    displayPreview(file);

    dropzone.classList.add('bg-yellow-50');
    upload.classList.remove('pointer-events-none');
    formData.append('file', file);
});

upload.addEventListener('click', () => {
    xhr.open('POST', dropzone.dataset.url);

    xhr.upload.addEventListener('progress', ({loaded, total}) => {
        let fileLoaded = Math.floor((loaded / total) * 100);

        let progressHTML = `<div class="w-full bg-gray-200 rounded-full dark:bg-gray-700">` +
            `<div class="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" style="width: ${fileLoaded}%"> ${fileLoaded}%</div>` +
            `</div>`;

        uploadedArea.classList.add('onprogress');
        progressArea.innerHTML = progressHTML;

        if (loaded === total) {
            uploadedArea.classList.remove('onprogress');
        }
    });

    xhr.send(formData);

    xhr.onload = () => {
        const jsonResponse = JSON.parse(xhr.response);

        if (jsonResponse.error !== undefined) {
            showToast(toastDanger, jsonResponse.error);
        }
        if (jsonResponse.success !== undefined) {
            showToast(toastSuccess, jsonResponse.success);
            templateRow(table, jsonResponse.operation);
            prune(collection);
        }
        upload.classList.add('pointer-events-none');
        dropzone.classList.remove('bg-yellow-50');
    }
});

const prune = (drops) => {
    [...drops].forEach((el) => {
        el.addEventListener('click', e => {
            e.preventDefault();
            Swal.fire({
                text: i18next.t('question'),
                showCancelButton: true,
                confirmButtonText: i18next.t('proceed'),
                denyButtonText: i18next.t('cancel'),
                customClass: customCss,
                icon: "question",
                showLoaderOnConfirm: true
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await fetch(el.dataset.url, {
                        method: 'POST',
                    }).then((res) => {
                        el.closest('li').remove();
                    });
                }
            });
        });
    });
}

const displayPreview = file => {

    if (!allowed.includes(file.type)) {
        upload.classList.add('pointer-events-none');
        return false;
    }
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => {
        preview.textContent = file.name;
        preview.classList.remove('hidden');
        upload.classList.remove('pointer-events-none');
    };
}

prune(drops);