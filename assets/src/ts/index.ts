/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

// altcha web component подключается отдельно через AltchaVendorAsset (локальный файл, без CDN).
// Этот бандл содержит только AltchaManager — утилитный слой поверх <altcha-widget>.

import { AltchaManager } from './manager';

/**
 * Расширение глобального window для TypeScript-проверки в других скриптах.
 * PHP-виджет и пользовательский JS обращаются к window.yii2Altcha напрямую.
 */
declare global {
    interface Window {
        /**
         * Менеджер виджетов ALTCHA.
         *
         * Методы для использования в JS:
         *   .reset('container-id')                        — сброс виджета (повторный сабмит)
         *   .attachModalReset('modal-id', 'container-id') — авто-сброс при закрытии модалки
         */
        yii2Altcha: AltchaManager;
    }
}

// Глобальный синглтон для взаимодействия с PHP-виджетом
window.yii2Altcha = new AltchaManager();
