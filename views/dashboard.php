<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDrive — Dashboard</title>
    <meta name="description" content="MDrive Dashboard - Manage your Google Drive files with a modern interface">
    <link rel="icon" type="image/png" href="/MDrive/public/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MDrive/public/css/style.css">
</head>
<body>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">

    <div class="app-layout" id="app">
        <!-- ==================== SIDEBAR ==================== -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon" style="width:160px;height:50px;border-radius:0;">
                        <img src="/MDrive/public/logo.png" alt="MDrive Logo" width="160" height="50">
                    </div>
                </div>
                <button class="sidebar-close-btn" id="sidebar-close" title="Close sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="sidebar-actions">
                <button class="btn btn-primary btn-new" id="btn-new-folder" title="Create new folder">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Folder
                </button>
                <button class="btn btn-secondary btn-upload" id="btn-upload" title="Upload files">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Upload
                </button>
                <button class="btn btn-secondary btn-upload" id="btn-upload-folder" title="Upload folder">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><polyline points="12 11 12 17"/><polyline points="9 14 12 11 15 14"/></svg>
                    Upload Folder
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-view="my-drive" id="nav-my-drive">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span>My Drive</span>
                </a>
                <a href="#" class="nav-item" data-view="recent" id="nav-recent">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Recent</span>
                </a>
                <a href="#" class="nav-item" data-view="starred" id="nav-starred">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Starred</span>
                </a>
                <a href="#" class="nav-item" data-view="trash" id="nav-trash">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <span>Trash</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="theme-toggle-wrapper">
                    <button class="theme-toggle-btn" id="theme-toggle" title="Toggle dark mode">
                        <svg class="theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <svg class="theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <span class="theme-label">Dark Mode</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar overlay -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- ==================== MAIN CONTENT ==================== -->
        <main class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar" id="top-navbar">
                <div class="navbar-left">
                    <button class="hamburger-btn" id="hamburger-btn" title="Open sidebar">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div class="search-bar" id="search-bar">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="search-input" id="search-input" placeholder="Search files and folders..." autocomplete="off">
                        <kbd class="search-shortcut">/</kbd>
                    </div>
                </div>
                <div class="navbar-right">
                    <div class="view-toggle" id="view-toggle">
                        <button class="view-btn active" data-view-mode="grid" title="Grid view" id="view-grid-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </button>
                        <button class="view-btn" data-view-mode="list" title="List view" id="view-list-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="user-profile" id="user-profile">
                        <img src="<?= htmlspecialchars($user['profile_picture'] ?? '') ?>" alt="Profile" class="user-avatar" id="user-avatar" referrerpolicy="no-referrer">
                        <div class="user-dropdown" id="user-dropdown">
                            <div class="dropdown-user-info">
                                <img src="<?= htmlspecialchars($user['profile_picture'] ?? '') ?>" alt="Profile" class="dropdown-avatar" referrerpolicy="no-referrer">
                                <div>
                                    <div class="dropdown-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                                    <div class="dropdown-user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="/MDrive/auth/logout" class="dropdown-item" id="logout-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Sign out
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area" id="content-area">
                <!-- Breadcrumbs -->
                <div class="breadcrumbs" id="breadcrumbs">
                    <a href="#" class="breadcrumb-item" data-folder-id="root">My Drive</a>
                </div>

                <!-- Content Header -->
                <div class="content-header" id="content-header">
                    <h2 class="content-title" id="content-title">My Drive</h2>
                    <div class="content-meta" id="content-meta"></div>
                </div>

                <!-- File Grid/List Container -->
                <div class="file-container" id="file-container" data-view="grid">
                    <!-- Files rendered by JavaScript -->
                </div>

                <!-- Empty State -->
                <div class="empty-state" id="empty-state" style="display:none;">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <h3 class="empty-title">No files here</h3>
                    <p class="empty-text">Drop files here or use the upload button to add files</p>
                </div>

                <!-- Loading Skeletons -->
                <div class="skeleton-container" id="skeleton-container" style="display:none;">
                </div>

                <!-- Load More -->
                <div class="load-more" id="load-more" style="display:none;">
                    <button class="btn btn-secondary" id="load-more-btn">Load More Files</button>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== DRAG & DROP OVERLAY ==================== -->
    <div class="dropzone-overlay" id="dropzone-overlay">
        <div class="dropzone-content">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <h3>Drop files to upload</h3>
            <p>Files will be uploaded to the current folder</p>
        </div>
    </div>

    <!-- ==================== HIDDEN FILE INPUTS ==================== -->
    <input type="file" id="file-input" multiple style="display:none;">
    <input type="file" id="folder-input" webkitdirectory directory multiple style="display:none;">

    <!-- ==================== CONTEXT MENU ==================== -->
    <div class="context-menu" id="context-menu" style="display:none;">
        <button class="context-menu-item" data-action="open" id="ctx-open">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Open
        </button>
        <button class="context-menu-item" data-action="preview" id="ctx-preview">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview
        </button>
        <div class="context-menu-divider"></div>
        <button class="context-menu-item" data-action="download" id="ctx-download">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download
        </button>
        <button class="context-menu-item" data-action="share" id="ctx-share">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            Share Link
        </button>
        <div class="context-menu-divider"></div>
        <button class="context-menu-item" data-action="star" id="ctx-star">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span id="ctx-star-label">Star</span>
        </button>
        <button class="context-menu-item" data-action="rename" id="ctx-rename">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
            Rename
        </button>
        <div class="context-menu-divider"></div>
        <button class="context-menu-item context-menu-danger" data-action="delete" id="ctx-delete">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Move to Trash
        </button>
    </div>

    <!-- Trash Context Menu -->
    <div class="context-menu" id="trash-context-menu" style="display:none;">
        <button class="context-menu-item" data-action="restore" id="ctx-restore">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            Restore
        </button>
        <button class="context-menu-item context-menu-danger" data-action="permanent-delete" id="ctx-permanent-delete">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            Delete Permanently
        </button>
    </div>

    <!-- ==================== MODALS ==================== -->
    <!-- Preview Modal -->
    <div class="modal-overlay" id="preview-modal" style="display:none;">
        <div class="modal modal-preview">
            <div class="modal-header">
                <h3 class="modal-title" id="preview-title">File Preview</h3>
                <button class="modal-close" id="preview-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body" id="preview-body">
                <!-- Preview content rendered by JS -->
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal-overlay" id="rename-modal" style="display:none;">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">Rename</h3>
                <button class="modal-close" data-dismiss="modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="modal-input" id="rename-input" placeholder="Enter new name" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="rename-confirm">Rename</button>
            </div>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div class="modal-overlay" id="folder-modal" style="display:none;">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">New Folder</h3>
                <button class="modal-close" data-dismiss="modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="modal-input" id="folder-name-input" placeholder="Folder name" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="folder-confirm">Create</button>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal-overlay" id="share-modal" style="display:none;">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">Share Link</h3>
                <button class="modal-close" data-dismiss="modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-secondary);font-size:14px;margin-bottom:12px;">Anyone with this link can view the file:</p>
                <div class="share-link-container">
                    <input type="text" class="modal-input" id="share-link-input" readonly>
                    <button class="btn btn-primary" id="copy-share-link" title="Copy link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal-overlay" id="confirm-modal" style="display:none;">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h3 class="modal-title" id="confirm-title">Confirm</h3>
                <button class="modal-close" data-dismiss="modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirm-message" style="color:var(--text-secondary);font-size:14px;">Are you sure?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="confirm-action">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Upload Progress -->
    <div class="upload-progress-bar" id="upload-progress-bar" style="display:none;">
        <div class="upload-header">
            <div class="upload-header-icon" id="upload-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="upload-header-info">
                <div class="upload-title" id="upload-title">Uploading files...</div>
                <div class="upload-subtitle" id="upload-subtitle">Preparing...</div>
            </div>
            <button class="upload-close" id="upload-progress-close" title="Dismiss">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="upload-progress-section">
            <div class="upload-progress-meta">
                <span class="upload-file-count" id="upload-file-count">0 / 0 files</span>
                <span class="upload-percent" id="upload-percent">0%</span>
            </div>
            <div class="upload-track">
                <div class="upload-fill" id="upload-fill"></div>
            </div>
        </div>
    </div>

    <!-- ==================== TOAST CONTAINER ==================== -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ==================== BULK ACTIONS BAR ==================== -->
    <div class="bulk-actions-bar" id="bulk-actions-bar">
        <div class="bulk-count" id="bulk-count">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span id="bulk-count-text">0 selected</span>
        </div>
        <div class="bulk-divider"></div>
        <button class="bulk-action-btn" id="bulk-select-all" title="Select all files">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><polyline points="9 11 12 14 22 4"/></svg>
            Select All
        </button>
        <div class="bulk-divider"></div>
        <button class="bulk-action-btn" id="bulk-download" title="Download selected files">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download
        </button>
        <button class="bulk-action-btn danger" id="bulk-delete" title="Delete selected files">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Delete
        </button>
        <div class="bulk-divider"></div>
        <button class="bulk-close-btn" id="bulk-clear" title="Clear selection">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- ==================== SCRIPTS ==================== -->
    <script src="/MDrive/public/js/theme.js"></script>
    <script src="/MDrive/public/js/ui.js"></script>
    <script src="/MDrive/public/js/drive.js"></script>
    <script src="/MDrive/public/js/upload.js"></script>
    <script src="/MDrive/public/js/app.js"></script>
</body>
</html>
