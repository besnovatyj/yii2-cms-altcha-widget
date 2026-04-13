<?php

declare(strict_types=1);

namespace Besnovatyj\Altcha\controllers;

use Besnovatyj\Altcha\contracts\AltchaServiceInterface;
use Yii;
use yii\base\Action;
use yii\web\Response;

/** Action для выдачи challenge (GRASP: Controller → делегирует сервису) */
final class AltchaChallengeAction extends Action
{
    public function run(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var AltchaServiceInterface $service */
        $service = Yii::$container->get(AltchaServiceInterface::class);

        // Важно: не кэшировать этот endpoint на уровне CDN/прокси.
        Yii::$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $service->createChallenge();
    }
}
