<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

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

    /**
     * altcha.min.js — это ES-модуль (у него нет IIFE-обёртки, верхнеуровневые
     * переменные модульно изолированы). При подключении обычным <script> его
     * `var e,t,n,...` (в т.ч. t = Array.prototype.indexOf) утекают в window и
     * затираются легаси-скриптами страницы, из-за чего Svelte-рантайм падает
     * с "t.call is not a function". Грузим как модуль — тогда область видимости
     * изолирована и коллизии глобалей нет.
     *
     * @var array<string, string>
     */
    public $jsOptions = ['type' => 'module'];
}
