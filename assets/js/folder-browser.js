'use strict';

/*  State  */
let currentFolderId    = null;
let ctxFolderId        = null;
let ctxFolderName      = '';
let selectedUploadFile = null;

/*  Colour palette — identical to My Documents  */
const EXT_PALETTE = {
    pdf:  { bg: '#fee2e2', stroke: '#dc2626' },
    doc:  { bg: '#dbeafe', stroke: '#1d4ed8' },
    docx: { bg: '#dbeafe', stroke: '#1d4ed8' },
    xlsx: { bg: '#dcfce7', stroke: '#16a34a' },
    xls:  { bg: '#dcfce7', stroke: '#16a34a' },
    pptx: { bg: '#ffedd5', stroke: '#ea580c' },
    ppt:  { bg: '#ffedd5', stroke: '#ea580c' },
    jpg:  { bg: '#ede9fe', stroke: '#7c3aed' },
    jpeg: { bg: '#ede9fe', stroke: '#7c3aed' },
    png:  { bg: '#ede9fe', stroke: '#7c3aed' },
    gif:  { bg: '#ede9fe', stroke: '#7c3aed' },
    zip:  { bg: '#fef3c7', stroke: '#b45309' },
    rar:  { bg: '#fef3c7', stroke: '#b45309' },
    txt:  { bg: '#f1f5f9', stroke: '#475569' },
};
const EXT_DEFAULT_PALETTE = { bg: '#f1f5f9', stroke: '#64748b' };

/*  SVG path groups — identical to functions.php fileTypeIcon()  */
const EXT_PATHS = {
    pdf:  ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M9 13h6','M9 17h3'],
    doc:  ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M16 13H8','M16 17H8'],
    docx: ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M16 13H8','M16 17H8'],
    xlsx: ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M8 13h2v4H8z','M12 11h2v6h-2z','M16 13h2v4h-2z'],
    xls:  ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M8 13h2v4H8z','M12 11h2v6h-2z','M16 13h2v4h-2z'],
    pptx: ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M9 12h4a2 2 0 000-4H9v8'],
    ppt:  ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M9 12h4a2 2 0 000-4H9v8'],
    jpg:  ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
    jpeg: ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
    png:  ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
    gif:  ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
    zip:  ['M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z','M12 22v-6','M12 2v6'],
    rar:  ['M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z','M12 22v-6','M12 2v6'],
    txt:  ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M16 13H8','M16 17H8'],
};
const EXT_DEFAULT_PATHS = ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6'];

const ALLOWED_EXT = ['pdf','docx','doc','xlsx','xls','pptx','ppt','jpg','jpeg','png','gif','zip','rar','txt'];
const MAX_BYTES   = 20 * 1024 * 1024;

/* BOOT */
document.addEventListener('DOMContentLoaded', () => {
    navigate(null);
    buildTree(null, document.getElementById('fbTree'));

    document.getElementById('inputNewFolder')
        .addEventListener('keydown', e => { if (e.key === 'Enter') submitNewFolder(); });
    document.getElementById('inputRename')
        .addEventListener('keydown', e => { if (e.key === 'Enter') submitRename(); });

    // File input change
    document.getElementById('fbFileInput')
        .addEventListener('change', function () { if (this.files[0]) applyFile(this.files[0]); });

    // Drop zone drag-and-drop
    const dz = document.getElementById('fbDropzone');
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('dz-over'); });
    dz.addEventListener('dragleave', ()  => dz.classList.remove('dz-over'));
    dz.addEventListener('drop', e => {
        e.preventDefault();
        dz.classList.remove('dz-over');
        if (e.dataTransfer.files[0]) applyFile(e.dataTransfer.files[0]);
    });

    document.addEventListener('click',   e => { if (!e.target.closest('#fbCtx')) hideCtx(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { hideCtx(); closeAllModals(); } });
});

/* NAVIGATION */
function navigate(folderId) {
    currentFolderId = folderId;
    // Keep CURRENT_FOLDER_ID in sync for the inline upload handler in index.php
    if (typeof CURRENT_FOLDER_ID !== 'undefined') CURRENT_FOLDER_ID = folderId;

    setLoading(true);
    fetch(`get-folder-content.php?id=${folderId ?? ''}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); setLoading(false); return; }
            renderBreadcrumb(data.path);
            renderFolders(data.folders);
            renderFiles(data.files);
            const currentName = data.path.length ? data.path[data.path.length - 1].name : 'Root';
            document.getElementById('mNFParentName').textContent  = currentName;
            document.getElementById('mUploadFolderName').textContent = currentName;
            highlightTree(folderId);
            setLoading(false);
        })
        .catch(() => { alert('Network error loading folder.'); setLoading(false); });
}

function setLoading(on) {
    document.getElementById('fbLoading').style.display         = on ? 'flex' : 'none';
    document.getElementById('fbFolderSection').style.visibility = on ? 'hidden' : 'visible';
    document.getElementById('fbFileSection').style.visibility   = on ? 'hidden' : 'visible';
}

/*  Breadcrumb  */
function renderBreadcrumb(path) {
    const nav = document.getElementById('fbBreadcrumb');
    let html = `<button class="bc-item bc-root" onclick="navigate(null)">
        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 11h1v6a1 1 0 001 1h4v-4h2v4h4a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-7-7z"/>
        </svg>
        Academic Vault
    </button>`;
    path.forEach(seg => {
        html += `<span class="bc-sep">›</span>
                 <button class="bc-item" onclick="navigate(${seg.id})">${esc(seg.name)}</button>`;
    });
    nav.innerHTML = html;
}

/*  Folder grid  */
function renderFolders(folders) {
    const grid = document.getElementById('fbFolderGrid');
    const sec  = document.getElementById('fbFolderSection');
    document.getElementById('fbFolderCount').textContent = folders.length;

    if (!folders.length) { sec.style.display = 'none'; return; }
    sec.style.display = '';

    grid.innerHTML = folders.map(f => `
        <div class="fb-folder-card"
             title="Double-click to open · Right-click for options"
             ondblclick="navigate(${f.id})"
             oncontextmenu="showCtx(event,${f.id},'${escJs(f.name)}')">
            <div class="fb-fc-icon">
                <svg viewBox="0 0 24 24" fill="#f59e0b" stroke="none">
                    <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                </svg>
            </div>
            <div class="fb-fc-name">${esc(f.name)}</div>
            ${!FB_VIEWER ? `
            <div class="fb-fc-actions">
                <button class="fb-icon-btn" title="Open"
                        onclick="event.stopPropagation();navigate(${f.id})">→</button>
                <button class="fb-icon-btn" title="Rename"
                        onclick="event.stopPropagation();openRenameModal(${f.id},'${escJs(f.name)}')">✏️</button>
                <button class="fb-icon-btn fb-icon-btn-danger" title="Delete"
                        onclick="event.stopPropagation();doDeleteFolder(${f.id},'${escJs(f.name)}')">🗑️</button>
            </div>` : ''}
        </div>
    `).join('');
}

/*  Files table — icons/colours identical to My Documents  */
function renderFiles(files) {
    const body = document.getElementById('fbFileBody');
    const sec  = document.getElementById('fbFileSection');
    document.getElementById('fbFileCount').textContent = files.length;
    sec.style.display = '';

    if (!files.length) {
        body.innerHTML = `<tr><td colspan="7" class="fb-empty">No files in this folder</td></tr>`;
        return;
    }

    body.innerHTML = files.map(f => {
        const ext  = (f.extension || '').toLowerCase();
        const pal  = EXT_PALETTE[ext] || EXT_DEFAULT_PALETTE;
        const kb   = f.size ? (f.size / 1024).toFixed(1) + ' KB' : '—';
        const date = f.upload_date ? f.upload_date.slice(0, 10).split('-').reverse().join('/') : '—';
        const cls  = (f.status || 'draft').toLowerCase();

        return `<tr>
            <td style="padding-left:.75rem;">
                <span class="fb-ft-icon" style="background:${pal.bg};">
                    ${makeFileSvg(ext, pal.stroke)}
                </span>
            </td>
            <td>
                <strong style="color:#0f172a;">${esc(f.title)}</strong>
            </td>
            <td class="col-hide-sm">
                <span class="fb-ext-badge" style="background:${pal.bg};color:${pal.stroke};">
                    ${ext.toUpperCase() || '—'}
                </span>
            </td>
            <td class="col-hide-sm" style="color:#64748b;font-size:.82rem;">${kb}</td>
            <td class="col-hide-sm" style="color:#64748b;font-size:.82rem;">${date}</td>
            <td class="col-hide-sm"><span class="fb-status-pill ${cls}">${f.status || 'Draft'}</span></td>
            <td style="text-align:right;padding-right:.75rem;">
                <div class="fb-file-btns">
                    <a href="../documents/view.php?id=${f.id}"
                       class="fb-file-btn" title="View">${svgEye()}</a>
                    <a href="../../download.php?id=${f.id}"
                       class="fb-file-btn fb-btn-dl" title="Download">${svgDownload()}</a>
                    ${!FB_VIEWER ? `
                    <a href="../documents/edit.php?id=${f.id}"
                       class="fb-file-btn" title="Edit Metadata">${svgEdit()}</a>
                    <a href="../documents/versions.php?id=${f.id}"
                       class="fb-file-btn" title="Version History">${svgClock()}</a>
                    ` : ''}
                </div>
            </td>
        </tr>`;
    }).join('');
}

/*  Build inline SVG for file type icon  */
function makeFileSvg(ext, strokeColor) {
    const paths = EXT_PATHS[ext] || EXT_DEFAULT_PATHS;
    const pathTags = paths.map(d => `<path d="${d}"/>`).join('');
    return `<svg viewBox="0 0 24 24" fill="none" stroke="${strokeColor}"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                style="width:18px;height:18px;">${pathTags}</svg>`;
}

/*  Action icon SVGs  */
function svgEye() {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>`;
}
function svgDownload() {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>`;
}
function svgEdit() {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </svg>`;
}
function svgClock() {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
    </svg>`;
}

/* LEFT TREE */
function buildTree(parentId, container) {
    container.innerHTML = '<div class="fb-tree-loading">Loading…</div>';
    fetch(`get-folder-content.php?id=${parentId ?? ''}`)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if (!data.folders || !data.folders.length) {
                if (!parentId) container.innerHTML = '<div class="fb-tree-empty">No folders yet</div>';
                return;
            }
            data.folders.forEach(f => {
                const item = document.createElement('div');
                item.className   = 'fb-tree-item';
                item.dataset.id  = f.id;
                item.innerHTML   = `
                    <div class="fb-tree-row" onclick="navigate(${f.id})">
                        <span class="fb-tree-toggle" data-open="0"
                              onclick="event.stopPropagation();toggleTreeNode(this,${f.id})">▶</span>
                        <svg viewBox="0 0 24 24" fill="#f59e0b" stroke="none"
                             style="width:14px;height:14px;flex-shrink:0;">
                            <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                        </svg>
                        <span class="fb-tree-label">${esc(f.name)}</span>
                    </div>
                    <div class="fb-tree-children" style="display:none;"></div>
                `;
                container.appendChild(item);
            });
        })
        .catch(() => { container.innerHTML = '<div class="fb-tree-empty">Error loading</div>'; });
}

function toggleTreeNode(toggleEl, folderId) {
    const item     = toggleEl.closest('.fb-tree-item');
    const children = item.querySelector('.fb-tree-children');
    const isOpen   = toggleEl.dataset.open === '1';
    if (isOpen) {
        children.style.display = 'none';
        toggleEl.textContent   = '▶';
        toggleEl.dataset.open  = '0';
    } else {
        if (!children.dataset.loaded) {
            buildTree(folderId, children);
            children.dataset.loaded = '1';
        }
        children.style.display = '';
        toggleEl.textContent   = '▼';
        toggleEl.dataset.open  = '1';
    }
}

function highlightTree(folderId) {
    document.querySelectorAll('.fb-tree-row').forEach(row => {
        const item = row.closest('.fb-tree-item');
        row.classList.toggle(
            'fb-tree-row-active',
            item && parseInt(item.dataset.id) === folderId
        );
    });
}

/* CONTEXT MENU */
function showCtx(e, id, name) {
    e.preventDefault(); e.stopPropagation();
    ctxFolderId   = id;
    ctxFolderName = name;
    const menu = document.getElementById('fbCtx');
    menu.style.display = 'block';
    menu.style.left    = e.pageX + 'px';
    menu.style.top     = e.pageY + 'px';
}
function hideCtx() { document.getElementById('fbCtx').style.display = 'none'; }
function ctxOpen()   { navigate(ctxFolderId); }
function ctxRename() { openRenameModal(ctxFolderId, ctxFolderName); }
function ctxMove()   { openMoveModal(ctxFolderId, ctxFolderName); }
function ctxDelete() { doDeleteFolder(ctxFolderId, ctxFolderName); }

/* MODALS: NEW FOLDER / RENAME */

function openNewFolderModal() {
    document.getElementById('inputNewFolder').value = '';
    openModal('modalNewFolder');
    setTimeout(() => document.getElementById('inputNewFolder').focus(), 80);
}
function submitNewFolder() {
    const name = document.getElementById('inputNewFolder').value.trim();
    if (!name) { alert('Please enter a folder name.'); return; }
    post('create-folder.php', { folder_name: name, parent_id: currentFolderId ?? '' })
        .then(d => {
            if (d.success) {
                closeModal('modalNewFolder');
                navigate(currentFolderId);
                refreshTree();
                alert('Folder "' + name + '" created successfully.');
            } else alert('Error: ' + d.message);
        })
        .catch(() => alert('Network error. Please try again.'));
}

function openRenameModal(id, name) {
    document.getElementById('inputRenameFolderId').value = id;
    document.getElementById('inputRename').value         = name;
    openModal('modalRename');
    setTimeout(() => document.getElementById('inputRename').focus(), 80);
}
function submitRename() {
    const id   = document.getElementById('inputRenameFolderId').value;
    const name = document.getElementById('inputRename').value.trim();
    if (!name) { alert('Please enter a name.'); return; }
    post('rename-folder.php', { folder_id: id, new_name: name })
        .then(d => {
            if (d.success) {
                closeModal('modalRename');
                navigate(currentFolderId);
                refreshTree();
                alert('Folder renamed to "' + name + '".');
            } else alert('Error: ' + d.message);
        })
        .catch(() => alert('Network error. Please try again.'));
}

/* MODAL: MOVE FOLDER */
function openMoveModal(id, name) {
    document.getElementById('inputMoveFolderId').value     = id;
    document.getElementById('mMoveSourceName').textContent = name;

    const sel = document.getElementById('selectMoveTarget');
    sel.innerHTML = '<option value="">— Root (top level) —</option>';

    fetch('get-folder-content.php?id=')
        .then(r => r.json())
        .then(data => {
            (data.folders || []).forEach(f => {
                if (f.id == id) return;
                const opt = document.createElement('option');
                opt.value = f.id;
                opt.textContent = f.name;
                sel.appendChild(opt);
            });
            openModal('modalMove');
        });
}
function submitMove() {
    const id       = document.getElementById('inputMoveFolderId').value;
    const parentId = document.getElementById('selectMoveTarget').value;
    post('move-folder.php', { folder_id: id, new_parent_id: parentId })
        .then(d => {
            if (d.success) {
                closeModal('modalMove');
                navigate(currentFolderId);
                refreshTree();
                alert('Folder moved successfully.');
            } else alert('Error: ' + d.message);
        })
        .catch(() => alert('Network error. Please try again.'));
}

/*DELETE FOLDER */
function doDeleteFolder(id, name) {
    if (!confirm('Delete folder "' + name + '"?\n\nThe folder must be empty (no files and no sub-folders).'))
        return;
    post('delete-folder.php', { folder_id: id })
        .then(d => {
            if (d.success) {
                navigate(currentFolderId);
                refreshTree();
                alert('Folder "' + name + '" deleted.');
            } else alert('Error: ' + d.message);
        })
        .catch(() => alert('Network error. Please try again.'));
}

/* UPLOAD MODAL */
function openUploadModal() {
    clearDropFileState();
    document.getElementById('fbUploadTitle').value       = '';
    document.getElementById('fbUploadAuthor').value      = '';
    document.getElementById('fbUploadSubject').value     = '';
    document.getElementById('fbUploadVersion').value     = '1';
    document.getElementById('fbUploadDescription').value = '';
    openModal('modalUpload');
}

function applyFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!ALLOWED_EXT.includes(ext)) {
        alert('File type ".' + ext + '" is not allowed.\nAllowed: ' + ALLOWED_EXT.join(', ').toUpperCase());
        return;
    }
    if (file.size > MAX_BYTES) {
        alert('File is too large (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB).\nMaximum size: 20 MB');
        return;
    }
    selectedUploadFile = file;

    // Sync file to the native input
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('fbFileInput').files = dt.files;

    const pal = EXT_PALETTE[ext] || EXT_DEFAULT_PALETTE;
    document.getElementById('fbDropPlaceholder').style.display = 'none';
    document.getElementById('fbDropSelected').style.display    = '';
    document.getElementById('fbDropIcon').innerHTML = `<span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:10px;background:${pal.bg};">${makeFileSvg(ext, pal.stroke).replace('18px', '26px').replace('18px', '26px')}</span>`;
    document.getElementById('fbDropName').textContent = file.name;
    document.getElementById('fbDropMeta').textContent =
        (file.size >= 1024 * 1024 ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB') +
        ' · ' + ext.toUpperCase();
    document.getElementById('fbDropzone').classList.add('dz-selected');

    if (!document.getElementById('fbUploadTitle').value)
        document.getElementById('fbUploadTitle').value = file.name.replace(/\.[^.]+$/, '');
}

function clearDropFile(e) { if (e) e.stopPropagation(); clearDropFileState(); }
function clearDropFileState() {
    selectedUploadFile = null;
    document.getElementById('fbFileInput').value = '';
    document.getElementById('fbDropPlaceholder').style.display = '';
    document.getElementById('fbDropSelected').style.display    = 'none';
    document.getElementById('fbDropzone').classList.remove('dz-selected', 'dz-over');
}

function submitUpload() {
    const title = document.getElementById('fbUploadTitle').value.trim();
    if (!selectedUploadFile) { alert('Please select a file first.'); return; }
    if (!title)               { alert('Please enter a document title.'); return; }
    if (!currentFolderId)     { alert('Please open a folder before uploading.\nFiles cannot be uploaded to the root level.'); return; }

    const btn = document.getElementById('fbUploadBtn');
    btn.disabled   = true;
    const origText = btn.innerHTML;
    btn.innerHTML  = 'Uploading…';

    const fd = new FormData();
    fd.append('document',        selectedUploadFile);
    fd.append('title',           title);
    fd.append('author',          document.getElementById('fbUploadAuthor').value.trim());
    fd.append('subject',         document.getElementById('fbUploadSubject').value.trim());
    fd.append('status',          document.getElementById('fbUploadStatus').value);
    fd.append('current_version', document.getElementById('fbUploadVersion').value);
    fd.append('description',     document.getElementById('fbUploadDescription').value.trim());
    fd.append('category_id',     currentFolderId);

    fetch('upload-to-folder.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled  = false;
            btn.innerHTML = origText;
            if (data.success) {
                closeModal('modalUpload');
                navigate(currentFolderId);
                alert('File "' + title + '" uploaded successfully.');
            } else {
                alert('Upload failed: ' + data.message);
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = origText;
            alert('A network error occurred. Please try again.');
        });
}

/* MODAL / TREE HELPERS */
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function closeAllModals() {
    ['modalNewFolder','modalRename','modalMove','modalUpload'].forEach(closeModal);
}
function refreshTree() {
    const tree = document.getElementById('fbTree');
    tree.innerHTML = '';
    buildTree(null, tree);
}

/*  Utility  */
function post(url, data) {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => fd.append(k, v ?? ''));
    return fetch(url, { method: 'POST', body: fd }).then(r => r.json());
}
function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}
function escJs(str) {
    return (str || '').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"');
}
