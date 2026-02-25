export function initTableCheckboxes() {
    const toggle = document.getElementById('toggle-checkbox');
    if (!toggle) return;

    toggle.addEventListener('change', e => {
        const rows = e.target.closest('table')?.tBodies[0]?.rows ?? [];
        for (const row of rows) {
            row.cells[0].firstElementChild.checked = e.target.checked;
        }
    });
}
