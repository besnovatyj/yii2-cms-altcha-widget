import type { AltchaWidgetElement } from './types';

/**
 * Менеджер виджетов ALTCHA.
 *
 * Отвечает за:
 * - reset() виджета после повторного использования формы (AJAX, повторный сабмит)
 * - автоматический reset при закрытии Bootstrap-модалки
 *
 * Синглтон доступен как window.yii2Altcha.
 *
 * Множество экземпляров <altcha-widget> на одной странице поддерживается нативно
 * через web component API — менеджер нужен только для управления жизненным циклом.
 */
export class AltchaManager {
    /**
     * Сбрасывает виджет по ID его HTML-элемента.
     *
     * Вызывать после неуспешного AJAX-сабмита или переоткрытия формы:
     *   window.yii2Altcha.reset('altcha-w0')
     */
    reset(containerId: string): void {
        const el = document.getElementById(containerId) as AltchaWidgetElement | null;
        el?.reset();
    }

    /**
     * Привязывает автоматический reset виджета к событию закрытия Bootstrap-модалки.
     *
     * PHP-виджет вызывает это при рендере с параметром modalId:
     *   window.yii2Altcha.attachModalReset('contact-modal', 'altcha-w0')
     *
     * После закрытия модалки виджет сбросится и запросит новый challenge,
     * чтобы при повторном открытии модалки пользователь увидел свежий виджет.
     */
    attachModalReset(modalId: string, containerId: string): void {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }
        modal.addEventListener('hidden.bs.modal', () => this.reset(containerId));
    }
}
