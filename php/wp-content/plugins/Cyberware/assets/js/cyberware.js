document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.classList.contains('cybercrud-form')) return;

    const action = form.querySelector('input[name="cybercrud_action"]')?.value || '';
    if (action !== 'create' && action !== 'update') return;

    const nom = form.querySelector('input[name="cybercrud_nom_client"]')?.value?.trim();
    if (!nom) {
        e.preventDefault();
        alert("Nom obligatoire.");
        return;
    }

    // au moins 1 implant (tu peux enlever si tu veux pas forcer)
    const checks = form.querySelectorAll('input[name="cybercrud_implants[]"]:checked');
    if (checks.length === 0) {
        e.preventDefault();
        alert("Choisis au moins 1 implant.");
    }
});
