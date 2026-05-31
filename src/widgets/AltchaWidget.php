<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\widgets;

use Besnovatyj\Altcha\assets\AltchaAsset;
use Yii;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/**
 * Виджет ALTCHA (proof-of-work captcha) для встраивания в формы.
 *
 * ## Использование с Yii2 ActiveForm (model/attribute)
 *
 * ```php
 * echo AltchaWidget::widget([
 *     'model'     => $form,
 *     'attribute' => 'altcha',
 * ]);
 * ```
 *
 * ## Использование с Bootstrap-модалкой (авто-сброс при закрытии)
 *
 * ```php
 * echo AltchaWidget::widget([
 *     'model'     => $form,
 *     'attribute' => 'altcha',
 *     'modalId'   => 'contact-modal',
 * ]);
 * ```
 *
 * ## Прямое использование (без модели)
 *
 * ```php
 * echo AltchaWidget::widget([
 *     'challengeUrl' => Url::to(['/Altcha/altcha-challenge/challenge']),
 * ]);
 * ```
 */
final class AltchaWidget extends Widget
{
    /**
     * Абсолютный или относительный URL endpoint'а, который отдаёт challenge JSON.
     * По умолчанию: /Altcha/altcha-challenge/challenge (фронтенд-контроллер модуля).
     *
     * Соответствует атрибуту challenge="" у <altcha-widget> (ALTCHA v3+).
     * В ALTCHA v2 и ниже атрибут назывался challengeurl="".
     */
    public string $challengeUrl = '';

    /**
     * Имя поля формы (атрибут name у <altcha-widget>).
     * Игнорируется, если заданы $model и $attribute — name вычисляется автоматически.
     */
    public string $name = 'altcha';

    /**
     * Модель формы для автоматического вычисления атрибута name по Yii2-конвенции.
     * Используется совместно с $attribute.
     */
    public ?Model $model = null;

    /**
     * Атрибут модели (имя поля).
     * Используется совместно с $model для вычисления name = ModelClass[attribute].
     */
    public ?string $attribute = null;

    /**
     * ID Bootstrap-модалки, при закрытии которой виджет авто-сбрасывается.
     * Позволяет использовать форму повторно без устаревшего challenge.
     *
     * ```php
     * 'modalId' => 'contact-modal'
     * ```
     */
    public ?string $modalId = null;

    /**
     * Любые дополнительные атрибуты <altcha-widget>
     * (например, floating, hidefooter, hidelogo и т.п.).
     * ```php
     *  \Besnovatyj\Altcha\widgets\AltchaWidget::widget([
     * 'name' => 'altcha',
     * 'challengeUrl' => \yii\helpers\Url::to(['/Altcha/backend/altcha-challenge/challenge']),
     * 'options' => [ // сюда можно класть любые атрибуты <altcha-widget>
     * 'hidefooter' => true,
     * ],
     * ]);
     * ```
     * @see https://altcha.org/docs/v2/widget-integration/
     */
    public array $options = [];

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        // Принудительная инициализация модуля для регистрации DI контейнера
        Yii::$app->getModule('Altcha');
    }

    /**
     * Рендерит <altcha-widget> и регистрирует JS-бандл.
     *
     * {@inheritdoc}
     */
    public function run(): string
    {
        AltchaAsset::register($this->view);

        // Вычисляем name из model/attribute по Yii2-конвенции (напр. MessageForm[altcha])
        if ($this->model !== null && $this->attribute !== null) {
            $this->name = Html::getInputName($this->model, $this->attribute);
        }

        // Дефолтный URL — фронтенд-контроллер модуля
        if ($this->challengeUrl === '') {
            $this->challengeUrl = Url::to(['/Altcha/altcha-challenge/challenge']);
        }

        // Если задан modalId — регистрируем JS для авто-сброса при закрытии модалки
        if ($this->modalId !== null) {
            $containerId = $this->getId();
            $this->options['id'] = $containerId;

            $this->view->registerJs(
                'window.yii2Altcha.attachModalReset(' .
                Json::encode($this->modalId) . ',' .
                Json::encode($containerId) . ');',
                View::POS_READY
            );
        }

        // ALTCHA v3+: атрибут называется 'challenge' (в v2 был 'challengeurl')
        $attrs = array_merge($this->options, [
            'challenge' => $this->challengeUrl,
            'name' => $this->name,
        ]);

        // Сам web component встраивается прямо внутрь формы.
        return Html::tag('altcha-widget', '', $attrs);
    }
}
