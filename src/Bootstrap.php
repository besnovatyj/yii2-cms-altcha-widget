<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Altcha;

use Besnovatyj\Altcha\config\AltchaConfig;
use Besnovatyj\Altcha\contracts\AltchaServiceInterface;
use Besnovatyj\Altcha\services\AltchaService;
use Besnovatyj\Helpers\SecretReader;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $container = \Yii::$container;

        // TODO Если разделять настройки фронтэнда и бэкэнда, то `set` вместо `setSingleton` и динамические настройки?
        $container->setSingleton(AltchaConfig::class,
            function () use ($app) {
                return new AltchaConfig(
                    hmacKey: SecretReader::get('ALTCHA_HMAC_KEY'),
                    maxNumber: 50000,
                    expiresSeconds: 120,
                    replayTtlSeconds: 600,
                );
            });

        $container->setSingleton(AltchaServiceInterface::class,
            function () use ($container) {
                /** @var $config AltchaConfig */
                $config = $container->get(AltchaConfig::class);
                return new AltchaService(
                    config: $config,
                );
            });
    }
}
