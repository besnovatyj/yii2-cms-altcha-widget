<?php

declare(strict_types=1);

namespace Besnovatyj\Altcha\services;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use Besnovatyj\Altcha\config\AltchaConfig;
use Besnovatyj\Altcha\contracts\AltchaServiceInterface;
use DateInterval;
use DateTimeImmutable;
use Yii;
use yii\caching\CacheInterface;

/** Реализация сервиса (SRP: только ALTCHA-логика + replay через cache) */
final class AltchaService implements AltchaServiceInterface
{
    private Altcha $altcha;

    public function __construct(
        private readonly AltchaConfig $config,
        private ?CacheInterface       $cache = null,
    )
    {
        $this->altcha = new Altcha($this->config->hmacKey);
        $this->cache = Yii::$app->cache;
    }

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function createChallenge(): array
    {
        $expires = new DateTimeImmutable()->add(new DateInterval('PT' . $this->config->expiresSeconds . 'S'));

        $options = new ChallengeOptions(
            maxNumber: $this->config->maxNumber,
            expires: $expires,
        );

        $challenge = $this->altcha->createChallenge($options);

        // Виджету нужен JSON challenge. Возвращаем как массив.
        return [
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'salt' => $challenge->salt,
            'signature' => $challenge->signature,
            'expires' => $challenge->expires ?? null, // на случай если библиотека отдаёт это поле
        ];
    }

    public function verifyBase64Payload(string $payloadBase64): bool
    {
        $payloadBase64 = trim($payloadBase64);
        if ($payloadBase64 === '') {
            return false;
        }

        // Replay protection: одно и то же решение нельзя принять повторно.
        // (ALTCHA прямо рекомендует вести реестр использованных payload, чтобы избегать replay-атак.)
        if ($this->cache !== null) {
            $hash = hash('sha256', $payloadBase64);
            $key = $this->config->cacheKeyPrefix . $hash;

            if ($this->cache->get($key) !== false) {
                return false;
            }
        }

        $json = base64_decode($payloadBase64, true);
        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return false;
        }

        // Минимальная форма payload для verifySolution (без Sentinel).
        foreach (['algorithm', 'challenge', 'number', 'salt', 'signature'] as $k) {
            if (!array_key_exists($k, $data)) {
                return false;
            }
        }

        $ok = $this->altcha->verifySolution($data, true);

        if ($ok && $this->cache !== null) {
            $hash = hash('sha256', $payloadBase64);
            $key = $this->config->cacheKeyPrefix . $hash;
            $this->cache->set($key, 1, $this->config->replayTtlSeconds);
        }

        return $ok;
    }
}
