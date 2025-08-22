// Auto-populate check-in and check-out dates based on travel period
document.addEventListener('DOMContentLoaded', function() {
    // Get travel period dates from the page
    const travelPeriodElement = document.querySelector('.info-label:contains("Travel Period:")');
    let startDate = '';
    let endDate = '';
    
    // Find travel period text and extract dates
    const travelPeriodRows = document.querySelectorAll('.info-row');
    travelPeriodRows.forEach(row => {
        const label = row.querySelector('.info-label');
        if (label && label.textContent.includes('Travel Period:')) {
            const value = row.querySelector('.info-value');
            if (value) {
                const dateText = value.textContent.trim();
                const dates = dateText.split(' To ');
                if (dates.length === 2) {
                    // Convert from dd-mm-yyyy to yyyy-mm-dd format
                    const startParts = dates[0].split('-');
                    const endParts = dates[1].split('-');
                    if (startParts.length === 3 && endParts.length === 3) {
                        startDate = `${startParts[2]}-${startParts[1]}-${startParts[0]}`;
                        endDate = `${endParts[2]}-${endParts[1]}-${endParts[0]}`;
                    }
                }
            }
        }
    });
    
    // Auto-populate accommodation dates and set restrictions
    if (startDate && endDate) {
        // Set accommodation check-in/check-out dates
        const accomCheckIn = document.querySelectorAll('input[name*="accommodation"][name*="check_in"]');
        const accomCheckOut = document.querySelectorAll('input[name*="accommodation"][name*="check_out"]');
        
        accomCheckIn.forEach(input => {
            if (!input.value) input.value = startDate;
            input.setAttribute('min', startDate);
            input.setAttribute('max', endDate);
        });
        
        accomCheckOut.forEach(input => {
            if (!input.value) input.value = endDate;
            input.setAttribute('min', startDate);
            input.setAttribute('max', endDate);
        });
        
        // Set cruise check-in/check-out dates
        const cruiseCheckIn = document.querySelectorAll('input[name*="cruise"][name*="check_in"]');
        const cruiseCheckOut = document.querySelectorAll('input[name*="cruise"][name*="check_out"]');
        
        cruiseCheckIn.forEach(input => {
            if (!input.value) input.value = startDate + 'T09:00';
            input.setAttribute('min', startDate + 'T00:00');
            input.setAttribute('max', endDate + 'T23:59');
        });
        
        cruiseCheckOut.forEach(input => {
            if (!input.value) input.value = endDate + 'T17:00';
            input.setAttribute('min', startDate + 'T00:00');
            input.setAttribute('max', endDate + 'T23:59');
        });
    }
    
    // Auto-populate dates for dynamically added rows
    const originalAddAccommodationRow = window.addAccommodationRow;
    if (typeof originalAddAccommodationRow === 'function') {
        window.addAccommodationRow = function() {
            originalAddAccommodationRow();
            setTimeout(() => {
                if (startDate && endDate) {
                    const newCheckIn = document.querySelector('input[name*="accommodation"][name*="check_in"]:not([min])');
                    const newCheckOut = document.querySelector('input[name*="accommodation"][name*="check_out"]:not([min])');
                    if (newCheckIn) {
                        newCheckIn.value = startDate;
                        newCheckIn.setAttribute('min', startDate);
                        newCheckIn.setAttribute('max', endDate);
                    }
                    if (newCheckOut) {
                        newCheckOut.value = endDate;
                        newCheckOut.setAttribute('min', startDate);
                        newCheckOut.setAttribute('max', endDate);
                    }
                }
            }, 100);
        };
    }
    
    const originalAddCruiseRow = window.addCruiseRow;
    if (typeof originalAddCruiseRow === 'function') {
        window.addCruiseRow = function() {
            originalAddCruiseRow();
            setTimeout(() => {
                if (startDate && endDate) {
                    const newCheckIn = document.querySelector('input[name*="cruise"][name*="check_in"]:not([min])');
                    const newCheckOut = document.querySelector('input[name*="cruise"][name*="check_out"]:not([min])');
                    if (newCheckIn) {
                        newCheckIn.value = startDate + 'T09:00';
                        newCheckIn.setAttribute('min', startDate + 'T00:00');
                        newCheckIn.setAttribute('max', endDate + 'T23:59');
                    }
                    if (newCheckOut) {
                        newCheckOut.value = endDate + 'T17:00';
                        newCheckOut.setAttribute('min', startDate + 'T00:00');
                        newCheckOut.setAttribute('max', endDate + 'T23:59');
                    }
                }
            }, 100);
        };
    }
    
    // Add validation for date inputs
    document.addEventListener('change', function(e) {
        if (e.target.matches('input[name*="accommodation"][name*="check_"], input[name*="cruise"][name*="check_"]')) {
            if (startDate && endDate && e.target.value) {
                const inputDate = new Date(e.target.value);
                const minDate = new Date(startDate);
                const maxDate = new Date(endDate);
                
                if (inputDate < minDate || inputDate > maxDate) {
                    alert('Date must be within the travel period');
                    e.target.value = e.target.name.includes('check_in') ? startDate : endDate;
                }
            }
        }
    });
});