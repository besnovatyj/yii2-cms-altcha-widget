<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Altcha\config;

/** Value-object конфигурации */
final readonly class AltchaConfig
{
    public function __construct(
        public string $hmacKey,
        public int    $maxNumber = 50000,
        // expiresSeconds поставьте 120–300 секунд (чтобы людям хватало времени)
        public int    $expiresSeconds = 120,
        // replayTtlSeconds 600–3600 (чтобы один и тот же payload не переиспользовали)
        public int    $replayTtlSeconds = 600,
        public string $cacheKeyPrefix = 'altcha:used:',
    ) {}
}
