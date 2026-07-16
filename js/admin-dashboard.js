// =====================================
// BloodMate Admin Dashboard JavaScript
// =====================================

document.addEventListener('DOMContentLoaded', function () {

    if (!checkAdminAuth()) {
        window.location.href = 'login.html';
        return;
    }

    loadDashboardData();
    initializeQuickActions();

    // Refresh every 2 minutes
    setInterval(loadDashboardData, 120000);
});

// =====================================
// Auth Check
// =====================================

function checkAdminAuth() {
    const token = localStorage.getItem('admin_token');
    const user  = localStorage.getItem('admin_user');
    return !!(token && user); // true if both exist (local or real token)
}

// =====================================
// Load Dashboard Data
// =====================================

async function loadDashboardData() {

    const token = localStorage.getItem('admin_token');

    // If running locally without a PHP server, load mock data directly
    if (!token || token.startsWith('local_fallback_token')) {
        loadMockData();
        return;
    }

    try {
        const response = await fetch('../api/admin-dashboard-data.php', {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!response.ok) throw new Error('Server error');

        const data = await response.json();

        if (data.success) {
            updateDashboardStats(data.stats);
            loadRecentRequests(data.recent_requests);
            loadInventorySummary(data.inventory);
            loadRecentDonors(data.recent_donors);
            loadRecentMessages(data.recent_messages);
        } else {
            if (data.error === 'unauthorized') {
                localStorage.removeItem('admin_token');
                localStorage.removeItem('admin_user');
                window.location.href = 'login.html';
            }
        }

    } catch (error) {
        console.warn('API unavailable, using mock data:', error.message);
        loadMockData();
    }
}

// =====================================
// Mock Data (no PHP server)
// =====================================

function loadMockData() {

    updateDashboardStats({
        totalDonors:     12800,
        pendingRequests: 47,
        totalUnits:      9600,
        urgentRequests:  8
    });

    loadRecentRequests([
        { id: 1, name: 'Riya Sharma',   blood_group_required: 'O-',  urgency_level: 'Critical', request_date: '2024-06-18' },
        { id: 2, name: 'Arjun Patel',   blood_group_required: 'AB+', urgency_level: 'Urgent',   request_date: '2024-06-17' },
        { id: 3, name: 'Meena Joshi',   blood_group_required: 'B+',  urgency_level: 'Normal',   request_date: '2024-06-17' },
        { id: 4, name: 'Rahul Verma',   blood_group_required: 'A+',  urgency_level: 'Pending',  request_date: '2024-06-16' },
        { id: 5, name: 'Sunita Kapoor', blood_group_required: 'O+',  urgency_level: 'Urgent',   request_date: '2024-06-15' },
    ]);

    loadInventorySummary([
        { blood_group: 'O+',  units_available: 88 },
        { blood_group: 'A+',  units_available: 72 },
        { blood_group: 'B+',  units_available: 55 },
        { blood_group: 'AB+', units_available: 44 },
        { blood_group: 'A-',  units_available: 28 },
        { blood_group: 'O-',  units_available: 12 },
        { blood_group: 'B-',  units_available: 18 },
        { blood_group: 'AB-', units_available: 9  },
    ]);

    loadRecentDonors([
        { name: 'Ankit Desai',  blood_group: 'A+',  city: 'Ahmedabad', created_at: '2024-06-19' },
        { name: 'Priya Mehta',  blood_group: 'O-',  city: 'Surat',     created_at: '2024-06-18' },
        { name: 'Vikram Singh', blood_group: 'B+',  city: 'Vadodara',  created_at: '2024-06-17' },
        { name: 'Kavita Rao',   blood_group: 'AB-', city: 'Rajkot',    created_at: '2024-06-16' },
    ]);

    loadRecentMessages([
        { subject: 'Emergency O- request',       name: 'Ravi Kumar',   email: 'ravi@example.com', message: 'Is O- blood available for emergency surgery tomorrow?', created_at: '2024-06-19' },
        { subject: 'Thank you!',                 name: 'Sunita Patel', email: 'sunita@example.com', message: 'Thank you for connecting me with a donor so quickly.', created_at: '2024-06-18' },
        { subject: 'Next donation schedule',     name: 'Amit Joshi',   email: 'amit@example.com', message: 'When can I schedule my next donation appointment?', created_at: '2024-06-17' },
        { subject: 'AB+ units needed urgently',  name: 'Dr. Mehta',    email: 'dr.mehta@hospital.com', message: 'We need 5 units of AB+ by tomorrow morning for a surgery.', created_at: '2024-06-16' },
    ]);
}

// =====================================
// Update Stats
// =====================================

function updateDashboardStats(stats) {
    const map = {
        totalDonors:     'totalDonors',
        pendingRequests: 'pendingRequests',
        totalUnits:      'totalUnits',
        urgentRequests:  'urgentRequests'
    };

    Object.keys(map).forEach(key => {
        const el = document.getElementById(map[key]);
        if (el && stats[key] !== undefined) animateCounterTo(el, stats[key]);
    });
}

// =====================================
// Render: Recent Requests
// =====================================

function loadRecentRequests(requests) {
    const tbody = document.getElementById('recentRequests');
    if (!tbody) return;

    tbody.innerHTML = '';

    const badgeMap = {
        Critical: 'badge-critical',
        Urgent:   'badge-urgent',
        Normal:   'badge-normal',
        Pending:  'badge-pending'
    };

    requests.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${r.name}</td>
            <td><span class="blood-type-chip">${r.blood_group_required}</span></td>
            <td><span class="badge ${badgeMap[r.urgency_level] || 'badge-normal'}">${r.urgency_level}</span></td>
            <td style="color:var(--text-muted)">${formatDate(r.request_date)}</td>
            <td><button class="tbl-btn" onclick="approveRequest(${r.id})">Review</button></td>
        `;
        tbody.appendChild(tr);
    });
}

// =====================================
// Render: Inventory
// =====================================

function loadInventorySummary(inventory) {
    const container = document.getElementById('inventorySummary');
    if (!container) return;

    container.innerHTML = '';

    inventory.forEach(item => {
        const pct = Math.min(Math.round((item.units_available / 100) * 100), 100);
        const cls = item.units_available <= 15 ? 'low' : item.units_available <= 40 ? 'mid' : 'ok';

        const div = document.createElement('div');
        div.className = 'inv-item';
        div.innerHTML = `
            <div class="inv-row">
                <span class="inv-type">${item.blood_group}</span>
                <span class="inv-units">${item.units_available} units</span>
            </div>
            <div class="inv-bar-bg">
                <div class="inv-bar ${cls}" data-w="${pct}" style="width:0"></div>
            </div>
        `;
        container.appendChild(div);
    });

    // Animate bars in
    setTimeout(() => {
        container.querySelectorAll('.inv-bar[data-w]').forEach(b => {
            b.style.width = b.dataset.w + '%';
        });
    }, 200);
}

// =====================================
// Render: Recent Donors
// =====================================

function loadRecentDonors(donors) {
    const tbody = document.getElementById('recentDonors');
    if (!tbody) return;

    tbody.innerHTML = '';

    donors.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${d.name}</td>
            <td><span class="blood-type-chip">${d.blood_group}</span></td>
            <td style="color:var(--text-muted)">${d.city}</td>
            <td style="color:var(--text-muted)">${formatDate(d.created_at)}</td>
        `;
        tbody.appendChild(tr);
    });
}

// =====================================
// Render: Recent Messages
// =====================================

function loadRecentMessages(messages) {
    const container = document.getElementById('recentMessages');
    if (!container) return;

    container.innerHTML = '';

    messages.forEach((m, i) => {
        const initials = m.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
        const unread = i < 2;

        const div = document.createElement('div');
        div.className = `msg-item${unread ? ' msg-unread' : ''}`;
        div.innerHTML = `
            <div class="msg-avatar">${initials}</div>
            <div style="flex:1;min-width:0;">
                <div class="msg-name">${m.name}</div>
                <div class="msg-preview">${m.message.substring(0, 80)}${m.message.length > 80 ? '…' : ''}</div>
            </div>
            <div class="msg-time">${formatDate(m.created_at)}</div>
        `;
        container.appendChild(div);
    });
}

// =====================================
// Quick Actions
// =====================================

function initializeQuickActions() {
    const addStockForm = document.getElementById('addStockForm');
    if (addStockForm) {
        addStockForm.addEventListener('submit', handleAddStock);
    }

    // Close modal overlay on outside click
    window.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('open');
        }
    });
}

function addBloodStock() {
    openModal('addStockModal');
}

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

async function handleAddStock(event) {
    event.preventDefault();

    const form        = event.target;
    const submitBtn   = form.querySelector('button[type="submit"]');
    const originalTxt = submitBtn.innerHTML;
    const token       = localStorage.getItem('admin_token');

    submitBtn.disabled = true;
    submitBtn.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Adding...";

    // Local fallback — no API call needed
    if (!token || token.startsWith('local_fallback_token')) {
        await new Promise(r => setTimeout(r, 600));
        showNotification('Blood stock added (demo mode)', 'success');
        closeModal('addStockModal');
        form.reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalTxt;
        return;
    }

    try {
        const response = await fetch('../api/admin-add-stock.php', {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Authorization': `Bearer ${token}` }
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Blood stock added successfully', 'success');
            closeModal('addStockModal');
            form.reset();
            loadDashboardData();
        } else {
            showNotification(result.message || 'Failed to add stock', 'error');
        }

    } catch (err) {
        showNotification('Network error — running in demo mode', 'error');
    }

    submitBtn.disabled = false;
    submitBtn.innerHTML = originalTxt;
}

async function approveRequest(requestId) {
    if (!confirm('Approve this blood request?')) return;

    const token = localStorage.getItem('admin_token');

    // Local demo mode
    if (!token || token.startsWith('local_fallback_token')) {
        showNotification(`Request #${requestId} approved (demo mode)`, 'success');
        return;
    }

    try {
        const response = await fetch('../api/admin-approve-request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ request_id: requestId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Request approved successfully', 'success');
            loadDashboardData();
        } else {
            showNotification(result.message || 'Failed to approve request', 'error');
        }

    } catch (err) {
        showNotification('Network error', 'error');
    }
}

function sendNotification() {
    showNotification('Notification feature coming soon', 'success');
}

function generateReport() {
    const token = localStorage.getItem('admin_token');
    if (!token || token.startsWith('local_fallback_token')) {
        showNotification('Report generation requires a live server', 'error');
        return;
    }
    window.open('../api/generate-report.php', '_blank');
}

// =====================================
// Helpers
// =====================================

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getStatusClass(units) {
    if (units <= 5)  return 'critical';
    if (units <= 15) return 'low';
    if (units <= 30) return 'mid';
    return 'ok';
}

function animateCounterTo(element, target) {
    const start     = parseInt(element.textContent.replace(/,/g, '')) || 0;
    const dur       = 1600;
    const startTime = performance.now();

    function tick(now) {
        const t = Math.min((now - startTime) / dur, 1);
        const ease = 1 - Math.pow(1 - t, 3);
        element.textContent = Math.round(start + (target - start) * ease).toLocaleString();
        if (t < 1) requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
}

// =====================================
// Toast Notification
// =====================================

function showNotification(message, type) {
    const old = document.querySelector('.bm-toast');
    if (old) old.remove();

    const n = document.createElement('div');
    n.className = 'bm-toast';
    n.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'circle-exclamation'}"></i> ${message}`;

    Object.assign(n.style, {
        position:     'fixed',
        top:          '1.5rem',
        right:        '1.5rem',
        padding:      '0.85rem 1.3rem',
        background:   type === 'success' ? '#16a34a' : '#C0152A',
        color:        '#fff',
        fontFamily:   'Inter, sans-serif',
        fontSize:     '0.84rem',
        fontWeight:   '500',
        borderRadius: '4px',
        zIndex:       '9999',
        display:      'flex',
        alignItems:   'center',
        gap:          '0.5rem',
        boxShadow:    '0 8px 24px rgba(0,0,0,0.35)'
    });

    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}

// =====================================
// Logout
// =====================================

function logout() {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    window.location.href = 'login.html';
}