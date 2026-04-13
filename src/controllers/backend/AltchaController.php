<?php

namespace Besnovatyj\Altcha\controllers\backend;

use Besnovatyj\Altcha\contracts\AltchaServiceInterface;

use Yii;
use yii\web\Response;

class AltchaController extends \yii\web\Controller
{
    public AltchaServiceInterface $service;

    public function __construct($id, $module, AltchaServiceInterface $service, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => \yii\filters\HttpCache::class,
                'only' => ['challenge'],
                'cacheControlHeader' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        ];
    }

    public function actionChallenge(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // TODO https://www.yiiframework.com/doc/guide/2.0/ru/caching-http#cache-control ???
        // Важно: не кэшировать этот endpoint на уровне CDN/прокси.
        Yii::$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $this->service->createChallenge();
    }
}
