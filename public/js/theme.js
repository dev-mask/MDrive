/**
 * MDrive — Theme Manager
 * Dark/light mode toggle with system preference detection
 */

const ThemeManager = {
    init() {
        const saved = localStorage.getItem('mdrive-theme');
        if (saved) {
            this.setTheme(saved);
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            this.setTheme('dark');
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('mdrive-theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });

        // Toggle button
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggle());
        }
    },

    getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    },

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('mdrive-theme', theme);

        const label = document.querySelector('.theme-label');
        if (label) {
            label.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
        }
    },

    toggle() {
        const current = this.getTheme();
        this.setTheme(current === 'dark' ? 'light' : 'dark');
    }
};

// Initialize immediately
ThemeManager.init();
