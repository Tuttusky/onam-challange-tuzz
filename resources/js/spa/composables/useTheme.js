import { watch } from 'vue';

const DEFAULT_THEME = {
    primary_color: '#6366f1',
    secondary_color: '#64748b',
    accent_color: '#818cf8',
    background_gradient: '#0f172a',
    font_family: 'Baloo 2',
};

export function useTheme() {
    function applyTheme(theme) {
        const t = { ...DEFAULT_THEME, ...(theme || {}) };
        const root = document.documentElement;

        root.style.setProperty('--primary', t.primary_color);
        root.style.setProperty('--secondary', t.secondary_color);
        root.style.setProperty('--accent', t.accent_color || '#818cf8');
        root.style.setProperty('--bg-gradient', t.background_gradient);
        root.style.setProperty('--font-family', `'${t.font_family}', 'Segoe UI', sans-serif`);

        if (t.background_image) {
            root.style.setProperty('--bg-image', `url(${t.background_image})`);
        }

        if (t.favicon) {
            let link = document.querySelector("link[rel*='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            link.href = t.favicon;
        }
    }

    function watchTheme(themeRef) {
        watch(themeRef, (theme) => applyTheme(theme), { deep: true, immediate: true });
    }

    return {
        applyTheme,
        watchTheme,
    };
}
