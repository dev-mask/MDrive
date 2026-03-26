/**
 * MDrive — Upload Manager
 * Drag & drop file upload with progress tracking
 */

const Upload = {
    currentFolderId: null,

    init() {
        this.bindDragDrop();
        this.bindFileInput();
        this.bindUploadButton();
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
                    e.target.value = ''; // Reset
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

    // ==================== UPLOAD LOGIC ====================
    async uploadFiles(fileList) {
        const files = Array.from(fileList);
        const total = files.length;
        let completed = 0;
        let failed = 0;

        // Show progress card
        this.showProgressCard(total);

        for (const file of files) {
            // Update current file name
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

        // Show completion state
        this.showComplete(completed, failed, total);

        // Auto-hide after delay
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
