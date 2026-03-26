/**
 * MDrive — Drive Manager
 * File listing, operations, preview, and context menu handling
 */

const Drive = {
    BASE_URL: '/MDrive/api',
    csrfToken: '',
    currentFiles: [],
    nextPageToken: null,
    currentView: 'my-drive',
    currentFolderId: null,
    folderStack: [], // breadcrumb history
    viewMode: 'grid',
    selectedFile: null,
    selectedFiles: [], // multi-select array

    // File type SVG icon mappings
    FILE_ICONS: {
        'application/vnd.google-apps.folder': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>', class: 'file-icon-folder' },
        'application/vnd.google-apps.document': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>', class: 'file-icon-doc' },
        'application/vnd.google-apps.spreadsheet': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>', class: 'file-icon-sheet' },
        'application/vnd.google-apps.presentation': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>', class: 'file-icon-slide' },
        'application/pdf': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', class: 'file-icon-pdf' },
        'application/zip': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>', class: 'file-icon-archive' },
        'application/x-rar': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>', class: 'file-icon-archive' },
        'text/plain': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>', class: 'file-icon-doc' },
        'text/html': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>', class: 'file-icon-code' },
        'application/json': { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>', class: 'file-icon-code' },
    },

    // SVG star icon for badges
    STAR_SVG: '<svg width="14" height="14" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',

    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.viewMode = localStorage.getItem('mdrive-view') || 'grid';
        this.updateViewToggle();
        this.bindEvents();
    },

    // ==================== API HELPERS ====================
    async apiGet(endpoint, params = {}) {
        const url = new URL(this.BASE_URL + endpoint, window.location.origin);
        Object.entries(params).forEach(([k, v]) => {
            if (v !== null && v !== undefined) url.searchParams.set(k, v);
        });

        const response = await fetch(url.toString());
        const data = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = '/MDrive/login';
                throw new Error('Session expired');
            }
            throw new Error(data.error || 'Request failed');
        }
        return data;
    },

    async apiPost(endpoint, body = {}, isFormData = false) {
        const options = {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrfToken },
        };

        if (isFormData) {
            options.body = body;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(this.BASE_URL + endpoint, options);
        const data = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = '/MDrive/login';
                throw new Error('Session expired');
            }
            throw new Error(data.error || 'Request failed');
        }
        return data;
    },

    async apiPatch(endpoint, body = {}) {
        const response = await fetch(this.BASE_URL + endpoint, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify(body),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Request failed');
        return data;
    },

    async apiDelete(endpoint) {
        const response = await fetch(this.BASE_URL + endpoint, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': this.csrfToken },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Request failed');
        return data;
    },

    // ==================== LOAD FILES ====================
    async loadFiles(append = false) {
        if (!append) {
            UI.showSkeletons(this.viewMode);
            this.currentFiles = [];
            this.nextPageToken = null;
        }

        try {
            let data;
            const params = { pageToken: append ? this.nextPageToken : null };

            switch (this.currentView) {
                case 'my-drive':
                    params.folderId = this.currentFolderId;
                    data = await this.apiGet('/files', params);
                    break;
                case 'recent':
                    data = await this.apiGet('/recent', params);
                    break;
                case 'starred':
                    data = await this.apiGet('/starred', params);
                    break;
                case 'trash':
                    data = await this.apiGet('/trash', params);
                    break;
                case 'search':
                    data = await this.apiGet('/search', { q: this.searchQuery, ...params });
                    break;
                default:
                    data = await this.apiGet('/files', params);
            }

            this.nextPageToken = data.nextPageToken;
            
            if (append) {
                this.currentFiles = [...this.currentFiles, ...data.files];
            } else {
                this.currentFiles = data.files;
            }

            UI.hideSkeletons();
            this.renderFiles(append);
            this.updateLoadMore();
            this.updateContentMeta();

        } catch (err) {
            UI.hideSkeletons();
            UI.error('Error', err.message);
            console.error('Load files error:', err);
        }
    },

    // ==================== RENDER FILES ====================
    renderFiles(append = false) {
        const container = document.getElementById('file-container');
        const emptyState = document.getElementById('empty-state');
        container.setAttribute('data-view', this.viewMode);

        if (!append) {
            container.innerHTML = '';

            // Add list header for list view
            if (this.viewMode === 'list') {
                container.innerHTML = `
                    <div class="file-list-header">
                        <span>Name</span>
                        <span>Type</span>
                        <span>Size</span>
                        <span>Modified</span>
                    </div>
                `;
            }
        }

        if (this.currentFiles.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'flex';
            return;
        }

        container.style.display = '';
        emptyState.style.display = 'none';

        const filesToRender = append 
            ? this.currentFiles.slice(this.currentFiles.length - (this.currentFiles.length - container.querySelectorAll('.file-item').length))
            : this.currentFiles;

        filesToRender.forEach((file, idx) => {
            const el = this.createFileElement(file);
            el.style.animationDelay = `${idx * 30}ms`;
            container.appendChild(el);
        });
    },

    createFileElement(file) {
        const el = document.createElement('div');
        el.className = 'file-item';
        el.dataset.fileId = file.id;
        el.dataset.fileName = file.name;
        el.dataset.mimeType = file.mimeType;
        el.dataset.isFolder = file.isFolder;

        // Mark as selected if in multi-select
        if (this.selectedFiles.some(f => f.id === file.id)) {
            el.classList.add('selected');
        }

        const iconInfo = this.getFileIcon(file.mimeType);
        const size = file.size ? this.formatSize(file.size) : '—';
        const modified = file.modifiedTime ? this.formatDate(file.modifiedTime) : '—';
        const typeLabel = this.getTypeLabel(file.mimeType);
        const starBadge = file.isStarred ? `<span class="file-star-badge">${this.STAR_SVG}</span>` : '';
        const checkboxSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';

        if (this.viewMode === 'grid') {
            let thumbnailHtml = '';
            if (file.thumbnailLink && this.isImageType(file.mimeType)) {
                thumbnailHtml = `<img src="${file.thumbnailLink}" class="file-thumbnail" loading="lazy" alt="${this.escapeHtml(file.name)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                 <div class="file-icon ${iconInfo.class}" style="display:none">${iconInfo.icon}</div>`;
            } else {
                thumbnailHtml = `<div class="file-icon ${iconInfo.class}">${iconInfo.icon}</div>`;
            }

            el.innerHTML = `
                <div class="file-checkbox">${checkboxSvg}</div>
                ${starBadge}
                ${thumbnailHtml}
                <div class="file-name" title="${this.escapeHtml(file.name)}">${this.escapeHtml(file.name)}</div>
                <div class="file-meta">${modified}</div>
            `;
        } else {
            el.innerHTML = `
                <div class="file-name-cell">
                    <div class="file-checkbox">${checkboxSvg}</div>
                    <div class="file-icon ${iconInfo.class}">${iconInfo.icon}</div>
                    <span class="file-name" title="${this.escapeHtml(file.name)}">${file.isStarred ? this.STAR_SVG + ' ' : ''}${this.escapeHtml(file.name)}</span>
                </div>
                <span class="file-list-meta">${typeLabel}</span>
                <span class="file-list-meta">${size}</span>
                <span class="file-list-meta">${modified}</span>
            `;
        }

        // Events
        el.addEventListener('click', (e) => this.handleFileClick(e, file));
        el.addEventListener('dblclick', () => this.handleFileDoubleClick(file));
        el.addEventListener('contextmenu', (e) => this.handleContextMenu(e, file));

        return el;
    },

    // ==================== EVENT HANDLERS ====================
    handleFileClick(e, file) {
        // Check if clicking the checkbox directly
        const isCheckboxClick = e.target.closest('.file-checkbox');

        if (e.ctrlKey || e.metaKey || isCheckboxClick) {
            // Toggle this file in multi-select
            this.toggleFileSelection(file, e.currentTarget);
        } else if (e.shiftKey && this.selectedFile) {
            // Range select from last selected to this file
            this.rangeSelect(file);
        } else {
            // Normal single click — clear others
            this.clearSelection();
            e.currentTarget.classList.add('selected');
            this.selectedFile = file;
            this.selectedFiles = [file];
        }
        this.updateBulkBar();
    },

    toggleFileSelection(file, el) {
        const idx = this.selectedFiles.findIndex(f => f.id === file.id);
        if (idx >= 0) {
            this.selectedFiles.splice(idx, 1);
            el.classList.remove('selected');
        } else {
            this.selectedFiles.push(file);
            el.classList.add('selected');
        }
        // Update selectedFile to last selected
        this.selectedFile = this.selectedFiles.length > 0 ? this.selectedFiles[this.selectedFiles.length - 1] : null;

        // Toggle multi-select mode class on container
        const container = document.getElementById('file-container');
        if (this.selectedFiles.length > 1) {
            container.classList.add('multi-select');
        } else {
            container.classList.remove('multi-select');
        }
    },

    rangeSelect(file) {
        const startIdx = this.currentFiles.findIndex(f => f.id === this.selectedFile.id);
        const endIdx = this.currentFiles.findIndex(f => f.id === file.id);
        if (startIdx < 0 || endIdx < 0) return;

        const [from, to] = startIdx < endIdx ? [startIdx, endIdx] : [endIdx, startIdx];
        this.selectedFiles = this.currentFiles.slice(from, to + 1);

        // Update DOM
        document.querySelectorAll('.file-item').forEach(el => {
            const fId = el.dataset.fileId;
            if (this.selectedFiles.some(f => f.id === fId)) {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        });

        const container = document.getElementById('file-container');
        container.classList.add('multi-select');
    },

    clearSelection() {
        document.querySelectorAll('.file-item.selected').forEach(el => el.classList.remove('selected'));
        this.selectedFiles = [];
        this.selectedFile = null;
        const container = document.getElementById('file-container');
        if (container) container.classList.remove('multi-select');
        this.updateBulkBar();
    },

    updateBulkBar() {
        const bar = document.getElementById('bulk-actions-bar');
        const countText = document.getElementById('bulk-count-text');
        const selectAllBtn = document.getElementById('bulk-select-all');
        if (!bar) return;

        if (this.selectedFiles.length >= 1) {
            bar.classList.add('visible');
            countText.textContent = `${this.selectedFiles.length} selected`;
            // Update Select All button text
            if (selectAllBtn) {
                const allSelected = this.selectedFiles.length === this.currentFiles.length;
                selectAllBtn.innerHTML = allSelected
                    ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg> Deselect All'
                    : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><polyline points="9 11 12 14 22 4"/></svg> Select All';
            }
        } else {
            bar.classList.remove('visible');
        }
    },

    selectAll() {
        const allSelected = this.selectedFiles.length === this.currentFiles.length;
        if (allSelected) {
            this.clearSelection();
            return;
        }
        this.selectedFiles = [...this.currentFiles];
        this.selectedFile = this.currentFiles[this.currentFiles.length - 1] || null;

        document.querySelectorAll('.file-item').forEach(el => {
            el.classList.add('selected');
        });
        const container = document.getElementById('file-container');
        if (container) container.classList.add('multi-select');
        this.updateBulkBar();
    },

    handleFileDoubleClick(file) {
        this.clearSelection();
        if (file.isFolder) {
            this.openFolder(file.id, file.name);
        } else if (file.webViewLink) {
            window.open(file.webViewLink, '_blank');
        }
    },

    handleContextMenu(e, file) {
        // If right-clicked file is not in current selection, select only this one
        if (!this.selectedFiles.some(f => f.id === file.id)) {
            this.clearSelection();
            this.selectedFile = file;
            this.selectedFiles = [file];
            e.currentTarget.classList.add('selected');
        }

        const menuId = this.currentView === 'trash' ? 'trash-context-menu' : 'context-menu';
        
        // Update context menu for multi-select
        if (menuId === 'context-menu') {
            const isMulti = this.selectedFiles.length > 1;

            const starLabel = document.getElementById('ctx-star-label');
            if (starLabel) starLabel.textContent = file.isStarred ? 'Unstar' : 'Star';
            
            // Hide single-file actions when multi-selecting
            const previewItem = document.getElementById('ctx-preview');
            if (previewItem) previewItem.style.display = (file.isFolder || isMulti) ? 'none' : '';
            const downloadItem = document.getElementById('ctx-download');
            if (downloadItem) downloadItem.style.display = file.isFolder ? 'none' : '';
            const shareItem = document.getElementById('ctx-share');
            if (shareItem) shareItem.style.display = isMulti ? 'none' : '';
            const starItem = document.getElementById('ctx-star');
            if (starItem) starItem.style.display = isMulti ? 'none' : '';
            const renameItem = document.getElementById('ctx-rename');
            if (renameItem) renameItem.style.display = isMulti ? 'none' : '';
            const openItem = document.getElementById('ctx-open');
            if (openItem) openItem.style.display = isMulti ? 'none' : '';

            // Update delete label for multi
            const deleteItem = document.getElementById('ctx-delete');
            if (deleteItem) {
                deleteItem.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    ${isMulti ? `Delete ${this.selectedFiles.length} items` : 'Move to Trash'}
                `;
            }
        }

        UI.showContextMenu(e, menuId);
        this.updateBulkBar();
    },

    // ==================== FOLDER NAVIGATION ====================
    openFolder(folderId, folderName) {
        this.folderStack.push({ id: this.currentFolderId, name: this.currentFolderId ? this.getFolderName() : 'My Drive' });
        this.currentFolderId = folderId;
        this.currentView = 'my-drive';
        this.updateBreadcrumbs(folderName);
        this.loadFiles();
    },

    navigateBack(targetIndex) {
        if (targetIndex === 0) {
            this.currentFolderId = null;
            this.folderStack = [];
        } else {
            const target = this.folderStack[targetIndex];
            this.currentFolderId = target.id;
            this.folderStack = this.folderStack.slice(0, targetIndex);
        }
        this.currentView = 'my-drive';
        this.updateBreadcrumbs();
        this.loadFiles();
    },

    getFolderName() {
        if (this.folderStack.length > 0) {
            return this.folderStack[this.folderStack.length - 1].name;
        }
        return 'My Drive';
    },

    updateBreadcrumbs(currentName = null) {
        const bc = document.getElementById('breadcrumbs');
        if (!bc) return;

        let html = '<a href="#" class="breadcrumb-item" onclick="Drive.navigateBack(0);return false;">My Drive</a>';

        this.folderStack.forEach((folder, idx) => {
            if (idx > 0) {
                html += '<span class="breadcrumb-separator">›</span>';
                html += `<a href="#" class="breadcrumb-item" onclick="Drive.navigateBack(${idx});return false;">${this.escapeHtml(folder.name)}</a>`;
            }
        });

        if (currentName) {
            html += '<span class="breadcrumb-separator">›</span>';
            html += `<span class="breadcrumb-item">${this.escapeHtml(currentName)}</span>`;
        }

        bc.innerHTML = html;
    },

    // ==================== FILE OPERATIONS ====================
    async renameFile() {
        if (!this.selectedFile) return;

        const input = document.getElementById('rename-input');
        input.value = this.selectedFile.name;
        UI.openModal('rename-modal');
        input.select();
    },

    async confirmRename() {
        const input = document.getElementById('rename-input');
        const newName = input.value.trim();
        if (!newName || !this.selectedFile) return;

        try {
            await this.apiPatch(`/files/${this.selectedFile.id}`, { name: newName });
            UI.closeModal('rename-modal');
            UI.success('Renamed', `File renamed to "${newName}"`);
            this.loadFiles();
        } catch (err) {
            UI.error('Rename failed', err.message);
        }
    },

    async deleteFile() {
        if (this.selectedFiles.length === 0 && !this.selectedFile) return;

        const files = this.selectedFiles.length > 0 ? this.selectedFiles : [this.selectedFile];
        const message = files.length > 1
            ? `Are you sure you want to move ${files.length} items to trash?`
            : `Are you sure you want to move "${files[0].name}" to trash?`;

        const confirmed = await UI.confirm('Move to Trash', message);

        if (confirmed) {
            try {
                let successCount = 0;
                for (const file of files) {
                    try {
                        await this.apiDelete(`/files/${file.id}`);
                        successCount++;
                    } catch (e) {
                        console.error(`Failed to trash ${file.name}:`, e);
                    }
                }
                const msg = files.length > 1 ? `${successCount} items moved to trash` : `"${files[0].name}" has been trashed`;
                UI.success('Moved to trash', msg);
                this.clearSelection();
                this.loadFiles();
            } catch (err) {
                UI.error('Delete failed', err.message);
            }
        }
    },

    async permanentDelete() {
        if (!this.selectedFile) return;

        const confirmed = await UI.confirm(
            'Delete Permanently',
            `This will permanently delete "${this.selectedFile.name}". This action cannot be undone.`
        );

        if (confirmed) {
            try {
                await this.apiDelete(`/files/${this.selectedFile.id}/permanent`);
                UI.success('Permanently deleted', `"${this.selectedFile.name}" has been permanently deleted`);
                this.loadFiles();
            } catch (err) {
                UI.error('Delete failed', err.message);
            }
        }
    },

    async restoreFile() {
        if (!this.selectedFile) return;

        try {
            await this.apiPost(`/files/${this.selectedFile.id}/restore`);
            UI.success('Restored', `"${this.selectedFile.name}" has been restored`);
            this.loadFiles();
        } catch (err) {
            UI.error('Restore failed', err.message);
        }
    },

    async downloadFile(file = null) {
        const f = file || this.selectedFile;
        if (!f) return;

        try {
            const link = document.createElement('a');
            link.href = `${this.BASE_URL}/files/${f.id}/download`;
            link.download = f.name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            UI.success('Downloading', `"${f.name}" is being downloaded`);
        } catch (err) {
            UI.error('Download failed', err.message);
        }
    },

    async bulkDownload() {
        const files = this.selectedFiles.filter(f => !f.isFolder);
        if (files.length === 0) {
            UI.warning('No files', 'Select files (not folders) to download');
            return;
        }
        UI.info('Downloading', `Starting download of ${files.length} file(s)...`);
        for (const file of files) {
            await new Promise(resolve => setTimeout(resolve, 300));
            const link = document.createElement('a');
            link.href = `${this.BASE_URL}/files/${file.id}/download`;
            link.download = file.name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    },

    async shareFile() {
        if (!this.selectedFile) return;

        try {
            UI.info('Sharing', 'Generating share link...');
            const data = await this.apiPost(`/files/${this.selectedFile.id}/share`);
            const input = document.getElementById('share-link-input');
            input.value = data.link;
            UI.openModal('share-modal');
        } catch (err) {
            UI.error('Share failed', err.message);
        }
    },

    async toggleStar() {
        if (!this.selectedFile) return;

        try {
            const data = await this.apiPost(`/files/${this.selectedFile.id}/star`, {
                fileName: this.selectedFile.name,
                mimeType: this.selectedFile.mimeType,
            });

            const action = data.starred ? 'Starred' : 'Unstarred';
            UI.success(action, `"${this.selectedFile.name}" ${action.toLowerCase()}`);

            // Update local data
            this.selectedFile.isStarred = data.starred;
            const fileInList = this.currentFiles.find(f => f.id === this.selectedFile.id);
            if (fileInList) fileInList.isStarred = data.starred;

            this.renderFiles();
        } catch (err) {
            UI.error('Star failed', err.message);
        }
    },

    async createFolder() {
        const input = document.getElementById('folder-name-input');
        input.value = '';
        UI.openModal('folder-modal');
    },

    async confirmCreateFolder() {
        const input = document.getElementById('folder-name-input');
        const name = input.value.trim();
        if (!name) return;

        try {
            await this.apiPost('/folder', {
                name: name,
                parentId: this.currentFolderId,
            });
            UI.closeModal('folder-modal');
            UI.success('Folder created', `"${name}" folder has been created`);
            this.loadFiles();
        } catch (err) {
            UI.error('Create folder failed', err.message);
        }
    },

    // ==================== PREVIEW ====================
    async previewFile() {
        if (!this.selectedFile || this.selectedFile.isFolder) return;

        const file = this.selectedFile;
        const body = document.getElementById('preview-body');
        document.getElementById('preview-title').textContent = file.name;

        const mime = file.mimeType || '';

        if (this.isImageType(mime)) {
            const thumbnailUrl = file.thumbnailLink ? file.thumbnailLink.replace('=s220', '=s1600') : '';
            body.innerHTML = `<img src="${thumbnailUrl}" class="preview-image" alt="${this.escapeHtml(file.name)}">`;
        } else if (this.isVideoType(mime)) {
            if (file.webContentLink) {
                body.innerHTML = `<video controls class="preview-video"><source src="${file.webContentLink}" type="${mime}">Your browser does not support video playback.</video>`;
            } else {
                body.innerHTML = `<iframe src="${file.webViewLink}" class="preview-iframe"></iframe>`;
            }
        } else if (mime === 'application/pdf') {
            body.innerHTML = `<iframe src="${file.webViewLink}" class="preview-iframe"></iframe>`;
        } else if (file.webViewLink) {
            body.innerHTML = `<iframe src="${file.webViewLink}" class="preview-iframe"></iframe>`;
        } else {
            body.innerHTML = `
                <div class="preview-unsupported">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <h3>Preview not available</h3>
                    <p>This file type can't be previewed. You can download it instead.</p>
                    <button class="btn btn-primary" onclick="Drive.downloadFile()" style="margin-top:16px;">Download File</button>
                </div>
            `;
        }

        UI.openModal('preview-modal');
    },

    // ==================== SEARCH ====================
    searchQuery: '',

    async search(query) {
        this.searchQuery = query;
        if (!query.trim()) {
            this.switchView('my-drive');
            return;
        }
        
        this.currentView = 'search';
        document.getElementById('content-title').textContent = `Search: "${query}"`;
        this.loadFiles();
    },

    // ==================== VIEW MANAGEMENT ====================
    switchView(view) {
        this.currentView = view;
        this.currentFolderId = null;
        this.folderStack = [];
        this.clearSelection();

        // Update nav
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
        const navItem = document.querySelector(`[data-view="${view}"]`);
        if (navItem) navItem.classList.add('active');

        // Update title
        const titles = {
            'my-drive': 'My Drive',
            'recent': 'Recent',
            'starred': 'Starred',
            'trash': 'Trash',
        };
        document.getElementById('content-title').textContent = titles[view] || 'My Drive';

        // Reset breadcrumbs
        this.updateBreadcrumbs();

        // Show/hide breadcrumbs
        document.getElementById('breadcrumbs').style.display = view === 'my-drive' ? '' : 'none';

        this.loadFiles();
    },

    setViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('mdrive-view', mode);
        this.updateViewToggle();
        this.renderFiles();
    },

    updateViewToggle() {
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.viewMode === this.viewMode);
        });
    },

    updateLoadMore() {
        const loadMore = document.getElementById('load-more');
        if (loadMore) {
            loadMore.style.display = this.nextPageToken ? 'flex' : 'none';
        }
    },

    updateContentMeta() {
        const meta = document.getElementById('content-meta');
        if (meta) {
            const count = this.currentFiles.length;
            const folders = this.currentFiles.filter(f => f.isFolder).length;
            const files = count - folders;
            const parts = [];
            if (folders > 0) parts.push(`${folders} folder${folders !== 1 ? 's' : ''}`);
            if (files > 0) parts.push(`${files} file${files !== 1 ? 's' : ''}`);
            meta.textContent = parts.join(', ') || '';
        }
    },

    // ==================== UTILITIES ====================
    getFileIcon(mimeType) {
        if (this.FILE_ICONS[mimeType]) return this.FILE_ICONS[mimeType];
        if (mimeType?.startsWith('image/')) return { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>', class: 'file-icon-image' };
        if (mimeType?.startsWith('video/')) return { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>', class: 'file-icon-video' };
        if (mimeType?.startsWith('audio/')) return { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>', class: 'file-icon-audio' };
        if (mimeType?.startsWith('text/')) return { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>', class: 'file-icon-doc' };
        return { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', class: 'file-icon-default' };
    },

    getTypeLabel(mimeType) {
        const labels = {
            'application/vnd.google-apps.folder': 'Folder',
            'application/vnd.google-apps.document': 'Google Doc',
            'application/vnd.google-apps.spreadsheet': 'Google Sheet',
            'application/vnd.google-apps.presentation': 'Google Slides',
            'application/pdf': 'PDF',
            'application/zip': 'ZIP Archive',
        };
        if (labels[mimeType]) return labels[mimeType];
        if (mimeType?.startsWith('image/')) return 'Image';
        if (mimeType?.startsWith('video/')) return 'Video';
        if (mimeType?.startsWith('audio/')) return 'Audio';
        return 'File';
    },

    isImageType(mimeType) {
        return mimeType?.startsWith('image/');
    },

    isVideoType(mimeType) {
        return mimeType?.startsWith('video/');
    },

    formatSize(bytes) {
        if (!bytes) return '—';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let i = 0;
        let size = parseInt(bytes);
        while (size >= 1024 && i < units.length - 1) {
            size /= 1024;
            i++;
        }
        return `${size.toFixed(i > 0 ? 1 : 0)} ${units[i]}`;
    },

    formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) {
            const hours = Math.floor(diff / (1000 * 60 * 60));
            if (hours === 0) {
                const mins = Math.floor(diff / (1000 * 60));
                return mins <= 1 ? 'Just now' : `${mins}m ago`;
            }
            return `${hours}h ago`;
        }
        if (days === 1) return 'Yesterday';
        if (days < 7) return `${days}d ago`;

        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // ==================== EVENT BINDINGS ====================
    bindEvents() {
        // Context menu actions
        document.getElementById('ctx-open')?.addEventListener('click', () => {
            if (this.selectedFile?.isFolder) {
                this.openFolder(this.selectedFile.id, this.selectedFile.name);
            } else if (this.selectedFile?.webViewLink) {
                window.open(this.selectedFile.webViewLink, '_blank');
            }
        });

        document.getElementById('ctx-preview')?.addEventListener('click', () => this.previewFile());
        document.getElementById('ctx-download')?.addEventListener('click', () => this.downloadFile());
        document.getElementById('ctx-share')?.addEventListener('click', () => this.shareFile());
        document.getElementById('ctx-star')?.addEventListener('click', () => this.toggleStar());
        document.getElementById('ctx-rename')?.addEventListener('click', () => this.renameFile());
        document.getElementById('ctx-delete')?.addEventListener('click', () => this.deleteFile());
        document.getElementById('ctx-restore')?.addEventListener('click', () => this.restoreFile());
        document.getElementById('ctx-permanent-delete')?.addEventListener('click', () => this.permanentDelete());

        // Rename modal
        document.getElementById('rename-confirm')?.addEventListener('click', () => this.confirmRename());
        document.getElementById('rename-input')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.confirmRename();
        });

        // Folder modal
        document.getElementById('folder-confirm')?.addEventListener('click', () => this.confirmCreateFolder());
        document.getElementById('folder-name-input')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.confirmCreateFolder();
        });

        // Preview close
        document.getElementById('preview-close')?.addEventListener('click', () => UI.closeModal('preview-modal'));

        // Share copy
        document.getElementById('copy-share-link')?.addEventListener('click', () => {
            const input = document.getElementById('share-link-input');
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                UI.success('Copied', 'Share link copied to clipboard');
            });
        });

        // Load more
        document.getElementById('load-more-btn')?.addEventListener('click', () => this.loadFiles(true));

        // Bulk actions bar
        document.getElementById('bulk-select-all')?.addEventListener('click', () => this.selectAll());
        document.getElementById('bulk-download')?.addEventListener('click', () => this.bulkDownload());
        document.getElementById('bulk-delete')?.addEventListener('click', () => this.deleteFile());
        document.getElementById('bulk-clear')?.addEventListener('click', () => this.clearSelection());

        // Click on content area background clears selection
        document.getElementById('content-area')?.addEventListener('click', (e) => {
            if (e.target.id === 'content-area' || e.target.id === 'file-container') {
                this.clearSelection();
            }
        });
    }
};
