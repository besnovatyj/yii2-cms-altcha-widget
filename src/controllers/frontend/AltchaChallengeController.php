<?php

declare(strict_types=1);

namespace Besnovatyj\Altcha\controllers\frontend;

use Besnovatyj\Altcha\contracts\AltchaServiceInterface;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Выдача ALTCHA challenge для фронтенда.
 *
 * Маршрут (app-frontend): /Altcha/altcha-challenge/challenge
 *
 * Используется виджетом AltchaWidget по умолчанию.
 * Важно: endpoint не должен кэшироваться на уровне CDN/прокси.
 */
final class AltchaChallengeController extends Controller
{
    /**
     * @param string $id
     * @param \yii\base\Module $module
     * @param AltchaServiceInterface $service
     * @param array $config
     */
    public function __construct(
        $id,
        $module,
        private readonly AltchaServiceInterface $service,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Возвращает новый ALTCHA challenge в формате JSON.
     *
     * @return array
     */
    public function actionChallenge(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Важно: не кэшировать этот endpoint на уровне CDN/прокси.
        Yii::$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $this->service->createChallenge();
    }
}
