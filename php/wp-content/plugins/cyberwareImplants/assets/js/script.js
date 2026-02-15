document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.classList.contains('cybercrudimplatns-form')) return;

    const nom = form.querySelector('input[name="cyberwareimplant_nom_implant"]')?.value?.trim();
    if (!nom) {
        e.preventDefault();
        alert("Nom obligatoire.");
    }
});
