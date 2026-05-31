<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\controllers\backend;

use Besnovatyj\Altcha\contracts\AltchaServiceInterface;
use Yii;
use yii\filters\HttpCache;
use yii\web\Controller;
use yii\web\Response;

class AltchaChallengeController extends Controller
{

    public function __construct(
        $id,
        $module,
        private readonly AltchaServiceInterface $service,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => HttpCache::class,
                'only' => ['challenge'],
                'cacheControlHeader' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        ];
    }

    /**
     * Возвращает новый ALTCHA challenge в формате JSON.
     *
     * @return array
     */
    public function actionChallenge(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // TODO https://www.yiiframework.com/doc/guide/2.0/ru/caching-http#cache-control ???
        // Важно: не кэшировать этот endpoint на уровне CDN/прокси.
        Yii::$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $this->service->createChallenge();
    }
}
