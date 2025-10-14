// DataTables integration for lotReservationForm.php
// This script will be loaded after the table is rendered

document.addEventListener('DOMContentLoaded', function() {
    // Wait for the table to be rendered by the fetch
    const observer = new MutationObserver(function(mutations, obs) {
        const table = document.querySelector('#availableLotsContainer table');
        if (table && window.jQuery && window.jQuery.fn.dataTable) {
            if (!table.classList.contains('datatable-initialized')) {
                window.jQuery(table).DataTable({
                    paging: true,
                    searching: true,
                    lengthChange: true,
                    pageLength: 10,
                    order: [],
                    language: {
                        search: 'Filter:',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ lots',
                        infoEmpty: 'No lots available',
                        paginate: {
                            previous: 'Prev',
                            next: 'Next'
                        }
                    }
                });
                table.classList.add('datatable-initialized');
            }
            obs.disconnect();
        }
    });
    observer.observe(document.getElementById('availableLotsContainer'), { childList: true, subtree: true });
});
