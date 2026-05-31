<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\assets;

use yii\web\AssetBundle;

/**
 * Asset-бандл AltchaManager.
 *
 * Бандл собирается командой:
 *   cd assets && npm install && npm run build
 *
 * Включает только AltchaManager (window.yii2Altcha) для reset() и Bootstrap-модалок.
 * Сам altcha web component подключается через зависимость AltchaVendorAsset.
 */
final class AltchaAsset extends AssetBundle
{
    /** Бандл собирается в assets/dist/js/ через npm run build */
    public $sourcePath = __DIR__ . '/../../assets/dist/js';

    /** @var string[] */
    public $js = ['altcha-manager.js'];

    /** @var string[] */
    public $depends = [AltchaVendorAsset::class];
}
