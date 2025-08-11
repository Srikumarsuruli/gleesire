// Agent Package Service calculations
function calculateAgentPackageTotal(row) {
    // Get the input elements
    const adultPriceInput = document.querySelector(`.agent-adult-price[data-row="${row}"]`);
    const adultCountInput = document.querySelector(`.agent-adult-count[data-row="${row}"]`);
    
    const childPriceInput = document.querySelector(`.agent-child-price[data-row="${row}"]`);
    const childCountInput = document.querySelector(`.agent-child-count[data-row="${row}"]`);
    
    const infantPriceInput = document.querySelector(`.agent-infant-price[data-row="${row}"]`);
    const infantCountInput = document.querySelector(`.agent-infant-count[data-row="${row}"]`);
    
    // Parse values
    const adultPrice = parseFloat(adultPriceInput?.value) || 0;
    const adultCount = parseInt(adultCountInput?.value) || 0;
    
    const childPrice = parseFloat(childPriceInput?.value) || 0;
    const childCount = parseInt(childCountInput?.value) || 0;
    
    const infantPrice = parseFloat(infantPriceInput?.value) || 0;
    const infantCount = parseInt(infantCountInput?.value) || 0;
    
    const total = (adultPrice * adultCount) + (childPrice * childCount) + (infantPrice * infantCount);
    
    document.querySelector(`.agent-package-total[data-row="${row}"]`).value = total.toFixed(2);
    calculateAgentPackageGrandTotal();
}

function calculateAgentPackageGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.agent-package-total').forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });
    document.getElementById('agent-package-grand-total').value = grandTotal.toFixed(2);
    updateSummaryTotals();
}

let agentPackageRowCount = 1;
function addAgentPackageRow() {
    const tbody = document.getElementById('agent-package-tbody');
    const newRow = document.createElement('tr');
    
    // Get values from the first row if it exists
    let adultCount = 0;
    let childCount = 0;
    let infantCount = 0;
    
    try {
        // Try to get values from the passenger info section
        const adultInfo = document.querySelector('.info-card:nth-child(3) .info-row:nth-child(1) .info-value');
        const childInfo = document.querySelector('.info-card:nth-child(3) .info-row:nth-child(2) .info-value');
        const infantInfo = document.querySelector('.info-card:nth-child(3) .info-row:nth-child(3) .info-value');
        
        if (adultInfo) adultCount = parseInt(adultInfo.textContent.trim()) || 0;
        if (childInfo) childCount = parseInt(childInfo.textContent.trim()) || 0;
        if (infantInfo) infantCount = parseInt(infantInfo.textContent.trim()) || 0;
    } catch (e) {
        console.log('Error getting passenger counts:', e);
    }
    
    newRow.innerHTML = `
        <td>
            <select class="form-control form-control-sm" name="agent_package[${agentPackageRowCount}][destination]">
                <option value="">Select Destination</option>
                <?php mysqli_data_seek($destinations, 0); while($dest = mysqli_fetch_assoc($destinations)): ?>
                    <option value="<?php echo htmlspecialchars($dest['name']); ?>"><?php echo htmlspecialchars($dest['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="agent_package[${agentPackageRowCount}][agent_supplier]" placeholder="Agent/Supplier"></td>
        <td><input type="date" class="form-control form-control-sm" name="agent_package[${agentPackageRowCount}][start_date]"></td>
        <td><input type="date" class="form-control form-control-sm" name="agent_package[${agentPackageRowCount}][end_date]"></td>
        <td><input type="number" class="form-control form-control-sm agent-adult-count" name="agent_package[${agentPackageRowCount}][adult_count]" data-row="${agentPackageRowCount}" value="${adultCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${agentPackageRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm agent-adult-price" name="agent_package[${agentPackageRowCount}][adult_price]" data-row="${agentPackageRowCount}" value="0" onchange="calculateAgentPackageTotal(${agentPackageRowCount})" style="width: 100px;"></td>
        <td><input type="number" class="form-control form-control-sm agent-child-count" name="agent_package[${agentPackageRowCount}][child_count]" data-row="${agentPackageRowCount}" value="${childCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${agentPackageRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm agent-child-price" name="agent_package[${agentPackageRowCount}][child_price]" data-row="${agentPackageRowCount}" value="0" onchange="calculateAgentPackageTotal(${agentPackageRowCount})" style="width: 100px;"></td>
        <td><input type="number" class="form-control form-control-sm agent-infant-count" name="agent_package[${agentPackageRowCount}][infant_count]" data-row="${agentPackageRowCount}" value="${infantCount}" style="width: 70px;" onchange="calculateAgentPackageTotal(${agentPackageRowCount})"></td>
        <td><input type="number" class="form-control form-control-sm agent-infant-price" name="agent_package[${agentPackageRowCount}][infant_price]" data-row="${agentPackageRowCount}" value="0" onchange="calculateAgentPackageTotal(${agentPackageRowCount})" style="width: 100px;"></td>
        <td><input type="text" class="form-control form-control-sm agent-package-total" name="agent_package[${agentPackageRowCount}][total]" data-row="${agentPackageRowCount}" readonly style="background: #f0f8ff; font-weight: bold; width: 120px;"></td>
    `;
    tbody.appendChild(newRow);
    agentPackageRowCount++;
}