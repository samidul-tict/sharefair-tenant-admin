(function (window) {
    'use strict';

    window.initCaseCloseModal = function () {
        var closeModal = document.querySelector('[data-case-close-modal]');
        if (!closeModal || closeModal.dataset.initialized === 'true') return;

        closeModal.dataset.initialized = 'true';
        var closeForm = closeModal.querySelector('[data-case-close-form]');
        var submitButton = closeModal.querySelector('[data-case-close-submit]');
        var lastFocusedElement = null;

        function openCloseModal(event) {
            lastFocusedElement = event.currentTarget;
            closeModal.hidden = false;
            document.body.classList.add('cs-dist-email-modal-open');
            closeModal.querySelector('.cs-dist-email-actions [data-case-close-cancel]').focus();
        }

        function dismissCloseModal() {
            if (closeForm.dataset.submitting === 'true') return;
            closeModal.hidden = true;
            document.body.classList.remove('cs-dist-email-modal-open');
            if (lastFocusedElement) lastFocusedElement.focus();
        }

        document.querySelectorAll('[data-case-close-open]').forEach(function (button) {
            button.addEventListener('click', openCloseModal);
        });

        closeModal.querySelectorAll('[data-case-close-cancel]').forEach(function (button) {
            button.addEventListener('click', dismissCloseModal);
        });

        document.addEventListener('keydown', function (event) {
            if (closeModal.hidden) return;
            if (event.key === 'Escape') {
                dismissCloseModal();
                return;
            }
            if (event.key !== 'Tab') return;

            var focusableElements = Array.from(
                closeModal.querySelectorAll('button:not([disabled]), [href], input:not([disabled])')
            );
            var firstFocusable = focusableElements[0];
            var lastFocusable = focusableElements[focusableElements.length - 1];

            if (event.shiftKey && document.activeElement === firstFocusable) {
                event.preventDefault();
                lastFocusable.focus();
            } else if (!event.shiftKey && document.activeElement === lastFocusable) {
                event.preventDefault();
                firstFocusable.focus();
            }
        });

        closeForm.addEventListener('submit', function () {
            closeForm.dataset.submitting = 'true';
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Closing…';
        });
    };
})(window);
