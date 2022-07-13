(function () {
    function addDataAttributesForTableCells() {
        const table_elements = document.querySelectorAll('table');

        table_elements.forEach(function (table_element) {
            const header_cells = table_element.querySelectorAll('th');
            /** @var {NodeListOf<HTMLTableRowElement>} table_rows */
            const table_rows = table_element.querySelectorAll('tbody > tr');

            table_rows.forEach(function (table_row) {
                table_row.querySelectorAll('td').forEach(function (cell, index) {
                    const header_cell = header_cells[index];

                    if (!header_cell) {
                        return;
                    }

                    cell.dataset['label'] = header_cell.textContent;
                })
            });
        });
    }

    window.addEventListener('DOMContentLoaded', function () {
        addDataAttributesForTableCells();
    });
}());
