<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDrive — Sign In</title>
    <meta name="description" content="MDrive - A modern Google Drive file manager. Sign in with your Google account to manage your files.">
    <link rel="icon" type="image/png" href="/MDrive/public/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MDrive/public/css/style.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--login-bg);
            position: relative;
            overflow: hidden;
        }

        /* Ambient gradient orbs */
        .login-page::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59, 123, 140, 0.08) 0%, transparent 70%);
            pointer-events: none;
            animation: ambientFloat 20s ease-in-out infinite;
        }

        .login-page::after {
            content: '';
            position: absolute;
            bottom: -15%;
            right: -5%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(146, 208, 227, 0.06) 0%, transparent 70%);
            pointer-events: none;
            animation: ambientFloat 25s ease-in-out infinite reverse;
        }

        @keyframes ambientFloat {
            0%, 100% { transform: translate(0, 0); }
            33% { transform: translate(30px, -20px); }
            66% { transform: translate(-20px, 15px); }
        }

        .login-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 460px;
            margin: 20px;
            padding: 44px 40px;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border-radius: var(--radius-xl);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-xl);
            animation: cardSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .login-logo-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-logo h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.8px;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 14.5px;
            margin-bottom: 32px;
            line-height: 1.65;
        }

        .login-btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 13px 24px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-full);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .login-btn-google::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--color-primary-glow);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .login-btn-google:hover {
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 123, 140, 0.12), 0 0 0 1px var(--color-primary);
        }

        .login-btn-google:hover::before {
            opacity: 1;
        }

        .login-btn-google svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 28px 0;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 500;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .login-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .login-feature {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 14px;
            border-radius: var(--radius-md);
            background: var(--bg-secondary);
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .login-feature:hover {
            background: var(--bg-tertiary);
            transform: translateY(-1px);
        }

        .login-feature-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(59, 123, 140, 0.10);
            color: #3B7B8C;
        }

        [data-theme="dark"] .login-feature-icon {
            background: rgba(146, 208, 227, 0.10);
            color: #92D0E3;
        }

        .login-feature-icon svg {
            width: 18px;
            height: 18px;
        }

        .login-error {
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.12);
            color: var(--color-danger);
            padding: 11px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 18px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-error svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .login-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        .login-footer svg {
            width: 14px;
            height: 14px;
            vertical-align: -2px;
            margin-right: 4px;
            color: var(--color-primary);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 28px 22px;
                margin: 16px;
            }
            .login-features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo" style="justify-content:center;margin-bottom:16px;">
                <div class="login-logo-icon" style="width:180px;height:56px;">
                    <img src="/MDrive/public/logo.png" alt="MDrive Logo" width="180" height="56">
                </div>
            </div>

            <p class="login-subtitle">Your modern Google Drive file manager. Sign in to access, organize, and manage your cloud files.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="login-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <?= htmlspecialchars(urldecode($_GET['error'])) ?>
                </div>
            <?php endif; ?>

            <a href="/MDrive/auth/redirect" class="login-btn-google" id="google-signin-btn">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Sign in with Google
            </a>

            <div class="login-divider">What you can do</div>

            <div class="login-features">
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <span>Browse & organize</span>
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <span>Upload & download</span>
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    </div>
                    <span>Share & collaborate</span>
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </div>
                    <span>Manage trash</span>
                </div>
            </div>

            <div class="login-footer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Secured with Google OAuth 2.0 · Your files stay in your Drive
            </div>
        </div>
    </div>
</body>
</html>
