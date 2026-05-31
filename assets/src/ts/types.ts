/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * DOM-интерфейс кастомного элемента <altcha-widget>.
 *
 * Предоставляет типизацию для методов web component'а ALTCHA,
 * используемых в AltchaManager.
 *
 * @see https://altcha.org/docs/v2/widget-integration/
 */
export interface AltchaWidgetElement extends HTMLElement {
    /** Сбрасывает виджет и запрашивает новый challenge с сервера */
    reset(state?: unknown, err?: unknown): void;
    /** Конфигурирует виджет после инициализации (v3: возвращает Promise) */
    configure(options: object): Promise<void>;
    /** Возвращает текущее состояние виджета */
    getState(): string;
}
