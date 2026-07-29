import {createCollapsibleInitializer} from './collapsible';

function initFixedCostView() {
    createCollapsibleInitializer({
        rootSelector: '[data-fixed-cost-transactions]',
        toggleSelector: '[data-fixed-cost-toggle]',
        panelSelector: '[data-fixed-cost-panel]',
        chevronSelector: '[data-fixed-cost-chevron]',
    })();
}

document.addEventListener('DOMContentLoaded', initFixedCostView);
document.addEventListener('livewire:navigated', initFixedCostView);

