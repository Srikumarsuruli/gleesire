// Custom JavaScript for Lead Management System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-generate Lead Number
    if (document.getElementById('lead-number')) {
        generateLeadNumber();
    }

    // Handle status change to show/hide converted lead details
    const statusSelect = document.getElementById('lead-status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const convertedDetails = document.getElementById('converted-lead-details');
            if (this.options[this.selectedIndex].text === 'Converted' && convertedDetails) {
                convertedDetails.classList.remove('d-none');
            } else if (convertedDetails) {
                convertedDetails.classList.add('d-none');
            }
        });
    }

    // CSV file validation
    const csvUpload = document.getElementById('csv-upload');
    if (csvUpload) {
        csvUpload.addEventListener('change', function() {
            const fileName = this.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'csv') {
                alert('Please upload a CSV file');
                this.value = '';
            }
        });
    }

    // Date range picker initialization
    const dateRangePicker = document.getElementById('date-range');
    if (dateRangePicker) {
        // This would typically use a date range picker library
        // For simplicity, we're just adding an event listener
        dateRangePicker.addEventListener('change', function() {
            // Handle date range change
        });
    }
});

// Function to generate lead number
function generateLeadNumber() {
    const leadNumberField = document.getElementById('lead-number');
    if (leadNumberField) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        // Generate a random 4-digit number for the sequence
        const sequence = Math.floor(1000 + Math.random() * 9000);
        
        const leadNumber = `GHL/${year}/${month}/${day}/${sequence}`;
        leadNumberField.value = leadNumber;
    }
}

// Function to generate enquiry number
function generateEnquiryNumber() {
    const enquiryNumberField = document.getElementById('enquiry-number');
    if (enquiryNumberField) {
        // Generate a random 4-digit number for the sequence
        const sequence = Math.floor(1000 + Math.random() * 9000);
        
        const enquiryNumber = `GH ${sequence}`;
        enquiryNumberField.value = enquiryNumber;
    }
}

// Function to handle comment submission
function submitComment(enquiryId) {
    const commentText = document.getElementById('comment-text').value;
    if (!commentText.trim()) {
        alert('Please enter a comment');
        return;
    }
    
    // This would typically be an AJAX call to save the comment
    // For now, we'll just simulate adding the comment to the UI
    const commentsContainer = document.getElementById('comments-container');
    const now = new Date();
    
    const commentHtml = `
        <div class="comment-box">
            <div class="comment-header">
                <span class="comment-user">${currentUser}</span>
                <span class="comment-date">${now.toLocaleString()}</span>
            </div>
            <div class="comment-body">
                ${commentText}
            </div>
        </div>
    `;
    
    commentsContainer.innerHTML += commentHtml;
    document.getElementById('comment-text').value = '';
}

// Function to confirm actions
function confirmAction(message) {
    return confirm(message);
}

// Function to handle filter form submission
function applyFilters() {
    // This would typically submit the form or make an AJAX call
    document.getElementById('filter-form').submit();
}

// Function to reset filters
function resetFilters() {
    const filterForm = document.getElementById('filter-form');
    const formElements = filterForm.elements;
    
    for (let i = 0; i < formElements.length; i++) {
        const element = formElements[i];
        
        if (element.type === 'select-one') {
            element.selectedIndex = 0;
        } else if (element.type === 'text' || element.type === 'date') {
            element.value = '';
        }
    }
    
    filterForm.submit();
}