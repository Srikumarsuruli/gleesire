// Function to set a cookie
function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

// Function to get a cookie
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

// Function to update lead status
function updateLeadStatus(id, status) {
    // Store the status in a cookie
    setCookie('lead_status_' + id, status, 30);
    
    // Show a small notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success position-fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.innerHTML = 'Status updated successfully';
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Apply saved statuses when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Find all lead status selects
    var statusSelects = document.querySelectorAll('.lead-status-select');
    
    // For each select, check if we have a saved status
    statusSelects.forEach(function(select) {
        var id = select.getAttribute('data-id');
        var savedStatus = getCookie('lead_status_' + id);
        
        if (savedStatus) {
            // Set the select to the saved status
            select.value = savedStatus;
        }
    });
    
    // Add event listeners to all lead status selects
    statusSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            var id = this.getAttribute('data-id');
            var status = this.value;
            updateLeadStatus(id, status);
        });
    });
});