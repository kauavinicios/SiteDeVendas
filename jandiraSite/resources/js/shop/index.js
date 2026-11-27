/**
 * JavaScript global da loja.
 *
 * Utilizar este arquivo somente para comportamentos
 * compartilhados entre várias páginas.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
            return;
        }

        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });

    document.addEventListener('change', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLSelectElement) && !(target instanceof HTMLInputElement)) {
            return;
        }

        if (!target.hasAttribute('data-auto-submit')) {
            return;
        }

        target.form?.submit();
    });
});
