/**
 * ESBuild configuration for ALTCHA Yii2 widget
 *
 * src/ts/index.ts → dist/js/altcha-manager.js
 *
 * Бандл включает только AltchaManager (reset, attachModalReset).
 * Сам altcha web component подключается отдельно через AltchaVendorAsset
 * из локального файла vendor/altcha-3.0.2/dist/main/altcha.min.js (без CDN).
 *
 * Экспортирует в window:
 *   yii2Altcha — менеджер виджетов (reset, attachModalReset)
 */

import * as esbuild from 'esbuild';
import { fileURLToPath } from 'url';
import * as path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const isWatch = process.argv.includes('--watch');

const buildOptions = {
    entryPoints: ['src/ts/index.ts'],
    outfile: 'dist/js/altcha-manager.js',
    bundle: true,
    format: 'iife',
    target: 'es2020',
    sourcemap: true,
    minify: true,
    treeShaking: true,
    platform: 'browser',
    tsconfig: './tsconfig.json',
    logLevel: 'info',
};

const build = async () => {
    if (isWatch) {
        const ctx = await esbuild.context(buildOptions);
        await ctx.watch();
        console.log('Watching TS... (altcha-manager.js)');
    } else {
        await esbuild.build(buildOptions);
        console.log('Build completed: dist/js/altcha-manager.js');
    }
};

build().catch(() => process.exit(1));
