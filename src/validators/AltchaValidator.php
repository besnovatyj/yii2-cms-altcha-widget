<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\validators;

use Besnovatyj\Altcha\contracts\AltchaServiceInterface;
use Yii;
use yii\validators\Validator;

final class AltchaValidator extends Validator
{
    public $message = 'Проверка "не робот" не пройдена. Попробуйте ещё раз.';

    public function init(): void
    {
        parent::init();

        // Принудительная инициализация модуля для регистрации DI контейнера
        Yii::$app->getModule('Altcha');
    }

    public function validateAttribute($model, $attribute): void
    {
        $value = (string)($model->$attribute ?? '');

        /** @var AltchaServiceInterface $service */
        $service = Yii::$container->get(AltchaServiceInterface::class);

        if (!$service->verifyBase64Payload($value)) {
            $this->addError($model, $attribute, $this->message);
        }
    }
}
