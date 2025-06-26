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
        .no-results {
            padding: 15px;
            text-align: center;
            color: #666;
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
                        }
                        
                        // Create HTML for result item
                        resultItem.innerHTML = `
                            <div class="search-result-title">
                                <span class="search-result-type ${typeClass}">${typeLabel}</span>
                                ${item.customer_name}
                            </div>
                            <div class="search-result-subtitle">
                                ${item.type === 'enquiry' ? item.lead_number : item.enquiry_number} | ${item.mobile_number} | ${formattedDate}
                            </div>
                        `;
                        
                        // Add click event to redirect
                        resultItem.addEventListener('click', function() {
                            window.location.href = `${item.redirect_url}?highlight=${item.id}`;
                        });
                        
                        searchResults.appendChild(resultItem);
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
        }
    });
    
    // Prevent form submission
    document.querySelector('.header-search form').addEventListener('submit', function(e) {
        e.preventDefault();
    });
});