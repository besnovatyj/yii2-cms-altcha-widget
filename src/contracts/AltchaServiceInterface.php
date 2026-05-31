<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\contracts;

/** Интерфейс сервиса (SOLID: зависим от абстракции) */
interface AltchaServiceInterface
{
    /** Возвращает challenge в виде массива для JSON-ответа. */
    public function createChallenge(): array;

    /** Проверяет Base64 payload, пришедший из формы (поле altcha). */
    public function verifyBase64Payload(string $payloadBase64): bool;
}
