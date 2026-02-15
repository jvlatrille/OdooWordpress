document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.classList.contains('cybercrudclient-form')) return;

    const action = form.querySelector('input[name="cyberwareclient_action"]')?.value || '';
    if (action !== 'create' && action !== 'update') return;

    const nom = form.querySelector('input[name="cyberwareclient_nom_client"]')?.value?.trim();
    if (!nom) {
        e.preventDefault();
        alert("Nom obligatoire.");
        return;
    }
    const checks = form.querySelectorAll('input[name="cyberwareclient_implants[]"]:checked');
    if (checks.length === 0) {
        e.preventDefault();
        alert("Choisis au moins 1 implant.");
    }
});
