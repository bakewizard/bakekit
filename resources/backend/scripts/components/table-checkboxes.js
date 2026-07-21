export function initTableCheckboxes() {
    const toggle = document.getElementById('toggle-checkbox');
    if (!toggle) return;

    toggle.addEventListener('change', e => {
        const rows = e.target.closest('table')?.tBodies[0]?.rows ?? [];
        for (const row of rows) {
            const checkbox = row.querySelector('input[name="ids[]"]') || row.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = e.target.checked;
            }
        }
    });
}
