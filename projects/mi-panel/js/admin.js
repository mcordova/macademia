(() => {
    'use strict';

    const API = 'api/admin.php';
    let allPrograms = [];
    let sortBy = 'id';
    let sortDir = 1; // 1 asc, -1 desc

    // ── DOM refs ──
    const tbody     = document.getElementById('adminBody');
    const empty     = document.getElementById('emptyState');
    const stats     = document.getElementById('stats');
    const search    = document.getElementById('search');
    const btnAdd    = document.getElementById('btnAdd');
    const btnScan   = document.getElementById('btnScan');
    const editModal = document.getElementById('editModal');
    const scanModal = document.getElementById('scanModal');
    const delModal  = document.getElementById('deleteModal');
    let deleteId    = null;

    // ── Init ──
    async function init() {
        await loadPrograms();
        setupEventListeners();
    }

    async function loadPrograms() {
        try {
            const res = await fetch(API);
            allPrograms = await res.json();
            render();
            renderStats();
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="9" class="error-cell">Failed to load: ${e.message}</td></tr>`;
        }
    }

    function renderStats() {
        const total = allPrograms.length;
        const enabled = allPrograms.filter(p => p.enabled).length;
        const disabled = total - enabled;
        stats.innerHTML = `
            <span><span class="stat-value">${total}</span> total</span>
            <span><span class="stat-value">${enabled}</span> enabled</span>
            <span><span class="stat-value">${disabled}</span> disabled</span>
        `;
    }

    function render() {
        const q = search.value.toLowerCase();
        const filtered = allPrograms.filter(p => {
            if (!q) return true;
            return `${p.name} ${p.package || ''} ${p.command_key || ''} ${p.category} ${p.notes || ''}`.toLowerCase().includes(q);
        });

        filtered.sort((a, b) => {
            let va, vb;
            switch (sortBy) {
                case 'id': va = a.id; vb = b.id; break;
                case 'name': va = a.name.toLowerCase(); vb = b.name.toLowerCase(); break;
                case 'category': va = a.category.toLowerCase(); vb = b.category.toLowerCase(); break;
                case 'type': va = a.program_type; vb = b.program_type; break;
                case 'enabled': va = a.enabled ? 1 : 0; vb = b.enabled ? 1 : 0; break;
                default: return (a.id - b.id) * sortDir;
            }
            if (va < vb) return -1 * sortDir;
            if (va > vb) return 1 * sortDir;
            return 0;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';

        tbody.innerHTML = filtered.map(p => `
            <tr class="${p.enabled ? '' : 'row-disabled'}">
                <td class="cell-id">${p.id}</td>
                <td><strong>${esc(p.name)}</strong></td>
                <td>${esc(p.package || '-')}</td>
                <td><code>${esc(p.command_key || '-')}</code></td>
                <td>${esc(p.category)}</td>
                <td><span class="badge badge-${p.program_type}">${p.program_type}</span></td>
                <td class="cell-notes">${esc(p.notes || '')}</td>
                <td>
                    <label class="toggle-switch">
                        <input type="checkbox" ${p.enabled ? 'checked' : ''} data-id="${p.id}">
                        <span class="toggle-slider"></span>
                    </label>
                </td>
                <td class="cell-actions">
                    <button class="btn btn-sm btn-edit" data-id="${p.id}">Edit</button>
                    <button class="btn btn-sm btn-danger" data-id="${p.id}">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    // ── Toggle enabled ──
    async function toggleEnabled(id, enabled) {
        try {
            const res = await fetch(`${API}?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enabled: enabled ? 1 : 0 }),
            });
            if (!res.ok) throw new Error((await res.json()).error || 'Failed');
            await loadPrograms();
            showToast(enabled ? 'Program enabled' : 'Program disabled', 'success');
        } catch (e) {
            showToast(`Error: ${e.message}`, 'error');
        }
    }

    // ── Delete ──
    async function deleteProgram(id) {
        try {
            const res = await fetch(`${API}?id=${id}`, { method: 'DELETE' });
            if (!res.ok) throw new Error((await res.json()).error || 'Failed');
            await loadPrograms();
            showToast('Program deleted', 'success');
        } catch (e) {
            showToast(`Error: ${e.message}`, 'error');
        }
    }

    // ── Save (create or update) ──
    async function saveProgram(data) {
        const isNew = !data.id;
        const url = isNew ? API : `${API}?id=${data.id}`;
        const method = isNew ? 'POST' : 'PUT';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            if (!res.ok) throw new Error((await res.json()).error || 'Failed');
            await loadPrograms();
            editModal.classList.remove('open');
            showToast(isNew ? 'Program created' : 'Program updated', 'success');
        } catch (e) {
            showToast(`Error: ${e.message}`, 'error');
        }
    }

    // ── Scan system ──
    async function scanSystem() {
        btnScan.classList.add('launching');
        btnScan.textContent = 'Scanning...';
        try {
            const res = await fetch(`${API}?action=scan`, { method: 'POST' });
            const data = await res.json();
            showScanResults(data);
        } catch (e) {
            showToast(`Scan failed: ${e.message}`, 'error');
        } finally {
            btnScan.classList.remove('launching');
            btnScan.textContent = 'Scan System';
        }
    }

    function showScanResults(data) {
        const body = document.getElementById('scanBody');
        let html = '';

        const packages = data.new_packages || [];
        const commands = data.orphan_commands || [];

        if (packages.length === 0 && commands.length === 0) {
            html = '<p>No new packages or commands found.</p>';
        } else {
            if (packages.length > 0) {
                html += `<h3 style="margin-bottom:0.5rem;">New Packages (${packages.length})</h3>`;
                html += '<table class="admin-table"><thead><tr><th>Package</th><th>Description</th></tr></thead><tbody>';
                packages.forEach(p => {
                    html += `<tr><td><code>${esc(p.package)}</code></td><td>${esc(p.notes)}</td></tr>`;
                });
                html += '</tbody></table>';
            }
            if (commands.length > 0) {
                html += `<h3 style="margin-top:1rem;margin-bottom:0.5rem;">Orphan Commands (${commands.length})</h3>`;
                html += '<p style="color:var(--text-dim);font-size:0.85rem;margin-bottom:0.5rem;">Commands in whitelist but not in any program card:</p>';
                html += '<table class="admin-table"><thead><tr><th>Key</th><th>Command</th></tr></thead><tbody>';
                commands.forEach(c => {
                    html += `<tr><td><code>${esc(c.command_key)}</code></td><td><code>${esc(c.command)}</code></td></tr>`;
                });
                html += '</tbody></table>';
            }
        }

        body.innerHTML = html;
        scanModal.classList.add('open');
    }

    // ── Edit modal helpers ──
    function openEdit(program) {
        document.getElementById('editTitle').textContent = program ? 'Edit Program' : 'Add Program';
        document.getElementById('editId').value = program ? program.id : '';
        document.getElementById('editName').value = program ? program.name : '';
        document.getElementById('editPackage').value = program ? (program.package || '') : '';
        document.getElementById('editCommandKey').value = program ? (program.command_key || '') : '';
        document.getElementById('editCategory').value = program ? program.category : 'Other';
        document.getElementById('editType').value = program ? program.program_type : 'terminal';
        document.getElementById('editNotes').value = program ? (program.notes || '') : '';
        document.getElementById('editEnabled').checked = program ? !!program.enabled : true;
        editModal.classList.add('open');
    }

    function getFormData() {
        return {
            id: document.getElementById('editId').value ? parseInt(document.getElementById('editId').value) : null,
            name: document.getElementById('editName').value.trim(),
            package: document.getElementById('editPackage').value.trim() || null,
            command_key: document.getElementById('editCommandKey').value.trim() || null,
            category: document.getElementById('editCategory').value.trim() || 'Other',
            program_type: document.getElementById('editType').value,
            notes: document.getElementById('editNotes').value.trim() || null,
            enabled: document.getElementById('editEnabled').checked ? 1 : 0,
        };
    }

    function buildCategoryList() {
        const cats = [...new Set(allPrograms.map(p => p.category))].sort();
        document.getElementById('catList').innerHTML = cats.map(c => `<option value="${esc(c)}">`).join('');
    }

    // ── Toast ──
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = msg;
        document.getElementById('toasts').appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // ── Event listeners ──
    function setupEventListeners() {
        search.addEventListener('input', render);

        btnAdd.addEventListener('click', () => openEdit(null));

        btnScan.addEventListener('click', scanSystem);

        // Sort by column (delegated on thead)
        document.querySelector('#adminTable thead').addEventListener('click', (e) => {
            const th = e.target.closest('th[data-sort]');
            if (!th) return;
            const key = th.dataset.sort;
            if (sortBy === key) {
                sortDir *= -1;
            } else {
                sortBy = key;
                sortDir = 1;
            }
            // Update arrow indicators
            document.querySelectorAll('#adminTable thead th[data-sort]').forEach(t => {
                const arrow = t.querySelector('.sort-arrow');
                if (arrow) arrow.textContent = '';
            });
            const arrow = th.querySelector('.sort-arrow');
            if (arrow) arrow.textContent = sortDir === 1 ? ' \u25B4' : ' \u25BE';
            render();
        });

        // Toggle enabled (delegated)
        tbody.addEventListener('change', (e) => {
            const cb = e.target.closest('.toggle-switch input[type="checkbox"]');
            if (cb) {
                toggleEnabled(parseInt(cb.dataset.id), cb.checked);
            }
        });

        // Edit/Delete buttons (delegated)
        tbody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.btn-edit');
            if (editBtn) {
                const id = parseInt(editBtn.dataset.id);
                const prog = allPrograms.find(p => p.id === id);
                if (prog) openEdit(prog);
                return;
            }

            const delBtn = e.target.closest('.btn-danger');
            if (delBtn) {
                deleteId = parseInt(delBtn.dataset.id);
                const prog = allPrograms.find(p => p.id === deleteId);
                document.getElementById('deleteText').textContent = `Delete "${prog?.name || 'Unknown'}" (ID: ${deleteId})?`;
                delModal.classList.add('open');
            }
        });

        // Edit form submit
        document.getElementById('editForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const data = getFormData();
            if (!data.name) return;
            saveProgram(data);
        });

        document.getElementById('editCancel').addEventListener('click', () => editModal.classList.remove('open'));
        document.getElementById('editClose').addEventListener('click', () => editModal.classList.remove('open'));
        editModal.addEventListener('click', (e) => { if (e.target === editModal) editModal.classList.remove('open'); });

        // Delete confirm
        document.getElementById('deleteConfirm').addEventListener('click', () => {
            if (deleteId) deleteProgram(deleteId);
            deleteId = null;
            delModal.classList.remove('open');
        });
        document.getElementById('deleteCancel').addEventListener('click', () => { deleteId = null; delModal.classList.remove('open'); });
        document.getElementById('deleteClose').addEventListener('click', () => { deleteId = null; delModal.classList.remove('open'); });
        delModal.addEventListener('click', (e) => { if (e.target === delModal) { deleteId = null; delModal.classList.remove('open'); } });

        // Scan modal close
        document.getElementById('scanClose').addEventListener('click', () => scanModal.classList.remove('open'));
        scanModal.addEventListener('click', (e) => { if (e.target === scanModal) scanModal.classList.remove('open'); });

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                editModal.classList.remove('open');
                scanModal.classList.remove('open');
                delModal.classList.remove('open');
            }
        });
    }

    function esc(str) {
        if (str == null) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    init();
})();
