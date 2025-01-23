import {Dismiss, initFlowbite} from "flowbite";

initFlowbite();

let toast = document.getElementsByClassName('toast')[0];
let toastClose = document.querySelector('[data-dismiss-target=".toast"]');

if (toast !== undefined) {
    const options = {};
    const instanceOptions = {
        id: toast,
        override: true
    };
    const dismiss = new Dismiss(toast, toastClose, options, instanceOptions);

    dismiss.updateOnHide(function () {
        toast.classList.add('animate__animated', 'animate__fadeOutDown');
    });
}

let clipboardEmail, tooltipEmail, clipboardPhone, tooltipPhone = undefined;
const btnBox = document.querySelectorAll('.copy-picture');

if (btnBox.length > 0) {
    [...btnBox].forEach(element => {
        element.addEventListener('click', (e) => {
            e.preventDefault();
            const text = element.getAttribute('data-clipboard-text');
            const tooltip = element.nextElementSibling;
            const input = document.createElement('input');
            input.setAttribute('value', text);
            document.body.appendChild(input);
            input.select();

            try {
                tooltip.classList.remove('invisible', 'opacity-0');
                let copy = document.execCommand('copy');
                document.body.removeChild(input);
                setTimeout(() => {
                    tooltip.classList.add('invisible', 'opacity-0');
                }, 1500);
            } catch (e) {
                console.error(e);
            }
        });
    });
}

if (document.getElementById('email') !== null) {
    clipboardEmail = window.FlowbiteInstances.getInstance('CopyClipboard', 'email');
}

if (document.getElementById('tooltip-copy-email') !== null) {
    tooltipEmail = window.FlowbiteInstances.getInstance('Tooltip', 'tooltip-copy-email');
}

if (document.getElementById('phone') !== null) {
    clipboardPhone = window.FlowbiteInstances.getInstance('CopyClipboard', 'phone');
}

if (document.getElementById('tooltip-copy-phone') !== null) {
    tooltipPhone = window.FlowbiteInstances.getInstance('Tooltip', 'tooltip-copy-phone');
}

const defaultIconEmail = document.getElementById('default-icon-email');
const successIconEmail = document.getElementById('success-icon-email');

const defaultIconPhone = document.getElementById('default-icon-phone');
const successIconPhone = document.getElementById('success-icon-phone');

const defaultTooltipMessageEmail = document.getElementById('default-tooltip-message-email');
const successTooltipMessageEmail = document.getElementById('success-tooltip-message-email');

const defaultTooltipMessagePhone = document.getElementById('default-tooltip-message-phone');
const successTooltipMessagePhone = document.getElementById('success-tooltip-message-phone');

if (clipboardPhone !== undefined) {
    clipboardPhone.updateOnCopyCallback((clipboard) => {
        showSuccess(tooltipPhone, defaultIconPhone, successIconPhone, defaultTooltipMessagePhone, successTooltipMessagePhone);
        setTimeout(() => {
            resetToDefault(tooltipPhone, defaultIconPhone, successIconPhone, defaultTooltipMessagePhone, successTooltipMessagePhone);
        }, 5000);
    });
}

if (clipboardEmail !== undefined) {
    clipboardEmail.updateOnCopyCallback((clipboard) => {
        showSuccess(tooltipEmail, defaultIconEmail, successIconEmail, defaultTooltipMessageEmail, successTooltipMessageEmail);
        setTimeout(() => {
            resetToDefault(tooltipEmail, defaultIconEmail, successIconEmail, defaultTooltipMessageEmail, successTooltipMessageEmail);
        }, 5000);
    });
}

const showSuccess = (tooltip, defaultIcon, successIcon, defaultMessage, successMessage) => {
    defaultIcon.classList.add('hidden');
    successIcon.classList.remove('hidden');
    defaultMessage.classList.add('hidden');
    successMessage.classList.remove('hidden');
    tooltip.show();
}

const resetToDefault = (tooltip, defaultIcon, successIcon, defaultMessage, successMessage) => {
    defaultIcon.classList.remove('hidden');
    successIcon.classList.add('hidden');
    defaultMessage.classList.remove('hidden');
    successMessage.classList.add('hidden');
    tooltip.hide();
}