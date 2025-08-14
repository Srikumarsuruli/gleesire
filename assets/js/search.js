// Search functionality for header search bar
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results';
    document.querySelector('.header-search').appendChild(searchResults);
    
    let searchTimeout;
    
    // Style for search results dropdown
    const style = document.createElement('style');
    style.textContent = `
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .search-results.active {
            display: block;
        }
        .search-result-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-result-item:hover {
            background: #f5f5f5;
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .search-result-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .search-result-subtitle {
            font-size: 12px;
            color: #666;
        }
        .search-result-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-right: 5px;
            color: white;
        }
        .search-result-type.enquiry {
            background: #007bff;
        }
        .search-result-type.lead {
            background: #28a745;
        }
        .search-result-type.booking {
            background: #6f42c1;
        }
        .search-result-type.comment {
            background: #fd7e14;
        }
        .no-results {
            padding: 15px;
            text-align: center;
            color: #666;
        }
        .search-result-details {
            display: none;
            padding: 10px;
            background: #f9f9f9;
            border-top: 1px solid #eee;
            margin-top: 5px;
        }
        .search-result-details.active {
            display: block;
        }
        .detail-row {
            margin-bottom: 5px;
            font-size: 12px;
        }
        .detail-label {
            font-weight: bold;
            display: inline-block;
            min-width: 120px;
        }
        .detail-value {
            display: inline-block;
        }
        .detail-section {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ddd;
        }
        .detail-section-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .comment-item {
            padding: 5px;
            border-left: 3px solid #ddd;
            margin-bottom: 5px;
        }
        .comment-text {
            font-style: italic;
        }
        .comment-meta {
            font-size: 11px;
            color: #777;
        }
    `;
    document.head.appendChild(style);
    
    // Search input event handler
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Hide results if query is empty
        if (query === '') {
            searchResults.classList.remove('active');
            return;
        }
        
        // Set timeout to prevent too many requests
        searchTimeout = setTimeout(function() {
            // Make AJAX request
            fetch(`search_results.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    // Clear previous results
                    searchResults.innerHTML = '';
                    
                    // Show results container
                    searchResults.classList.add('active');
                    
                    if (data.length === 0) {
                        searchResults.innerHTML = '<div class="no-results">No results found</div>';
                        return;
                    }
                    
                    // Display results
                    data.forEach(item => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'search-result-item';
                        
                        // Format date
                        const date = new Date(item.received_datetime);
                        const formattedDate = date.toLocaleDateString('en-GB');
                        
                        // Determine type label
                        let typeLabel = '';
                        let typeClass = '';
                        
                        switch(item.type) {
                            case 'enquiry':
                                typeLabel = 'Enquiry';
                                typeClass = 'enquiry';
                                break;
                            case 'lead':
                                typeLabel = 'Lead';
                                typeClass = 'lead';
                                break;
                            case 'booking':
                                typeLabel = 'Booking';
                                typeClass = 'booking';
                                break;
                            case 'comment':
                                typeLabel = 'Comment';
                                typeClass = 'comment';
                                break;
                        }
                        
                        // Create HTML for result item
                        if (item.type === 'comment') {
                            const commentDate = new Date(item.comment_date);
                            const formattedCommentDate = commentDate.toLocaleDateString('en-GB');
                            const truncatedComment = item.comment.length > 60 ? item.comment.substring(0, 60) + '...' : item.comment;
                            
                            resultItem.innerHTML = `
                                <div class="search-result-title">
                                    <span class="search-result-type ${typeClass}">${typeLabel}</span>
                                    Comments - ${item.customer_name}
                                </div>
                                <div class="search-result-subtitle">
                                    "${truncatedComment}" - ${item.comment_user} | ${formattedCommentDate}
                                </div>
                            `;
                        } else {
                            // Show both lead number and enquiry number if available
                            let numberDisplay = '';
                            if (item.lead_number && item.enquiry_number) {
                                numberDisplay = `${item.lead_number} → ${item.enquiry_number}`;
                            } else if (item.enquiry_number) {
                                numberDisplay = item.enquiry_number;
                            } else {
                                numberDisplay = item.lead_number;
                            }
                            
                            resultItem.innerHTML = `
                                <div class="search-result-title">
                                    <span class="search-result-type ${typeClass}">${typeLabel}</span>
                                    ${item.customer_name}
                                </div>
                                <div class="search-result-subtitle">
                                    ${numberDisplay} | ${item.mobile_number} | ${formattedDate}
                                    ${item.referral_code ? ' | Ref: ' + item.referral_code : ''}
                                </div>
                            `;
                        }
                        
                        // Create details container
                        const detailsContainer = document.createElement('div');
                        detailsContainer.className = 'search-result-details';
                        detailsContainer.innerHTML = '<div class="loading-details">Loading details...</div>';
                        
                        // Add click event to toggle details
                        resultItem.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // Toggle details container
                            if (detailsContainer.classList.contains('active')) {
                                detailsContainer.classList.remove('active');
                                return;
                            }
                            
                            // Hide all other active details
                            document.querySelectorAll('.search-result-details.active').forEach(el => {
                                el.classList.remove('active');
                            });
                            
                            // Show this details container
                            detailsContainer.classList.add('active');
                            
                            // Fetch details if not already loaded
                            if (detailsContainer.querySelector('.loading-details')) {
                                fetch(`get_record_details.php?id=${item.id}&type=${item.type}&t=${Date.now()}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        console.log('Debug: Received data:', data);
                                        let detailsHTML = '';
                                        
                                        // Basic enquiry details
                                        if (data.enquiry) {
                                            detailsHTML += '<div class="detail-section">';
                                            detailsHTML += '<div class="detail-section-title">Enquiry Details</div>';
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Lead Number:</span> <span class="detail-value">${data.enquiry.lead_number}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Customer Name:</span> <span class="detail-value">${data.enquiry.customer_name}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Mobile:</span> <span class="detail-value">${data.enquiry.mobile_number}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Email:</span> <span class="detail-value">${data.enquiry.email || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Referral Code:</span> <span class="detail-value">${data.enquiry.referral_code || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Department:</span> <span class="detail-value">${data.enquiry.department_name}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Source:</span> <span class="detail-value">${data.enquiry.source_name}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Status:</span> <span class="detail-value">${data.enquiry.status_name}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Attended By:</span> <span class="detail-value">${data.enquiry.attended_by_name}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Received Date:</span> <span class="detail-value">${new Date(data.enquiry.received_datetime).toLocaleString()}</span></div>`;
                                            detailsHTML += '</div>';
                                        }
                                        
                                        // Lead details if available
                                        console.log('Debug: Lead data exists:', !!data.lead);
                                        if (data.lead) {
                                            console.log('Debug: night_day value:', data.lead.night_day);
                                            console.log('Debug: night_day_name value:', data.lead.night_day_name);
                                            console.log('Debug: travel_month value:', data.lead.travel_month);
                                        }
                                        if (data.lead) {
                                            detailsHTML += '<div class="detail-section">';
                                            detailsHTML += '<div class="detail-section-title">Converted Enquiry Details</div>';
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Enquiry Number:</span> <span class="detail-value">${data.lead.enquiry_number}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Customer Location:</span> <span class="detail-value">${data.lead.customer_location || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Secondary Contact:</span> <span class="detail-value">${data.lead.secondary_contact || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Destination:</span> <span class="detail-value">${data.lead.destination_name || data.lead.others || 'N/A'}</span></div>`;
                                            let travelMonth = 'N/A';
                                            if (data.lead.travel_month && data.lead.travel_month !== null && data.lead.travel_month !== '') {
                                                travelMonth = data.lead.travel_month;
                                            }
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Travel Month:</span> <span class="detail-value">${travelMonth}</span></div>`;
                                            // Handle night_day display - check multiple possible fields
                                            let nightDayValue = 'N/A';
                                            if (data.lead.night_day_name) {
                                                nightDayValue = data.lead.night_day_name;
                                            } else if (data.lead.night_day && data.lead.night_day !== null && data.lead.night_day !== '') {
                                                nightDayValue = data.lead.night_day;
                                            }
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Night/Day:</span> <span class="detail-value">${nightDayValue}</span></div>`;
                                            let startDate = data.lead.travel_start_date ? new Date(data.lead.travel_start_date).toLocaleDateString() : 'N/A';
                                            let endDate = data.lead.travel_end_date ? new Date(data.lead.travel_end_date).toLocaleDateString() : 'N/A';
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Travel Period:</span> <span class="detail-value">${startDate} to ${endDate}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Adults Count:</span> <span class="detail-value">${data.lead.adults_count || '0'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Children Count:</span> <span class="detail-value">${data.lead.children_count || '0'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Infants Count:</span> <span class="detail-value">${data.lead.infants_count || '0'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Children Age Details:</span> <span class="detail-value">${data.lead.children_age_details || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Total Passengers:</span> <span class="detail-value">${(parseInt(data.lead.adults_count || 0) + parseInt(data.lead.children_count || 0) + parseInt(data.lead.infants_count || 0))}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Customer Available Timing:</span> <span class="detail-value">${data.lead.customer_available_timing || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">File Manager:</span> <span class="detail-value">${data.lead.file_manager_name || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Lead Priority:</span> <span class="detail-value">${data.lead.lead_type || 'N/A'}</span></div>`;
                                            detailsHTML += `<div class="detail-row"><span class="detail-label">Booking Status:</span> <span class="detail-value">${data.lead.booking_confirmed == 1 ? 'Confirmed' : 'Not Confirmed'}</span></div>`;
                                            if (data.lead.booking_confirmed == 1) {
                                                detailsHTML += `<div class="detail-row"><span class="detail-label">Sale Amount:</span> <span class="detail-value">₹${data.lead.sale_amount ? parseFloat(data.lead.sale_amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '0.00'}</span></div>`;
                                                detailsHTML += `<div class="detail-row"><span class="detail-label">Revenue Amount:</span> <span class="detail-value">₹${data.lead.revenue_amount ? parseFloat(data.lead.revenue_amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '0.00'}</span></div>`;
                                            }
                                            detailsHTML += '</div>';
                                        } else {
                                            console.log('Debug: No lead data found');
                                        }
                                        
                                        // Comments if available
                                        if (data.comments && data.comments.length > 0) {
                                            detailsHTML += '<div class="detail-section">';
                                            detailsHTML += '<div class="detail-section-title">Recent Comments</div>';
                                            
                                            data.comments.forEach(comment => {
                                                detailsHTML += '<div class="comment-item">';
                                                detailsHTML += `<div class="comment-text">${comment.comment}</div>`;
                                                detailsHTML += `<div class="comment-meta">By ${comment.user_name} on ${new Date(comment.created_at).toLocaleString()}</div>`;
                                                detailsHTML += '</div>';
                                            });
                                            
                                            detailsHTML += '</div>';
                                        }
                                        
                                        // Add edit details link
                                        detailsHTML += `<div class="detail-section">`;
                                        detailsHTML += `<a href="edit_enquiry.php?id=${item.id}" class="btn btn-sm btn-primary">Edit Details</a>`;
                                        detailsHTML += `</div>`;
                                        
                                        detailsContainer.innerHTML = detailsHTML;
                                    })
                                    .catch(error => {
                                        console.error('Error fetching details:', error);
                                        detailsContainer.innerHTML = '<div class="error-details">Error loading details</div>';
                                    });
                            }
                        });
                        
                        // Append result item and details container
                        searchResults.appendChild(resultItem);
                        searchResults.appendChild(detailsContainer);
                    });
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="no-results">Error fetching results</div>';
                    searchResults.classList.add('active');
                });
        }, 300);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.header-search')) {
            searchResults.classList.remove('active');
            // Also hide all detail containers
            document.querySelectorAll('.search-result-details').forEach(el => {
                el.classList.remove('active');
            });
        }
    });
    
    // Prevent form submission
    document.querySelector('.header-search form').addEventListener('submit', function(e) {
        e.preventDefault();
    });
});