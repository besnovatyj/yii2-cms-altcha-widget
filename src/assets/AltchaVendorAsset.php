<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Вендорный asset-бандл ALTCHA web component.
 *
 * Подключает локальный файл main/altcha.min.js (скачанный с jsDelivr),
 * который регистрирует кастомный элемент <altcha-widget> в браузере.
 *
 * CDN не используется — файл хранится в assets/vendor/altcha-3.0.2/.
 *
 * ## Локализация
 *
 * В ALTCHA v3 переводы вынесены из основного бандла: main/altcha.min.js
 * содержит только английские строки. Строки остальных локалей лежат в
 * dist/i18n/{lang}.js и регистрируются вызовом globalThis.$altcha.i18n.set().
 * Поэтому здесь дополнительно подключается i18n-файл текущего языка приложения
 * (Yii::$app->language) — только один, чтобы не тянуть все локали.
 * Сам выбор локали делает атрибут language="" у <altcha-widget> (см. AltchaWidget).
 */
final class AltchaVendorAsset extends AssetBundle
{
    /** @var string Путь к dist — содержит основной бандл (main/) и локали (i18n/) */
    public $sourcePath = __DIR__ . '/../../assets/vendor/altcha-3.0.2/dist';

    /** @var string[] */
    public $js = ['main/altcha.min.js'];

    /**
     * main/altcha.min.js — это ES-модуль (у него нет IIFE-обёртки, верхнеуровневые
     * переменные модульно изолированы). При подключении обычным <script> его
     * `var e,t,n,...` (в т.ч. t = Array.prototype.indexOf) утекают в window и
     * затираются легаси-скриптами страницы, из-за чего Svelte-рантайм падает
     * с "t.call is not a function". Грузим как модуль — тогда область видимости
     * изолирована и коллизии глобалей нет.
     *
     * @var array<string, string>
     */
    public $jsOptions = ['type' => 'module'];

    /**
     * {@inheritdoc}
     *
     * Догружает i18n-файл текущего языка приложения после основного бандла,
     * если такая локаль есть в комплекте altcha. Порядок в $js сохраняется:
     * ES-модули выполняются в порядке подключения, поэтому к моменту вызова
     * i18n.set() глобаль $altcha из основного бандла уже создана.
     */
    public function init(): void
    {
        parent::init();

        $lang = $this->resolveLanguageCode();
        if ($lang !== null && $lang !== 'en') {
            $this->js[] = "i18n/{$lang}.js";
        }
    }

    /**
     * Возвращает двухбуквенный код локали (напр. 'ru' из 'ru-RU'),
     * если для неё есть файл переводов altcha, иначе null (останется английский).
     */
    private function resolveLanguageCode(): ?string
    {
        $code = strtolower(substr(Yii::$app->language, 0, 2));

        return is_file($this->sourcePath . "/i18n/{$code}.js") ? $code : null;
    }
}
