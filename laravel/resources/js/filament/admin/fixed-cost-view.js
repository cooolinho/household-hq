function setupFixedCostTransactionsToggle(root) {
    const toggle = root.querySelector('[data-fixed-cost-toggle]');
    const panel = root.querySelector('[data-fixed-cost-panel]');
    const chevron = root.querySelector('[data-fixed-cost-chevron]');

    if (!toggle || !panel) {
        return;
    }

    const setOpenState = (open) => {
        console.log('setOpenState', open);

        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', 'true');
        }

        if (chevron) {
            chevron.classList.toggle('is-open', open);
        }
    };

    setOpenState(false);

    if (toggle.getAttribute('initialized') === 'true') {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        console.log('click isOpen', isOpen);

        setOpenState(!isOpen);
    });
    toggle.setAttribute('initialized', 'true');
}

function initFixedCostView() {
    document
        .querySelectorAll('[data-fixed-cost-transactions]')
        .forEach((root) => setupFixedCostTransactionsToggle(root));
}

document.addEventListener('DOMContentLoaded', initFixedCostView);
document.addEventListener('livewire:navigated', initFixedCostView);

