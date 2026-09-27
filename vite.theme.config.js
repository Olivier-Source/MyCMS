import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'node:path';

/*
 * Builds a bundled theme into resources/themes/{theme}/assets with stable
 * file names (theme.css, theme.js, fonts…). The result is committed: MyCMS
 * never needs Node.js on the server.
 *
 *   npx vite build -c vite.theme.config.js --mode default
 *   npx vite build -c vite.theme.config.js --mode minimal
 * (npm run build builds the administration and every bundled theme)
 */
export default defineConfig(({ mode }) => {
    const theme = mode === 'production' ? 'default' : mode;
    const root = resolve(import.meta.dirname, 'resources/themes', theme);

    return {
    root,
    base: './',
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: resolve(root, 'assets'),
        emptyOutDir: true,
        cssCodeSplit: false,
        assetsInlineLimit: 0,
        rollupOptions: {
            input: resolve(root, 'theme.js'),
            output: {
                entryFileNames: 'theme.js',
                assetFileNames: (asset) => (asset.names?.[0] ?? asset.name ?? '').endsWith('.css') ? 'theme.css' : '[name][extname]',
            },
        },
    },
    };
});
