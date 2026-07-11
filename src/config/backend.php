<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Altcha\Module;

/**
 * Пер-аппликационный вклад Altcha в backend (группа `app-backend`) для движка yiisoft/config.
 *
 * Добавляет challenge-роут в whitelist ядрового гейта (нужен гостю на форме входа). Это append к
 * `as access.allowActions` (не переопределение) — слой vendor, класс гейта задаёт только ядро (root).
 * Значение — из {@see Module::appConfig()}, единый источник.
 */
return Module::appConfig()['app-backend'] ?? [];
