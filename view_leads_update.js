// Function to toggle Last Reason dropdown visibility
function toggleLastReasonDropdown(selectElement, rowId) {
    var lastReasonContainer = document.getElementById('last-reason-container-' + rowId);
    if (selectElement.value === 'Not Interested - Cancelled') {
        lastReasonContainer.style.display = 'block';
    } else {
        lastReasonContainer.style.display = 'none';
    }
}

// Initialize all dropdowns on page load
document.addEventListener('DOMContentLoaded', function() {
    var statusSelects = document.querySelectorAll('.lead-status-select');
    statusSelects.forEach(function(select) {
        var rowId = select.getAttribute('data-id');
        toggleLastReasonDropdown(select, rowId);
    });
});