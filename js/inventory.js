// Blood Inventory JavaScript

document.addEventListener('DOMContentLoaded', function() {
    loadInventoryData();
    loadUrgentRequests();
    initializeInventoryControls();
    
    // Refresh data every 5 minutes
    setInterval(loadInventoryData, 300000);
});

function initializeInventoryControls() {
    const searchInput = document.getElementById('searchBloodType');
    if (searchInput) {
        searchInput.addEventListener('input', filterInventory);
    }
}

async function loadInventoryData() {
    try {
        const response = await fetch('api/get-inventory.php');
        const data = await response.json();
        
        if (data.success) {
            displayInventoryGrid(data.inventory);
            displayInventoryTable(data.inventory);
            updateInventoryStats(data.inventory);
            updateLastUpdated();
        } else {
            showNotification('Failed to load inventory data', 'error');
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
        showNotification('Error loading inventory data', 'error');
    }
}

function displayInventoryGrid(inventory) {
    const gridContainer = document.getElementById('inventoryGrid');
    if (!gridContainer) return;
    
    gridContainer.innerHTML = '';
    
    inventory.forEach(item => {
        const card = createBloodTypeCard(item);
        gridContainer.appendChild(card);
    });
}

function createBloodTypeCard(item) {
    const card = document.createElement('div');
    card.className = 'blood-type-card';
    
    const statusClass = getStatusClass(item.units_available);
    
    card.innerHTML = `
        <div class="blood-type">${item.blood_group}</div>
        <div class="units-available ${statusClass}">${item.units_available}</div>
        <div class="status">
            <span class="status-indicator ${statusClass}"></span>
            ${getStatusText(item.units_available)}
        </div>
        <div class="last-updated">
            Updated: ${formatTime(item.last_updated)}
        </div>
    `;
    
    return card;
}

function displayInventoryTable(inventory) {
    const tableBody = document.getElementById('inventoryTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    inventory.forEach(item => {
        const row = document.createElement('tr');
        const statusClass = getStatusClass(item.units_available);
        
        row.innerHTML = `
            <td><strong>${item.blood_group}</strong></td>
            <td>${item.units_available}</td>
            <td>
                <span class="status-indicator ${statusClass}"></span>
                ${getStatusText(item.units_available)}
            </td>
            <td>${formatDate(item.last_updated)}</td>
            <td>
                <button class="btn btn-outline btn-sm" onclick="requestBlood('${item.blood_group}')">
                    Request
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

function updateInventoryStats(inventory) {
    const totalUnits = inventory.reduce((sum, item) => sum + parseInt(item.units_available), 0);
    const criticalTypes = inventory.filter(item => parseInt(item.units_available) <= 5).length;
    
    const totalUnitsElement = document.getElementById('totalUnits');
    const criticalTypesElement = document.getElementById('criticalTypes');
    
    if (totalUnitsElement) {
        animateCounterTo(totalUnitsElement, totalUnits);
    }
    
    if (criticalTypesElement) {
        animateCounterTo(criticalTypesElement, criticalTypes);
    }
}

function animateCounterTo(element, target) {
    const current = parseInt(element.textContent) || 0;
    const increment = (target - current) / 20;
    let counter = current;
    
    const timer = setInterval(() => {
        counter += increment;
        if ((increment > 0 && counter >= target) || (increment < 0 && counter <= target)) {
            counter = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(counter);
    }, 50);
}

function getStatusClass(units) {
    if (units <= 5) return 'critical';
    if (units <= 15) return 'low';
    if (units <= 30) return 'moderate';
    return 'good';
}

function getStatusText(units) {
    if (units <= 5) return 'Critical';
    if (units <= 15) return 'Low';
    if (units <= 30) return 'Moderate';
    return 'Good';
}

async function loadUrgentRequests() {
    try {
        const response = await fetch('api/get-urgent-requests.php');
        const data = await response.json();
        
        if (data.success) {
            displayUrgentRequests(data.requests);
        }
    } catch (error) {
        console.error('Error loading urgent requests:', error);
    }
}

function displayUrgentRequests(requests) {
    const urgentGrid = document.getElementById('urgentNeeds');
    const noUrgent = document.getElementById('noUrgent');
    
    if (!urgentGrid) return;
    
    if (requests.length === 0) {
        urgentGrid.style.display = 'none';
        if (noUrgent) noUrgent.style.display = 'block';
        return;
    }
    
    urgentGrid.style.display = 'grid';
    if (noUrgent) noUrgent.style.display = 'none';
    
    urgentGrid.innerHTML = '';
    
    requests.forEach(request => {
        const card = document.createElement('div');
        card.className = 'urgent-card';
        
        card.innerHTML = `
            <h4>${request.blood_group_required} Blood Needed</h4>
            <p><strong>Patient:</strong> ${request.name}</p>
            <p><strong>Hospital:</strong> ${request.hospital_location}</p>
            <p><strong>Units Needed:</strong> ${request.units_needed}</p>
            <p><strong>Urgency:</strong> <span class="urgency-${request.urgency_level.toLowerCase()}">${request.urgency_level}</span></p>
            <p><strong>Required By:</strong> ${formatDate(request.request_date)}</p>
            <div class="urgent-actions">
                <button class="btn btn-primary btn-sm" onclick="contactForDonation('${request.id}')">
                    <i class="fas fa-phone"></i> Contact
                </button>
            </div>
        `;
        
        urgentGrid.appendChild(card);
    });
}

function filterInventory() {
    const searchTerm = document.getElementById('searchBloodType').value.toLowerCase();
    const cards = document.querySelectorAll('.blood-type-card');
    const rows = document.querySelectorAll('#inventoryTableBody tr');
    
    // Filter grid cards
    cards.forEach(card => {
        const bloodType = card.querySelector('.blood-type').textContent.toLowerCase();
        if (bloodType.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Filter table rows
    rows.forEach(row => {
        const bloodType = row.cells[0].textContent.toLowerCase();
        if (bloodType.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function refreshInventory() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    showLoading(button);
    
    loadInventoryData().then(() => {
        hideLoading(button, originalText);
        showNotification('Inventory data refreshed', 'success');
    }).catch(() => {
        hideLoading(button, originalText);
        showNotification('Failed to refresh inventory', 'error');
    });
}

function requestBlood(bloodGroup) {
    // Redirect to request form with pre-selected blood group
    window.location.href = `recipient-request.html?blood_group=${bloodGroup}`;
}

function contactForDonation(requestId) {
    // Open contact modal or redirect to donor contact page
    window.location.href = `contact.html?request_id=${requestId}`;
}

function updateLastUpdated() {
    const lastUpdatedElement = document.getElementById('lastUpdated');
    if (lastUpdatedElement) {
        const now = new Date();
        lastUpdatedElement.textContent = now.toLocaleString();
    }
}

// Pre-fill blood group if coming from inventory page
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const bloodGroup = urlParams.get('blood_group');
    
    if (bloodGroup) {
        const bloodGroupSelect = document.getElementById('blood_group_required');
        if (bloodGroupSelect) {
            bloodGroupSelect.value = bloodGroup;
        }
    }
});
