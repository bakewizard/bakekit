import { defineConfig } from 'vite';
import path from 'node:path';

const THEME_NAME = 'backend';
const SRC_ROOT = 'resources/backend';
const DEST_ROOT = 'webroot/backend';

export default defineConfig(({ command }) => ({
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, `${SRC_ROOT}/scripts`)
        }
    },
    base: command === 'build' ? `/${THEME_NAME}/` : '/',
    // publicDir: `${SRC_ROOT}/public`,
    server: {
        proxy: {
            // Exclude Vite internals and source/destination folders from proxy
            [`^/(?!@vite|@fs|node_modules|${SRC_ROOT}|${DEST_ROOT}).*`]: {
                target: 'http://bakekit.test',
                changeOrigin: true,
            },
        },
        host: true,
    },

    build: {
        outDir: DEST_ROOT,
        emptyOutDir: false,
        minify: command === 'build' ? 'terser' : false,
        rollupOptions: {
            input: {
                app: `${SRC_ROOT}/scripts/app.js`,
                blocks: `${SRC_ROOT}/scripts/pages/blocks/blocks.js`,
                menu: `${SRC_ROOT}/scripts/pages/blocks/menu.js`,
                menus: `${SRC_ROOT}/scripts/pages/menus/menus.js`,
                meta: `${SRC_ROOT}/scripts/pages/meta/meta.js`,
                users: `${SRC_ROOT}/scripts/pages/users/users.js`,
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? '';
                    if (/\.(css|scss|sass)$/.test(name)) return 'css/app.[ext]';
                    if (/\.(woff2?|ttf|eot|otf)$/.test(name)) return 'fonts/[name][extname]';
                    if (/\.(png|jpe?g|gif|svg|webp|avif)$/.test(name)) return 'img/[name].[ext]';
                    return 'assets/[name].[ext]';
                },
            },
        },
    },

    css: {
        preprocessorOptions: {
            scss: {
                loadPaths: [
                    'node_modules',
                    `${SRC_ROOT}/styles`,
                ],
                api: 'modern-compiler',
                silenceDeprecations: [
                    'import',
                    'color-functions',
                    'global-builtin',
                    'legacy-js-api',
                    'slash-div',
                    'if-function'
                ],
            }
        }
    }
}));
