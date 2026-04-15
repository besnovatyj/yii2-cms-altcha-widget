<?php

declare(strict_types=1);

namespace Besnovatyj\Altcha\assets;

use yii\web\AssetBundle;

/**
 * Вендорный asset-бандл ALTCHA web component.
 *
 * Подключает локальный файл altcha.min.js (скачанный с jsDelivr),
 * который регистрирует кастомный элемент <altcha-widget> в браузере.
 *
 * CDN не используется — файл хранится в assets/vendor/altcha-3.0.2/.
 */
final class AltchaVendorAsset extends AssetBundle
{
    /** @var string Путь к dist/main — содержит полный бандл altcha (стили встроены) */
    public $sourcePath = __DIR__ . '/../../assets/vendor/altcha-3.0.2/dist/main';

    /** @var string[] */
    public $js = ['altcha.min.js'];
}
