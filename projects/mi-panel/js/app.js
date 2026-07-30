(() => {
    'use strict';

    const API_BASE = 'api';
    const COOKIE_NAME = 'mi_panel_state';
    let allPrograms = [];
    let serviceStates = {};  // { command_key: { active, memory, pid } }
    let lastRun = {};        // { program_id: '2026-07-26 10:31:53' }
    let activeType = 'all';
    let activeCategory = null;
    let searchQuery = '';
    let sortBy = 'name';
    let currentLogsService = null;
    let showExternal = true;

    // ── Cookie helpers ──
    function saveState() {
        const state = { v: 2, t: activeType, c: activeCategory, q: searchQuery, s: sortBy, x: showExternal };
        document.cookie = `${COOKIE_NAME}=${encodeURIComponent(JSON.stringify(state))};path=/;max-age=2592000`;
    }

    function loadState() {
        const match = document.cookie.match(new RegExp('(?:^|; )' + COOKIE_NAME + '=([^;]*)'));
        if (!match) return;
        try {
            const state = JSON.parse(decodeURIComponent(match[1]));
            if (state && (state.v === 1 || state.v === 2)) {
                activeType = state.t || 'all';
                activeCategory = state.c || null;
                searchQuery = state.q || '';
                sortBy = state.s || 'name';
                if (state.v >= 2) showExternal = state.x !== false;
            }
        } catch { /* ignore corrupt cookie */ }
    }

    // ── DOM refs ──
    const grid         = document.getElementById('programsGrid');
    const emptyState   = document.getElementById('emptyState');
    const stats        = document.getElementById('stats');
    const searchInput  = document.getElementById('search');
    const typeFilters  = document.getElementById('typeFilters');
    const catFilters   = document.getElementById('categoryFilters');
    const sortSelect   = document.getElementById('sortBy');
    const launchModal  = document.getElementById('launchModal');
    const historyModal = document.getElementById('historyModal');
    const logsModal    = document.getElementById('logsModal');

    // ── Init ──
    async function init() {
        loadState();
        searchInput.value = searchQuery;
        sortSelect.value = sortBy;
        await loadPrograms();
        applyFilterUI();
        await loadLastRunTimes();
        render();
        setupEventListeners();
    }

    // ── Apply loaded state to filter UI ──
    function applyFilterUI() {
        // Restore active type tab
        typeFilters.querySelectorAll('.tab').forEach(t => {
            t.classList.toggle('active', t.dataset.type === activeType);
        });
        // Restore active category button
        if (activeCategory) {
            catFilters.querySelectorAll('.cat-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.category === activeCategory);
            });
        }
        // Restore sort select
        sortSelect.value = sortBy;
        // Restore external checkbox
        document.getElementById('showExternal').checked = showExternal;
    }

    // ── Load programs ──
    async function loadPrograms() {
        try {
            const res = await fetch(`${API_BASE}/programs.php`);
            allPrograms = await res.json();
            buildCategoryFilters();
            renderStats();
            fetchAllServiceStatuses();
        } catch (e) {
            grid.innerHTML = '<div class="empty-state">Failed to load programs. Is the PHP server running?</div>';
        }
    }

    // ── Load last execution times for all programs ──
    async function loadLastRunTimes() {
        try {
            const res = await fetch(`${API_BASE}/history.php?limit=200`);
            const data = await res.json();
            for (const e of data) {
                // Keep only the most recent per program
                if (!lastRun[e.program_id] || e.executed_at > lastRun[e.program_id]) {
                    lastRun[e.program_id] = e.executed_at;
                }
            }
        } catch { /* ignore */ }
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

    // ── Fetch all service statuses ──
    async function fetchAllServiceStatuses() {
        const services = allPrograms.filter(p => p.program_type === 'service' && p.command_key);
        const promises = services.map(async (p) => {
            const key = p.command_key;
            try {
                const res = await fetch(`${API_BASE}/status.php?service=${encodeURIComponent(key)}`);
                const data = await res.json();
                serviceStates[key] = data;
            } catch {
                serviceStates[key] = { active: false, enabled: false, status: 'unknown' };
            }
        });
        await Promise.allSettled(promises);
        render();  // re-render with status data
    }

    // ── Render cards ──
    function render() {
        const filtered = allPrograms.filter(p => {
            if (activeType !== 'all' && p.program_type !== activeType) return false;
            if (activeCategory && p.category !== activeCategory) return false;
            if (!showExternal && p.program_type === 'service' && !p.command_key) return false;
            if (searchQuery) {
                const q = searchQuery.toLowerCase();
                const haystack = `${p.name} ${p.package || ''} ${p.notes || ''} ${p.category}`.toLowerCase();
                if (!haystack.includes(q)) return false;
            }
            return true;
        });

        // Sort
        const typeOrder = { service: 0, terminal: 1, gui: 2 };
        filtered.sort((a, b) => {
            switch (sortBy) {
                case 'status': {
                    // Running services first, then stopped, then non-services
                    const aActive = a.program_type === 'service' && serviceStates[a.command_key]?.active;
                    const bActive = b.program_type === 'service' && serviceStates[b.command_key]?.active;
                    if (aActive !== bActive) return aActive ? -1 : 1;
                    return a.name.localeCompare(b.name);
                }
                case 'recent': {
                    const aTime = lastRun[a.id] || '';
                    const bTime = lastRun[b.id] || '';
                    if (aTime !== bTime) return aTime > bTime ? -1 : 1;
                    return a.name.localeCompare(b.name);
                }
                case 'type': {
                    const aType = typeOrder[a.program_type] ?? 3;
                    const bType = typeOrder[b.program_type] ?? 3;
                    if (aType !== bType) return aType - bType;
                    return a.name.localeCompare(b.name);
                }
                case 'port': {
                    const aPort = (a.program_type === 'service' && serviceStates[a.command_key]?.port) || 0;
                    const bPort = (b.program_type === 'service' && serviceStates[b.command_key]?.port) || 0;
                    // Services with port first (ascending), then no-port + non-services alphabetically
                    if (aPort && bPort) return aPort - bPort;
                    if (aPort && !bPort) return -1;
                    if (!aPort && bPort) return 1;
                    return a.name.localeCompare(b.name);
                }
                case 'name':
                default:
                    return a.name.localeCompare(b.name);
            }
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

            let statusHtml = '';
            let actionsHtml = '';

            if (isService) {
                const hasKey = !!p.command_key;
                const st = hasKey ? (serviceStates[p.command_key] || null) : null;
                const isActive = st ? st.active : false;
                const hasPort = st && st.port;

                statusHtml = `
                    <div class="service-status" data-service="${esc(p.command_key || '')}">
                        <span class="status-dot ${st ? (isActive ? 'status-active' : 'status-inactive') : 'status-loading'}"></span>
                        <span class="status-text">${st ? (isActive ? 'Running' : 'Stopped') : hasKey ? 'Checking...' : 'External'}</span>
                        ${st && st.memory ? `<span class="status-mem">(${esc(st.memory)})</span>` : ''}
                        ${st && st.port ? `<span class="card-port">Port ${st.port}</span>` : ''}
                    </div>`;

                if (hasKey) {
                    actionsHtml = isActive
                        ? `<button class="btn btn-stop" data-action="stop" data-service="${esc(p.command_key)}">Stop</button>
                           <button class="btn btn-restart" data-action="restart" data-service="${esc(p.command_key)}">Restart</button>`
                        : `<button class="btn btn-launch" data-action="start" data-service="${esc(p.command_key)}">Start</button>`;
                    actionsHtml += `<button class="btn btn-logs" data-service="${esc(p.command_key)}" data-program-name="${esc(p.name)}">Logs</button>`;

                    // Web link when running and has a port
                    if (isActive && hasPort) {
                        const url = `http://localhost:${st.port}${st.url_path || '/'}`;
                        actionsHtml += `<a class="btn btn-open" href="${esc(url)}" target="_blank" rel="noopener" title="Open in new tab">Open</a>`;
                    }
                }
            }

            return `
                <div class="program-card" data-id="${p.id}">
                    <div class="card-header">
                        <span class="card-name">${esc(p.name)}</span>
                        <div class="card-badges">
                            <span class="badge ${badgeClass}">${typeLabel}</span>
                        </div>
                    </div>
                    <div class="card-notes">${esc(p.notes || '')}</div>
                    ${statusHtml}
                    <div class="card-footer">
                        <span class="card-category">${esc(p.category)}</span>
                        <div class="card-actions">
                            <button class="btn btn-history" data-program-id="${p.id}" title="History">History</button>
                            ${canLaunch ? `<button class="btn btn-launch" data-program-id="${p.id}" data-cmd="${esc(p.command_key || '')}">Launch</button>` : ''}
                            ${actionsHtml}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // ── Service control (start/stop/restart) ──
    async function controlService(action, serviceKey, btn) {
        const origText = btn.textContent;
        btn.classList.add('launching');
        btn.textContent = `${action}...`;

        try {
            const res = await fetch(`${API_BASE}/service-control.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ service: serviceKey, action }),
            });
            const data = await res.json();

            if (data.error) {
                showToast(`Error: ${data.error}`, 'error');
            } else {
                showToast(
                    data.success
                        ? `${serviceKey}: ${action} OK (${data.duration_ms}ms)`
                        : `${serviceKey}: ${action} failed (exit ${data.exit_code})`,
                    data.success ? 'success' : 'error'
                );
                // Update local state
                serviceStates[serviceKey] = {
                    ...serviceStates[serviceKey],
                    active: data.new_status === 'active',
                };
                render();
            }
        } catch (e) {
            showToast(`Service control failed: ${e.message}`, 'error');
        } finally {
            // render() already replaced the button, so no need to restore
        }
    }

    // ── Launch program (non-service) ──
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
            btn.textContent = 'Launch';
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

    // ── Show logs modal ──
    async function showLogs(serviceKey, programName) {
        currentLogsService = serviceKey;
        document.getElementById('logsTitle').textContent = `Logs — ${programName}`;
        await loadLogs(serviceKey);
        logsModal.classList.add('open');
    }

    async function loadLogs(serviceKey) {
        const lines = document.getElementById('logsLines').value;
        const output = document.getElementById('logsOutput');
        const meta = document.getElementById('logsMeta');

        output.textContent = 'Loading...';
        meta.innerHTML = '';

        try {
            const res = await fetch(`${API_BASE}/logs.php?service=${encodeURIComponent(serviceKey)}&lines=${lines}`);
            const data = await res.json();

            if (data.error) {
                output.textContent = `Error: ${data.error}`;
                return;
            }

            meta.innerHTML = `
                <span>Source: <strong>${esc(data.source)}</strong></span>
                ${data.log_file ? `<span>File: <strong>${esc(data.log_file)}</strong></span>` : ''}
                <span>View with:</span>
                <code class="meta-cmd" title="Click to copy" data-copy="${esc(data.view_command)}">${esc(data.view_command)}</code>
            `;

            output.textContent = data.log || '(no log entries)';

            // Scroll to bottom
            output.scrollTop = output.scrollHeight;
        } catch (e) {
            output.textContent = `Failed to load logs: ${e.message}`;
        }
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
        // Sort
        sortSelect.addEventListener('change', (e) => {
            sortBy = e.target.value;
            saveState();
            render();
        });

        // Search
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            saveState();
            render();
        });

        // Type filter tabs
        typeFilters.addEventListener('click', (e) => {
            const btn = e.target.closest('.tab');
            if (!btn) return;
            typeFilters.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            activeType = btn.dataset.type;
            saveState();
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
            saveState();
            render();
        });

        // External services toggle
        document.getElementById('showExternal').addEventListener('change', (e) => {
            showExternal = e.target.checked;
            saveState();
            render();
        });

        // Card actions (delegated)
        grid.addEventListener('click', (e) => {
            // Service control: start/stop/restart
            const svcBtn = e.target.closest('.btn-stop, .btn-restart, .btn-launch[data-action]');
            if (svcBtn && svcBtn.dataset.action) {
                controlService(svcBtn.dataset.action, svcBtn.dataset.service, svcBtn);
                return;
            }

            // Logs button
            const logsBtn = e.target.closest('.btn-logs');
            if (logsBtn) {
                showLogs(logsBtn.dataset.service, logsBtn.dataset.programName);
                return;
            }

            // Launch (non-service)
            const launchBtn = e.target.closest('.btn-launch[data-program-id]');
            if (launchBtn) {
                launchProgram(parseInt(launchBtn.dataset.programId), launchBtn);
                return;
            }

            // History
            const histBtn = e.target.closest('.btn-history');
            if (histBtn) {
                showHistory(parseInt(histBtn.dataset.programId));
            }
        });

        // Logs refresh
        document.getElementById('logsRefresh').addEventListener('click', () => {
            if (currentLogsService) loadLogs(currentLogsService);
        });
        document.getElementById('logsLines').addEventListener('change', () => {
            if (currentLogsService) loadLogs(currentLogsService);
        });

        // Copy log command
        document.getElementById('logsMeta').addEventListener('click', (e) => {
            const cmd = e.target.closest('.meta-cmd');
            if (cmd) {
                navigator.clipboard.writeText(cmd.dataset.copy).then(() => {
                    showToast('Command copied to clipboard', 'success');
                });
            }
        });

        // Close modals
        document.getElementById('modalClose').addEventListener('click', () => launchModal.classList.remove('open'));
        document.getElementById('historyClose').addEventListener('click', () => historyModal.classList.remove('open'));
        document.getElementById('logsClose').addEventListener('click', () => logsModal.classList.remove('open'));
        launchModal.addEventListener('click', (e) => { if (e.target === launchModal) launchModal.classList.remove('open'); });
        historyModal.addEventListener('click', (e) => { if (e.target === historyModal) historyModal.classList.remove('open'); });
        logsModal.addEventListener('click', (e) => { if (e.target === logsModal) logsModal.classList.remove('open'); });

        // Escape key closes modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                launchModal.classList.remove('open');
                historyModal.classList.remove('open');
                logsModal.classList.remove('open');
            }
        });
    }

    // ── Helper: escape HTML ──
    function esc(str) {
        if (str == null) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // ── Boot ──
    init();
})();
