<?php

return [
    // TODO - конфиг не нужен, yii2 сам бутстрапит класс `Besnovatyj\\Altcha\\Bootstrap` из composer.json при установке пакета
    'singletons' => [
        // TODO Если разделять настройки фронтэнда и бэкэнда, то в раздел definitions и динамические настройки?
        Besnovatyj\Altcha\config\AltchaConfig::class => [
            'class' => Besnovatyj\Altcha\config\AltchaConfig::class,
            '__construct()' => [
                'hmacKey' => \common\components\SecretReader::get('altcha_hmac_key'),
                'maxNumber' => 50000,
                'expiresSeconds' => 120,
                'replayTtlSeconds' => 600,
            ],
        ],
        Besnovatyj\Altcha\contracts\AltchaServiceInterface::class => [
            'class' => Besnovatyj\Altcha\services\AltchaService::class,
            '__construct()' => [
                'config' => yii\di\Instance::of('Besnovatyj\Altcha\config\AltchaConfig'),
                'cache' => Yii::$app->cache,
            ],
        ],
    ],
    'definitions' => [
        // Создаётся КАЖДЫЙ РАЗ заново
        // Каждый запрос возвращает НОВЫЙ объект
    ],
];
