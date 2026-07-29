export function setupCollapsibleSection(root, options) {
    const {
        toggleSelector,
        panelSelector,
        chevronSelector,
        initializedAttribute = 'initialized',
    } = options;

    const toggle = root.querySelector(toggleSelector);
    const panel = root.querySelector(panelSelector);
    const chevron = chevronSelector ? root.querySelector(chevronSelector) : null;

    if (!toggle || !panel) {
        return;
    }

    const setOpenState = (open) => {
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

    if (toggle.getAttribute(initializedAttribute) === 'true') {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        setOpenState(!isOpen);
    });

    toggle.setAttribute(initializedAttribute, 'true');
}

export function createCollapsibleInitializer({
                                                 rootSelector,
                                                 toggleSelector,
                                                 panelSelector,
                                                 chevronSelector,
                                             }) {
    return () => {
        document
            .querySelectorAll(rootSelector)
            .forEach((root) => setupCollapsibleSection(root, {
                toggleSelector,
                panelSelector,
                chevronSelector,
            }));
    };
}

