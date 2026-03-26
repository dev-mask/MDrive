/**
 * MDrive — Main Application Controller
 * Initializes all modules, binds navigation, search, and keyboard shortcuts
 */

(function() {
    'use strict';

    // ==================== INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize modules
        UI.initModals();
        UI.initContextMenu();
        Drive.init();
        Upload.init();
        Upload.bindProgressClose();

        // Bind navigation
        initNavigation();
        initSidebar();
        initSearch();
        initUserDropdown();
        initKeyboardShortcuts();
        initNewFolderButton();

        // Load initial files
        Drive.loadFiles();
    });

    // ==================== NAVIGATION ====================
    function initNavigation() {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const view = item.dataset.view;
                if (view) {
                    Drive.switchView(view);
                    closeSidebarMobile();
                }
            });
        });

        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                Drive.setViewMode(btn.dataset.viewMode);
            });
        });
    }

    // ==================== SIDEBAR (MOBILE) ====================
    function initSidebar() {
        const hamburger = document.getElementById('hamburger-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const closeBtn = document.getElementById('sidebar-close');

        hamburger?.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        });

        overlay?.addEventListener('click', closeSidebarMobile);
        closeBtn?.addEventListener('click', closeSidebarMobile);
    }

    function closeSidebarMobile() {
        document.getElementById('sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('active');
    }

    // ==================== SEARCH ====================
    function initSearch() {
        const input = document.getElementById('search-input');
        let debounceTimer;

        input?.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            debounceTimer = setTimeout(() => {
                Drive.search(query);
            }, 400);
        });

        input?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(debounceTimer);
                Drive.search(e.target.value.trim());
            }
        });
    }

    // ==================== USER DROPDOWN ====================
    function initUserDropdown() {
        const avatar = document.getElementById('user-avatar');
        const dropdown = document.getElementById('user-dropdown');

        avatar?.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-profile')) {
                dropdown?.classList.remove('show');
            }
        });
    }

    // ==================== KEYBOARD SHORTCUTS ====================
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Don't trigger shortcuts when typing in inputs
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                if (e.key === 'Escape') {
                    e.target.blur();
                }
                return;
            }

            switch (e.key) {
                case '/':
                    e.preventDefault();
                    document.getElementById('search-input')?.focus();
                    break;

                case 'Delete':
                    if (Drive.selectedFile) {
                        if (Drive.currentView === 'trash') {
                            Drive.permanentDelete();
                        } else {
                            Drive.deleteFile();
                        }
                    }
                    break;

                case 'F2':
                    e.preventDefault();
                    if (Drive.selectedFile && Drive.currentView !== 'trash') {
                        Drive.renameFile();
                    }
                    break;

                case 'n':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        Drive.createFolder();
                    }
                    break;
            }
        });
    }

    // ==================== NEW FOLDER BUTTON ====================
    function initNewFolderButton() {
        document.getElementById('btn-new-folder')?.addEventListener('click', () => {
            Drive.createFolder();
        });
    }

})();
