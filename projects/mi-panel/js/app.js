(() => {
    'use strict';

    const API_BASE = 'api';
    let allPrograms = [];
    let activeType = 'all';
    let activeCategory = null;
    let searchQuery = '';

    // ── DOM refs ──
    const grid         = document.getElementById('programsGrid');
    const emptyState   = document.getElementById('emptyState');
    const stats        = document.getElementById('stats');
    const searchInput  = document.getElementById('search');
    const typeFilters  = document.getElementById('typeFilters');
    const catFilters   = document.getElementById('categoryFilters');
    const launchModal  = document.getElementById('launchModal');
    const historyModal = document.getElementById('historyModal');

    // ── Init ──
    async function init() {
        await loadPrograms();
        setupEventListeners();
    }

    // ── Load programs ──
    async function loadPrograms() {
        try {
            const res = await fetch(`${API_BASE}/programs.php`);
            allPrograms = await res.json();
            buildCategoryFilters();
            renderStats();
            render();
        } catch (e) {
            grid.innerHTML = '<div class="empty-state">Failed to load programs. Is the PHP server running?</div>';
        }
    }

    // ── Build category filter buttons ──
    function buildCategoryFilters() {
        const cats = [...new Set(allPrograms.map(p => p.category))].sort();
        catFilters.innerHTML = cats.map(c =>
            `<button class="cat-btn" data-category="${esc(c)}">${esc(c)}</button>`
        ).join('');
    }

    // ── Stats ──
    function renderStats() {
        const services = allPrograms.filter(p => p.program_type === 'service').length;
        const terminals = allPrograms.filter(p => p.program_type === 'terminal').length;
        const guis = allPrograms.filter(p => p.program_type === 'gui').length;
        stats.innerHTML = `
            <span><span class="stat-value">${allPrograms.length}</span> programs</span>
            <span><span class="stat-value">${services}</span> services</span>
            <span><span class="stat-value">${terminals}</span> terminal</span>
            <span><span class="stat-value">${guis}</span> GUI</span>
        `;
    }

    // ── Render cards ──
    function render() {
        const filtered = allPrograms.filter(p => {
            if (activeType !== 'all' && p.program_type !== activeType) return false;
            if (activeCategory && p.category !== activeCategory) return false;
            if (searchQuery) {
                const q = searchQuery.toLowerCase();
                const haystack = `${p.name} ${p.package || ''} ${p.notes || ''} ${p.category}`.toLowerCase();
                if (!haystack.includes(q)) return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            return;
        }
        emptyState.style.display = 'none';

        grid.innerHTML = filtered.map(p => {
            const badgeClass = `badge-${p.program_type}`;
            const typeLabel = p.program_type.charAt(0).toUpperCase() + p.program_type.slice(1);
            const canLaunch = p.command_key && p.program_type !== 'service';
            const isService = p.program_type === 'service';

            return `
                <div class="program-card" data-id="${p.id}">
                    <div class="card-header">
                        <span class="card-name">${esc(p.name)}</span>
                        <div class="card-badges">
                            <span class="badge ${badgeClass}">${typeLabel}</span>
                        </div>
                    </div>
                    <div class="card-notes">${esc(p.notes || '')}</div>
                    ${isService ? `<div class="service-status" data-service="${esc(p.command_key || '')}">
                        <span class="status-dot status-loading"></span>
                        <span class="status-text">Checking...</span>
                    </div>` : ''}
                    <div class="card-footer">
                        <span class="card-category">${esc(p.category)}</span>
                        <div class="card-actions">
                            <button class="btn btn-history" data-program-id="${p.id}" title="History">History</button>
                            ${canLaunch ? `<button class="btn btn-launch" data-program-id="${p.id}" data-cmd="${esc(p.command_key || '')}">Launch</button>` : ''}
                            ${isService ? `<button class="btn btn-launch" data-program-id="${p.id}" data-cmd="${esc(p.command_key || '')}">Start</button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Fetch service statuses
        grid.querySelectorAll('.service-status').forEach(el => {
            checkServiceStatus(el.dataset.service, el);
        });
    }

    // ── Check service status ──
    async function checkServiceStatus(service, container) {
        if (!service) return;
        try {
            const res = await fetch(`${API_BASE}/status.php?service=${encodeURIComponent(service)}`);
            const data = await res.json();
            const dot = container.querySelector('.status-dot');
            const text = container.querySelector('.status-text');

            dot.classList.remove('status-loading');
            if (data.active) {
                dot.classList.add('status-active');
                text.textContent = 'Running';
                if (data.memory) text.textContent += ` (${data.memory})`;
            } else {
                dot.classList.add('status-inactive');
                text.textContent = 'Stopped';
            }
        } catch {
            const dot = container.querySelector('.status-dot');
            const text = container.querySelector('.status-text');
            dot.classList.remove('status-loading');
            dot.classList.add('status-inactive');
            text.textContent = 'Unknown';
        }
    }

    // ── Launch program ──
    async function launchProgram(programId, btn) {
        btn.classList.add('launching');
        btn.textContent = 'Running...';

        try {
            const res = await fetch(`${API_BASE}/launch.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ program_id: programId }),
            });
            const data = await res.json();

            if (data.error) {
                showToast(`Error: ${data.error}`, 'error');
            } else {
                showToast(
                    data.success
                        ? `${data.command} completed in ${data.duration_ms}ms`
                        : `${data.command} failed (exit ${data.exit_code})`,
                    data.success ? 'success' : 'error'
                );
                showLaunchResult(data);
            }
        } catch (e) {
            showToast(`Launch failed: ${e.message}`, 'error');
        } finally {
            btn.classList.remove('launching');
            btn.textContent = btn.dataset.cmd ? 'Start' : 'Launch';
        }
    }

    // ── Show launch result modal ──
    function showLaunchResult(data) {
        document.getElementById('modalTitle').textContent = data.success ? 'Success' : 'Failed';
        document.getElementById('launchMeta').innerHTML = `
            <span><span class="meta-label">Command:</span> <span class="meta-value">${esc(data.command)}</span></span>
            <span><span class="meta-label">Exit:</span> <span class="meta-value ${data.success ? 'exit-ok' : 'exit-fail'}">${data.exit_code}</span></span>
            <span><span class="meta-label">Duration:</span> <span class="meta-value">${data.duration_ms}ms</span></span>
        `;
        document.getElementById('launchOutput').textContent = data.output || '(no output)';
        launchModal.classList.add('open');
    }

    // ── Show history modal ──
    async function showHistory(programId) {
        const program = allPrograms.find(p => p.id == programId);
        document.getElementById('historyTitle').textContent = `History — ${program?.name || 'Unknown'}`;

        try {
            const res = await fetch(`${API_BASE}/history.php?program_id=${programId}&limit=30`);
            const data = await res.json();
            const tbody = document.getElementById('historyBody');
            const empty = document.getElementById('historyEmpty');

            if (data.length === 0) {
                tbody.innerHTML = '';
                empty.style.display = 'block';
            } else {
                empty.style.display = 'none';
                tbody.innerHTML = data.map(e => `
                    <tr>
                        <td>${esc(e.executed_at)}</td>
                        <td><pre>${esc(e.command_run)}</pre></td>
                        <td class="${e.exit_code === 0 ? 'exit-ok' : 'exit-fail'}">${e.exit_code}</td>
                        <td>${e.duration_ms}ms</td>
                        <td><pre>${esc(e.output || '')}</pre></td>
                    </tr>
                `).join('');
            }
        } catch {
            document.getElementById('historyBody').innerHTML = '';
            document.getElementById('historyEmpty').style.display = 'block';
            document.getElementById('historyEmpty').textContent = 'Failed to load history.';
        }
        historyModal.classList.add('open');
    }

    // ── Toast notification ──
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = msg;
        document.getElementById('toasts').appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // ── Event listeners ──
    function setupEventListeners() {
        // Search
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            render();
        });

        // Type filter tabs
        typeFilters.addEventListener('click', (e) => {
            const btn = e.target.closest('.tab');
            if (!btn) return;
            typeFilters.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            activeType = btn.dataset.type;
            render();
        });

        // Category filter
        catFilters.addEventListener('click', (e) => {
            const btn = e.target.closest('.cat-btn');
            if (!btn) return;
            const wasActive = btn.classList.contains('active');
            catFilters.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            if (wasActive) {
                activeCategory = null;
            } else {
                btn.classList.add('active');
                activeCategory = btn.dataset.category;
            }
            render();
        });

        // Card actions (delegated)
        grid.addEventListener('click', (e) => {
            const launchBtn = e.target.closest('.btn-launch');
            if (launchBtn) {
                launchProgram(parseInt(launchBtn.dataset.programId), launchBtn);
                return;
            }
            const histBtn = e.target.closest('.btn-history');
            if (histBtn) {
                showHistory(parseInt(histBtn.dataset.programId));
            }
        });

        // Close modals
        document.getElementById('modalClose').addEventListener('click', () => launchModal.classList.remove('open'));
        document.getElementById('historyClose').addEventListener('click', () => historyModal.classList.remove('open'));
        launchModal.addEventListener('click', (e) => { if (e.target === launchModal) launchModal.classList.remove('open'); });
        historyModal.addEventListener('click', (e) => { if (e.target === historyModal) historyModal.classList.remove('open'); });

        // Escape key closes modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                launchModal.classList.remove('open');
                historyModal.classList.remove('open');
            }
        });
    }

    // ── Helper: escape HTML ──
    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Boot ──
    init();
})();
