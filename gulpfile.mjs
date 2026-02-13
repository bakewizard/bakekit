import path from 'node:path';
import { series, parallel, src, dest, watch } from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
import { deleteAsync } from 'del';
import browserSync from 'browser-sync';
import { rollup } from 'rollup';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import terser from '@rollup/plugin-terser';

const sass = gulpSass(dartSass);
const browser = browserSync.create();

const isDevBuild = ((process.env.NODE_ENV || 'development').trim().toLowerCase() === 'development');

const config = {
    src: {
        scripts: [
            'resources/backend/scripts/app.js',
            'resources/backend/scripts/pages/blocks/blocks.js',
            'resources/backend/scripts/pages/blocks/menu.js',
            'resources/backend/scripts/pages/menus/menus.js',
            'resources/backend/scripts/pages/meta/meta.js',
            'resources/backend/scripts/pages/users/users.js',
        ],
        styles: 'resources/backend/styles/**/*.scss',
        fonts: [
            'node_modules/@fortawesome/fontawesome-free/webfonts/*.woff2',
            'node_modules/bootstrap-icons/font/fonts/*.woff2'
        ]
    },
    build: {
        js: 'webroot/backend/js/',
        css: 'webroot/backend/css/',
        fonts: 'webroot/backend/fonts/'
    },
    watch: {
        html: 'templates/Admin/**/*.php',
        styles: 'resources/backend/styles/**/*.scss',
        scripts: 'resources/backend/scripts/**/*.js',
    },
    browser: {
        proxy: 'bakekit.test',
        open: 'external',
        notify: false,
        watchEvents: ['add', 'change', 'unlink', 'addDir', 'unlinkDir']
    },
    clean: [
        'webroot/backend/js/',
        'webroot/backend/css/',
        'webroot/backend/fonts/'
    ]
};

export function styles() {
    return src(config.src.styles)
        .pipe(sass({
            loadPaths: ['node_modules'],
            style: isDevBuild ? 'expanded' : 'compressed',
            silenceDeprecations: ['legacy-js-api', 'color-functions', 'global-builtin', 'import', 'slash-div', 'if-function'],
        }).on('error', sass.logError))
        .pipe(dest(config.build.css))
        .pipe(browser.stream());
}

export async function scripts() {
    await Promise.all(
        config.src.scripts.map(async (entry) => {
            const name = path.parse(entry).name;

            const bundle = await rollup({
                input: entry,
                plugins: [
                    resolve(),
                    commonjs(),
                    !isDevBuild && terser({ format: { comments: false } })
                ].filter(Boolean)
            });

            await bundle.write({
                file: `${config.build.js}${name}.js`,
                format: 'iife',
                name: 'app',
                sourcemap: false
            });
        })
    );

    browser.reload();
}

export function fonts() {
    return src(config.src.fonts, { encoding: false })
        .pipe(dest(config.build.fonts));
}

export function clean() {
    return deleteAsync(config.clean);
}

export function listen() {
    browser.init(config.browser);

    watch(config.watch.styles, styles);
    watch(config.watch.scripts, scripts);
    watch(config.watch.html).on('change', browser.reload);
}

export const build = parallel(styles, scripts, fonts);

export default series(clean, build, listen);
