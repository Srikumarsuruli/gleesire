// Agent Package calculations
function calculateAgentPackageTotal(row) {
    const adultCount = parseFloat(document.querySelector(`.agent-adult-count[data-row="${row}"]`).value) || 0;
    const adultPrice = parseFloat(document.querySelector(`.agent-adult-price[data-row="${row}"]`).value) || 0;
    const childCount = parseFloat(document.querySelector(`.agent-child-count[data-row="${row}"]`).value) || 0;
    const childPrice = parseFloat(document.querySelector(`.agent-child-price[data-row="${row}"]`).value) || 0;
    const infantCount = parseFloat(document.querySelector(`.agent-infant-count[data-row="${row}"]`).value) || 0;
    const infantPrice = parseFloat(document.querySelector(`.agent-infant-price[data-row="${row}"]`).value) || 0;
    
    const total = (adultCount * adultPrice) + (childCount * childPrice) + (infantCount * infantPrice);
    
    document.querySelector(`.agent-package-total[data-row="${row}"]`).value = total.toFixed(2);
    calculateAgentPackageGrandTotal();
}

function calculateAgentPackageGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.agent-package-total').forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });
    document.getElementById('agent-package-grand-total').value = grandTotal.toFixed(2);
    
    // Update summary totals if the function exists
    if (typeof updateSummaryTotals === 'function') {
        updateSummaryTotals();
    } else if (typeof calculateSummary === 'function') {
        calculateSummary();
    }
}

function addAgentPackageRow() {
    const tbody = document.getElementById('agent-package-tbody');
    const rows = tbody.querySelectorAll('tr');
    const newIndex = rows.length;
    
    // Get current PAX counts
    const adultsCount = document.querySelector('input[name="adults_count"]').value || 0;
    const childrenCount = document.querySelector('input[name="children_count"]').value || 0;
    const infantsCount = document.querySelector('input[name="infants_count"]').value || 0;
    
    const newRow = document.createElement('tr');
    
    // Get destinations for dropdown
    const destinationsSelect = document.querySelector('select[name="agent_package[0][destination]"]');
    let destinationsOptions = '';
    if (destinationsSelect) {
        destinationsOptions = destinationsSelect.innerHTML;
    }
    
    newRow.innerHTML = `
        <td>
            <select class="form-control form-control-sm" name="agent_package[${newIndex}][destination]">
                ${destinationsOptions}
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="agent_package[${newIndex}][agent_supplier]" placeholder="Agent/Supplier" style="width: 120px;"></td>
        <td><input type="date" class="form-control form-control-sm" name="agent_package[${newIndex}][start_date]"></td>
        <td><input type="date" class="form-control form-control-sm" name="agent_package[${newIndex}][end_date]"></td>
        <td><input type="number" class="form-control form-control-sm agent-adult-count" name="agent_package[${newIndex}][adult_count]" data-row="${newIndex}" value="${adultsCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
        <td><input type="number" class="form-control form-control-sm agent-adult-price" name="agent_package[${newIndex}][adult_price]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
        <td><input type="number" class="form-control form-control-sm agent-child-count" name="agent_package[${newIndex}][child_count]" data-row="${newIndex}" value="${childrenCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
        <td><input type="number" class="form-control form-control-sm agent-child-price" name="agent_package[${newIndex}][child_price]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
        <td><input type="number" class="form-control form-control-sm agent-infant-count" name="agent_package[${newIndex}][infant_count]" data-row="${newIndex}" value="${infantsCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
        <td><input type="number" class="form-control form-control-sm agent-infant-price" name="agent_package[${newIndex}][infant_price]" data-row="${newIndex}" value="0" style="width: 100px;" onchange="calculateAgentPackageTotal(${newIndex})"></td>
        <td><input type="text" class="form-control form-control-sm agent-package-total" name="agent_package[${newIndex}][total]" data-row="${newIndex}" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
    `;
    
    tbody.appendChild(newRow);
}