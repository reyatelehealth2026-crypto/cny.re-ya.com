/**
 * Odoo Dashboard Local API Integration Layer
 * Provides fast, local-only data access using new cache tables
 * 
 * This file extends odoo-dashboard.js with local API capabilities.
 * Add this script tag AFTER odoo-dashboard.js:
 * <script src="odoo-dashboard-local.js"></script>
 * 
 * @version 1.0.0
 * @created 2026-03-11
 */

// Local API Configuration
const LOCAL_API = {
    endpoint: 'api/odoo-dashboard-local.php',
    enabled: true,
    fallbackToWebhook: true, // Fallback to old API if local fails
    cacheTTL: 30000 // 30 seconds client-side cache
};

// Local API client
async function localApiCall(action, params = {}) {
    const payload = { action, ...params, _t: Date.now() };
    
    try {
        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 10000); // 10s timeout for local
        
        const r = await fetch(LOCAL_API.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            signal: ctrl.signal
        });
        
        clearTimeout(timer);
        
        if (!r.ok) throw new Error('HTTP ' + r.status);
        
        const data = await r.json();
        return data;
    } catch (e) {
        return { success: false, error: e.message, local: true };
    }
}

// Check if local tables are available
async function checkLocalApiAvailable() {
    const res = await localApiCall('health');
    const available = res.success && res.data && res.data.status === 'ok';
    
    // Check if tables exist and have data
    if (available && res.data.local_tables) {
        const tables = res.data.local_tables;
        const hasData = Object.values(tables).some(t => t.exists && t.count > 0);
        return { available, hasData, tables };
    }
    
    return { available, hasData: false, tables: {} };
}

// ============================================
// REPLACEMENT FUNCTIONS (override original)
// ============================================

// Store original functions
const _original = {
    loadTodayOverview: typeof loadTodayOverview === 'function' ? loadTodayOverview : null,
    loadCustomers: typeof loadCustomers === 'function' ? loadCustomers : null,
    loadSlips: typeof loadSlips === 'function' ? loadSlips : null,
    loadWebhookStats: typeof loadWebhookStats === 'function' ? loadWebhookStats : null
};

// Override: Load Today's Overview (KPI cards)
async function loadTodayOverviewLocal() {
    // Try local API first
    const res = await localApiCall('overview_kpi');
    
    if (!res.success || !res.data) {
        console.log('[LocalAPI] Overview KPI not available, using fallback');
        if (_original.loadTodayOverview) return _original.loadTodayOverview();
        return;
    }
    
    const kpi = res.data;
    
    // Update KPI cards
    const kpiOrdersToday = document.getElementById('kpiOrdersToday');
    const kpiSalesToday = document.getElementById('kpiSalesToday');
    const kpiSlipsPending = document.getElementById('kpiSlipsPending');
    const kpiBdosPending = document.getElementById('kpiBdosPending');
    const kpiPaymentsToday = document.getElementById('kpiPaymentsToday');
    const kpiOverdueCustomers = document.getElementById('kpiOverdueCustomers');
    
    if (kpiOrdersToday) kpiOrdersToday.textContent = Number(kpi.orders?.today || 0).toLocaleString();
    if (kpiSalesToday) kpiSalesToday.textContent = '฿' + Number(kpi.revenue?.today || 0).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0});
    if (kpiSlipsPending) kpiSlipsPending.textContent = Number(kpi.slips?.pending || 0).toLocaleString();
    if (kpiOverdueCustomers) kpiOverdueCustomers.textContent = Number(kpi.invoices?.overdue || 0).toLocaleString();
    
    // Update lists
    await Promise.all([
        loadRecentOrdersLocal(),
        loadPendingSlipsLocal(),
        loadOverdueCustomersLocal()
    ]);
    
    console.log('[LocalAPI] Overview loaded from local cache');
}

// Load recent orders for overview
async function loadRecentOrdersLocal() {
    const res = await localApiCall('orders_today');
    if (!res.success) return;
    
    const container = document.getElementById('overviewRecentOrders');
    if (!container) return;
    
    const orders = res.data.orders || [];
    if (orders.length === 0) {
        container.innerHTML = '<p style="color:var(--gray-400);padding:1rem;text-align:center;">ยังไม่มีออเดอร์วันนี้</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:0.5rem;">';
    orders.forEach(o => {
        html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem;border-radius:6px;background:#f8fafc;">
            <div style="display:flex;flex-direction:column;">
                <span style="font-size:0.85rem;font-weight:600;color:var(--gray-800);">${escapeHtml(o.order_key)}</span>
                <span style="font-size:0.75rem;color:var(--gray-500);">${escapeHtml(o.customer_name || '-')}</span>
            </div>
            <div style="text-align:right;">
                <span style="font-size:0.85rem;font-weight:600;color:var(--success);">฿${Number(o.amount_total || 0).toLocaleString()}</span>
                <span style="font-size:0.7rem;color:var(--gray-400);display:block;">${escapeHtml(o.state_display || o.state || '-')}</span>
            </div>
        </div>`;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Load pending slips for overview
async function loadPendingSlipsLocal() {
    const res = await localApiCall('slips_pending');
    if (!res.success) return;
    
    const container = document.getElementById('overviewPendingSlips');
    if (!container) return;
    
    const slips = res.data.slips || [];
    if (slips.length === 0) {
        container.innerHTML = '<p style="color:var(--gray-400);padding:1rem;text-align:center;">ไม่มีสลิปรอดำเนินการ</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:0.5rem;">';
    slips.slice(0, 5).forEach(s => {
        html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem;border-radius:6px;background:#fffbeb;">
            <div style="display:flex;flex-direction:column;">
                <span style="font-size:0.8rem;font-weight:500;color:var(--gray-800);">${escapeHtml(s.customer_name || '-')}</span>
                <span style="font-size:0.7rem;color:var(--gray-500);">${escapeHtml(s.slip_id)}</span>
            </div>
            <div style="text-align:right;">
                <span style="font-size:0.8rem;font-weight:600;color:#d97706;">฿${Number(s.amount || 0).toLocaleString()}</span>
                <span style="font-size:0.7rem;color:var(--gray-400);display:block;">${escapeHtml(s.payment_date || '-')}</span>
            </div>
        </div>`;
    });
    if (slips.length > 5) {
        html += `<div style="text-align:center;font-size:0.75rem;color:var(--gray-400);padding:0.5rem;">+${slips.length - 5} รายการอื่น</div>`;
    }
    html += '</div>';
    
    container.innerHTML = html;
}

// Load overdue customers for overview
async function loadOverdueCustomersLocal() {
    const res = await localApiCall('invoices_overdue');
    if (!res.success) return;
    
    const container = document.getElementById('overviewOverdueCustomers');
    if (!container) return;
    
    const invoices = res.data.invoices || [];
    if (invoices.length === 0) {
        container.innerHTML = '<p style="color:var(--gray-400);padding:1rem;text-align:center;">ไม่มีลูกค้าค้างชำระ</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:0.5rem;">';
    invoices.slice(0, 5).forEach(inv => {
        html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem;border-radius:6px;background:#fee2e2;">
            <div style="display:flex;flex-direction:column;">
                <span style="font-size:0.8rem;font-weight:500;color:var(--gray-800);">${escapeHtml(inv.customer_name || '-')}</span>
                <span style="font-size:0.7rem;color:#dc2626;">${escapeHtml(inv.invoice_number)} · ${inv.days_overdue} วันเกินกำหนด</span>
            </div>
            <div style="text-align:right;">
                <span style="font-size:0.8rem;font-weight:600;color:#dc2626;">฿${Number(inv.amount_residual || 0).toLocaleString()}</span>
            </div>
        </div>`;
    });
    if (invoices.length > 5) {
        html += `<div style="text-align:center;font-size:0.75rem;color:var(--gray-400);padding:0.5rem;">+${invoices.length - 5} รายการอื่น</div>`;
    }
    html += '</div>';
    
    container.innerHTML = html;
}

// Override: Load Customers List
async function loadCustomersLocal() {
    const c = document.getElementById('customerList');
    if (!c) return;
    
    c.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spin"></i><div>กำลังโหลด (local)...</div></div>';
    
    const invoiceFilter = document.getElementById('custInvoiceFilter')?.value || '';
    const sortBy = document.getElementById('custSortBy')?.value || '';
    const salespersonId = document.getElementById('custSalesperson')?.value || '';
    const search = document.getElementById('custSearch')?.value || '';
    
    const res = await localApiCall('customers_list', {
        limit: 30,
        offset: window.custCurrentOffset || 0,
        search,
        invoice_filter: invoiceFilter,
        sort_by: sortBy,
        salesperson_id: salespersonId
    });
    
    if (!res.success || !res.data) {
        // Fallback to original
        console.log('[LocalAPI] Customers local failed, using fallback');
        if (_original.loadCustomers) return _original.loadCustomers();
        c.innerHTML = '<p style="padding:1rem;color:var(--danger);">Error loading customers</p>';
        return;
    }
    
    const { customers, total } = res.data;
    
    const tc = document.getElementById('custTotalCount');
    if (tc) tc.textContent = Number(total || 0).toLocaleString() + ' รายการ';
    
    if (!customers || customers.length === 0) {
        c.innerHTML = '<p style="padding:1rem;color:var(--gray-500);text-align:center;">ไม่พบข้อมูลลูกค้า</p>';
        return;
    }
    
    // Render customer table
    let html = '<table class="data-table" style="width:100%;"><thead><tr>';
    html += '<th>ลูกค้า</th><th>รหัส</th><th>Partner ID</th><th>ยอดรวม</th><th>ออเดอร์</th><th>พนักงานขาย</th><th>LINE</th><th>ยอดค้าง/เกินกำหนด</th><th>การจัดการ</th>';
    html += '</tr></thead><tbody>';
    
    customers.forEach(cu => {
        const hasLine = cu.line_user_id ? 'เชื่อม' : 'ยังไม่';
        const lineBadge = cu.line_user_id 
            ? '<span style="background:#dcfce7;color:#16a34a;padding:2px 7px;border-radius:50px;font-size:0.72rem;">เชื่อม</span>'
            : '<span style="background:#f3f4f6;color:#9ca3af;padding:2px 7px;border-radius:50px;font-size:0.72rem;">ยังไม่</span>';
        
        const overdueBadge = cu.overdue_amount > 0
            ? `<span style="background:#fee2e2;color:#dc2626;padding:2px 7px;border-radius:50px;font-size:0.72rem;">฿${Number(cu.overdue_amount).toLocaleString()}</span>`
            : (cu.total_due > 0 ? `<span style="background:#fef3c7;color:#d97706;padding:2px 7px;border-radius:50px;font-size:0.72rem;">฿${Number(cu.total_due).toLocaleString()}</span>` : '-');
        
        html += `<tr style="cursor:pointer;" onclick="showCustomerDetail('${escapeHtml(cu.customer_id || cu.partner_id || '')}', '${escapeHtml(cu.customer_ref || '')}')">`;
        html += `<td><strong>${escapeHtml(cu.customer_name || '-')}</strong></td>`;
        html += `<td>${escapeHtml(cu.customer_ref || '-')}</td>`;
        html += `<td>${escapeHtml(cu.partner_id || '-')}</td>`;
        html += `<td style="text-align:right;">฿${Number(cu.spend_30d || 0).toLocaleString()}</td>`;
        html += `<td>${cu.orders_count_30d || 0} / ${cu.orders_count_total || 0}</td>`;
        html += `<td>${escapeHtml(cu.salesperson_name || '-')}</td>`;
        html += `<td>${lineBadge}</td>`;
        html += `<td>${overdueBadge}</td>`;
        html += `<td><button class="chip" onclick="event.stopPropagation();showCustomerDetail('${escapeHtml(cu.customer_id || cu.partner_id || '')}', '${escapeHtml(cu.customer_ref || '')}')">รายละเอียด</button></td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    
    // Pagination
    const totalPages = Math.ceil(total / 30);
    const currentPage = Math.floor((window.custCurrentOffset || 0) / 30) + 1;
    
    if (totalPages > 1) {
        html += '<div style="display:flex;justify-content:center;gap:0.5rem;margin-top:1rem;">';
        for (let i = 1; i <= Math.min(totalPages, 10); i++) {
            const active = i === currentPage ? 'background:var(--primary);color:white;' : 'background:var(--gray-100);color:var(--gray-600);';
            html += `<button onclick="window.custCurrentOffset=${(i-1)*30};loadCustomersLocal()" style="${active}border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-size:0.8rem;">${i}</button>`;
        }
        html += '</div>';
    }
    
    c.innerHTML = html;
    console.log('[LocalAPI] Customers loaded from local cache');
}

// Override: Show Customer Detail
async function showCustomerDetailLocal(customerId, ref) {
    const modal = document.getElementById('customerInvoiceModal');
    const content = document.getElementById('customerInvoiceContent');
    const title = document.getElementById('customerInvoiceTitle');
    
    if (!modal || !content) return;
    
    modal.style.display = 'flex';
    content.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spin"></i><div>กำลังโหลด (local)...</div></div>';
    
    const res = await localApiCall('customer_detail', { customer_id: customerId, partner_id: customerId });
    
    if (!res.success || !res.data) {
        // Fallback to original implementation
        console.log('[LocalAPI] Customer detail local failed, using fallback');
        // Call original function if exists
        if (typeof showCustomerDetail === 'function') {
            showCustomerDetail(customerId, ref);
        }
        return;
    }
    
    const data = res.data;
    const profile = data.profile || {};
    
    if (title) title.innerHTML = `<i class="bi bi-person-lines-fill"></i> ${escapeHtml(profile.customer_name || 'รายละเอียดลูกค้า')}`;
    
    let html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">';
    html += `<div class="info-box"><div class="info-label">รหัสลูกค้า</div><div class="info-value">${escapeHtml(profile.customer_ref || '-')}</div></div>`;
    html += `<div class="info-box"><div class="info-label">Partner ID</div><div class="info-value">${escapeHtml(profile.partner_id || profile.customer_id || '-')}</div></div>`;
    html += `<div class="info-box"><div class="info-label">ยอดซื้อรวม</div><div class="info-value">฿${Number(profile.spend_total || 0).toLocaleString()}</div></div>`;
    html += `<div class="info-box"><div class="info-label">ออเดอร์รวม</div><div class="info-value">${profile.orders_count_total || 0}</div></div>`;
    html += '</div>';
    
    // Recent orders
    if (data.orders && data.orders.length > 0) {
        html += '<h6 style="margin:1rem 0 0.5rem;font-weight:600;"><i class="bi bi-box-seam"></i> ออเดอร์ล่าสุด</h6>';
        html += '<div style="max-height:200px;overflow-y:auto;">';
        data.orders.slice(0, 10).forEach(o => {
            html += `<div style="display:flex;justify-content:space-between;padding:0.5rem;border-bottom:1px solid var(--gray-100);">
                <span>${escapeHtml(o.order_key)} · ${escapeHtml(o.customer_name || '-')}</span>
                <span style="font-weight:600;">฿${Number(o.amount_total || 0).toLocaleString()}</span>
            </div>`;
        });
        html += '</div>';
    }
    
    // Invoices
    if (data.invoices && data.invoices.length > 0) {
        html += '<h6 style="margin:1rem 0 0.5rem;font-weight:600;"><i class="bi bi-receipt"></i> ใบแจ้งหนี้</h6>';
        html += '<div style="max-height:200px;overflow-y:auto;">';
        data.invoices.forEach(inv => {
            const statusColor = inv.is_overdue ? '#dc2626' : (inv.state === 'paid' ? '#16a34a' : '#d97706');
            html += `<div style="display:flex;justify-content:space-between;padding:0.5rem;border-bottom:1px solid var(--gray-100);">
                <span>${escapeHtml(inv.invoice_number)}${inv.is_overdue ? ` <span style="color:#dc2626;font-size:0.75rem;">(${inv.days_overdue} วัน)</span>` : ''}</span>
                <span style="font-weight:600;color:${statusColor};">฿${Number(inv.amount_residual || 0).toLocaleString()}</span>
            </div>`;
        });
        html += '</div>';
    }
    
    content.innerHTML = html;
    console.log('[LocalAPI] Customer detail loaded from local cache');
}

// Override: Global Search
async function globalSearchLocal(query) {
    if (!query || query.length < 2) return { results: [], total: 0 };
    
    const res = await localApiCall('search_global', { q: query, limit: 20 });
    
    if (!res.success) {
        return { results: [], total: 0, error: res.error };
    }
    
    return res.data || { results: [], total: 0 };
}

// ============================================
// INITIALIZATION
// ============================================

// Auto-detect and switch to local API when available
async function initLocalApi() {
    const status = await checkLocalApiAvailable();
    
    console.log('[LocalAPI] Status:', status);
    
    if (status.available && status.hasData) {
        console.log('[LocalAPI] Local tables available with data - enabling local mode');
        
        // Override global functions
        if (typeof window !== 'undefined') {
            window.loadTodayOverview = loadTodayOverviewLocal;
            window.loadCustomers = loadCustomersLocal;
            window.showCustomerDetail = showCustomerDetailLocal;
            window.globalSearch = globalSearchLocal;
            
            console.log('[LocalAPI] Functions overridden to use local cache');
        }
        
        return true;
    } else if (status.available && !status.hasData) {
        console.log('[LocalAPI] Local tables exist but empty - need sync');
        return false;
    } else {
        console.log('[LocalAPI] Local tables not available - using fallback');
        return false;
    }
}

// Auto-init when DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLocalApi);
} else {
    initLocalApi();
}

// Expose for manual control
window.LocalApi = {
    call: localApiCall,
    check: checkLocalApiAvailable,
    init: initLocalApi,
    config: LOCAL_API
};

console.log('[LocalAPI] Module loaded - v1.0.0');
