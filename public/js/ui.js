/**
 * MDrive — UI Components
 * Toast notifications, modals, skeletons, context menu
 */

const UI = {
    // ==================== TOAST NOTIFICATIONS ====================
    toast(type, title, message, duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const icons = {
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                ${message ? `<div class="toast-message">${message}</div>` : ''}
            </div>
            <button class="toast-close" onclick="this.closest('.toast').remove()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="toast-progress" style="animation-duration:${duration}ms"></div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    success(title, message) { this.toast('success', title, message); },
    error(title, message) { this.toast('error', title, message); },
    info(title, message) { this.toast('info', title, message); },
    warning(title, message) { this.toast('warning', title, message); },

    // ==================== MODALS ====================
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            const input = modal.querySelector('input:not([readonly])');
            if (input) setTimeout(() => input.focus(), 100);
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    },

    closeAllModals() {
        document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
    },

    initModals() {
        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.style.display = 'none';
            });
        });

        // Close buttons
        document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal-overlay').style.display = 'none';
            });
        });

        // Escape key closes modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeAllModals();
        });
    },

    // ==================== CONFIRM DIALOG ====================
    confirm(title, message) {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirm-modal');
            document.getElementById('confirm-title').textContent = title;
            document.getElementById('confirm-message').textContent = message;

            const confirmBtn = document.getElementById('confirm-action');
            const newBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

            newBtn.addEventListener('click', () => {
                this.closeModal('confirm-modal');
                resolve(true);
            });

            const cancelBtns = modal.querySelectorAll('[data-dismiss="modal"]');
            cancelBtns.forEach(btn => {
                const newCancel = btn.cloneNode(true);
                btn.parentNode.replaceChild(newCancel, btn);
                newCancel.addEventListener('click', () => {
                    this.closeModal('confirm-modal');
                    resolve(false);
                });
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal('confirm-modal');
                    resolve(false);
                }
            }, { once: true });

            this.openModal('confirm-modal');
        });
    },

    // ==================== SKELETONS ====================
    showSkeletons(viewMode = 'grid') {
        const container = document.getElementById('skeleton-container');
        if (!container) return;

        container.setAttribute('data-view', viewMode);
        container.style.display = viewMode === 'grid' ? 'grid' : 'flex';
        container.innerHTML = '';

        const count = viewMode === 'grid' ? 12 : 8;
        for (let i = 0; i < count; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = 'skeleton-item';
            skeleton.innerHTML = `
                <div class="skeleton-bar skeleton-icon"></div>
                <div class="skeleton-bar skeleton-text"></div>
                <div class="skeleton-bar skeleton-text-sm"></div>
            `;
            container.appendChild(skeleton);
        }

        container.style.display = '';
        document.getElementById('file-container').style.display = 'none';
        document.getElementById('empty-state').style.display = 'none';
    },

    hideSkeletons() {
        const container = document.getElementById('skeleton-container');
        if (container) {
            container.style.display = 'none';
            container.innerHTML = '';
        }
        document.getElementById('file-container').style.display = '';
    },

    // ==================== CONTEXT MENU ====================
    showContextMenu(e, menuId = 'context-menu') {
        e.preventDefault();
        this.hideAllContextMenus();

        const menu = document.getElementById(menuId);
        if (!menu) return;

        menu.style.display = 'block';

        // Position menu
        const rect = menu.getBoundingClientRect();
        let x = e.clientX;
        let y = e.clientY;

        if (x + rect.width > window.innerWidth) x = window.innerWidth - rect.width - 10;
        if (y + rect.height > window.innerHeight) y = window.innerHeight - rect.height - 10;

        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
    },

    hideAllContextMenus() {
        document.querySelectorAll('.context-menu').forEach(m => m.style.display = 'none');
    },

    initContextMenu() {
        document.addEventListener('click', () => this.hideAllContextMenus());
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideAllContextMenus();
        });
    }
};
