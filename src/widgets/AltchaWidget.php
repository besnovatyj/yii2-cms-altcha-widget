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
     * Дополнительные опции виджета ALTCHA.
     *
     * ВАЖНО (ALTCHA v3): web component читает напрямую с тега только «отражённые»
     * атрибуты — auto, challenge, configuration, display, language, name, theme,
     * type, workers. Все прочие опции конфигурации (hideFooter, hideLogo,
     * floatingPlacement и т.п.) как отдельные атрибуты игнорируются и должны
     * передаваться единым JSON-атрибутом configuration. Виджет делает это сам:
     * отражённые опции уходят атрибутами, остальные пакуются в configuration.
     *
     * Ключи опций конфигурации — в camelCase, как их называет ALTCHA
     * (hideFooter, hideLogo, а НЕ hidefooter/hidelogo).
     *
     * ```php
     *  \Besnovatyj\Altcha\widgets\AltchaWidget::widget([
     * 'name' => 'altcha',
     * 'challengeUrl' => \yii\helpers\Url::to(['/Altcha/backend/altcha-challenge/challenge']),
     * 'options' => [ // сюда можно класть любые опции <altcha-widget>
     * 'hideFooter' => true,
     * 'hideLogo' => true,
     * ],
     * ]);
     * ```
     * @see https://altcha.org/docs/v2/widget-integration/
     */
    public array $options = [];

    /**
     * Атрибуты, которые ALTCHA v3 отражает напрямую с тега <altcha-widget>.
     * Остальные опции web component берёт только из JSON-атрибута configuration.
     * Дополнены стандартными DOM-атрибутами, которые должны остаться на теге.
     *
     * @var string[]
     */
    private const REFLECTED_ATTRS = [
        'auto', 'challenge', 'configuration', 'display', 'language',
        'name', 'theme', 'type', 'workers',
        'id', 'class', 'style',
    ];

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

        // Разносим опции: отражённые ALTCHA'ой атрибуты — на тег, остальные — в configuration.
        [$attrs, $config] = $this->splitOptions($this->options);

        // ALTCHA v3+: атрибут называется 'challenge' (в v2 был 'challengeurl')
        $attrs['challenge'] = $this->challengeUrl;
        $attrs['name'] = $this->name;

        // Прочие опции конфигурации v3 принимает только единым JSON-атрибутом.
        if ($config !== []) {
            $attrs['configuration'] = Json::encode($config);
        }

        // Сам web component встраивается прямо внутрь формы.
        return Html::tag('altcha-widget', '', $attrs);
    }

    /**
     * Делит опции на атрибуты тега (отражаемые ALTCHA v3) и конфигурацию
     * для JSON-атрибута configuration.
     *
     * @param array<string, mixed> $options
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [$attrs, $config]
     */
    private function splitOptions(array $options): array
    {
        $attrs = [];
        $config = [];

        foreach ($options as $key => $value) {
            if (in_array(strtolower((string)$key), self::REFLECTED_ATTRS, true)) {
                $attrs[$key] = $value;
            } else {
                $config[$key] = $value;
            }
        }

        return [$attrs, $config];
    }
}
