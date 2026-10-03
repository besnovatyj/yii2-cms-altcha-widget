<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Altcha\Module;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Регистрация модуля + bootstrap-классы (L2 — выполняются только у активного модуля, гейт modman).
 * Пер-аппликационный вклад (whitelist challenge-роута) — в config/backend.php.
 * Меню админки — `adminMenu.php` (группа `admin-menu`), миграции — вклад modman.
 * Значения — из статических методов {@see Module}.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
        ),
    ],
    'bootstrap' => array_values(Module::bootstrapClasses()),
];
