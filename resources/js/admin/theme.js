// Same algorithm as app/Cms/ThemePalette.php: shades computed from 9 colours.

const hexToRgb = (h) => {
    const n = parseInt(h.replace('#', ''), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
};
const rgbToHex = (rgb) => '#' + rgb.map((v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
const mix = (a, b, t) => {
    const x = hexToRgb(a), y = hexToRgb(b);
    return rgbToHex(x.map((v, i) => v + (y[i] - v) * t));
};
const luminance = (hex) => {
    const c = hexToRgb(hex).map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
};
const contrast = (a, b) => {
    const l1 = luminance(a), l2 = luminance(b);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
};
const onColor = (c) => (contrast(c, '#ffffff') >= contrast(c, '#1d1d1b') ? '#ffffff' : '#1d1d1b');

export const isHex = (v) => /^#[0-9a-fA-F]{6}$/.test(v || '');
export { contrast };

export function derive(p) {
    const { primary: P, secondary: S, accent: A, button: B, background: bg, section: sec, text: txt, textMuted: muted } = p;
    const dark = luminance(bg) < 0.2;
    return {
        'primary': P, 'on-primary': onColor(P),
        'primary-container': mix(P, bg, 0.3), 'on-primary-container': mix(P, txt, 0.75),
        'primary-fixed': mix(P, bg, 0.72), 'primary-fixed-dim': mix(P, bg, 0.45),
        'on-primary-fixed': mix(P, txt, 0.65), 'on-primary-fixed-variant': mix(P, txt, 0.35),
        'secondary': S, 'on-secondary': onColor(S),
        'secondary-container': mix(S, bg, 0.84), 'secondary-fixed': mix(S, bg, 0.84),
        'on-secondary-container': mix(S, txt, 0.3), 'on-secondary-fixed': mix(S, txt, 0.7), 'on-secondary-fixed-variant': mix(S, txt, 0.4),
        'tertiary': A, 'on-tertiary': onColor(A),
        'tertiary-container': mix(A, bg, 0.3), 'tertiary-fixed': mix(A, bg, 0.72),
        'on-tertiary-fixed': mix(A, txt, 0.7), 'on-tertiary-fixed-variant': mix(A, txt, 0.4),
        'btn': B, 'on-btn': onColor(B), 'btn-hover': luminance(B) < 0.06 ? mix(B, '#ffffff', 0.15) : mix(B, '#000000', 0.15),
        'surface': bg,
        'surface-container-lowest': p.card,
        'surface-container-low': sec,
        'surface-container': mix(sec, txt, 0.03),
        'surface-container-high': mix(sec, txt, 0.06),
        'surface-container-highest': mix(sec, txt, 0.1),
        'surface-variant': mix(sec, txt, 0.1),
        'on-surface': txt, 'on-surface-variant': muted, 'outline-variant': mix(muted, bg, 0.7),
        'error': dark ? '#ff8a80' : '#b83230', 'on-error': dark ? '#3a0a08' : '#ffffff',
        'error-container': dark ? '#5c1a17' : '#ffdad8', 'on-error-container': dark ? '#ffdad8' : '#690005',
    };
}

/** Applies the palette to an element (preview). */
export function applyTheme(el, colors) {
    const tokens = derive(colors);
    for (const [k, hex] of Object.entries(tokens)) {
        el.style.setProperty('--c-' + k, hexToRgb(hex).join(' '));
    }
}
