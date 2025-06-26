// Script to highlight a row based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a highlight parameter in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    
    if (highlightId) {
        // Find all rows in tables
        const tables = document.querySelectorAll('table');
        let found = false;
        
        tables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                // Check if this row contains data for the highlighted ID
                // We'll look for data-id attributes or links containing the ID
                if (row.getAttribute('data-id') === highlightId) {
                    highlightRow(row);
                    found = true;
                    return;
                }
                
                // Check links within the row
                const links = row.querySelectorAll('a[href*="id="], button[data-id]');
                links.forEach(link => {
                    const href = link.getAttribute('href') || '';
                    const dataId = link.getAttribute('data-id') || '';
                    
                    if (href.includes('id=' + highlightId) || dataId === highlightId) {
                        highlightRow(row);
                        found = true;
                        return;
                    }
                });
            });
        });
        
        // If we found and highlighted a row, scroll to it
        if (found) {
            const highlightedRow = document.querySelector('tr.highlight-row');
            if (highlightedRow) {
                highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }
    
    function highlightRow(row) {
        row.classList.add('highlight-row');
    }
});