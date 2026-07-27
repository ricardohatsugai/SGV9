(() => {
    const passwordInput = document.querySelector('#senha');
    const toggleButton = document.querySelector('[data-password-toggle]');
    const emailInput = document.querySelector('#email');

    if (emailInput && !emailInput.value) {
        const savedEmail = document.cookie
            .split('; ')
            .find((item) => item.startsWith('sgv9_email='));

        if (savedEmail) {
            emailInput.value = decodeURIComponent(savedEmail.split('=').slice(1).join('='));
        }
    }

    if (!passwordInput || !toggleButton) {
        return;
    }

    toggleButton.addEventListener('click', () => {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        toggleButton.setAttribute(
            'aria-label',
            isHidden ? 'Ocultar senha' : 'Mostrar senha'
        );
        passwordInput.focus();
    });
})();
