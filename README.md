# MDrive — Modern Google Drive File Manager

A production-ready PHP web application for managing Google Drive files through a beautiful, modern web interface. Features Google OAuth 2.0 authentication and full Drive API integration.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![Google Drive API](https://img.shields.io/badge/Google%20Drive-API%20v3-4285F4?style=flat&logo=google-drive&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

## ✨ Features

- **Google OAuth 2.0** — Secure sign-in with Google
- **File Management** — List, upload, download, rename, delete, and preview files
- **Folder Navigation** — Navigate folders with breadcrumb trail
- **Drag & Drop Upload** — Drop files anywhere to upload
- **File Preview** — Preview images, PDFs, and videos inline
- **Share Links** — Generate shareable links for any file
- **Star/Favorite** — Star files for quick access
- **Trash Management** — Trash, restore, and permanently delete files
- **Search** — Search files by name with debounced API calls
- **Dark Mode** — Full dark/light theme with system preference detection
- **Responsive** — Works on desktop, tablet, and mobile
- **Grid & List Views** — Toggle between grid and list layouts
- **Activity Log** — Track recent actions
- **Secure Tokens** — Encrypted token storage with auto-refresh

## 📋 Prerequisites

- **XAMPP** (or Apache + PHP 7.4+ + MySQL)
- **Composer** — [Download here](https://getcomposer.org/download/)
- **Google Cloud Console** project with OAuth 2.0 credentials

## 🚀 Installation

### 1. Clone / Download
Place the `MDrive` folder in your web server root:
```
xampp/htdocs/MDrive/
```

### 2. Install Dependencies
```bash
cd /path/to/MDrive
composer install
```

### 3. Google Cloud Console Setup

Follow these steps carefully to create your Google OAuth credentials:

#### Step 3.1 — Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click the **project dropdown** at the top-left of the page (next to "Google Cloud")
3. Click **New Project** in the top-right of the popup
4. Enter project details:
   - **Project name**: `MDrive` (or any name you prefer)
   - **Organization**: Leave as default
5. Click **Create** and wait for the project to be created
6. Make sure your new project is **selected** in the project dropdown

#### Step 3.2 — Configure the OAuth Consent Screen

> ⚠️ You **must** configure the consent screen **before** creating credentials.

1. In the left sidebar, go to **APIs & Services** → **OAuth consent screen**
2. Select **User Type**:
   - Choose **External** (unless you have a Google Workspace org, then choose Internal)
   - Click **Create**
3. Fill in the **App information**:
   - **App name**: `MDrive`
   - **User support email**: Select your email
   - **App logo**: Optional (skip for now)
4. Under **App domain**, leave all fields blank (not needed for localhost)
5. **Developer contact information**: Enter your email address
6. Click **Save and Continue**
7. On the **Scopes** page:
   - Click **Add or Remove Scopes**
   - Search and select these scopes:
     - `../auth/userinfo.email`
     - `../auth/userinfo.profile`
     - `../auth/drive` (under Google Drive API — if not visible, enable the API first in Step 3.3, then come back)
   - Click **Update** → **Save and Continue**
8. On the **Test users** page:
   - Click **+ Add Users**
   - Enter the **Gmail address(es)** you'll use to test the app
   - Click **Add** → **Save and Continue**
9. Review the summary and click **Back to Dashboard**

#### Step 3.3 — Enable the Google Drive API

1. In the left sidebar, go to **APIs & Services** → **Library**
2. In the search bar, type **Google Drive API**
3. Click on **Google Drive API** from the results
4. Click the **Enable** button
5. Wait for it to be enabled (you'll be redirected to the API overview page)

#### Step 3.4 — Create OAuth 2.0 Credentials

1. In the left sidebar, go to **APIs & Services** → **Credentials**
2. Click **+ Create Credentials** at the top of the page
3. Select **OAuth client ID**
4. Fill in the form:
   - **Application type**: Select **Web application**
   - **Name**: `MDrive Web Client` (or any name)
   - **Authorized JavaScript origins**: Click **+ Add URI** and enter:
     ```
     http://localhost
     ```
   - **Authorized redirect URIs**: Click **+ Add URI** and enter:
     ```
     http://localhost/MDrive/auth/callback
     ```
5. Click **Create**
6. A popup will show your credentials:
   - 📋 **Copy the Client ID** (looks like: `xxxx.apps.googleusercontent.com`)
   - 📋 **Copy the Client Secret** (looks like: `GOCSPX-xxxx`)
   - Click **Download JSON** to save a backup (optional)
7. Click **OK**

#### Step 3.5 — Add Credentials to Your `.env` File

Open the `.env` file in your MDrive folder and paste your credentials:
```env
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-secret-here
GOOGLE_REDIRECT_URI=http://localhost/MDrive/auth/callback
```

> **Important**: The `GOOGLE_REDIRECT_URI` must match **exactly** what you entered in Step 3.4 — including the protocol (`http://`), path, and no trailing slash.

### 4. Environment Configuration
```bash
cp .env.example .env
```
Edit `.env` with your values:
```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost/MDrive/auth/callback

DB_HOST=localhost
DB_DATABASE=mdrive
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Database Setup
Create a MySQL database named `mdrive`:
```sql
CREATE DATABASE mdrive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Tables are created automatically on first login.

Or manually import:
```bash
mysql -u root mdrive < database/schema.sql
```

### 6. Enable mod_rewrite (if not already)
In `httpd.conf`:
```
LoadModule rewrite_module modules/mod_rewrite.so
```
Restart Apache after changes.

### 7. Launch
Visit: [http://localhost/MDrive/](http://localhost/MDrive/)

## 📁 Project Structure

```
MDrive/
├── .htaccess              # URL routing & security
├── .env.example           # Environment template
├── composer.json          # Dependencies
├── index.php              # Front controller
├── config/                # Configuration
├── app/
│   ├── Controllers/       # Request handlers
│   ├── Services/          # Business logic (Google API)
│   ├── Middleware/         # Auth & CSRF guards
│   └── Models/            # Database models
├── database/              # SQL schema
├── views/                 # PHP views (login, dashboard)
├── public/
│   ├── css/style.css      # Design system
│   └── js/                # Frontend modules
└── storage/               # Encryption keys (auto-generated)
```

## ⌨️ Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `/` | Focus search bar |
| `F2` | Rename selected file |
| `Delete` | Delete selected file |
| `Ctrl+N` | Create new folder |
| `Esc` | Close modals/menus |

## 🔒 Security

- CSRF protection on all state-changing requests
- Encrypted token storage (defuse/php-encryption)
- Session-based auth with auto token refresh
- Input sanitization and validation
- Blocked access to sensitive files via .htaccess

## 🛠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| 404 errors on routes | Enable `mod_rewrite` and restart Apache |
| OAuth error | Check redirect URI matches exactly |
| Token errors | Delete `storage/encryption.key` and re-login |
| Database errors | Ensure MySQL is running and credentials are correct |

## 📄 License

MIT License — feel free to use, modify, and distribute.
