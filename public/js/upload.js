/**
 * MDrive — Upload Manager
 * Drag & drop file upload + folder upload with structure preservation
 */

const Upload = {
    currentFolderId: null,

    init() {
        this.bindDragDrop();
        this.bindFileInput();
        this.bindUploadButton();
        this.bindFolderUpload();
        this.bindProgressClose();
    },

    // ==================== DRAG & DROP ====================
    bindDragDrop() {
        const overlay = document.getElementById('dropzone-overlay');
        let dragCounter = 0;

        document.addEventListener('dragenter', (e) => {
            e.preventDefault();
            dragCounter++;
            overlay.classList.add('active');
        });

        document.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                overlay.classList.remove('active');
            }
        });

        document.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            dragCounter = 0;
            overlay.classList.remove('active');

            // Check if dropped items contain directories
            const items = e.dataTransfer?.items;
            if (items && items.length > 0) {
                const entries = [];
                for (let i = 0; i < items.length; i++) {
                    const entry = items[i].webkitGetAsEntry?.();
                    if (entry) entries.push(entry);
                }

                if (entries.some(e => e.isDirectory)) {
                    // Folder drop — traverse and upload with structure
                    this.handleDroppedEntries(entries);
                    return;
                }
            }

            // Regular file drop
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                this.uploadFiles(files);
            }
        });
    },

    // ==================== FILE INPUT ====================
    bindFileInput() {
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files.length > 0) {
                    this.uploadFiles(e.target.files);
                    e.target.value = '';
                }
            });
        }
    },

    bindUploadButton() {
        const btn = document.getElementById('btn-upload');
        if (btn) {
            btn.addEventListener('click', () => {
                document.getElementById('file-input')?.click();
            });
        }
    },

    // ==================== FOLDER UPLOAD ====================
    bindFolderUpload() {
        const btn = document.getElementById('btn-upload-folder');
        const folderInput = document.getElementById('folder-input');

        if (btn) {
            btn.addEventListener('click', () => {
                folderInput?.click();
            });
        }

        if (folderInput) {
            folderInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files.length > 0) {
                    this.uploadFolder(e.target.files);
                    e.target.value = '';
                }
            });
        }
    },

    /**
     * Upload a folder selected via input[webkitdirectory]
     * Files have webkitRelativePath like "MyFolder/sub/file.txt"
     */
    async uploadFolder(fileList) {
        const files = Array.from(fileList);
        if (files.length === 0) return;

        // Build folder tree from relative paths
        const tree = this.buildFolderTree(files);
        const rootParentId = Drive.currentFolderId || null;

        // Count totals
        const totalFiles = files.length;
        const folderPaths = new Set();
        files.forEach(f => {
            const parts = f.webkitRelativePath.split('/');
            for (let i = 1; i < parts.length; i++) {
                folderPaths.add(parts.slice(0, i).join('/'));
            }
        });
        const totalFolders = folderPaths.size;

        this.showProgressCard(totalFiles);
        this.updateTitle(`Uploading folder (${totalFolders} folders, ${totalFiles} files)`);
        this.updateSubtitle('Creating folder structure...');

        // Create all folders first and get their IDs
        const folderIdMap = {};
        let foldersCreated = 0;
        const sortedPaths = Array.from(folderPaths).sort((a, b) => a.split('/').length - b.split('/').length);

        for (const path of sortedPaths) {
            const parts = path.split('/');
            const folderName = parts[parts.length - 1];
            const parentPath = parts.slice(0, -1).join('/');
            const parentId = parentPath ? folderIdMap[parentPath] : rootParentId;

            try {
                this.updateSubtitle(`Creating: ${folderName}`);
                const result = await Drive.apiPost('/folder', {
                    name: folderName,
                    parentId: parentId,
                });
                folderIdMap[path] = result.folder.id;
                foldersCreated++;
            } catch (err) {
                console.error(`Failed to create folder ${path}:`, err);
                UI.error('Folder error', `Failed to create "${folderName}"`);
            }
        }

        // Now upload all files into their correct folders
        let completed = 0;
        let failed = 0;

        for (const file of files) {
            const relativePath = file.webkitRelativePath;
            const parts = relativePath.split('/');
            const parentPath = parts.slice(0, -1).join('/');
            const targetFolderId = folderIdMap[parentPath] || rootParentId;

            this.updateSubtitle(file.name);

            try {
                const formData = new FormData();
                formData.append('file', file);
                if (targetFolderId) {
                    formData.append('folderId', targetFolderId);
                }
                await Drive.apiPost('/upload', formData, true);
                completed++;
            } catch (err) {
                failed++;
                console.error(`Upload failed for ${relativePath}:`, err);
            }

            const progress = ((completed + failed) / totalFiles) * 100;
            this.updateProgress(progress, completed + failed, totalFiles);
        }

        this.showComplete(completed, failed, totalFiles);

        setTimeout(() => {
            this.hideProgress();
            Drive.loadFiles();
        }, 2500);
    },

    /**
     * Handle drag-and-dropped directories
     */
    async handleDroppedEntries(entries) {
        // Collect all files with their relative paths
        const allFiles = [];
        for (const entry of entries) {
            await this.traverseEntry(entry, '', allFiles);
        }

        if (allFiles.length === 0) return;

        // Build folder paths from collected files
        const rootParentId = Drive.currentFolderId || null;
        const folderPaths = new Set();
        allFiles.forEach(({ path }) => {
            const parts = path.split('/');
            for (let i = 1; i < parts.length; i++) {
                folderPaths.add(parts.slice(0, i).join('/'));
            }
        });

        const totalFiles = allFiles.length;
        const totalFolders = folderPaths.size;

        this.showProgressCard(totalFiles);
        this.updateTitle(`Uploading (${totalFolders} folders, ${totalFiles} files)`);
        this.updateSubtitle('Creating folder structure...');

        // Create folders
        const folderIdMap = {};
        const sortedPaths = Array.from(folderPaths).sort((a, b) => a.split('/').length - b.split('/').length);

        for (const path of sortedPaths) {
            const parts = path.split('/');
            const folderName = parts[parts.length - 1];
            const parentPath = parts.slice(0, -1).join('/');
            const parentId = parentPath ? folderIdMap[parentPath] : rootParentId;

            try {
                this.updateSubtitle(`Creating: ${folderName}`);
                const result = await Drive.apiPost('/folder', {
                    name: folderName,
                    parentId: parentId,
                });
                folderIdMap[path] = result.folder.id;
            } catch (err) {
                console.error(`Failed to create folder ${path}:`, err);
            }
        }

        // Upload files
        let completed = 0;
        let failed = 0;

        for (const { file, path } of allFiles) {
            const parts = path.split('/');
            const parentPath = parts.slice(0, -1).join('/');
            const targetFolderId = folderIdMap[parentPath] || rootParentId;

            this.updateSubtitle(file.name);

            try {
                const formData = new FormData();
                formData.append('file', file);
                if (targetFolderId) {
                    formData.append('folderId', targetFolderId);
                }
                await Drive.apiPost('/upload', formData, true);
                completed++;
            } catch (err) {
                failed++;
                console.error(`Upload failed for ${path}:`, err);
            }

            const progress = ((completed + failed) / totalFiles) * 100;
            this.updateProgress(progress, completed + failed, totalFiles);
        }

        this.showComplete(completed, failed, totalFiles);

        setTimeout(() => {
            this.hideProgress();
            Drive.loadFiles();
        }, 2500);
    },

    /**
     * Recursively traverse a FileSystemEntry
     */
    traverseEntry(entry, parentPath, results) {
        return new Promise((resolve) => {
            const fullPath = parentPath ? `${parentPath}/${entry.name}` : entry.name;

            if (entry.isFile) {
                entry.file((file) => {
                    results.push({ file, path: fullPath });
                    resolve();
                }, () => resolve());
            } else if (entry.isDirectory) {
                const reader = entry.createReader();
                const readEntries = () => {
                    reader.readEntries(async (entries) => {
                        if (entries.length === 0) {
                            resolve();
                            return;
                        }
                        for (const child of entries) {
                            await this.traverseEntry(child, fullPath, results);
                        }
                        // readEntries can return partial results, so keep reading
                        readEntries();
                    }, () => resolve());
                };
                readEntries();
            } else {
                resolve();
            }
        });
    },

    /**
     * Build a tree structure from webkitRelativePath
     */
    buildFolderTree(files) {
        const tree = {};
        files.forEach(file => {
            const parts = file.webkitRelativePath.split('/');
            let current = tree;
            for (let i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) current[parts[i]] = {};
                current = current[parts[i]];
            }
        });
        return tree;
    },

    // ==================== REGULAR FILE UPLOAD ====================
    async uploadFiles(fileList) {
        const files = Array.from(fileList);
        const total = files.length;
        let completed = 0;
        let failed = 0;

        this.showProgressCard(total);

        for (const file of files) {
            this.updateSubtitle(file.name);

            try {
                const formData = new FormData();
                formData.append('file', file);
                
                if (Drive.currentFolderId) {
                    formData.append('folderId', Drive.currentFolderId);
                }

                await Drive.apiPost('/upload', formData, true);
                completed++;
            } catch (err) {
                failed++;
                console.error(`Upload failed for ${file.name}:`, err);
            }

            const progress = ((completed + failed) / total) * 100;
            this.updateProgress(progress, completed + failed, total);
        }

        this.showComplete(completed, failed, total);

        setTimeout(() => {
            this.hideProgress();
            Drive.loadFiles();
        }, 2500);
    },

    // ==================== PROGRESS UI ====================
    showProgressCard(total) {
        const bar = document.getElementById('upload-progress-bar');
        const icon = document.getElementById('upload-icon');
        const title = document.getElementById('upload-title');
        const fill = document.getElementById('upload-fill');

        if (bar) bar.style.display = 'block';
        if (icon) {
            icon.className = 'upload-header-icon';
            icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>';
        }
        if (title) title.textContent = `Uploading ${total} file${total > 1 ? 's' : ''}...`;
        if (fill) {
            fill.classList.remove('complete');
            fill.style.width = '0%';
        }

        bar?.classList.remove('done');
        this.updateProgress(0, 0, total);
    },

    updateTitle(text) {
        const el = document.getElementById('upload-title');
        if (el) el.textContent = text;
    },

    updateSubtitle(text) {
        const el = document.getElementById('upload-subtitle');
        if (el) el.textContent = text;
    },

    updateProgress(percent, done, total) {
        const fill = document.getElementById('upload-fill');
        const countEl = document.getElementById('upload-file-count');
        const percentEl = document.getElementById('upload-percent');

        if (fill) fill.style.width = `${Math.min(percent, 100)}%`;
        if (countEl) countEl.textContent = `${done} / ${total} files`;
        if (percentEl) percentEl.textContent = `${Math.round(percent)}%`;
    },

    showComplete(completed, failed, total) {
        const bar = document.getElementById('upload-progress-bar');
        const icon = document.getElementById('upload-icon');
        const title = document.getElementById('upload-title');
        const subtitle = document.getElementById('upload-subtitle');
        const fill = document.getElementById('upload-fill');
        const percentEl = document.getElementById('upload-percent');

        if (fill) fill.classList.add('complete');
        bar?.classList.add('done');

        if (failed === 0) {
            if (icon) {
                icon.className = 'upload-header-icon done';
                icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
            }
            if (title) title.textContent = 'Upload complete';
            if (subtitle) subtitle.textContent = `${completed} file${completed > 1 ? 's' : ''} uploaded successfully`;
            if (percentEl) percentEl.textContent = '100%';
        } else {
            if (icon) {
                icon.className = 'upload-header-icon error';
                icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            }
            if (title) title.textContent = 'Upload finished with errors';
            if (subtitle) subtitle.textContent = `${completed} succeeded, ${failed} failed`;
        }
    },

    hideProgress() {
        const bar = document.getElementById('upload-progress-bar');
        if (bar) bar.style.display = 'none';
        const fill = document.getElementById('upload-fill');
        if (fill) {
            fill.style.width = '0%';
            fill.classList.remove('complete');
        }
    },

    bindProgressClose() {
        const closeBtn = document.getElementById('upload-progress-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideProgress());
        }
    }
};
