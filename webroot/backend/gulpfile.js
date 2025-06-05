import { series, parallel, src, dest, watch, lastRun } from 'gulp';
import imagemin, { gifsicle, mozjpeg, optipng, svgo } from 'gulp-imagemin';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
import noop from "gulp-noop";
import { deleteAsync } from 'del';
import newer from 'gulp-newer';
import browser from 'browser-sync';
import { rollup } from 'rollup';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import terser from '@rollup/plugin-terser';

const sass = gulpSass(dartSass);

const isDevBuild = ((process.env.NODE_ENV || 'development').trim().toLowerCase() === 'development');

const config = {
    src: {
        scripts: [
            'src/scripts/app.js',
            'src/scripts/pages/blocks/blocks.js',
            'src/scripts/pages/blocks/menu.js',
            'src/scripts/pages/menus/menus.js',
            'src/scripts/pages/meta/meta.js',
            'src/scripts/pages/users/users.js'
        ],
        styles: 'src/styles/**/*.scss',
        // images: [
        //     'src/images/*.*',
        // ],
        fonts: [
            // 'src/fonts/*.woff2',
            'node_modules/@fortawesome/fontawesome-free/webfonts/*.woff2',
            'node_modules/bootstrap-icons/font/fonts/*.woff2'
        ]
    },
    build: {
        js: 'js/',
        css: 'css/',
        // img: 'img/',
        fonts: 'fonts/'
    },
    watch: {
        html: '../../templates/**/*.php',
        styles: 'src/styles/**/*.scss',
        scripts: 'src/scripts/**/*.js',
        // images: 'src/images/**/*.{jpg,png,svg}'
    },
    browser: {
        proxy: 'cakecms.test',
        notify: false,
        watchEvents: ['add', 'change', 'unlink', 'addDir', 'unlinkDir']
    },
    clean: [
        'js/',
        'css/',
        // 'img/',
        'fonts/'
    ]
};

export function styles() {
    return src(config.src.styles)
        .pipe(sass({
            style: isDevBuild ? 'expanded' : 'compressed',
            silenceDeprecations: ['legacy-js-api', 'mixed-decls', 'color-functions', 'global-builtin', 'import', 'slash-div'],
        }).on('error', sass.logError))
        .pipe(dest(config.build.css))
        .pipe(browser.stream());
}

export async function scripts() {
    await Promise.all(config.src.scripts.map(async (entry) => {
        const bundle = await rollup({
            input: entry,
            plugins: [
                resolve(),
                commonjs(),
                !isDevBuild ? terser({ format: { comments: false }, compress: false }) : noop()
            ]
        });

        await bundle.write({
            dir: config.build.js,
            format: 'iife',
            entryFileNames: '[name].js',
            name: 'app'
        }).then(browser.stream());
    }));
}

export function images() {
    return src(config.src.images, { encoding: false, since: lastRun(images) })
        .pipe(newer(config.build.img))
        .pipe(imagemin([
            gifsicle({ interlaced: true }),
            mozjpeg({ quality: 75, progressive: true }),
            optipng({ optimizationLevel: 5 }),
            svgo({
                plugins: [
                    { name: 'removeViewBox', active: true },
                    { name: 'cleanupIDs', active: true }
                ]
            })
        ]))
        .pipe(dest(config.build.img))
        .pipe(browser.stream());
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
    // watch(config.watch.images, images);
    watch(config.watch.html).on('change', browser.reload);
}

export const build = parallel(styles, scripts, images, fonts);

export default series(clean, build, listen);