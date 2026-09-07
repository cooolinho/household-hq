import {createCollapsibleInitializer} from './collapsible';

function initInsuranceView() {
    createCollapsibleInitializer({
        rootSelector: '[data-insurance-collapsible]',
        toggleSelector: '[data-insurance-toggle]',
        panelSelector: '[data-insurance-panel]',
        chevronSelector: '[data-insurance-chevron]',
    })();
}

document.addEventListener('DOMContentLoaded', initInsuranceView);
document.addEventListener('livewire:navigated', initInsuranceView);

