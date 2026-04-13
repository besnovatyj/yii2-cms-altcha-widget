<?php

declare(strict_types=1);

namespace Besnovatyj\Altcha\widgets;

use Besnovatyj\Altcha\assets\AltchaAsset;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

final class AltchaWidget extends Widget
{
    /** Абсолютный или относительный URL endpoint'а, который отдаёт challenge JSON. */
    public string $challengeUrl;

    /** Имя поля, которое уйдёт в POST (по умолчанию altcha). */
    public string $name = 'altcha';

    /** Любые дополнительные атрибуты <altcha-widget> (например, floating, hidefooter и т.п.). */
    public array $options = [];

    /** Использовать CDN (true) или self-host (false) для altcha.min.js */
    public bool $useCdn = false;

    public function init(): void
    {
        parent::init();

        // Принудительная инициализация модуля для регистрации DI контейнера
        Yii::$app->getModule('Altcha');
    }

    public function run(): string
    {
        $asset = new AltchaAsset();
        $asset->useCdn = $this->useCdn;
        $asset::register($this->view);

        $this->challengeUrl = $this->challengeUrl ?? Url::to(['/Altcha/backend/altcha/challenge'], true);

        $attrs = array_merge($this->options, [
            'challengeurl' => $this->challengeUrl,
            'name' => $this->name,
        ]);

        // Сам web component, встраивается прямо внутрь формы.
        return Html::tag('altcha-widget', '', $attrs);
    }
}
