import Alpine from 'alpinejs';
import sort from '@alpinejs/sort';
import focus from '@alpinejs/focus';
import 'trix';
import { applyTheme, contrast, isHex } from './admin/theme.js';

// No attachments in the text editor (images go through the media library)
document.addEventListener('trix-file-accept', (e) => e.preventDefault());

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

// Configuration and translated texts provided by the layout (admin-config)
const config = (() => {
    const el = document.getElementById('admin-config');
    return el ? JSON.parse(el.textContent) : {};
})();
const t = (key) => key.split('.').reduce((o, k) => o?.[k], config.i18n || {}) || key;

async function postJson(url, body, method = 'POST') {
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(body),
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error((await res.json().catch(() => ({}))).message || t('error') + ' ' + res.status);
    return res.json();
}

let uid = 0;
const nextId = () => 'k' + ++uid;

/* ------------------------------------------------------------------ */
/* Shared media library: picking and uploading images                  */
/* ------------------------------------------------------------------ */
Alpine.store('media', {
    open: false,
    loading: false,
    uploading: false,
    error: '',
    items: [],
    map: {},
    listUrl: '',
    uploadUrl: '',
    callback: null,
    search: '',

    init() {
        this.listUrl = config.mediaList;
        this.uploadUrl = config.mediaUpload;
    },
    register(map) {
        Object.assign(this.map, map || {});
    },
    async pick(callback) {
        this.callback = callback;
        this.open = true;
        this.error = '';
        this.loading = true;
        try {
            const res = await fetch(this.listUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            this.items = await res.json();
            this.items.forEach((m) => (this.map[m.id] = m));
        } catch (e) {
            this.error = t('loadError');
        }
        this.loading = false;
    },
    get filtered() {
        const q = this.search.trim().toLowerCase();
        return q ? this.items.filter((m) => (m.name + ' ' + (m.alt || '')).toLowerCase().includes(q)) : this.items;
    },
    choose(item) {
        this.map[item.id] = item;
        this.callback?.(item);
        this.open = false;
    },
    async upload(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.uploading = true;
        this.error = '';
        const body = new FormData();
        body.append('file', file);
        try {
            const res = await fetch(this.uploadUrl, {
                method: 'POST', body, credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.errors?.file?.[0] || json.message || t('uploadError'));
            this.items.unshift(json);
            this.choose(json);
        } catch (e) {
            this.error = e.message;
        }
        this.uploading = false;
        event.target.value = '';
    },
});

/* ------------------------------------------------------------------ */
/* Icons: loaded on demand for the picker                              */
/* ------------------------------------------------------------------ */
Alpine.store('icons', {
    open: false,
    paths: {},
    loaded: false,
    search: '',
    callback: null,
    url: '',

    init() {
        this.url = config.icons || '';
    },
    async load() {
        if (this.loaded || !this.url) return;
        const res = await fetch(this.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        this.paths = await res.json();
        this.loaded = true;
    },
    async pick(callback) {
        this.callback = callback;
        this.search = '';
        this.open = true;
        await this.load();
    },
    get names() {
        const q = this.search.trim().toLowerCase().replace(/\s+/g, '_');
        const all = Object.keys(this.paths);
        return q ? all.filter((n) => n.includes(q)) : all;
    },
    choose(name) {
        this.callback?.(name);
        this.open = false;
    },
});

/* ------------------------------------------------------------------ */
/* Section editor (form generated from the block schema)               */
/* ------------------------------------------------------------------ */
Alpine.data('blockEditor', (initial, errors, media) => ({
    data: initial,
    errors: errors || {},
    dirty: false,
    saving: false,

    init() {
        Alpine.store('media').register(media);
        this.prepare(this.data);
        this.openErrored();
        this.$watch('data', () => (this.dirty = true), { deep: true });
        window.addEventListener('beforeunload', (e) => {
            if (this.dirty && !this.saving) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },
    // Each repeated item gets a stable key (and a collapsed state)
    prepare(obj) {
        for (const value of Object.values(obj)) {
            if (Array.isArray(value)) {
                value.forEach((item) => {
                    if (item && typeof item === 'object') {
                        item._key ??= nextId();
                        item._open ??= false;
                        this.prepare(item);
                    }
                });
            }
        }
    },
    openErrored() {
        for (const path of Object.keys(this.errors)) {
            const parts = path.split('.');
            let node = this.data;
            for (let i = 0; i < parts.length - 1; i++) {
                node = node?.[parts[i]];
                if (node && typeof node === 'object' && !Array.isArray(node)) node._open = true;
            }
        }
    },
    err(path) {
        return this.errors[path] ? (Array.isArray(this.errors[path]) ? this.errors[path][0] : this.errors[path]) : '';
    },
    add(list, template) {
        const item = JSON.parse(JSON.stringify(template));
        item._key = nextId();
        item._open = true;
        this.prepare(item);
        list.push(item);
    },
    remove(list, index) {
        if (confirm(t('removeItem'))) list.splice(index, 1);
    },
    move(list, index, delta) {
        const to = index + delta;
        if (to < 0 || to >= list.length) return;
        const [item] = list.splice(index, 1);
        list.splice(to, 0, item);
    },
    duplicate(list, index) {
        const copy = JSON.parse(JSON.stringify(list[index]));
        const rekey = (o) => {
            if (Array.isArray(o)) o.forEach(rekey);
            else if (o && typeof o === 'object') {
                if ('_key' in o) o._key = nextId();
                Object.values(o).forEach(rekey);
            }
        };
        rekey(copy);
        copy._open = true;
        list.splice(index + 1, 0, copy);
    },
    toggleAll(list, open) {
        list.forEach((i) => (i._open = open));
    },
    // Data sent to the server, without the technical keys
    serialized() {
        return JSON.stringify(this.data, (k, v) => (k === '_key' || k === '_open' ? undefined : v));
    },
    submit() {
        this.saving = true;
        this.dirty = false;
    },
}));

/* Formatted text (Trix), created by script to work inside repeated lists */
Alpine.data('richText', (get, set) => ({
    init() {
        const id = 'trix-' + nextId();
        const input = document.createElement('input');
        input.type = 'hidden';
        input.id = id;
        input.value = get() || '';
        const editor = document.createElement('trix-editor');
        editor.setAttribute('input', id);
        editor.classList.add('trix-content');
        editor.addEventListener('trix-change', () => set(input.value));
        this.$el.append(input, editor);
    },
}));

/* Image field: preview + choice in the media library */
Alpine.data('imageField', (get, set) => ({
    get media() {
        return Alpine.store('media').map[get()] || null;
    },
    get value() {
        return get();
    },
    choose() {
        Alpine.store('media').pick((item) => set(item.id));
    },
    clear() {
        set(null);
    },
}));

/* Icon field */
Alpine.data('iconField', (get, set) => ({
    init() {
        Alpine.store('icons').load();
    },
    get value() {
        return get();
    },
    get path() {
        return Alpine.store('icons').paths[get()] || '';
    },
    choose() {
        Alpine.store('icons').pick((name) => set(name));
    },
    clear() {
        set('');
    },
}));

/* ------------------------------------------------------------------ */
/* Sortable lists (pages, sections)                                     */
/* ------------------------------------------------------------------ */
Alpine.data('reorder', (url) => ({
    saved: false,
    async save() {
        const ids = [...this.$root.querySelectorAll('[data-id]')].map((el) => Number(el.dataset.id));
        try {
            await postJson(url, { ids });
            this.saved = true;
            setTimeout(() => (this.saved = false), 2000);
        } catch (e) {
            alert(t('orderError') + ' ' + e.message);
        }
    },
}));

/* ------------------------------------------------------------------ */
/* Appearance: colours + live preview                                   */
/* ------------------------------------------------------------------ */
Alpine.data('appearance', (initial, presets, defaults) => ({
    colors: { ...initial },
    presets,
    defaults,

    init() {
        this.apply();
        this.$watch('colors', () => this.apply(), { deep: true });
    },
    apply() {
        const valid = Object.fromEntries(Object.entries(this.colors).map(([k, v]) => [k, isHex(v) ? v : this.defaults[k]]));
        applyTheme(this.$refs.preview, valid);
    },
    usePreset(key) {
        this.colors = { ...this.presets[key].colors };
    },
    isPreset(key) {
        return Object.entries(this.presets[key].colors).every(([k, v]) => (this.colors[k] || '').toLowerCase() === v);
    },
    reset() {
        this.colors = { ...this.defaults };
    },
    setHex(key, value) {
        value = value.trim();
        if (!value.startsWith('#')) value = '#' + value;
        if (isHex(value)) this.colors[key] = value.toLowerCase();
    },
    // Warning when the text is hard to read on its background
    get readability() {
        const ok = (a, b) => isHex(a) && isHex(b) && contrast(a, b) >= 4.5;
        const issues = [];
        if (!ok(this.colors.text, this.colors.background)) issues.push(t('readability.text'));
        if (!ok(this.colors.textMuted, this.colors.background)) issues.push(t('readability.muted'));
        if (!ok(this.colors.text, this.colors.card)) issues.push(t('readability.card'));
        return issues;
    },
}));

/* Mobile menu of the administration */
Alpine.data('shell', () => ({ menu: false }));

window.Alpine = Alpine;
Alpine.plugin([sort, focus]);
Alpine.start();
