import './theme.css';

// "Minimal" theme — same small enhancements as the default theme.

// Mobile menu (<details>): closes after a click on a link or with Escape
const menu = document.getElementById('mobile-menu');
if (menu) {
    menu.addEventListener('click', (e) => {
        if (e.target.closest('a')) menu.open = false;
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') menu.open = false;
    });
    window.matchMedia('(min-width: 1280px)').addEventListener('change', () => (menu.open = false));
}

// Language menu: closes with Escape or a click outside
document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) dropdown.open = false;
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dropdown.open = false;
    });
});

// FAQ: a single open answer at a time in a group
document.querySelectorAll('[data-accordion]').forEach((group) => {
    group.addEventListener('toggle', (e) => {
        if (!e.target.open) return;
        group.querySelectorAll('details[open]').forEach((d) => d !== e.target && (d.open = false));
    }, true);
});

// Videos: the player (YouTube / Vimeo) is loaded only after a click
document.querySelectorAll('[data-video]').forEach((button) => {
    button.addEventListener('click', () => {
        const iframe = document.createElement('iframe');
        iframe.src = button.dataset.video + (button.dataset.video.includes('?') ? '&' : '?') + 'autoplay=1';
        iframe.title = button.dataset.title || '';
        iframe.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
        iframe.allowFullscreen = true;
        iframe.className = 'absolute inset-0 w-full h-full border-0';
        button.replaceWith(iframe);
    });
});
