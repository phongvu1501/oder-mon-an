import './bootstrap';
import { createIcons, icons } from 'lucide';
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => { sidebar?.classList.toggle('open'); overlay?.classList.toggle('open'); });
    overlay?.addEventListener('click', () => { sidebar?.classList.remove('open'); overlay.classList.remove('open'); });
    document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirm)) event.preventDefault();
    }));
    document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type=submit]');
        if (submit && ! form.hasAttribute('data-no-loading')) { submit.disabled = true; submit.textContent = 'Đang xử lý...'; }
    }));
});
